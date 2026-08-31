<?php get_header(); ?>
<?php
global $post;
$second_cat = wpnovc_get_second_level_cat( $post->ID );
$novel_meta = $second_cat ? wpnovc_get_novel_meta( $second_cat->term_id ) : [];
$novel_link = $second_cat ? get_category_link( $second_cat ) : '#';

// 获取上一章/下一章
$all_chapters = [];
if ( $second_cat ) {
    $all_chapters = get_posts( [
        'post_type'      => 'post',
        'cat'            => $second_cat->term_id,
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'ASC',
    ] );
    // 按标题数字排序
    // Use post_date order (matches category listing)
}
$current_idx = 0;
foreach ( $all_chapters as $i => $ch ) {
    if ( $ch->ID == $post->ID ) { $current_idx = $i; break; }
}
$prev = $current_idx > 0 ? $all_chapters[ $current_idx - 1 ] : null;
$next = $current_idx < count( $all_chapters ) - 1 ? $all_chapters[ $current_idx + 1 ] : null;
?>
<div id="single-bar" class="row">
    <div class="container">
        <div class="col-xs-12">
            <?php wpnovc_breadcrumb(); ?>
        </div>
    </div>
</div>

<div class="container">
    <div id="single" class="content-area">
        <article class="content">
            <h1 class="single-title"><?php the_title(); ?></h1>
            <div class="single-meta">
                <span>小说：<a href="<?php echo $novel_link; ?>"><?php echo $second_cat ? $second_cat->name : ''; ?></a></span>
                <span>作者：<?php echo esc_html( $novel_meta['xs_author'] ?? '' ); ?></span>
            </div>
            <div class="content-body">
                <?php 
                // 付费章节控制
                if ( $novel_meta['pay_mode'] == 'article' && $novel_meta['free_chapter'] > 0 ) {
                    $chapter_num = $current_idx + 1;
                    if ( $chapter_num > $novel_meta['free_chapter'] ) {
                        if ( ! is_user_logged_in() || ! wpnovc_has_purchased( get_current_user_id(), $second_cat->term_id ) ) {
                            echo '<div class="pay-wall"><p>本章为付费章节，请购买后阅读。</p>';
                            echo '<a href="#" class="btn btn-a pay-btn" data-novel="' . $second_cat->term_id . '">购买整本 (' . number_format( $novel_meta['category_price'] / 100, 2 ) . ' 元)</a>';
                            echo '</div>';
                            get_footer();
                            return;
                        }
                    }
                }
                the_content();
                wp_link_pages( ['before' => '<div class="page-links">', 'after' => '</div>'] );
                ?>
            </div>
        </article>

        <div class="control clearfix">
            <span><?php if ( $prev ) : ?><a href="<?php echo get_permalink( $prev ); ?>">上一章</a><?php else: ?>没有了<?php endif; ?></span>
            <span><a href="<?php echo $novel_link; ?>">目录</a></span>
            <span><?php if ( $next ) : ?><a href="<?php echo get_permalink( $next ); ?>">下一章</a><?php else: ?>没有了<?php endif; ?></span>
        </div>
    </div>
</div>


<div id="reading-toolbar">
    <div class="reading-inner">
        <?php if ( $prev ): ?><a href="<?php echo get_permalink( $prev ); ?>">上一章</a><?php else: ?><span>上一章</span><?php endif; ?>
        <button type="button" id="reading-mode-btn">日间</button>
        <button type="button" id="reading-tts-btn">朗读</button>
        <a href="<?php echo $novel_link; ?>">目录</a>
        <?php if ( $next ): ?><a href="<?php echo get_permalink( $next ); ?>">下一章</a><?php else: ?><span>下一章</span><?php endif; ?>
    </div>
</div>

<div id="reading-mode-popup">
    <button type="button" class="reading-mode-option" data-mode="day">日间模式</button>
    <button type="button" class="reading-mode-option" data-mode="night-black">夜间（纯黑）</button>
    <button type="button" class="reading-mode-option" data-mode="night-kindle">夜间（Kindle）</button>
</div>

<style>
#reading-toolbar{position:fixed;left:0;right:0;bottom:0;z-index:1500;background:#fff;border-top:1px solid #ddd;box-shadow:0 -2px 8px rgba(0,0,0,.08);transition:transform .25s}
#reading-toolbar.hidden{transform:translateY(110%)}
#reading-toolbar .reading-inner{max-width:1200px;margin:0 auto;display:flex;justify-content:space-around;align-items:center;height:50px}
#reading-toolbar a,#reading-toolbar button,#reading-toolbar span{background:none;border:none;color:#333;font-size:14px;text-decoration:none;cursor:pointer}
#reading-mode-popup{display:none;position:fixed;left:50%;bottom:70px;transform:translateX(-50%);background:#fff;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,.15);padding:8px;z-index:1600;min-width:160px}
#reading-mode-popup button{display:block;width:100%;padding:8px 18px;border:none;background:none;cursor:pointer;text-align:left;white-space:nowrap}
#reading-mode-popup button:hover{background:#f0f4f8}
body.reading-night-black{background:#1f1f1f}
body.reading-night-black #single,body.reading-night-black .container,body.reading-night-black .content,body.reading-night-black .content-body{background:#1f1f1f!important;color:#c9c9c9!important}
body.reading-night-black #single .single-title,body.reading-night-black #single a,body.reading-night-black #single p,body.reading-night-black #single span{color:#c9c9c9!important}
body.reading-night-kindle{background:#f4ecd8}
body.reading-night-kindle #single,body.reading-night-kindle .container,body.reading-night-kindle .content,body.reading-night-kindle .content-body{background:#f4ecd8!important;color:#3a3226!important}
body.reading-night-kindle #single .single-title,body.reading-night-kindle #single a,body.reading-night-kindle #single p,body.reading-night-kindle #single span{color:#3a3226!important}
</style>

<script>
window.wpnovcReading = {
    ajaxUrl: <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
    nonce: <?php echo wp_json_encode( wp_create_nonce( 'wpnovc_ajax' ) ); ?>,
    userMode: <?php echo wp_json_encode( get_user_meta( get_current_user_id(), 'wpnovc_reading_mode', true ) ?: '' ); ?>,
    postId: <?php echo (int) $post->ID; ?>,
    bookId: <?php echo (int) ( $second_cat ? $second_cat->term_id : 0 ); ?>,
    page: <?php echo (int) ( get_query_var('page') ?: 1 ); ?>,
    nextUrl: <?php echo wp_json_encode( $next ? get_permalink( $next ) : '' ); ?>
};
</script>

<?php get_footer(); ?>

