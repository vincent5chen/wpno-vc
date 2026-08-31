<?php
/**
 * AI 图像生成通用适配层
 * 支持多个模型，统一接口切换
 *
 * @package wpno-vc
 */

// ── 模型配置 ──────────────────────────────────────────
function wpnovc_ai_image_models() {
    return [
        'qwen-image-3.0' => [
            'name'     => 'Qwen-Image-3.0（阿里云通义千问）',
            'endpoint' => 'https://dashscope.aliyuncs.com/api/v1/services/aigc/multimodal-generation/generation',
            'headers'  => function ( $api_key ) {
                return [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ];
            },
            'build_body' => function ( $prompt, $options ) {
                return [
                    'model' => 'qwen-image-3.0',
                    'input' => [
                        'messages' => [
                            [
                                'role'    => 'user',
                                'content' => [
                                    [ 'text' => $prompt ],
                                ],
                            ],
                        ],
                    ],
                    'parameters' => [
                        'size' => '1024*1024',
                        'n'    => 1,
                    ],
                ];
            },
            'parse_response' => function ( $body ) {
                // 同步多模态返回：output.choices[0].message.content[0].image
                $image = $body['output']['choices'][0]['message']['content'][0]['image'] ?? null;
                if ( $image ) {
                    // 可能是 data:image/...;base64,xxx
                    if ( strpos( $image, 'data:image' ) === 0 ) {
                        return preg_replace( '/^data:image\/[a-zA-Z]+;base64,/', '', $image );
                    }
                    return $image;
                }
                // 兼容异步：output.task_id
                return $body['output']['task_id'] ?? null;
            },
            'is_async'  => false,
            'default_key_option' => 'ai_image_api_key',
        ],
    ];
}

// ── 当前激活的模型 ──────────────────────────────────
function wpnovc_ai_image_active_model() {
    return wpnovc_option( 'ai_image_model', 'qwen-image-3.0' );
}

// ── 核心生成函数 ──────────────────────────────────────
function wpnovc_ai_generate_image( $prompt, $options = [] ) {
    $model_id = wpnovc_ai_image_active_model();
    $models   = wpnovc_ai_image_models();

    if ( ! isset( $models[ $model_id ] ) ) {
        error_log( 'WPNOVC AI Image: unknown model ' . $model_id );
        return false;
    }

    $model   = $models[ $model_id ];
    $key_opt = $model['default_key_option'] ?? 'ai_image_api_key';
    $api_key = wpnovc_option( $key_opt, '' );

    if ( empty( $api_key ) ) {
        error_log( 'WPNOVC AI Image: API key not set for ' . $model['name'] );
        return false;
    }

    $body    = $model['build_body']( $prompt, $options );
    $headers = $model['headers']( $api_key );

    $response = wp_remote_post( $model['endpoint'], [
        'timeout' => 120,
        'headers' => $headers,
        'body'    => json_encode( $body ),
    ] );

    if ( is_wp_error( $response ) ) {
        error_log( 'WPNOVC AI Image HTTP error: ' . $response->get_error_message() );
        return false;
    }

    $http_code = wp_remote_retrieve_response_code( $response );
    $resp_body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( $http_code !== 200 || empty( $resp_body ) ) {
        error_log( 'WPNOVC AI Image API error: HTTP ' . $http_code . ' body=' . substr( wp_remote_retrieve_body( $response ), 0, 500 ) );
        return false;
    }

    $result = $model['parse_response']( $resp_body );

    // 异步模式：轮询结果
    if ( ! empty( $model['is_async'] ) && ! empty( $model['poll_endpoint'] ) ) {
        if ( ! $result ) {
            error_log( 'WPNOVC AI Image: async task creation failed' );
            return false;
        }
        $task_id  = $result;
        $poll_url = str_replace( '{task_id}', $task_id, $model['poll_endpoint'] );
        $max_polls = 30; // 最多轮询30次（约60秒）

        for ( $i = 0; $i < $max_polls; $i++ ) {
            sleep( 2 );
            $poll_resp = wp_remote_get( $poll_url, [
                'timeout' => 30,
                'headers' => $model['headers']( $api_key ),
            ] );
            if ( is_wp_error( $poll_resp ) ) continue;
            $poll_body = json_decode( wp_remote_retrieve_body( $poll_resp ), true );
            $result    = $model['poll_parse']( $poll_body );
            if ( $result === 'PENDING' ) continue;
            break;
        }

        if ( $result === false || $result === 'PENDING' ) {
            error_log( 'WPNOVC AI Image: async poll failed or timed out' );
            return false;
        }
    }

    if ( empty( $result ) ) {
        error_log( 'WPNOVC AI Image: empty result from ' . $model['name'] );
        return false;
    }

    // 如果是 URL，下载到本地
    if ( filter_var( $result, FILTER_VALIDATE_URL ) ) {
        $image_data = wp_remote_get( $result, [ 'timeout' => 60 ] );
        if ( is_wp_error( $image_data ) ) {
            error_log( 'WPNOVC AI Image: failed to download image' );
            return false;
        }
        return wp_remote_retrieve_body( $image_data );
    }

    // 如果是 base64
    return base64_decode( $result );
}

