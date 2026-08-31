<?php
/**
 * 虚拟页面路由 - /bookshelf/, /search/, /writer/ 等
 *
 * @package wpno-vc
 */

add_action( 'init', 'wpnovc_add_rewrite_rules' );
function wpnovc_add_rewrite_rules() {
    add_rewrite_rule( '^bookshelf/?$', 'index.php?wpnovc_page=bookshelf', 'top' );
    add_rewrite_rule( '^reading-history/?$', 'index.php?wpnovc_page=reading_history', 'top' );
    add_rewrite_rule( '^search/?$', 'index.php?wpnovc_page=search', 'top' );
    add_rewrite_rule( '^noveltype/?$', 'index.php?wpnovc_page=noveltype', 'top' );
    add_rewrite_rule( '^writer/?$', 'index.php?wpnovc_page=writer', 'top' );
    add_rewrite_rule( '^hot/?$', 'index.php?wpnovc_page=hot', 'top' );
    add_rewrite_rule( '^register/?$', 'index.php?wpnovc_page=register', 'top' );
    add_rewrite_rule( '^login/?$', 'index.php?wpnovc_page=login', 'top' );
    add_rewrite_rule( '^latest/?$', 'index.php?wpnovc_page=latest', 'top' );
    add_rewrite_rule( '^editor-picks/?$', 'index.php?wpnovc_page=editor_picks', 'top' );
    add_rewrite_rule( '^editor-picks/([0-9]{4}-[0-9]{2}-[0-9]{2}-[a-z0-9-]+)/?$', 'index.php?wpnovc_page=editor_pick_detail&wpnovc_issue_slug=$matches[1]', 'top' );
    add_rewrite_rule( '^editor-picks/page/([0-9]+)/?$', 'index.php?wpnovc_page=editor_picks&paged=$matches[1]', 'top' );
    add_rewrite_rule( '^user/?$', 'index.php?wpnovc_page=user', 'top' );
}

add_filter( 'query_vars', 'wpnovc_query_vars' );
function wpnovc_query_vars( $vars ) {
    $vars[] = 'wpnovc_page';
    $vars[] = 'wpnovc_issue_slug';
    return $vars;
}

add_action( 'template_redirect', 'wpnovc_handle_virtual_pages' );
function wpnovc_handle_virtual_pages() {
    $page = get_query_var( 'wpnovc_page' );
    if ( ! $page ) return;

    $templates = [
        'bookshelf'      => 'bookshelf.php',
        'reading_history' => 'reading-history.php',
        'search'    => 'novel-search.php',
        'noveltype' => 'novel-type.php',
        'writer'    => 'writer.php',
        'hot'       => 'hot_novel.php',
        'register'  => 'register.php',
        'login'     => 'login.php',
        'latest'    => 'latest.php',
        'editor_picks'      => 'editor-picks.php',
        'editor_pick_detail' => 'editor-picks.php',
        'user'      => 'user.php',
    ];

    if ( ! isset( $templates[ $page ] ) ) return;

    global $wp_query;
    $wp_query->is_home     = false;
    $wp_query->is_single   = false;
    $wp_query->is_page     = false;
    $wp_query->is_singular = false;
    status_header( 200 );

    $dir      = wpnovc_template_dir();
    $template = WPNOVC_DIR . "/{$dir}/{$templates[$page]}";

    if ( file_exists( $template ) ) {
        // disable_cache comment removed to prevent header issues
        include $template;
        exit;
    }
}
