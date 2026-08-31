<?php
/**
 * wpno-vc 主题核心函数
 * 
 * 灵感来源: wpnovo (imwpweb.com)
 * 完全重写，无混淆，可读性强
 * 
 * @package wpno-vc
 */

// ── 常量定义 ──────────────────────────────────────────
define( 'WPNOVC_VERSION', '1.2' );
define( 'WPNOVC_DIR', get_template_directory() );
define( 'WPNOVC_URI', get_template_directory_uri() );
define( 'WPNOVC_ASSETS', WPNOVC_URI . '/assets' );

// ── 加载核心模块 ──────────────────────────────────────


// ── PC 知乎风格顶栏 body class ──
add_filter( 'body_class', function ( $classes ) {
    $classes[] = wpnovc_is_mobile() ? 'm-body-push' : 'zh-body-push';
    return $classes;
} );

// ── 全局禁用前台顶部工具栏 ──
add_filter( 'show_admin_bar', '__return_false' );
// ── 安全响应头 ──
add_action( 'send_headers', function () {
    // HSTS：强制 HTTPS（仅生产环境启用）
    if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
        header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains; preload' );
    }
    // 禁止被嵌入 iframe（防止点击劫持）
    header( 'X-Frame-Options: SAMEORIGIN' );
    // 禁止 MIME 类型嗅探
    header( 'X-Content-Type-Options: nosniff' );
    // 启用浏览器 XSS 过滤器
    header( 'X-XSS-Protection: 1; mode=block' );
    // 控制 Referer 信息
    header( 'Referrer-Policy: strict-origin-when-cross-origin' );
    // 权限策略
    header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()' );
    // CSP：内容安全策略
    header( "Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://pagead2.googlesyndication.com https://*.googlesyndication.com https://www.googletagmanager.com https://cdn.bootcdn.net; style-src 'self' 'unsafe-inline' https://cdn.bootcdn.net; img-src 'self' data: https:; font-src 'self' https://cdn.bootcdn.net; frame-src https://googleads.g.doubleclick.net; connect-src 'self' https://*.google-analytics.com https://api.deepseek.com" );
} );


// ── Google AdSense ──
add_action( 'wp_head', function () {
    echo '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-4768632289650914" crossorigin="anonymous"></script>' . "\n";
}, 1 );
// ── Favicon ──
add_action( 'wp_head', function () {
    echo '<link rel="icon" type="image/png" href="' . WPNOVC_ASSETS . '/img/logo-58.png">' . "\n";
}, 2 );



// ── 全局替换用户头像为本地默认图（后续开发头像上传功能）──
add_filter( 'get_avatar_url', function ( $url, $id_or_email, $args ) {
    return WPNOVC_ASSETS . '/img/avatar.jpg';
}, 999, 3 );

// 临时：首次加载刷新 rewrite 规则
add_action( 'init', function () {
    if ( get_option( 'wpnovc_flush_rewrite_v5' ) !== '1' ) {
        flush_rewrite_rules();
        update_option( 'wpnovc_flush_rewrite_v5', '1' );
    }
} );

$inc_files = [
    'helper-functions.php',     // 通用工具函数
    'novel-meta.php',           // 小说分类元数据（封面/作者/简介）
    'template-tags.php',        // 模板标签函数
    'rewrite-rules.php',        // 虚拟页面路由
    'seo.php',                  // SEO 优化
    'ajax-handlers.php',        // AJAX 处理
    'ai-generator.php',         // DeepSeek AI 内容生成
    'ai-image-adapter.php',      // AI 图像生成通用适配层
    'cover-generator.php',      // 小说封面图自动生成
    'editor-picks.php',         // 编辑推荐期次数据层
    'home-banner.php',          // 首页轮播
    'admin-settings.php',       // 后台设置页
    'category-fields.php',      // 分类自定义字段
    'user-fields.php',          // 用户自定义字段
    'payment-init.php',         // 支付系统初始化
];

foreach ( $inc_files as $file ) {
    $path = WPNOVC_DIR . '/inc/' . $file;
    if ( file_exists( $path ) ) {
        require_once $path;
    }
}

