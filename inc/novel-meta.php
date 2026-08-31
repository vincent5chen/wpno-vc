<?php
/**
 * 小说分类元数据管理
 *
 * @package wpno-vc
 */

define( 'WPNOVC_META_KEY', 'wpnovc_novel_meta' );

function wpnovc_default_meta() {
    return [
        'xs_author'      => '',
        'xs_cover'       => '',
        'xs_cover_id'    => '',
        'xs_cover_path'  => '',
        'xs_status'      => 'serial',
        'xs_description' => '',
        'editor_note'    => '',
        'brief_rec'     => '',
        'pay_mode'       => 'default',
        'article_price'  => 0,
        'category_price' => 0,
        'free_chapter'   => 0,
        'author_id'      => 0,
        'seo_title'      => '',
        'seo_description'=> '',
        'seo_keywords'   => '',
    ];
}

function wpnovc_get_novel_meta( $term_id ) {
    $meta = get_term_meta( $term_id, WPNOVC_META_KEY, true );
    if ( empty( $meta ) || ! is_array( $meta ) ) {
        $meta = wpnovc_default_meta();
    } else {
        $meta = wp_parse_args( $meta, wpnovc_default_meta() );
    }
    // 独立 term_meta（通过 REST API 批量维护的 seo_description 等）优先于数组。
    // 注意：数组里可能存着旧模板句（"《X》，全文免费在线阅读。情节精彩，人物丰满，快来阅读吧。"），
    // 它非空但无价值，视为空 → 回退读独立 key；已定制文案则直接用数组（零额外查询）。
    // 优化：一次性取该 term 的全部 meta（走 WP term_meta 缓存，避免每 key 一次 DB 查询）。
    $flat_keys = array( 'xs_author', 'xs_cover', 'xs_description', 'seo_title', 'seo_description', 'seo_keywords', 'editor_note', 'brief_rec' );
    $need_check = false;
    foreach ( $flat_keys as $k ) {
        $cur = isset( $meta[ $k ] ) ? $meta[ $k ] : '';
        if ( $cur === '' ) {
            $need_check = true;
        } elseif ( $k === 'seo_description' && is_string( $cur )
            && ( strpos( $cur, '快来阅读' ) !== false || strpos( $cur, '情节精彩' ) !== false ) ) {
            $meta[ $k ] = ''; // 旧模板句视为空
            $need_check = true;
        }
    }
    if ( $need_check ) {
        $all = get_term_meta( $term_id ); // 一次查询取全部，WP 有 per-request 缓存
        if ( is_array( $all ) ) {
            foreach ( $flat_keys as $k ) {
                if ( ( empty( $meta[ $k ] ) || ( $k === 'seo_description' && strpos( (string) $meta[ $k ], '快来阅读' ) !== false ) )
                    && isset( $all[ $k ][0] ) && $all[ $k ][0] !== '' ) {
                    $meta[ $k ] = $all[ $k ][0];
                }
            }
        }
    }
    return $meta;
}

function wpnovc_update_novel_meta( $term_id, $data ) {
    $current = wpnovc_get_novel_meta( $term_id );
    $merged  = array_merge( $current, $data );
    return update_term_meta( $term_id, WPNOVC_META_KEY, $merged );
}

function wpnovc_get_author_info( $author_id ) {
    if ( ! $author_id ) return null;
    $user = get_userdata( $author_id );
    if ( ! $user ) return null;
    return [
        'id'          => $user->ID,
        'display_name'=> $user->display_name,
        'nickname'    => get_user_meta( $user->ID, 'nickname', true ) ?: $user->user_login,
        'avatar'      => get_avatar_url( $user->ID ),
        'url'         => get_author_posts_url( $user->ID ),
        'description' => get_user_meta( $user->ID, 'description', true ),
    ];
}

function wpnovc_get_author_novels( $author_id ) {
    global $wpdb;
    $sql = $wpdb->prepare(
        "SELECT term_id FROM {$wpdb->termmeta} WHERE meta_key = %s",
        $author_id
    );
    $results = $wpdb->get_results( $sql );
    if ( empty( $results ) ) return [];
    $ids = array_map( function ( $r ) { return $r->term_id; }, $results );
    return get_terms( [ 'taxonomy' => 'category', 'include' => $ids, 'hide_empty' => false ] );
}

function wpnovc_bump_views( $term_id ) {
    $count = (int) get_term_meta( $term_id, 'wpnovc_views', true );
    update_term_meta( $term_id, 'wpnovc_views', $count + 1 );
}

function wpnovc_get_views( $term_id ) {
    return (int) get_term_meta( $term_id, 'wpnovc_views', true );
}

function wpnovc_get_novel_hierarchy( $term_id ) {
    $hierarchy = [ 'top' => null, 'novel' => null, 'volume' => null ];
    $term = get_term( $term_id, 'category' );
    if ( is_wp_error( $term ) || ! $term ) return $hierarchy;
    if ( $term->parent == 0 ) {
        $hierarchy['top'] = $term;
    } elseif ( get_term( $term->parent, 'category' )->parent == 0 ) {
        $hierarchy['top']   = get_term( $term->parent, 'category' );
        $hierarchy['novel'] = $term;
    } else {
        $parent             = get_term( $term->parent, 'category' );
        $hierarchy['top']   = get_term( $parent->parent, 'category' );
        $hierarchy['novel'] = $parent;
        $hierarchy['volume']= $term;
    }
    return $hierarchy;
}
