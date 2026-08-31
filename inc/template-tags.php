<?php
/**
 * 模板标签函数 - 供 PC/Mobile 模板调用
 *
 * @package wpno-vc
 */

// ── 导航菜单 ───────────────────────────────────────────
function wpnovc_nav_menu( $location = 'navpc' ) {
    $cache_key = 'wpnovc_nav_' . $location;
    $cached    = wpnovc_cache_get( $cache_key );
    if ( $cached && wpnovc_option( 'menu_cache', 1 ) ) {
        echo $cached;
        return;
    }
    ob_start();
    wp_nav_menu( [
        'theme_location' => $location,
        'menu_class'     => $location == 'navpc' ? 'nav-pc' : 'nav-mobile clearfix',
        'fallback_cb'    => false,
        'container'      => false,
    ] );
    $menu = ob_get_clean();
    echo $menu;
    if ( wpnovc_option( 'menu_cache', 1 ) ) {
        wpnovc_cache_set( $cache_key, $menu );
    }
}

// ── 面包屑 ─────────────────────────────────────────────
function wpnovc_breadcrumb( $separator = ' &raquo; ' ) {
    global $wp_query;
    $output = '<div style="padding:14px 0 10px;font-size:13px;color:#888">';
    $output .= '<a href="' . home_url() . '">首页</a>';

    if ( is_category() ) {
        $term  = $wp_query->queried_object;
        $chain = wpnovc_get_category_breadcrumb( $term->term_id );
        foreach ( $chain as $c ) {
            $output .= $separator . '<a href="' . get_category_link( $c ) . '">' . $c->name . '</a>';
        }
    } elseif ( is_single() ) {
        $cats = get_the_category();
        if ( $cats ) {
            $second = wpnovc_get_second_level_cat( get_the_ID() );
            if ( $second ) {
                $chain = wpnovc_get_category_breadcrumb( $second->term_id );
                foreach ( $chain as $c ) {
                    $output .= $separator . '<a href="' . get_category_link( $c ) . '">' . $c->name . '</a>';
                }
            }
            $output .= $separator . '<span>' . get_the_title() . '</span>';
        }
    } elseif ( is_page() ) {
        $output .= $separator . '<span>' . get_the_title() . '</span>';
    } elseif ( is_search() ) {
        $output .= $separator . '<span>搜索: ' . get_search_query() . '</span>';
    }
    $output .= '</div>';
    echo $output;
}

// ── 封面图 ─────────────────────────────────────────────
function wpnovc_novel_cover( $meta, $alt = '', $eager = false ) {
    if ( ! empty( $meta['xs_cover'] ) ) {
        $url = $meta['xs_cover'];
        $w   = 300;
        $h   = 400;
    } else {
        $url = WPNOVC_ASSETS . '/img/cover-1.jpg';
        $w   = 120;
        $h   = 150;
    }
    // 书籍主页主封面传 true：eager + fetchpriority=high，保护 LCP；其余列表封面懒加载
    $loading  = $eager ? 'eager' : 'lazy';
    $fetchpri = $eager ? ' fetchpriority="high"' : '';
    return '<img alt="' . esc_attr( $alt ) . '" src="' . esc_url( $url )
        . '" width="' . $w . '" height="' . $h
        . '" loading="' . $loading . '"' . $fetchpri . ' decoding="async" />';
}

// ── 分页 ───────────────────────────────────────────────
function wpnovc_pagination() {
    global $wp_query;
    $total   = $wp_query->max_num_pages;
    $current = max( 1, get_query_var( 'paged' ) );
    if ( $total <= 1 ) return;

    $big    = 999999999;
    $output = '<div class="wpnovc-pagination">';
    $output .= paginate_links( [
        'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
        'format'    => '?paged=%#%',
        'current'   => $current,
        'total'     => $total,
        'prev_text' => '&laquo;',
        'next_text' => '&raquo;',
    ] );
    $output .= '</div>';
    $output .= '</div>';
    echo $output;
}

// ── 状态标签 ───────────────────────────────────────────
function wpnovc_status_label( $meta ) {
    $status = $meta['xs_status'] ?? 'serial';
    $labels = [
        'serial'   => '<span class="label label-info">连载中</span>',
        'complete' => '<span class="label label-success">已完结</span>',
    ];
    return $labels[ $status ] ?? $labels['serial'];
}

// ── 分类列表项 ─────────────────────────────────────────
function wpnovc_category_list_item( $cat_id ) {
    $meta    = wpnovc_get_novel_meta( $cat_id );
    $cat     = get_term( $cat_id, 'category' );
    $link    = get_category_link( $cat );
    $cover   = wpnovc_novel_cover( $meta, $cat->name );
    $desc    = wpnovc_strimwidth( $meta['xs_description'] ?: $cat->description, 100 );

    return <<<HTML
    <div class="novel-item clearfix">
        <div class="cover">
            <a href="{$link}" title="{$cat->name}">{$cover}</a>
        </div>
        <div class="xs-info">
            <p class="name"><a href="{$link}" title="{$cat->name}">{$cat->name}</a></p>
            <p class="author">{$meta['xs_author']}</p>
            <p class="description">{$desc}</p>
        </div>
    </div>
HTML;
}

// ── 获取当前小说信息（用于分类页） ────────────────────
function wpnovc_get_current_novel() {
    $term = get_queried_object();
    if ( ! $term || ! isset( $term->term_id ) ) return null;
    $meta = wpnovc_get_novel_meta( $term->term_id );
    return [
        'term' => $term,
        'meta' => $meta,
    ];
}