// ── 主题初始化 ────────────────────────────────────────
add_action( 'after_setup_theme', 'wpnovc_setup' );
function wpnovc_setup() {
    // 多语言支持
    load_theme_textdomain( 'wpno-vc', WPNOVC_DIR . '/lang' );

    // 标题标签
    add_theme_support( 'title-tag' );

    // 特色图片
    add_theme_support( 'post-thumbnails' );

    // HTML5 支持
    add_theme_support( 'html5', [
        'search-form', 'comment-form', 'comment-list', 'gallery', 'caption',
    ] );

    // 注册导航菜单位置
    register_nav_menus( [
        'navpc'     => 'PC 端导航菜单',
        'navmobile' => '移动端导航菜单',
    ] );
}

// ── PC/Mobile 自适应 ─────────────────────────────────
function wpnovc_is_mobile() {
    if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
        return false;
    }
    $ua = strtolower( $_SERVER['HTTP_USER_AGENT'] );
    $mobiles = [ 'mobile', 'android', 'iphone', 'ipod', 'blackberry', 'opera mini', 'windows phone' ];
    foreach ( $mobiles as $device ) {
        if ( strpos( $ua, $device ) !== false ) {
            return true;
        }
    }
    return false;
}

function wpnovc_template_dir() {
    return wpnovc_is_mobile() ? 'mobile' : 'pc';
}

// ── 资产加载 ──────────────────────────────────────────
add_action( 'wp_enqueue_scripts', 'wpnovc_enqueue_assets' );
function wpnovc_enqueue_assets() {
    $is_mobile = wpnovc_is_mobile();
    $theme_ver = WPNOVC_VERSION;

    // jQuery (CDN 开关)
    $use_cdn = get_option( 'wpnovc_jquery_cdn', 1 );
    if ( $use_cdn ) {
        wp_deregister_script( 'jquery' );
        wp_register_script( 'jquery', 'https://cdn.bootcdn.net/ajax/libs/jquery/1.10.2/jquery.min.js', [], '1.10.2', false );
    }
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'jquery-cookie', WPNOVC_ASSETS . '/js/jquery.cookie.min.js', [ 'jquery' ], '1.4.1', false );

    // 翻译对象 (mobile.min.js 依赖)
    wp_add_inline_script( 'jquery', 'var g_tr={};g_tr["tr-add-success"]="已加入书架";g_tr["tr-remove-success"]="已移出书架";g_tr["tr-no-reading-record"]="暂无阅读记录";g_tr["tr-continue-read"]="继续阅读";', 'before' );

    // Font Awesome (CDN)
    wp_enqueue_style( 'font-awesome', 'https://cdn.bootcdn.net/ajax/libs/font-awesome/5.15.4/css/all.min.css', [], '5.15.4' );

    // 主题样式
    $css_file = $is_mobile ? 'mobile.min.css' : 'pc.min.css';
    wp_enqueue_style( 'wpnovc-main', WPNOVC_ASSETS . '/css/' . $css_file, [], $theme_ver );

    // 主题脚本
    $js_file = $is_mobile ? 'mobile.min.js' : 'pc.min.js';
    wp_enqueue_script( 'wpnovc-main', WPNOVC_ASSETS . '/js/' . $js_file, [ 'jquery' ], $theme_ver, true );

    // 内联变量
    wp_localize_script( 'wpnovc-main', 'admin_ajax', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'home_url' => home_url(),
        'homeUrl' => home_url(),
        'is_mobile' => $is_mobile,
        'nonce' => wp_create_nonce( 'wpnovc_ajax' ),
    ] );

    // 文章页传递 postId, categoryId, homeUrl
    if ( is_single() ) {
        global $post;
        $cats = get_the_category( $post->ID );
        $cid = $cats ? $cats[0]->term_id : 0;
        wp_add_inline_script( 'wpnovc-main', 'var postId=' . $post->ID . ';var categoryId=' . $cid . ';var bookId=' . $cid . ';var homeUrl="' . home_url() . '";var currentPage=' . ( get_query_var( 'page' ) ?: 1 ) . ';', 'before' );

        wp_enqueue_script( 'wpnovc-reading', WPNOVC_ASSETS . '/js/reading-page.js', [ 'jquery' ], $theme_ver, true );
    }

    // 自定义 CSS
    $custom_css = wpnovc_custom_css();
    if ( $custom_css ) {
        wp_add_inline_style( 'wpnovc-main', $custom_css );
    }
}


