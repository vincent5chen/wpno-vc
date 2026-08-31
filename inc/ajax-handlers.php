<?php
/**
 * AJAX 处理 - 书架、搜索、用户操作、小说导入等
 *
 * @package wpno-vc
 */

// ── AJAX 注册辅助 ──────────────────────────────────
function wpnovc_register_ajax( $action, $callback, $nopriv = true ) {
    add_action( 'wp_ajax_' . $action, function () use ( $callback ) {
        check_ajax_referer( 'wpnovc_ajax', 'nonce' );
        try {
            $callback();
        } catch ( Exception $e ) {
            wp_send_json_error( [ 'msg' => $e->getMessage() ] );
        }
    } );
    if ( $nopriv ) {
        add_action( 'wp_ajax_nopriv_' . $action, function () use ( $callback ) {
            try {
                $callback();
            } catch ( Exception $e ) {
                wp_send_json_error( [ 'msg' => $e->getMessage() ] );
            }
        } );
    }
}

function admin_ajax_url( $action, $params = [] ) {
    $url = admin_url( 'admin-ajax.php?action=' . $action . '&nonce=' . wp_create_nonce( 'admin_ajax' ) );
    if ( $params ) $url .= '&' . http_build_query( $params );
    return $url;
}

// ── 书架 - 添加收藏 ────────────────────────────────
wpnovc_register_ajax( 'wpnovc_bookshelf_add', function () {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( [ 'msg' => '请先登录' ] );
    }
    $user_id  = get_current_user_id();
    $novel_id = intval( $_POST['novel_id'] ?? 0 );
    if ( ! $novel_id ) wp_send_json_error( [ 'msg' => '参数错误' ] );

    $shelf = get_user_meta( $user_id, 'wpnovc_bookshelf', true ) ?: [];
    if ( ! in_array( $novel_id, $shelf ) ) {
        $shelf[] = $novel_id;
        update_user_meta( $user_id, 'wpnovc_bookshelf', $shelf );
    }
    echo '1'; die;
} );

// ── 书架 - 取消收藏 ────────────────────────────────
wpnovc_register_ajax( 'wpnovc_bookshelf_remove', function () {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( [ 'msg' => '请先登录' ] );
    }
    $user_id  = get_current_user_id();
    $novel_id = intval( $_POST['novel_id'] ?? 0 );
    $shelf    = get_user_meta( $user_id, 'wpnovc_bookshelf', true ) ?: [];
    $shelf    = array_diff( $shelf, [ $novel_id ] );
    update_user_meta( $user_id, 'wpnovc_bookshelf', array_values( $shelf ) );
    echo '1'; die;
} );

// ── 书架 - 获取列表 ────────────────────────────────
wpnovc_register_ajax( 'wpnovc_bookshelf_list', function () {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( [ 'msg' => '请先登录' ] );
    }
    $user_id = get_current_user_id();
    $shelf   = get_user_meta( $user_id, 'wpnovc_bookshelf', true ) ?: [];
    $novels  = [];
    foreach ( $shelf as $term_id ) {
        $cat  = get_term( $term_id, 'category' );
        if ( ! $cat || is_wp_error( $cat ) ) continue;
        $meta = wpnovc_get_novel_meta( $term_id );
        $novels[] = [
            'id'    => $term_id,
            'name'  => $cat->name,
            'link'  => get_category_link( $cat ),
            'cover' => $meta['xs_cover'] ?: WPNOVC_ASSETS . '/img/cover-1.jpg',
            'author'=> $meta['xs_author'],
        ];
    }
    wp_send_json_success( [ 'novels' => $novels ] );
}, false );

// ── 搜索 ──────────────────────────────────────────
wpnovc_register_ajax( 'wpnovc_search', function () {
    $keyword = sanitize_text_field( $_GET['keyword'] ?? '' );
    if ( ! $keyword ) wp_send_json_error( [ 'msg' => '请输入搜索关键词' ] );

    $terms = get_terms( [
        'taxonomy'   => 'category',
        'name__like' => $keyword,
        'hide_empty' => false,
        'number'     => 20,
    ] );

    $results = [];
    foreach ( $terms as $term ) {
        $meta = wpnovc_get_novel_meta( $term->term_id );
        $results[] = [
            'id'    => $term->term_id,
            'name'  => $term->name,
            'link'  => get_category_link( $term ),
            'author'=> $meta['xs_author'],
            'desc'  => wpnovc_strimwidth( $meta['xs_description'] ?: $term->description, 80 ),
        ];
    }
    wp_send_json_success( [ 'results' => $results ] );
} );

