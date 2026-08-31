<?php
/**
 * 移动端头部 — 与 PC 端统一风格
 * 
 * @package wpno-vc
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <?php wp_head(); ?>
    <style>
    .m-topbar{position:fixed;top:0;left:0;right:0;z-index:1000;height:48px;background:#fff;box-shadow:0 1px 3px rgba(18,52,90,.08);display:flex;align-items:center;padding:0 14px;transition:transform .3s}
    .m-topbar .m-logo{width:28px;height:28px}
    .m-topbar .m-brand{font-size:18px;font-weight:700;color:#12345a;margin-left:6px;text-decoration:none}
    .m-topbar .m-toggle{margin-left:auto;font-size:22px;color:#12345a;cursor:pointer;padding:4px 8px}
    .m-nav-panel{display:none;position:fixed;top:48px;left:0;right:0;bottom:0;z-index:999;background:#fff;overflow-y:auto;padding:16px}
    .m-nav-panel a{display:block;padding:10px 0;font-size:15px;color:#444;text-decoration:none;border-bottom:1px solid #f0f0f0}
    .m-nav-panel .m-search{margin:10px 0}
    .m-nav-panel .m-search input{width:100%;height:36px;padding:0 12px;border:1px solid #ddd;border-radius:18px;font-size:14px;box-sizing:border-box}
    .m-body-push{padding-top:48px}
    /* Global mobile spacing */
    .container{padding-left:16px!important;padding-right:16px!important}
    .m-body-push .container,.m-body-push > .row{padding-left:16px!important;padding-right:16px!important}
    /* Mobile cover fix */
    .category-list img,.novel-item img,.cover img,.rec-block img,img[alt]{max-width:100%!important;height:auto!important;max-height:none!important}.bookshelf-cover img{width:100%!important;height:auto!important;max-width:100%!important;max-height:none!important;display:block}
    </style>
</head>
<body <?php body_class(); ?>>

<div class="m-topbar">
    <a href="<?php echo home_url(); ?>" style="display:flex;align-items:center;text-decoration:none">
        <img class="m-logo" src="/wp-content/themes/wpno-vc/assets/img/logo-58.png" alt="快看小说" />
        <span class="m-brand">快看小说</span>
    </a>
    <div class="m-toggle" id="m-toggle">☰</div>
</div>

<div class="m-nav-panel" id="m-nav-panel">
    <?php wpnovc_nav_menu( 'navmobile' ); ?>

    <div class="m-search">
        <form action="<?php echo home_url( '/search/' ); ?>" method="get">
            <input type="text" name="keyword" placeholder="搜索小说..." />
        </form>
    </div>

    <?php if ( is_user_logged_in() ) : $user = wp_get_current_user(); ?>
    <div class="m-user-links" style="margin-top:10px;padding-top:10px;border-top:1px solid #f0f0f0">
        <div style="padding:8px 0;font-size:14px;color:#333;font-weight:600"><?php echo esc_html( $user->display_name ); ?></div>
        <a href="<?php echo home_url( '/bookshelf/' ); ?>">我的书架</a>
        <a href="<?php echo home_url( '/user/?uid=' . $user->ID ); ?>">我的资料</a>
                    <a href="<?php echo home_url( '/reading-history/' ); ?>">我的阅读记录</a>
        <a href="<?php echo wp_logout_url( home_url() ); ?>">退出</a>
    </div>
    <?php else : ?>
    <div class="m-user-links" style="margin-top:10px;padding-top:10px;border-top:1px solid #f0f0f0">
        <a href="<?php echo home_url( '/login/' ); ?>">登录</a>
        <a href="<?php echo home_url( '/register/' ); ?>">注册</a>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var toggle = document.getElementById('m-toggle');
    if (toggle) toggle.addEventListener('click', function() {
        var panel = document.getElementById('m-nav-panel');
        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
    });

    // 滚动时自动隐藏/显示顶栏
    var topbar = document.querySelector('.m-topbar');
    if (!topbar) return;
    var lastScroll = 0;
    var ticking = false;
    window.addEventListener('scroll', function() {
        if (!ticking) {
            requestAnimationFrame(function() {
                var st = window.pageYOffset || document.documentElement.scrollTop;
                if (st > lastScroll && st > 60) {
                    topbar.style.transform = 'translateY(-100%)';
                } else {
                    topbar.style.transform = 'translateY(0)';
                }
                lastScroll = st <= 0 ? 0 : st;
                ticking = false;
            });
            ticking = true;
        }
    }, {passive: true});
});
</script>
