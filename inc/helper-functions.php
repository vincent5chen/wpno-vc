<?php
/**
 * 通用工具函数
 * 
 * @package wpno-vc
 */

// ── 选项读写 ──────────────────────────────────────────
function wpnovc_option( $key, $default = '' ) {
    return get_option( 'wpnovc_' . $key, $default );
}

function wpnovc_update_option( $key, $value ) {
    update_option( 'wpnovc_' . $key, $value );
}


// ── Markdown 简单格式化 ────────────────────────────────
function wpnovc_md_format( $text ) {
    if ( empty( $text ) ) return '';
    $text = esc_html( $text );
    // Bold **text**
    $text = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text );
    // Italic *text*
    $text = preg_replace( '/\*(.+?)\*/', '<em>$1</em>', $text );
    // Headings ## text
    $text = preg_replace( '/^### (.+)$/m', '<h4>$1</h4>', $text );
    $text = preg_replace( '/^## (.+)$/m', '<h3>$1</h3>', $text );
    // Unordered lists - item
    $text = preg_replace( '/^- (.+)$/m', '<li>$1</li>', $text );
    // Wrap consecutive <li> in <ul>
    $text = preg_replace( '/(<li>.*<\/li>)/s', '<ul>$1</ul>', $text );
    // Double newlines to paragraphs
    $text = wpautop( $text );
    return $text;
}

// ── 内容截断 ──────────────────────────────────────────
function wpnovc_strimwidth( $content, $length = 160, $suffix = '...' ) {
    $content = wp_strip_all_tags( $content );
    if ( mb_strlen( $content, 'UTF-8' ) <= $length ) {
        return $content;
    }
    return mb_substr( $content, 0, $length, 'UTF-8' ) . $suffix;
}

