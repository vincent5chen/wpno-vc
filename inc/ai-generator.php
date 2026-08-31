<?php
/**
 * DeepSeek AI 内容生成器
 */

function wpnovc_ai_generate_novel_info( $novel_name, $author ) {
    $api_key = wpnovc_option( 'deepseek_api_key', '' );
    if ( empty( $api_key ) ) return false;

    $site_name = get_bloginfo( 'name' );
    
    $prompt = <<<PROMPT
你是一个小说网站的SEO优化助手。请根据以下小说信息，生成JSON格式的输出（只输出JSON，不要其他任何文字）：

小说名：《{$novel_name}》
作者：{$author}
站点名：{$site_name}

请生成：
1. novel_description：小说简介，约500字，文笔优美，详细介绍故事背景、主要情节、人物特色和小说看点
2. novel_editor_note：编辑推荐语，1000字以上，包括故事背景、主要人物、情节亮点、写作风格、适合读者群体
3. seo_title：SEO标题，15-30字，包含小说名和吸引力词汇
4. seo_description：SEO描述，50-100字，包含关键词，吸引点击
5. seo_keywords：5-8个关键词，逗号分隔英文逗号

JSON格式示例：
{"novel_description":"...","novel_editor_note":"...","seo_title":"...","seo_description":"...","seo_keywords":"..."}
PROMPT;

    $response = wp_remote_post( 'https://api.deepseek.com/chat/completions', [
        'timeout' => 15,
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'body' => json_encode( [
            'model'    => 'deepseek-chat',
            'messages' => [
                [ 'role' => 'user', 'content' => $prompt ],
            ],
            'temperature' => 0.8,
            'max_tokens'  => 600,
        ] ),
    ] );

    if ( is_wp_error( $response ) ) {
        error_log( 'WPNOVC AI: HTTP error - ' . $response->get_error_message() );
        return false;
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $body['choices'][0]['message']['content'] ) ) {
        error_log( 'WPNOVC AI: empty response' );
        return false;
    }

    $raw = $body['choices'][0]['message']['content'];

    // Extract fields using regex (more reliable than json_decode for AI output)
    $extract = function( $field, $text ) {
        if ( preg_match( '/"' . $field . '"\s*:\s*"([^"]*)"/s', $text, $m ) ) {
            return stripslashes( $m[1] );
        }
        return '';
    };

    $desc   = $extract( 'novel_description', $raw );
    $editor = $extract( 'novel_editor_note', $raw );
    $title  = $extract( 'seo_title', $raw );
    $seo_d  = $extract( 'seo_description', $raw );
    $kw     = $extract( 'seo_keywords', $raw );

    if ( empty( $desc ) ) {
        error_log( 'WPNOVC AI: regex parse failed, raw=' . substr( $raw, 0, 200 ) );
        return false;
    }

    error_log( 'WPNOVC AI: success - ' . $title );

    return [
        'description'     => sanitize_textarea_field( $desc ),
        'editor_note'     => sanitize_textarea_field( $editor ),
        'seo_title'       => sanitize_text_field( $title ),
        'seo_description' => sanitize_textarea_field( $seo_d ),
        'seo_keywords'    => sanitize_text_field( $kw ),
    ];
}