// ── 章节导航（上次阅读位置） ──────────────────────────
wpnovc_register_ajax( 'wpnovc_last_read', function () {
    if ( ! is_user_logged_in() ) wp_send_json_error( [] );
    $user_id  = get_current_user_id();
    $novel_id = intval( $_POST['novel_id'] ?? 0 );
    $post_id  = intval( $_POST['post_id'] ?? 0 );
    if ( $novel_id && $post_id ) {
        update_user_meta( $user_id, 'wpnovc_last_read_' . $novel_id, $post_id );
    }
    wp_send_json_success();
}, false );

// ============================================================
//  小说导入 - 分步 AJAX 上传 + 批量处理
// ============================================================

function wpnovc_import_temp_dir() {
    $dir = WP_CONTENT_DIR . '/uploads/novel-imports';
    if ( ! file_exists( $dir ) ) {
        wp_mkdir_p( $dir );
    }
    return $dir;
}

// Step 1: 上传文件并解析章节元数据
add_action( 'wp_ajax_wpnovc_upload_novel', function () {
    check_ajax_referer( 'wpnovc_ajax', 'nonce' );
    if ( ! current_user_can( 'edit_theme_options' ) ) {
        wp_send_json_error( [ 'msg' => '权限不足' ] );
    }

    if ( empty( $_FILES['novel_file'] ) || ! empty( $_FILES['novel_file']['error'] ) ) {
        $err = $_FILES['novel_file']['error'] ?? 4;
        $msg = '文件上传失败，错误码: ' . $err;
        if ( $err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE ) {
            $msg = '文件超过服务器上传限制（最大 64M）';
        } elseif ( $err === UPLOAD_ERR_PARTIAL ) {
            $msg = '文件上传中断，请重试';
        } elseif ( $err === UPLOAD_ERR_NO_FILE ) {
            $msg = '请选择 TXT 文件';
        }
        wp_send_json_error( [ 'msg' => $msg ] );
    }

    $file     = $_FILES['novel_file'];
    $file_id  = md5( uniqid( 'novel_', true ) . microtime() );
    $ext      = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
    if ( $ext !== 'txt' ) {
        wp_send_json_error( [ 'msg' => '仅支持 .txt 格式文件' ] );
    }

    $dest      = wpnovc_import_temp_dir() . '/' . $file_id . '.txt';
    $meta_dest = wpnovc_import_temp_dir() . '/' . $file_id . '.json';

    if ( ! move_uploaded_file( $file['tmp_name'], $dest ) ) {
        wp_send_json_error( [ 'msg' => '文件保存失败' ] );
    }

    $raw = @file_get_contents( $dest );
    if ( empty( trim( $raw ) ) ) {
        @unlink( $dest );
        wp_send_json_error( [ 'msg' => '文件内容为空' ] );
    }

    if ( substr( $raw, 0, 3 ) === "\xEF\xBB\xBF" ) $raw = substr( $raw, 3 );
    $enc = mb_detect_encoding( $raw, [ 'UTF-8', 'GB18030', 'GBK', 'GB2312', 'BIG5' ], true );
    if ( $enc && $enc !== 'UTF-8' ) $raw = @mb_convert_encoding( $raw, 'UTF-8', $enc );
    if ( empty( trim( $raw ) ) ) {
        @unlink( $dest );
        wp_send_json_error( [ 'msg' => '文件编码转换失败' ] );
    }

    $raw    = str_replace( [ "\r\n", "\r" ], "\n", $raw );
    $lines  = explode( "\n", $raw );
    $novel_name = '';
    $author     = '';
    $chapters   = [];
    $current    = null;
    $started    = false;

    foreach ( $lines as $line ) {
        $l = trim( $line );
        $l = preg_replace( "/^[\x{3000}\s]+|[\x{3000}\s]+$/u", "", $l );
        if ( empty( $l ) ) continue;
        if ( empty( $author ) && preg_match( "/^作者[：:]\s*(.+)/u", $l, $m ) ) {
            $author = trim( $m[1] );
            continue;
        }
        if ( preg_match( "/^=+$|知轩藏书|zxcs\.me/", $l ) ) continue;
        if ( ! $started ) {
            if ( empty( $novel_name ) ) {
                $novel_name = preg_replace( "/^[《﹤]|[》﹥]$/u", "", $l );
                $novel_name = trim( $novel_name );
                // 解析 "小说名 作者：xxx" 格式，同时拆出小说名和作者
                if ( preg_match( "/^(.*?)\s*作者[：:]\s*(.+)$/u", $novel_name, $nm ) ) {
                    $novel_name = trim( $nm[1] );
                    if ( empty( $author ) ) {
                        $author = trim( $nm[2] );
                    }
                } elseif ( preg_match( "/^(.*?)作者$/u", $novel_name, $nm2 ) ) {
                    // 兼容 "小说名作者"（无冒号、无作者名）
                    $novel_name = trim( $nm2[1] );
                }
            }
            if ( preg_match( "/^(第[零一二三四五六七八九十百千\d]+[章节卷部篇回话集]|序[章言]?|楔子|前言|后记|尾声)/u", $l ) ) {
                $started = true;
                $current = [ 'title' => $l, 'content' => '' ];
            }
            continue;
        }
        if ( preg_match( "/^(第[零一二三四五六七八九十百千\d]+[章节卷部篇回话集]|序[章言]?|楔子|前言|后记|尾声)/u", $l ) ) {
            if ( $current ) $chapters[] = $current;
            $current = [ 'title' => $l, 'content' => '' ];
            continue;
        }
        if ( $current ) $current['content'] .= $l . "\n";
    }
    if ( $current ) $chapters[] = $current;

    if ( empty( $novel_name ) ) {
        @unlink( $dest );
        wp_send_json_error( [ 'msg' => '未找到小说名' ] );
    }

    $novel_name = trim( preg_replace( "#[/\\\\*?\"<>|:]#", "", $novel_name ) );
    $novel_name = mb_substr( $novel_name, 0, 180, 'UTF-8' );

    $chapters_meta = [];
    foreach ( $chapters as $ch ) {
        $chapters_meta[] = [ 'title' => $ch['title'] ];
    }

    file_put_contents( $meta_dest, json_encode( [
        'novel_name' => $novel_name,
        'author'     => $author,
        'chapters'   => $chapters_meta,
    ], JSON_UNESCAPED_UNICODE ) );

    wp_send_json_success( [
        'file_id'        => $file_id,
        'file_name'      => $file['name'],
        'novel_name'     => $novel_name,
        'author'         => $author,
        'total_chapters' => count( $chapters ),
    ] );
} );