// ── 安全截断 HTML ─────────────────────────────────────
function wpnovc_trim_html( $html, $length ) {
    $text = wp_strip_all_tags( $html );
    if ( mb_strlen( $text, 'UTF-8' ) <= $length ) {
        return $html;
    }
    $html   = wpautop( $html );
    $html   = preg_replace( '#<!--(.*?)-->#', '', $html );
    $parts  = preg_split( '/(<[^>]*>)/is', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
    $result = [];
    $remain = $length;
    foreach ( $parts as $i => $part ) {
        $part = trim( $part );
        if ( empty( $part ) ) continue;
        $result[ $i ] = $part;
        if ( $part[0] != '<' ) {
            $remain -= mb_strlen( $part, 'UTF-8' );
        }
        if ( $remain <= 0 && isset( $part[1] ) && $part[1] == '/' ) {
            break;
        }
    }
    return implode( '', $result );
}

// ── 获取分类层级路径 ──────────────────────────────────
function wpnovc_get_category_breadcrumb( $term_id ) {
    $chain = [];
    $term  = get_term( $term_id, 'category' );
    if ( is_wp_error( $term ) || ! $term ) {
        return $chain;
    }
    $chain[] = $term;
    while ( $term->parent > 0 ) {
        $term    = get_term( $term->parent, 'category' );
        $chain[] = $term;
    }
    return array_reverse( $chain );
}

// ── 获取文章所属二级分类 ──────────────────────────────
function wpnovc_get_second_level_cat( $post_id ) {
    $cats = get_the_category( $post_id );
    if ( empty( $cats ) ) return null;

    $top_cat = null;
    foreach ( $cats as $cat ) {
        if ( $cat->parent == 0 ) {
            $top_cat = $cat;
            break;
        }
    }
    if ( ! $top_cat ) {
        $top_cat = $cats[0];
        while ( $top_cat->parent > 0 ) {
            $top_cat = get_category( $top_cat->parent );
        }
    }
    foreach ( $cats as $cat ) {
        if ( $cat->parent == $top_cat->term_id ) {
            return $cat;
        }
    }
    return null;
}

// ── 获取所有顶级分类 ──────────────────────────────────
function wpnovc_get_top_categories() {
    return get_terms( [
        'taxonomy'   => 'category',
        'parent'     => 0,
        'hide_empty' => false,
    ] );
}

// ── 获取子分类 ────────────────────────────────────────
function wpnovc_get_child_categories( $parent_id, $limit = 20, $offset = 0 ) {
    $children = get_terms( [
        'taxonomy'   => 'category',
        'parent'     => $parent_id,
        'hide_empty' => false,
        'orderby'    => 'term_id',
        'order'      => 'DESC',
        'number'     => $limit,
        'offset'     => $offset,
    ] );
    if ( is_wp_error( $children ) ) return [];
    return $children;
}

function wpnovc_count_child_categories( $parent_id ) {
    $children = get_terms( [
        'taxonomy'   => 'category',
        'parent'     => $parent_id,
        'hide_empty' => false,
        'fields'     => 'count',
    ] );
    return is_wp_error( $children ) ? 0 : $children;
}

// ── 获取分类最新文章 ──────────────────────────────────
function wpnovc_get_category_posts( $term_id, $limit = 10 ) {
    return get_posts( [
        'post_type'      => 'post',
        'cat'            => $term_id,
        'posts_per_page' => $limit,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ] );
}

// ── 获取分类文章总数 ──────────────────────────────────
function wpnovc_get_category_post_count( $term_id ) {
    $term = get_term( $term_id, 'category' );
    if ( is_wp_error( $term ) ) return 0;
    return $term->count;
}

// ── 中文转拼音首字母 ──────────────────────────────────
function wpnovc_get_first_letter( $str ) {
    $str  = mb_substr( $str, 0, 1, 'UTF-8' );
    $byte = ord( $str[0] );

    // ASCII 字母
    if ( $byte >= 65 && $byte <= 90 ) return strtolower( $str );
    if ( $byte >= 97 && $byte <= 122 ) return $str;
    if ( $byte < 0x80 ) return false;

    // GB2312 拼音表（简化版）
    if ( strlen( $str ) < 2 ) return false;
    $code = ord( $str[0] ) * 256 + ord( $str[1] ) - 65536;
    $map  = [
        [ -20319, -20284, 'a' ], [ -20283, -19776, 'b' ],
        [ -19775, -19219, 'c' ], [ -19218, -18711, 'd' ],
        [ -18710, -18527, 'e' ], [ -18526, -18240, 'f' ],
        [ -18239, -17923, 'g' ], [ -17922, -17418, 'h' ],
        [ -17417, -16475, 'j' ], [ -16474, -16213, 'k' ],
        [ -16212, -15641, 'l' ], [ -15640, -15166, 'm' ],
        [ -15165, -14923, 'n' ], [ -14922, -14915, 'o' ],
        [ -14914, -14631, 'p' ], [ -14630, -14150, 'q' ],
        [ -14149, -14091, 'r' ], [ -14090, -13319, 's' ],
        [ -13318, -12839, 't' ], [ -12838, -12557, 'w' ],
        [ -12556, -11848, 'x' ], [ -11847, -11056, 'y' ],
        [ -11055, -10247, 'z' ],
    ];
    foreach ( $map as $m ) {
        if ( $code >= $m[0] && $code <= $m[1] ) return $m[2];
    }
    return false;
}

// ── 页面缓存写入 ──────────────────────────────────────
function wpnovc_cache_get( $key ) {
    $file = WPNOVC_DIR . '/cache/' . md5( $key ) . '.html';
    if ( file_exists( $file ) && ( time() - filemtime( $file ) < 3600 ) ) {
        return file_get_contents( $file );
    }
    return false;
}

function wpnovc_cache_set( $key, $content ) {
    $dir = WPNOVC_DIR . '/cache';
    if ( ! is_dir( $dir ) ) mkdir( $dir, 0755, true );
    $file = $dir . '/' . md5( $key ) . '.html';
    file_put_contents( $file, '<!-- cached ' . date('Y-m-d H:i:s') . ' -->' . $content );
}

// ── 内容过滤器 ────────────────────────────────────────
function wpnovc_filter_content( $content ) {
    // 去链接
    $content = preg_replace( '|<a[^>]*>|i', '', $content );
    $content = str_ireplace( '</a>', '', $content );
    // 去脚本
    $content = preg_replace( '|<script[^>]*>.*?</script>|is', '', $content );
    // 去样式
    $content = preg_replace( '|<style[^>]*>.*?</style>|is', '', $content );
    return $content;
}

// ── 设备检测 ──────────────────────────────────────────
function wpnovc_is_crawler() {
    if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) return false;
    $ua      = strtolower( $_SERVER['HTTP_USER_AGENT'] );
    $crawlers = [ 'baiduspider', 'googlebot', 'bingbot', 'sogou', '360spider', 'yisouspider', 'duckduckbot' ];
    foreach ( $crawlers as $bot ) {
        if ( strpos( $ua, $bot ) !== false ) return true;
    }
    return false;
}

