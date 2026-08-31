<?php
/**
 * 编辑推荐期次数据层
 *
 * @package wpno-vc
 */

// ── 注册自定义文章类型 ──────────────────────────────────
add_action( 'init', 'wpnovc_register_editor_pick_type' );
function wpnovc_register_editor_pick_type() {
    register_post_type( 'editor_pick', [
        'label'               => '编辑推荐期次',
        'labels'              => [
            'name'               => '编辑推荐期次',
            'singular_name'      => '编辑推荐期次',
            'add_new'            => '新增一期',
            'add_new_item'       => '新增编辑推荐期次',
            'edit_item'          => '编辑编辑推荐期次',
            'new_item'           => '新编辑推荐期次',
            'view_item'          => '查看编辑推荐期次',
            'search_items'       => '搜索期次',
            'not_found'          => '未找到期次',
            'not_found_in_trash' => '回收站中没有期次',
        ],
        'public'              => false,
        'show_ui'             => false,
        'show_in_menu'        => false,
        'show_in_rest'        => false,
        'supports'            => [ 'title', 'editor', 'thumbnail' ],
        'has_archive'         => false,
        'rewrite'             => false,
        'query_var'           => false,
    ] );
}

// ── 获取期次列表 ──────────────────────────────────────
function wpnovc_get_editor_pick_issues( $paged = 1, $per_page = 5 ) {
    return get_posts( [
        'post_type'      => 'editor_pick',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $paged,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ] );
}

// ── 获取期次总数 ──────────────────────────────────────
function wpnovc_count_editor_pick_issues() {
    $counts = wp_count_posts( 'editor_pick' );
    return isset( $counts->publish ) ? intval( $counts->publish ) : 0;
}

// ── 获取期次小说列表 ──────────────────────────────────
function wpnovc_get_editor_pick_novels( $issue_id ) {
    $ids = get_post_meta( $issue_id, 'pick_novels', true );
    if ( ! is_array( $ids ) ) {
        $ids = [];
    }
    return array_values( array_filter( array_map( 'intval', $ids ) ) );
}

// ── 获取期次 Banner ───────────────────────────────────
function wpnovc_get_editor_pick_banner( $issue_id, $device = 'pc' ) {
    $key   = 'pick_banner_' . $device;
    $url   = get_post_meta( $issue_id, $key, true );
    if ( $device === 'mobile' && empty( $url ) ) {
        $url = get_post_meta( $issue_id, 'pick_banner_pc', true );
    }
    return $url ?: '';
}

// ── 获取已推荐过的小说 ID ─────────────────────────────
function wpnovc_get_recommended_novel_ids() {
    $issues = get_posts( [
        'post_type'      => 'editor_pick',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ] );
    $recommended = [];
    foreach ( $issues as $issue_id ) {
        $novels = wpnovc_get_editor_pick_novels( $issue_id );
        $recommended = array_merge( $recommended, $novels );
    }
    return array_values( array_unique( $recommended ) );
}

// ── 根据 URL 别名解析期次 ─────────────────────────────
function wpnovc_get_editor_pick_by_slug( $slug ) {
    if ( empty( $slug ) ) return null;
    $posts = get_posts( [
        'post_type'      => 'editor_pick',
        'post_status'    => 'publish',
        'name'           => sanitize_title( $slug ),
        'posts_per_page' => 1,
    ] );
    return $posts ? $posts[0] : null;
}

// ── 获取期次前台 URL ─────────────────────────────────
function wpnovc_get_editor_pick_url( $issue_id ) {
    $post = get_post( $issue_id );
    if ( ! $post || $post->post_type !== 'editor_pick' ) return '#';
    $slug = $post->post_name ?: ( get_the_date( 'Y-m-d', $post ) . '-' . $post->ID );
    return home_url( '/editor-picks/' . $slug . '/' );
}

// ── 保存期次 ──────────────────────────────────────────
function wpnovc_save_editor_pick_issue( $data ) {
    $issue_id = intval( $data['issue_id'] ?? 0 );
    $title    = sanitize_text_field( $data['title'] ?? '' );
    if ( empty( $title ) ) {
        return new WP_Error( 'empty_title', '期次标题不能为空' );
    }

    $post_data = [
        'post_title'   => $title,
        'post_type'    => 'editor_pick',
        'post_status'  => 'publish',
        'post_excerpt' => sanitize_textarea_field( $data['excerpt'] ?? '' ),
    ];

    $date = sanitize_text_field( $data['post_date'] ?? '' );
    if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
        $date .= ' 00:00:00';
    } elseif ( empty( $date ) ) {
        $date = current_time( 'mysql' );
    }
    $post_data['post_date'] = $date;

    if ( $issue_id ) {
        $post_data['ID'] = $issue_id;
        $result = wp_update_post( $post_data, true );
    } else {
        $result = wp_insert_post( $post_data, true );
    }

    if ( is_wp_error( $result ) ) {
        return $result;
    }

    $issue_id = $result;

    // 生成稳定的 URL 别名：日期-自定义别名，空别名则用同日序号
    $custom_slug = sanitize_title( $data['slug'] ?? '' );
    $date_part   = date( 'Y-m-d', strtotime( $post_data['post_date'] ) );
    if ( empty( $custom_slug ) ) {
        $seq = wpnovc_count_issues_on_date( $post_data['post_date'], $issue_id );
        $custom_slug = str_pad( $seq + 1, 3, '0', STR_PAD_LEFT );
    }
    $full_slug = $date_part . '-' . $custom_slug;

    wp_update_post( [
        'ID'        => $issue_id,
        'post_name' => sanitize_title( $full_slug ),
    ] );

    update_post_meta( $issue_id, 'pick_slug', sanitize_text_field( $custom_slug ) );
    update_post_meta( $issue_id, 'pick_banner_pc', esc_url_raw( $data['banner_pc'] ?? '' ) );
    update_post_meta( $issue_id, 'pick_banner_mobile', esc_url_raw( $data['banner_mobile'] ?? '' ) );

    $novels = array_filter( array_map( 'intval', (array) ( $data['novels'] ?? [] ) ) );
    $novels = array_slice( array_values( array_unique( $novels ) ), 0, 10 );
    update_post_meta( $issue_id, 'pick_novels', $novels );

    return $issue_id;
}

// ── 计算同一天的期次序号 ─────────────────────────────
function wpnovc_count_issues_on_date( $date, $exclude_id = 0 ) {
    $date_start = date( 'Y-m-d 00:00:00', strtotime( $date ) );
    $date_end   = date( 'Y-m-d 23:59:59', strtotime( $date ) );
    $posts = get_posts( [
        'post_type'      => 'editor_pick',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'date_query'     => [
            [
                'after'     => $date_start,
                'before'    => $date_end,
                'inclusive' => true,
            ],
        ],
    ] );
    if ( $exclude_id ) {
        $posts = array_filter( $posts, function ( $id ) use ( $exclude_id ) {
            return intval( $id ) !== intval( $exclude_id );
        } );
    }
    return count( $posts );
}