// ── 封面专用生成 ──────────────────────────────────────
function wpnovc_ai_image_generate_cover( $novel_name, $term_id ) {
    $prompt = wpnovc_ai_image_cover_prompt( $novel_name );
    $image_data = wpnovc_ai_generate_image( $prompt, [
        'size'    => '1024x1024',
        'quality' => 'medium',
    ] );

    if ( ! $image_data ) return false;

    // 统一裁剪为 300x400 封面尺寸
    $resized = wpnovc_ai_image_resize_to_cover( $image_data, 300, 400 );
    if ( $resized ) $image_data = $resized;

    // 保存封面
    $upload_dir = wp_upload_dir();
    $cover_dir  = $upload_dir['basedir'] . '/covers';
    if ( ! file_exists( $cover_dir ) ) {
        wp_mkdir_p( $cover_dir );
    }

    $file_path = $cover_dir . '/' . $term_id . '.png';
    $file_url  = $upload_dir['baseurl'] . '/covers/' . $term_id . '.png';
    file_put_contents( $file_path, $image_data );

    return $file_url;
}

// 将图片裁剪到指定封面尺寸
function wpnovc_ai_image_resize_to_cover( $image_data, $width, $height ) {
    if ( ! function_exists( 'imagecreatetruecolor' ) ) return false;
    $src = imagecreatefromstring( $image_data );
    if ( ! $src ) return false;
    $src_w = imagesx( $src );
    $src_h = imagesy( $src );
    $dst = imagecreatetruecolor( $width, $height );
    $src_ratio = $src_w / $src_h;
    $dst_ratio = $width / $height;
    $crop_w = $src_w;
    $crop_h = $src_h;
    if ( $src_ratio > $dst_ratio ) {
        $crop_w = $src_h * $dst_ratio;
        $crop_x = ( $src_w - $crop_w ) / 2;
        $crop_y = 0;
    } else {
        $crop_h = $src_w / $dst_ratio;
        $crop_x = 0;
        $crop_y = ( $src_h - $crop_h ) / 2;
    }
    imagecopyresampled( $dst, $src, 0, 0, (int)$crop_x, (int)$crop_y, $width, $height, (int)$crop_w, (int)$crop_h );
    ob_start();
    imagepng( $dst );
    $data = ob_get_clean();
    imagedestroy( $src );
    imagedestroy( $dst );
    return $data;
}


// ── Banner 专用生成 ──────────────────────────────────
function wpnovc_ai_image_generate_banner( $prompt, $device = 'pc' ) {
    $image_data = wpnovc_ai_generate_image( $prompt, [
        'size'    => '1024x1024',
        'quality' => 'medium',
    ] );
    if ( ! $image_data ) return false;

    $width  = ( $device === 'mobile' ) ? 750 : 1200;
    $height = 375;
    $resized = wpnovc_ai_image_resize_to_cover( $image_data, $width, $height );
    if ( $resized ) $image_data = $resized;

    $upload_dir = wp_upload_dir();
    $banner_dir = $upload_dir['basedir'] . '/banners';
    if ( ! file_exists( $banner_dir ) ) {
        wp_mkdir_p( $banner_dir );
    }
    $file_name = 'banner-' . uniqid() . '-' . $device . '.png';
    $file_path = $banner_dir . '/' . $file_name;
    $file_url  = $upload_dir['baseurl'] . '/banners/' . $file_name;
    file_put_contents( $file_path, $image_data );
    return $file_url;
}

// ── 封面 Prompt 模板 ──────────────────────────────────
function wpnovc_ai_image_cover_prompt( $novel_name ) {
    return "根据书名是\"{$novel_name}\"，生成一个适合小说风格的封面图，封面上要显示书名。";
}
