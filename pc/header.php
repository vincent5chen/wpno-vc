<?php
/**
 * PC 端头部 — 知乎风格
 * 
 * @package wpno-vc
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <?php wp_head(); ?>
    <style>
    /* ── Zhihu-style Top Bar ── */
    .zh-topbar{position:fixed;top:0;left:0;right:0;z-index:1000;height:52px;background:#fff;box-shadow:0 1px 3px rgba(18,52,90,.08)}
    .zh-topbar .zh-inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;height:100%;padding:0 20px}
    .zh-topbar .zh-logo-wrap{display:flex;align-items:center;flex-shrink:0;margin-right:24px}
    .zh-topbar .zh-logo{width:30px;height:30px;display:block}
    .zh-topbar .zh-brand{font-size:20px;font-weight:700;color:#12345a;margin-left:8px;text-decoration:none;line-height:30px}
    .zh-topbar .zh-brand:hover{color:#12345a;text-decoration:none}
    .zh-topbar .zh-nav,.zh-topbar .zh-nav ul{display:flex;align-items:center;list-style:none;margin:0;padding:0;gap:0;flex:1;overflow:hidden}.zh-topbar .zh-nav li,.zh-topbar .zh-nav .menu-item{display:flex}
    .zh-topbar .zh-nav a,.zh-topbar .zh-nav .menu-item a{display:inline-flex;align-items:center;height:52px;padding:0 14px;font-size:15px;color:#444;text-decoration:none;white-space:nowrap;border-bottom:2px solid transparent;transition:color .2s}
    .zh-topbar .zh-nav a:hover{color:#12345a}
    .zh-topbar .zh-nav .current-menu-item a,.zh-topbar .zh-nav .current-menu-item a:hover{color:#12345a;border-bottom-color:#12345a;font-weight:600}
    .zh-topbar .zh-right{display:flex;align-items:center;gap:10px;flex-shrink:0;margin-left:16px}
    .zh-topbar .zh-search{position:relative;width:160px}
    .zh-topbar .zh-search input{width:100%;height:32px;padding:0 32px 0 10px;border:1px solid #e0e0e0;border-radius:16px;font-size:13px;outline:none;background:#f6f6f6;transition:border .2s,background .2s;box-sizing:border-box}
    .zh-topbar .zh-search input:focus{border-color:#12345a;background:#fff}
    .zh-topbar .zh-search button{position:absolute;right:2px;top:50%;transform:translateY(-50%);width:28px;height:28px;border:none;background:transparent;color:#999;cursor:pointer;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;padding:0}
    .zh-topbar .zh-search button:hover{color:#12345a;background:#f0f0f0}
    .zh-topbar .zh-btn{display:inline-flex;align-items:center;height:34px;padding:0 16px;font-size:14px;border:1px solid #12345a;border-radius:3px;text-decoration:none;transition:all .2s;white-space:nowrap;cursor:pointer}
    .zh-topbar .zh-btn-primary{background:#12345a;color:#fff}
    .zh-topbar .zh-btn-primary:hover{background:#0d2a4a;color:#fff;text-decoration:none}
    .zh-topbar .zh-btn-outline{background:#fff;color:#12345a}
    .zh-topbar .zh-btn-outline:hover{background:#f0f4f8;text-decoration:none}
    .zh-topbar .zh-avatar{position:relative}
    .zh-topbar .zh-avatar img{width:34px;height:34px;border-radius:50%;cursor:pointer;object-fit:cover}
    .zh-topbar .zh-dropdown{display:none;position:absolute;top:44px;right:0;min-width:140px;background:#fff;border-radius:4px;box-shadow:0 4px 16px rgba(0,0,0,.12);z-index:1100;overflow:hidden}
    .zh-topbar .zh-dropdown.show{display:block}
    .zh-topbar .zh-dropdown a{display:block;padding:10px 16px;font-size:14px;color:#444;text-decoration:none;transition:background .15s}
    .zh-topbar .zh-dropdown a:hover{background:#f6f6f6;color:#12345a}
    .zh-topbar .zh-dropdown .zh-dropdown-divider{border-top:1px solid #eee;margin:4px 0}
    /* Push body down */
    .zh-body-push{padding-top:52px}.list-cover img{width:100%!important;height:auto!important;max-width:100%!important;max-height:none!important;display:block}.bookshelf-cover img{width:100%!important;height:auto!important;max-width:100%!important;max-height:none!important;display:block}.editor-picks-cover img,#description .cover img{width:100%!important;height:auto!important;max-width:100%!important}.editor-picks-cover img{width:100%!important;height:auto!important;max-width:100%!important;max-height:none!important;display:block}
    </style>
</head>
<body <?php body_class(); ?>>

<div class="zh-topbar">
<div class="zh-inner">
    <!-- Logo + 品牌名 -->
    <a href="<?php echo home_url(); ?>" class="zh-logo-wrap" style="text-decoration:none">
        <img class="zh-logo" src="/wp-content/themes/wpno-vc/assets/img/logo-58.png" alt="快看小说" />
        <span class="zh-brand">快看小说</span>
    </a>

    <!-- 主导航 -->
    <nav class="zh-nav">
        <?php wpnovc_nav_menu( 'navpc' ); ?>
    </nav>

    <!-- 右侧：搜索 + 用户 -->
    <div class="zh-right">
        <form class="zh-search" action="<?php echo home_url( '/search/' ); ?>" method="get">
            <input name="keyword" type="text" value="<?php echo esc_attr( $_GET['keyword'] ?? '' ); ?>" placeholder="搜索小说…" />
            <button type="submit">🔍</button>
        </form>

        <?php if ( is_user_logged_in() ) : 
            $user = wp_get_current_user();
            $uid  = $user->ID;
            $avatar_url = get_avatar_url( $uid, [ 'size' => 68 ] );
        ?>
            <div class="zh-avatar" id="zh-avatar-dropdown">
                <img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $user->display_name ); ?>" />
                <div class="zh-dropdown" id="zh-dropdown-menu">
                    <div style="padding:10px 16px;font-size:14px;color:#333;font-weight:600;border-bottom:1px solid #f0f0f0"><?php echo esc_html( $user->display_name ); ?></div>
                    <a href="<?php echo home_url( '/bookshelf/' ); ?>">我的书架</a>
                    <a href="<?php echo home_url( '/user/?uid=' . $uid ); ?>">我的资料</a>
                    <a href="<?php echo home_url( '/reading-history/' ); ?>">我的阅读记录</a>
                    <div class="zh-dropdown-divider"></div>
                    <a href="<?php echo wp_logout_url( home_url() ); ?>">退出</a>
                </div>
            </div>
        <?php else : ?>
            <a href="<?php echo home_url( '/register/' ); ?>" style="font-size:14px;color:#444;text-decoration:none;white-space:nowrap">注册</a>
            <a href="<?php echo home_url( '/login/' ); ?>" style="font-size:14px;color:#12345a;text-decoration:none;white-space:nowrap;margin-left:4px">登录</a>
        <?php endif; ?>
    </div>
</div><!-- /zh-inner -->
</div>

<script>
(function(){
    var avatar = document.getElementById('zh-avatar-dropdown');
    if (!avatar) return;
    var menu = document.getElementById('zh-dropdown-menu');
    avatar.addEventListener('click', function(e){
        e.stopPropagation();
        menu.classList.toggle('show');
    });
    document.addEventListener('click', function(){ menu.classList.remove('show'); });
})();
</script>
