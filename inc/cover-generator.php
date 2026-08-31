<?php
/**
 * 封面图自动生成器
 * 使用 PHP GD 库，不依赖外部 API
 *
 * @package wpno-vc
 */

/**
 * 为小说生成封面图
 *
 * @param string $novel_name 小说名
 * @param int    $term_id    分类 ID
 * @return string|false      成功返回封面 URL，失败返回 false
 */
function wpnovc_generate_cover( $novel_name, $term_id ) {
    if ( ! function_exists( 'imagecreatetruecolor' ) ) {
        error_log( 'WPNOVC Cover: GD not available' );
        return false;
    }

    $width  = 300;
    $height = 400;
    $img    = imagecreatetruecolor( $width, $height );
    if ( ! $img ) return false;

    // ── 背景配色方案（6套，按 term_id 轮换）──
    $schemes = [
        [ 'bg' => [ 18,  52,  90], 'line' => [100, 140, 180], 'text' => [220, 225, 235] ], // 深蓝
        [ 'bg' => [ 20,  70,  50], 'line' => [ 80, 140, 120], 'text' => [210, 230, 220] ], // 墨绿
        [ 'bg' => [ 90,  30,  30], 'line' => [160,  90,  90], 'text' => [230, 210, 210] ], // 暗红
        [ 'bg' => [ 60,  45,  30], 'line' => [130, 110,  90], 'text' => [225, 215, 200] ], // 棕褐
        [ 'bg' => [ 40,  50,  60], 'line' => [110, 120, 130], 'text' => [215, 220, 225] ], // 青灰
        [ 'bg' => [ 30,  25,  45], 'line' => [100,  90, 120], 'text' => [210, 205, 225] ], // 紫黑
    ];
    $scheme = $schemes[ $term_id % count( $schemes ) ];

    // ── 填充背景 ──
    $bg_color = imagecolorallocate( $img, $scheme['bg'][0], $scheme['bg'][1], $scheme['bg'][2] );
    imagefill( $img, 0, 0, $bg_color );

    // ── 装饰线 ──
    $line_color = imagecolorallocate( $img, $scheme['line'][0], $scheme['line'][1], $scheme['line'][2] );
    imageline( $img, 40, 50, $width - 40, 50, $line_color );
    imageline( $img, 40, $height - 50, $width - 40, $height - 50, $line_color );

    // ── 书名垂直排版 ──
    $text_color = imagecolorallocate( $img, $scheme['text'][0], $scheme['text'][1], $scheme['text'][2] );
    $chars = wpnovc_mb_str_split( $novel_name );
    $count = count( $chars );

    if ( $count <= 4 ) {
        $font_size = 48;
    } elseif ( $count <= 8 ) {
        $font_size = 36;
    } else {
        $font_size = 28;
    }

    // 使用内置字体（5号字体，仅支持 ASCII，中文需用 imagettftext）
    // 尝试查找系统中文字体
    $font_path = wpnovc_find_cjk_font();
    if ( $font_path ) {
        // TrueType 字体：竖排绘制每个字
        $total_h = $count * ( $font_size + 6 );
        $start_y = ( $height - $total_h ) / 2 + $font_size;

        foreach ( $chars as $i => $char ) {
            $bbox    = imagettfbbox( $font_size, 0, $font_path, $char );
            $char_w  = $bbox[2] - $bbox[0];
            $char_x  = ( $width - $char_w ) / 2;
            $char_y  = $start_y + $i * ( $font_size + 6 );
            imagettftext( $img, $font_size, 0, (int) $char_x, (int) $char_y, $text_color, $font_path, $char );
        }
    } else {
        // 降级：水平排列显示英文/拼音
        $text  = $novel_name;
        $fw    = 5;
        $tw    = strlen( $text ) * imagefontwidth( $fw );
        $tx    = ( $width - $tw ) / 2;
        $ty    = ( $height - imagefontheight( $fw ) ) / 2;
        imagestring( $img, $fw, (int) $tx, (int) $ty, $text, $text_color );
    }

    // ── 保存 ──
    $upload_dir = wp_upload_dir();
    $cover_dir  = $upload_dir['basedir'] . '/covers';
    if ( ! file_exists( $cover_dir ) ) {
        wp_mkdir_p( $cover_dir );
    }

    $file_path = $cover_dir . '/' . $term_id . '.jpg';
    $file_url  = $upload_dir['baseurl'] . '/covers/' . $term_id . '.jpg';
    imagejpeg( $img, $file_path, 90 );

    return $file_url;
}

/**
 * 查找系统中文字体路径
 */
function wpnovc_find_cjk_font() {
    $candidates = [
        // Ubuntu/Debian
        '/usr/share/fonts/truetype/wqy/wqy-zenhei.ttc',
        // macOS
        '/System/Library/Fonts/PingFang.ttc',
        '/System/Library/Fonts/STHeiti Light.ttc',
        '/System/Library/Fonts/Hiragino Sans GB.ttc',
        // Linux
        '/usr/share/fonts/truetype/wqy/wqy-zenhei.ttc',
        '/usr/share/fonts/truetype/droid/DroidSansFallbackFull.ttf',
        '/usr/share/fonts/opentype/noto/NotoSansCJK-Regular.ttc',
        '/usr/share/fonts/truetype/noto/NotoSansCJK-Regular.ttc',
    ];

    foreach ( $candidates as $f ) {
        if ( file_exists( $f ) ) return $f;
    }
    return false;
}

/**
 * 将字符串按 UTF-8 字符拆分为数组
 */
function wpnovc_mb_str_split( $string ) {
    return preg_split( '/(?<!^)(?!$)/u', $string );
}
/**
 * AI 生成封面图（占位，需配置图像生成 API）
 */
function wpnovc_generate_ai_cover( $novel_name, $term_id ) {
    $model  = wpnovc_ai_image_active_model();
    $models = wpnovc_ai_image_models();
    if ( $model === 'gd' || ! isset( $models[ $model ] ) ) {
        return wpnovc_generate_cover( $novel_name, $term_id );
    }
    require_once __DIR__ . '/ai-image-adapter.php';
    return wpnovc_ai_image_generate_cover( $novel_name, $term_id );
}