// Step 2: 批量导入章节
add_action( 'wp_ajax_wpnovc_process_chapters', function () {
    check_ajax_referer( 'wpnovc_ajax', 'nonce' );
    if ( ! current_user_can( 'edit_theme_options' ) ) {
        wp_send_json_error( [ 'msg' => '权限不足' ] );
    }

    $file_id    = sanitize_text_field( $_POST['file_id'] ?? '' );
    $parent_id  = intval( $_POST['parent_id'] ?? 0 );
    $offset     = intval( $_POST['offset'] ?? 0 );
    $batch_size = intval( $_POST['batch_size'] ?? 30 );
    $batch_size = min( $batch_size, 100 );

    if ( ! $file_id || ! $parent_id ) {
        wp_send_json_error( [ 'msg' => '参数不完整' ] );
    }

    $temp_dir  = wpnovc_import_temp_dir();
    $txt_path  = $temp_dir . '/' . $file_id . '.txt';
    $meta_path = $temp_dir . '/' . $file_id . '.json';

    if ( ! file_exists( $txt_path ) || ! file_exists( $meta_path ) ) {
        wp_send_json_error( [ 'msg' => '临时文件已过期，请重新上传' ] );
    }

    $meta       = json_decode( file_get_contents( $meta_path ), true );
    $novel_name = $meta['novel_name'];
    $author     = $meta['author'];
    $chapters   = $meta['chapters'];
    $total      = count( $chapters );

    if ( $offset >= $total ) {
        wp_send_json_success( [ 'done' => true, 'processed' => $total, 'total' => $total ] );
    }

    $raw = @file_get_contents( $txt_path );
    if ( $raw === false ) {
        wp_send_json_error( [ 'msg' => '读取临时文件失败' ] );
    }
    if ( substr( $raw, 0, 3 ) === "\xEF\xBB\xBF" ) $raw = substr( $raw, 3 );
    $enc = mb_detect_encoding( $raw, [ 'UTF-8', 'GB18030', 'GBK', 'GB2312', 'BIG5' ], true );
    if ( $enc && $enc !== 'UTF-8' ) $raw = @mb_convert_encoding( $raw, 'UTF-8', $enc );
    $raw   = str_replace( [ "\r\n", "\r" ], "\n", $raw );
    $lines = explode( "\n", $raw );

    $parsed_chapters = [];
    $current = null;
    $started = false;
    foreach ( $lines as $line ) {
        $l = trim( $line );
        $l = preg_replace( "/^[\x{3000}\s]+|[\x{3000}\s]+$/u", "", $l );
        if ( empty( $l ) ) continue;
        if ( preg_match( "/^=+$|知轩藏书|zxcs\.me/", $l ) ) continue;
        if ( ! $started ) {
            if ( preg_match( "/^(第[零一二三四五六七八九十百千\d]+[章节卷部篇回话集]|序[章言]?|楔子|前言|后记|尾声)/u", $l ) ) {
                $started = true;
                $current = [ 'title' => $l, 'content' => '' ];
            }
            continue;
        }
        if ( preg_match( "/^(第[零一二三四五六七八九十百千\d]+[章节卷部篇回话集]|序[章言]?|楔子|前言|后记|尾声)/u", $l ) ) {
            if ( $current ) $parsed_chapters[] = $current;
            $current = [ 'title' => $l, 'content' => '' ];
            continue;
        }
        if ( $current ) $current['content'] .= $l . "\n";
    }
    if ( $current ) $parsed_chapters[] = $current;

    $novel_term = get_term_by( 'name', $novel_name, 'category', ARRAY_A );
    $is_new     = empty( $novel_term );
    if ( $is_new ) {
        $r = wp_insert_term( $novel_name, 'category', [ 'parent' => $parent_id ] );
        if ( is_wp_error( $r ) ) {
            wp_send_json_error( [ 'msg' => '创建分类失败: ' . $r->get_error_message() ] );
        }
        $novel_id = $r['term_id'];
    } else {
        $novel_id = $novel_term['term_id'];
    }

    if ( $is_new && $offset === 0 ) {
        require_once WPNOVC_DIR . '/inc/novel-meta.php';
        $cover_url = wpnovc_generate_cover( $novel_name, $novel_id );
        $fallback  = wpnovc_fallback_novel_info( $novel_name, $author );
        $meta_data = [
            'xs_author'       => $author,
            'xs_description'  => $fallback['description'],
            'editor_note'     => $fallback['editor_note'],
            'xs_status'       => 'serial',
            'seo_title'       => $fallback['seo_title'],
            'seo_description' => $fallback['seo_description'],
            'seo_keywords'    => $fallback['seo_keywords'],
        ];
        if ( ! empty( $cover_url ) ) $meta_data['xs_cover'] = $cover_url;
        wpnovc_update_novel_meta( $novel_id, $meta_data );
        wp_update_term( $novel_id, 'category', [ 'description' => $fallback['description'] ] );
    }

    global $wpdb;
    $existing_titles = [];
    $existing_posts  = get_posts( [
        'post_type'      => 'post',
        'cat'            => $novel_id,
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ] );
    if ( ! empty( $existing_posts ) ) {
        $ids_str = implode( ',', array_map( 'intval', $existing_posts ) );
        $existing_titles = $wpdb->get_col( "SELECT post_title FROM $wpdb->posts WHERE ID IN ($ids_str)" );
    }
    $existing_titles = is_array( $existing_titles ) ? $existing_titles : [];

    $end      = min( $offset + $batch_size, count( $parsed_chapters ) );
    $imported = 0;
    $skipped  = 0;

    wp_defer_term_counting( true );
    $base_time = time() - count( $parsed_chapters ) + $offset;

    for ( $i = $offset; $i < $end; $i++ ) {
        $ch = $parsed_chapters[ $i ];
        if ( in_array( $ch['title'], $existing_titles ) ) {
            $skipped++;
            continue;
        }
        $post_date = date( 'Y-m-d H:i:s', $base_time + $i );
        $pid = wp_insert_post( [
            'post_title'    => $ch['title'],
            'post_content'  => wpautop( $ch['content'] ),
            'post_status'   => 'publish',
            'post_type'     => 'post',
            'post_category' => [ $novel_id ],
            'post_date'     => $post_date,
        ] );
        if ( $pid && ! is_wp_error( $pid ) ) $imported++;
    }

    wp_defer_term_counting( false );

    $done = ( $end >= count( $parsed_chapters ) );

    wp_send_json_success( [
        'done'       => $done,
        'processed'  => $end,
        'total'      => count( $parsed_chapters ),
        'imported'   => $imported,
        'skipped'    => $skipped,
        'novel_id'   => $novel_id,
        'novel_name' => $novel_name,
        'is_new'     => $is_new,
    ] );
} );