// ── 后台资产加载 ──────────────────────────────────────
add_action( 'admin_enqueue_scripts', 'wpnovc_admin_enqueue_assets' );
function wpnovc_admin_enqueue_assets( $hook ) {
    // 仅在小说导入和批量导入页面加载
    if ( strpos( $hook, 'wpnovo_novel_importer' ) === false && strpos( $hook, 'wpnovo_batch_importer' ) === false ) {
        return;
    }
    wp_enqueue_script(
        'wpnovc-novel-import',
        WPNOVC_ASSETS . '/js/novel-import.js',
        [ 'jquery' ],
        WPNOVC_VERSION,
        true
    );
    wp_localize_script( 'wpnovc-novel-import', 'admin_ajax', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'home_url' => home_url(),
        'nonce'    => wp_create_nonce( 'wpnovc_ajax' ),
    ] );
}

function wpnovc_custom_css() {
    $css  = '';
    $main_color = get_option( 'wpnovc_main_color', '' );
    if ( $main_color ) {
        $css .= "a,.top-nav .col i{color:{$main_color}}";
        $css .= ".tab-container .tab-title.active a{background:{$main_color}}";
        $css .= "#readbtn a.btn-a{background:{$main_color};border-color:{$main_color}}";
        $css .= ".block .title{border-color:{$main_color}}";
    }
    $font_size = get_option( 'wpnovc_single_fontsize', '' );
    if ( $font_size ) {
        $css .= ".content p{font-size:{$font_size}px}";
    }
    return $css;
}

// ── 模板加载器 (template_include 过滤器) ────────────
add_filter( 'template_include', 'wpnovc_template_include', 99 );
function wpnovc_template_include( $template ) {
    $dir   = wpnovc_template_dir();
    $file  = basename( $template );
    
    // 映射 WordPress 模板层级到子目录
    $map = [
        'index.php'            => 'index.php',
        'single.php'           => 'single.php',
        'page.php'             => 'page.php',
        'category.php'         => 'category-level1.php', // overridden below by depth check
        'archive.php'          => 'category-level0.php',
        'author.php'           => 'author.php',
        'search.php'           => 'novel-search.php',
        '404.php'              => '404.php',
        'tag.php'              => 'tag.php',
    ];
    
    $target = $map[ $file ] ?? $file;
    
    // Category depth routing: parent=0 → novel list, parent>0 → novel detail
    if ( $file === 'category.php' || is_category() ) {
        $cat = get_queried_object();
        if ( isset( $cat->parent ) ) {
            if ( $cat->parent == 0 ) {
                $target = 'category-level0.php'; // novel list
            } elseif ( get_category( $cat->parent )->parent == 0 ) {
                $target = 'category-level1.php'; // novel detail
            } else {
                $target = 'category-level2.php'; // volume
            }
        }
    }
    
    $custom = WPNOVC_DIR . "/{$dir}/{$target}";
    
    if ( file_exists( $custom ) ) {
        return $custom;
    }
    return $template;
}

// ── PC/Mobile 模板加载辅助 ────────────────────────────
function wpnovc_load_template( $template_name ) {
    $dir  = wpnovc_template_dir();
    $file = WPNOVC_DIR . "/{$dir}/{$template_name}";
    if ( file_exists( $file ) ) {
        include $file;
    }
}

// ── 禁用无用功能 ──────────────────────────────────────
add_filter( 'wp_revisions_to_keep', '__return_false' );

// ── 安全加固：隐藏 WordPress 版本号（修复 generator 泄露）──
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