// ── 获取封面图 ────────────────────────────────────────
function wpnovc_get_cover( $novel_meta, $alt = '' ) {
    if ( ! empty( $novel_meta['xs_cover'] ) ) {
        return '<img alt="' . esc_attr( $alt ) . '" src="' . esc_url( $novel_meta['xs_cover'] ) . '" />';
    }
    // 默认封面
    $default = WPNOVC_ASSETS . '/img/cover-1.jpg';
    return '<img alt="' . esc_attr( $alt ) . '" src="' . esc_url( $default ) . '" />';
}

// ── 章节排序 ──────────────────────────────────────────
function wpnovc_sort_chapters( $posts, $order = 'ASC' ) {
    // Parse title into (volume_num, chapter_num)
    $parse = function( $title ) {
        $vol = 0; $ch = 0;
        // 序章/楔子/前言 → special
        if ( preg_match( '/^(序[章言]?|楔子|前言|后记|尾声)/u', $title ) ) {
            return [ -1, 0 ];
        }
        // 第X卷 → volume header
        if ( preg_match( '/^第([零一二三四五六七八九十百千\d]+)[卷部篇集]/u', $title, $m ) ) {
            return [ wpnovc_chinese_to_int( $m[1] ), 0 ];
        }
        // Extract volume and chapter
        if ( preg_match( '/第([零一二三四五六七八九十百千\d]+)[卷部篇集].*?第([零一二三四五六七八九十百千\d]+)[章节]/u', $title, $m ) ) {
            $vol = wpnovc_chinese_to_int( $m[1] );
            $ch  = wpnovc_chinese_to_int( $m[2] );
        } elseif ( preg_match( '/第([零一二三四五六七八九十百千\d]+)[章节]/u', $title, $m ) ) {
            $ch = wpnovc_chinese_to_int( $m[1] );
        } elseif ( preg_match( '/第([零一二三四五六七八九十百千\d]+)[卷部篇集]/u', $title, $m ) ) {
            $vol = wpnovc_chinese_to_int( $m[1] );
        }
        return [ $vol, $ch ];
    };
    
    usort( $posts, function ( $a, $b ) use ( $order, $parse ) {
        [ $va, $ca ] = $parse( $a->post_title );
        [ $vb, $cb ] = $parse( $b->post_title );
        if ( $va !== $vb ) return $order === 'ASC' ? $va - $vb : $vb - $va;
        return $order === 'ASC' ? $ca - $cb : $cb - $ca;
    } );
    return $posts;
}

// 中文数字→阿拉伯数字
function wpnovc_chinese_to_int( $str ) {
    if ( is_numeric( $str ) ) return intval( $str );
    $map = [ '零'=>0,'一'=>1,'二'=>2,'三'=>3,'四'=>4,'五'=>5,'六'=>6,'七'=>7,'八'=>8,'九'=>9,'十'=>10,'百'=>100,'千'=>1000 ];
    $result = 0; $tmp = 0;
    $chars = preg_split( '//u', $str, -1, PREG_SPLIT_NO_EMPTY );
    foreach ( $chars as $c ) {
        if ( ! isset( $map[$c] ) ) continue;
        $n = $map[$c];
        if ( $n >= 10 ) { $tmp = $tmp ?: 1; $result += $tmp * $n; $tmp = 0; }
        else { $tmp = $n; }
    }
    return $result + $tmp;
}