// Step 3: 清理临时文件
add_action( 'wp_ajax_wpnovc_import_cleanup', function () {
    check_ajax_referer( 'wpnovc_ajax', 'nonce' );
    if ( ! current_user_can( 'edit_theme_options' ) ) {
        wp_send_json_error( [ 'msg' => '权限不足' ] );
    }
    $file_id = sanitize_text_field( $_POST['file_id'] ?? '' );
    if ( $file_id ) {
        $temp_dir  = wpnovc_import_temp_dir();
        $txt_path  = $temp_dir . '/' . $file_id . '.txt';
        $meta_path = $temp_dir . '/' . $file_id . '.json';
        @unlink( $txt_path );
        @unlink( $meta_path );
    }
    wp_send_json_success( [ 'msg' => '清理完成' ] );
} );

// ── AI 生成编辑推荐语 ──────────────────────────────────
add_action( 'wp_ajax_wpnovc_ai_brief_rec', function () {
    check_ajax_referer( 'wpnovc_ajax', 'nonce' );
    if ( ! current_user_can( 'edit_theme_options' ) ) {
        wp_send_json_error( [ 'msg' => '权限不足' ] );
    }
    $novel_id = intval( $_POST['novel_id'] ?? 0 );
    $term = get_term( $novel_id, 'category' );
    if ( ! $term || is_wp_error( $term ) ) {
        wp_send_json_error( [ 'msg' => '小说不存在' ] );
    }
    $meta   = wpnovc_get_novel_meta( $novel_id );
    $author = $meta['xs_author'] ?? '佚名';
    $rec = wpnovc_ai_generate_brief_recommendation( $term->name, $author );
    if ( $rec ) {
        $meta['brief_rec'] = $rec;
        wpnovc_update_novel_meta( $novel_id, $meta );
        wp_send_json_success( [ 'novel_id' => $novel_id, 'brief_rec' => $rec ] );
    }
    wp_send_json_error( [ 'msg' => 'AI 生成失败，请检查 DeepSeek API 配置' ] );
} );