function wpnovc_fallback_novel_info( $novel_name, $author ) {
    $api_key = wpnovc_option( "deepseek_api_key", "" );
    $site_name = get_bloginfo( 'name' );
    if ( empty( $api_key ) ) {
        return [
            'description'     => "《{$novel_name}》是{$author}倾心创作的一部精彩小说。故事背景宏大，情节环环相扣，人物塑造丰满立体，情感描写细腻动人。作品以独特的视角展现了主人公的成长历程，在困境中不断突破自我、迎难而上的精神令人动容。作者文笔流畅，叙事节奏把控得当，既有磅礴大气的场景渲染，又不乏温情脉脉的细节刻画。无论您是喜欢热血冒险、权谋博弈，还是偏爱细腻情感、深度思考，这部作品都能带给您丰富的阅读体验。本站提供《{$novel_name}》全文免费在线阅读。",
            'editor_note'     => "本书情节紧凑，人物形象鲜明，是{$author}的经典代表作之一。作者以深厚笔力构建了一个引人入胜的故事世界。作品叙事节奏把控精准，既有宏大的世界观设定，又不乏细腻的情感描写。主人公形象立体丰满，配角群像各具特色，故事线索层层递进，悬念设置巧妙得当。文笔流畅自然，场景渲染力强，读来令人沉浸其中，欲罢不能。推荐给所有热爱此类型小说的读者朋友，相信您一定会被这个精彩的故事所吸引。",
            'seo_title'       => "{$novel_name} - {$author} - {$site_name}",
            'seo_description' => "《{$novel_name}》是{$author}创作的经典小说，全文免费在线阅读。情节精彩，人物丰满，快来阅读吧。",
            'seo_keywords'    => "{$novel_name}, {$author}, 小说, 在线阅读, 免费小说, {$site_name}",
        ];
    }else{

        $prompt = <<<PROMPT
        你是一个资深网站编辑。请为以下小说写一段150字的总结文字。
        内容包括：核心看点、独特之处。必须恰好150字左右。
        只需输出总结文字，不要其他任何文字。

        小说名：《{$novel_name}》
        作者：{$author}
        PROMPT;

        $response = wp_remote_post( "https://api.deepseek.com/chat/completions", [
            "timeout" => 30,
            "headers" => [
                "Content-Type"  => "application/json",
                "Authorization" => "Bearer " . $api_key,
            ],
            "body" => json_encode( [
                "model"    => "deepseek-chat",
                "messages" => [
                    [ "role" => "user", "content" => $prompt ],
                ],
                "temperature" => 0.8,
                "max_tokens"  => 1500,
            ] ),
        ] );
        if ( is_wp_error( $response ) ) return false;

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $content = trim( $body["choices"][0]["message"]["content"] ?? "" );
        
        $info = $content ? sanitize_textarea_field( $content ) : false;

        return [
            'description'     => "{$info}",
            'editor_note'     => "本书情节紧凑，人物形象鲜明，是{$author}的经典代表作之一。作者以深厚笔力构建了一个引人入胜的故事世界。作品叙事节奏把控精准，既有宏大的世界观设定，又不乏细腻的情感描写。主人公形象立体丰满，配角群像各具特色，故事线索层层递进，悬念设置巧妙得当。文笔流畅自然，场景渲染力强，读来令人沉浸其中，欲罢不能。推荐给所有热爱此类型小说的读者朋友，相信您一定会被这个精彩的故事所吸引。",
            'seo_title'       => "{$novel_name} - {$author} - {$site_name}",
            'seo_description' => "《{$novel_name}》，全文免费在线阅读。情节精彩，人物丰满，快来阅读吧。",
            'seo_keywords'    => "{$novel_name}, {$author}, 小说, 在线阅读, 免费小说, {$site_name}",
        ];
        
    }
}
/**
 * AI 生成200字编辑简要推荐
 */
function wpnovc_ai_generate_brief_recommendation( $novel_name, $author ) {
    $api_key = wpnovc_option( "deepseek_api_key", "" );
    if ( empty( $api_key ) ) return false;

    $prompt = <<<PROMPT
你是一个资深小说编辑。请为以下小说写一段200字的推荐语，从编辑视角说明为什么值得阅读。
内容包括：核心看点、独特之处、适合读者。必须恰好200字左右。
只需输出推荐语，不要其他任何文字。

小说名：《{$novel_name}》
作者：{$author}
PROMPT;

    $response = wp_remote_post( "https://api.deepseek.com/chat/completions", [
        "timeout" => 30,
        "headers" => [
            "Content-Type"  => "application/json",
            "Authorization" => "Bearer " . $api_key,
        ],
        "body" => json_encode( [
            "model"    => "deepseek-chat",
            "messages" => [
                [ "role" => "user", "content" => $prompt ],
            ],
            "temperature" => 0.8,
            "max_tokens"  => 1500,
        ] ),
    ] );

    if ( is_wp_error( $response ) ) return false;

    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    $content = trim( $body["choices"][0]["message"]["content"] ?? "" );
    
    return $content ? sanitize_textarea_field( $content ) : false;
}
/**
 * AI 生成 1000 字完整编辑导语
 */
function wpnovc_ai_generate_editor_note_full( $novel_name, $author ) {
    $api_key = wpnovc_option( "deepseek_api_key", "" );
    if ( empty( $api_key ) ) return false;

    $prompt = <<<PROMPT
你是一个资深小说编辑。请为以下小说写一篇1000字以上的编辑导语，必须不少于1000字。
内容包括：故事背景介绍、主要人物分析、情节亮点点评、写作风格评价、适合读者群体推荐。
输出格式：纯文本，使用 Markdown 格式（## 标题、**加粗**、- 列表），不要JSON。
确保字数达到1000字以上，内容专业有深度。

小说名：《{$novel_name}》
作者：{$author}
PROMPT;

    $response = wp_remote_post( "https://api.deepseek.com/chat/completions", [
        "timeout" => 60,
        "headers" => [
            "Content-Type"  => "application/json",
            "Authorization" => "Bearer " . $api_key,
        ],
        "body" => json_encode( [
            "model"    => "deepseek-chat",
            "messages" => [
                [ "role" => "user", "content" => $prompt ],
            ],
            "temperature" => 0.8,
            "max_tokens"  => 5000,
        ] ),
    ] );

    if ( is_wp_error( $response ) ) return false;

    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    $content = trim( $body["choices"][0]["message"]["content"] ?? "" );
    
    return $content ? sanitize_textarea_field( $content ) : false;
}