// ── 安全加固：阻止未授权访问暴露管理员登录名 ──
// 1) 禁止未登录用户通过 REST API 枚举用户 (/wp-json/wp/v2/users)
add_filter( 'rest_authentication_errors', function ( $result ) {
    if ( ! empty( $result ) ) {
        return $result;
    }
    if ( ! is_user_logged_in() ) {
        $uri = wp_unslash( $_SERVER['REQUEST_URI'] ?? '' );
        if ( strpos( $uri, '/wp-json/wp/v2/users' ) !== false ) {
            return new WP_Error( 'rest_forbidden', '暂无权限访问', [ 'status' => 401 ] );
        }
    }
    return $result;
} );

// 2) 禁止未登录用户通过 ?author=N 枚举登录名
//    必须用 init 钩子且早于 WP 自带的 redirect_canonical（同在 template_redirect 且注册更早），
//    否则会被先 301 跳到 /author/xxx/ 而绕过本拦截
add_action( 'init', function () {
    if ( ! is_user_logged_in() && ! empty( $_GET['author'] ) ) {
        wp_redirect( home_url(), 301 );
        exit;
    }
} );

// 2b) 禁止未登录用户直接访问 /author/xxx/ 归档，彻底隐藏登录名
add_action( 'template_redirect', function () {
    if ( ! is_user_logged_in() && is_author() ) {
        wp_redirect( home_url(), 301 );
        exit;
    }
} );

// 3) 禁用 XML-RPC（防暴力枚举/ Pingback 滥用）— 用 PHP 过滤，避免 .htaccess Require 在部分主机 AllowOverride 下导致全局 500
add_filter( 'xmlrpc_enabled', '__return_false', 999 );
// 双保险：init 阶段直接拦截 xmlrpc 请求，防止被插件用更高优先级的过滤器覆盖回 true
add_action( 'init', function () {
    if ( ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST )
        || stripos( (string) ( $_SERVER['SCRIPT_NAME'] ?? '' ), 'xmlrpc.php' ) !== false ) {
        status_header( 405 );
        header( 'Content-Type: text/plain; charset=utf-8' );
        echo 'XML-RPC services are disabled.';
        exit;
    }
}, 1 );


// 书架独立脚本 (不依赖 mobile.min.js)
add_action( 'wp_footer', function () {
    if ( ! is_category() ) return;
    $ajax_url = admin_url( 'admin-ajax.php' );
    $nonce    = wp_create_nonce( 'wpnovc_ajax' );
    ?>
    <script>
    function wpnovcAddBookshelf(el) {
        var id = el.getAttribute("data-id");
        var xhr = new XMLHttpRequest();
        xhr.open("POST", <?php echo wp_json_encode( $ajax_url ); ?>, true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onload = function() {
            try {
                var r = JSON.parse(xhr.responseText);
                if (r.success) {
                    el.textContent = "已加入书架";
                    el.style.background = "#27ae60";
                    el.style.color = "#fff";
                    el.onclick = null;
                } else {
                    alert(r.data && r.data.msg ? r.data.msg : "加入书架失败，请重试");
                }
            } catch(e) {
                alert("请先登录后再加入书架");
            }
        };
        xhr.send("action=add_book_to_shelf&book_id=" + id + "&nonce=" + <?php echo wp_json_encode( $nonce ); ?>);
    }
    </script>
    <?php
} );

// ── 激活主题时刷新 rewrite ──────────────────────────
add_action( 'after_switch_theme', function () {
    flush_rewrite_rules();
} );

// ── 第十课：书籍 term meta 注册到 REST（支持 API 批量维护 seo_description 等） ──
add_action( 'init', function () {
    $keys = array(
        'xs_author', 'xs_cover', 'xs_description',
        'seo_title', 'seo_description', 'seo_keywords',
        'editor_note', 'brief_rec',
    );
    foreach ( $keys as $k ) {
        register_term_meta( 'category', $k, array(
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => function ( $v ) {
                return sanitize_textarea_field( (string) $v );
            },
        ) );
    }
} );