// ── AI 生成 Banner ──────────────────────────────────
add_action( 'wp_ajax_wpnovc_ai_banner_generate', function () {
    check_ajax_referer( 'wpnovc_ajax', 'nonce' );
    if ( ! current_user_can( 'edit_theme_options' ) ) {
        wp_send_json_error( [ 'msg' => '权限不足' ] );
    }
    $prompt = sanitize_textarea_field( stripslashes( $_POST['prompt'] ?? '' ) );
    $device = sanitize_text_field( $_POST['device'] ?? 'pc' );
    if ( empty( $prompt ) ) {
        wp_send_json_error( [ 'msg' => '请输入提示词' ] );
    }
    if ( $device !== 'mobile' ) $device = 'pc';
    $url = wpnovc_ai_image_generate_banner( $prompt, $device );
    if ( $url ) {
        wp_send_json_success( [ 'url' => $url ] );
    }
    wp_send_json_error( [ 'msg' => 'AI 图片生成失败，请检查 AI 图片设置' ] );
} );

// ── 保存阅读进度 ──────────────────────────────────────
wpnovc_register_ajax( 'save_read_process', function () {
    $user_id  = get_current_user_id();
    $post_id  = intval( $_POST['postId'] ?? 0 );
    $book_id  = intval( $_POST['book_id'] ?? 0 );
    $page     = intval( $_POST['currentPage'] ?? 1 );
    if ( $user_id && $post_id ) {
        $time = current_time( 'mysql' );
        update_user_meta( $user_id, 'wpnovc_last_read_' . $book_id, $post_id );
        update_user_meta( $user_id, 'wpnovc_last_read_page_' . $post_id, $page );
        update_user_meta( $user_id, 'wpnovc_last_read_time_' . $book_id, $time );

        $history = get_user_meta( $user_id, 'wpnovc_reading_history', true );
        if ( ! is_array( $history ) ) $history = [];
        $history = array_values( array_diff( $history, [ $book_id ] ) );
        array_unshift( $history, $book_id );
        $history = array_slice( $history, 0, 100 );
        update_user_meta( $user_id, 'wpnovc_reading_history', $history );
    }
    wp_send_json_success();
} );

