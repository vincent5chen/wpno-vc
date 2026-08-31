<?php
/**
 * 首页轮播
 *
 * @package wpno-vc
 */

function wpnovc_get_home_banners() {
    $banners = wpnovc_option( 'home_banners', [] );
    if ( ! is_array( $banners ) ) $banners = [];
    usort( $banners, function ( $a, $b ) {
        $ao = isset( $a['order'] ) ? intval( $a['order'] ) : 0;
        $bo = isset( $b['order'] ) ? intval( $b['order'] ) : 0;
        return $bo - $ao;
    } );
    return $banners;
}

function wpnovc_render_home_banner() {
    $banners = wpnovc_get_home_banners();
    if ( empty( $banners ) ) return;
    ?>
    <div class="home-banner-wrap" style="max-width:1200px;margin:0 auto;padding:12px 16px 0">
        <div class="home-banner-slider" id="home-banner-slider" style="position:relative;border-radius:8px;overflow:hidden;background:#eee;box-shadow:0 2px 8px rgba(0,0,0,.06)">
            <?php foreach ( $banners as $i => $banner ): ?>
                <a class="home-banner-slide" href="<?php echo esc_url( $banner['link'] ?: '#' ); ?>" style="display:none;position:relative;text-decoration:none">
                    <img src="<?php echo esc_url( $banner['image'] ); ?>" alt="" style="width:100%;height:375px;object-fit:cover;display:block;max-width:100%">
                </a>
            <?php endforeach; ?>
            <button type="button" class="home-banner-prev" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);width:36px;height:36px;border:none;border-radius:50%;background:rgba(0,0,0,.35);color:#fff;font-size:18px;cursor:pointer;z-index:2">‹</button>
            <button type="button" class="home-banner-next" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);width:36px;height:36px;border:none;border-radius:50%;background:rgba(0,0,0,.35);color:#fff;font-size:18px;cursor:pointer;z-index:2">›</button>
            <div class="home-banner-dots" style="position:absolute;left:0;right:0;bottom:10px;text-align:center;z-index:2"></div>
        </div>
    </div>
    <style>
        @media (max-width:768px){
            .home-banner-slide img{height:auto!important;aspect-ratio:1200/375!important}
            .home-banner-prev,.home-banner-next{width:30px;height:30px;font-size:15px}
        }
    </style>
    <script>
    jQuery(function($){
        var $slider = $('#home-banner-slider');
        if (!$slider.length) return;
        var slides = $slider.find('.home-banner-slide');
        var dotsWrap = $slider.find('.home-banner-dots');
        if (slides.length === 0) return;
        slides.each(function(i){
            dotsWrap.append('<span class="home-banner-dot" data-index="' + i + '" style="display:inline-block;width:8px;height:8px;margin:0 4px;border-radius:50%;background:rgba(255,255,255,.6);cursor:pointer"></span>');
        });
        var dots = dotsWrap.find('.home-banner-dot');
        var current = 0;
        function show(i){
            current = (i + slides.length) % slides.length;
            slides.hide().eq(current).show();
            dots.css('background','rgba(255,255,255,.6)').eq(current).css('background','#fff');
        }
        $slider.find('.home-banner-prev').click(function(){ show(current - 1); });
        $slider.find('.home-banner-next').click(function(){ show(current + 1); });
        dots.click(function(){ show(parseInt($(this).data('index'),10)); });
        show(0);
        setInterval(function(){ show(current + 1); }, 4000);
    });
    </script>
    <?php
}