// ── 获取阅读进度 ──────────────────────────────────────
wpnovc_register_ajax( 'get_read_process', function () {
    $user_id = get_current_user_id();
    $book_id = intval( $_POST['book_id'] ?? 0 );
    $post_id = 0;
    $page    = 1;
    if ( $user_id && $book_id ) {
        $post_id = (int) get_user_meta( $user_id, 'wpnovc_last_read_' . $book_id, true );
        if ( $post_id ) {
            $page = (int) get_user_meta( $user_id, 'wpnovc_last_read_page_' . $post_id, true );
            if ( ! $page ) $page = 1;
        }
    }
    wp_send_json_success( [ 'postId' => $post_id, 'page' => $page ] );
} );

// ── 保存阅读模式（用户级，跨设备同步） ──────────────────
wpnovc_register_ajax( 'save_reading_mode', function () {
    $user_id = get_current_user_id();
    $mode    = sanitize_text_field( $_POST['mode'] ?? 'day' );
    if ( ! in_array( $mode, [ 'day', 'night-black', 'night-kindle' ], true ) ) {
        $mode = 'day';
    }
    if ( $user_id ) {
        update_user_meta( $user_id, 'wpnovc_reading_mode', $mode );
    }
    wp_send_json_success( [ 'mode' => $mode ] );
}, false );

// ── TTS / 购买 / 充值 占位 ──────────────────────────
wpnovc_register_ajax( 'tts', function () {
    wp_send_json_error( [ 'msg' => 'TTS功能暂未实现' ] );
}, false );

wpnovc_register_ajax( 'wpnovo_buy', function () {
    wp_send_json_error( [ 'msg' => '购买功能需配置支付网关后使用' ] );
}, false );

wpnovc_register_ajax( 'wpnovo_recharge', function () {
    wp_send_json_error( [ 'msg' => '充值功能需配置支付网关后使用' ] );
}, false );

// ── 文章排序 (admin) ──────────────────────────────────
wpnovc_register_ajax( 'post_move', function () {
    if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error();
    $post_id = intval( $_POST['post_id'] ?? 0 );
    $target  = intval( $_POST['target_id'] ?? 0 );
    if ( $post_id && $target ) {
        wp_update_post( [ 'ID' => $post_id, 'post_date' => get_post_field( 'post_date', $target ) ] );
    }
    wp_send_json_success();
} );

wpnovc_register_ajax( 'post_move_up', function () {
    if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error();
    $post_id = intval( $_POST['post_id'] ?? 0 );
    $cat     = intval( $_POST['cat'] ?? 0 );
    if ( $post_id ) {
        $adjacent = get_posts( [
            'post_type' => 'post', 'cat' => $cat, 'posts_per_page' => 1,
            'orderby' => 'date', 'order' => 'ASC',
            'date_query' => [ 'after' => get_post_field( 'post_date', $post_id ) ],
        ] );
        if ( $adjacent ) {
            $new_date = date( 'Y-m-d H:i:s', strtotime( get_post_field( 'post_date', $adjacent[0] ) ) - 1 );
            wp_update_post( [ 'ID' => $post_id, 'post_date' => $new_date ] );
        }
    }
    wp_send_json_success();
} );

wpnovc_register_ajax( 'post_move_down', function () {
    if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error();
    $post_id = intval( $_POST['post_id'] ?? 0 );
    $cat     = intval( $_POST['cat'] ?? 0 );
    if ( $post_id ) {
        $adjacent = get_posts( [
            'post_type' => 'post', 'cat' => $cat, 'posts_per_page' => 1,
            'orderby' => 'date', 'order' => 'DESC',
            'date_query' => [ 'before' => get_post_field( 'post_date', $post_id ) ],
        ] );
        if ( $adjacent ) {
            $new_date = date( 'Y-m-d H:i:s', strtotime( get_post_field( 'post_date', $adjacent[0] ) ) + 1 );
            wp_update_post( [ 'ID' => $post_id, 'post_date' => $new_date ] );
        }
    }
    wp_send_json_success();
} );

// ── 书架操作 (匹配JS参数格式) ──
add_action( 'wp_ajax_add_book_to_shelf', function () {
    check_ajax_referer( 'wpnovc_ajax', 'nonce' );
    $user_id  = get_current_user_id();
    $novel_id = intval( $_POST['book_id'] ?? 0 );
    if ( ! $user_id || ! $novel_id ) { wp_send_json_error(); }
    $shelf = get_user_meta( $user_id, 'wpnovc_bookshelf', true ) ?: [];
    if ( ! in_array( $novel_id, $shelf ) ) {
        $shelf[] = $novel_id;
        update_user_meta( $user_id, 'wpnovc_bookshelf', $shelf );
    }
    wp_send_json_success();
} );

add_action( 'wp_ajax_remove_book_from_shelf', function () {
    check_ajax_referer( 'wpnovc_ajax', 'nonce' );
    $user_id  = get_current_user_id();
    $novel_id = intval( $_POST['book_id'] ?? 0 );
    if ( ! $user_id || ! $novel_id ) { wp_send_json_error(); }
    $shelf = get_user_meta( $user_id, 'wpnovc_bookshelf', true ) ?: [];
    $shelf = array_values( array_diff( $shelf, [ $novel_id ] ) );
    update_user_meta( $user_id, 'wpnovc_bookshelf', $shelf );
    wp_send_json_success();
} );

// ── 封面 / 导语 生成 ──
add_action( 'wp_ajax_wpnovc_gen_cover', function () {
    $id = intval( $_POST['novel_id'] ?? 0 );
    $method = sanitize_text_field( $_POST['method'] ?? 'gd' );
    $term = get_term( $id, 'category' );
    if ( ! $term || is_wp_error( $term ) ) wp_send_json_error( '小说不存在' );
    $url = ( $method === 'ai' || $method === 'ai_model' ) ? wpnovc_generate_ai_cover( $term->name, $id ) : wpnovc_generate_cover( $term->name, $id );
    if ( $url ) {
        $meta = wpnovc_get_novel_meta( $id );
        $meta['xs_cover'] = $url;
        wpnovc_update_novel_meta( $id, $meta );
        wp_send_json_success( [ 'name' => $term->name, 'url' => $url ] );
    }
    wp_send_json_error( '生成失败' );
} );

add_action( 'wp_ajax_wpnovc_gen_editor_note', function () {
    $id = intval( $_POST['novel_id'] ?? 0 );
    $term = get_term( $id, 'category' );
    if ( ! $term || is_wp_error( $term ) ) wp_send_json_error( '小说不存在' );
    $meta   = wpnovc_get_novel_meta( $id );
    $author = $meta['xs_author'] ?? '佚名';
    $rec = wpnovc_ai_generate_editor_note_full( $term->name, $author );
    if ( $rec ) {
        $meta['editor_note'] = $rec;
        wpnovc_update_novel_meta( $id, $meta );
        wp_send_json_success( [ 'name' => $term->name, 'note' => $rec ] );
    }
    wp_send_json_error( '生成失败' );
} );
