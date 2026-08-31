<?php
/**
 * 移动端首页
 * 
 * @package wpno-vc
 */
get_header();
wpnovc_render_home_banner();
?>
<!-- SEO：首页唯一 H1（视觉隐藏） -->
<h1 class="wpnovc-sr-only" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;">快看小说 - 免费小说在线阅读网，海量热门小说每日更新</h1>
<?php
// ── 推荐小说区 ──
$recommend_cats = array_filter( explode( ',', wpnovc_option( 'home_recommend_cats', '' ) ) );
if ( empty( $recommend_cats ) ) {
    $top_cats = wpnovc_get_top_categories();
    $recommend_cats = array_slice( array_map( function( $c ) { return $c->term_id; }, $top_cats ), 0, 3 );
}
if ( ! empty( $recommend_cats ) ) :
    foreach ( $recommend_cats as $parent_id ) :
        $novels   = wpnovc_get_child_categories( $parent_id, 6 );
        if ( empty( $novels ) ) continue;
        $parent   = get_term( $parent_id, 'category' );
?>
<div class="container" style="padding:0 16px;margin-bottom:10px">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 4px 6px">
        <h2 style="font-size:16px;font-weight:700;color:#333;margin:0"><?php echo esc_html( $parent->name ); ?></h2>
        <a href="<?php echo get_category_link( $parent ); ?>" style="font-size:13px;color:#999">更多 »</a>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:8px">
        <?php foreach ( $novels as $novel ) : $m = wpnovc_get_novel_meta( $novel->term_id ); ?>
        <div style="width:calc(50% - 4px);margin-bottom:8px">
            <a href="<?php echo get_category_link( $novel ); ?>" style="display:block;border-radius:4px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,.08)"><div style="width:100%;aspect-ratio:3/4;overflow:hidden">
                <?php echo wpnovc_novel_cover( $m, $novel->name ); ?></div>
            </a>
            <div style="padding:4px 2px 0;text-align:center">
                <a href="<?php echo get_category_link( $novel ); ?>" style="font-size:14px;color:#333;text-decoration:none;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo esc_html( $novel->name ); ?></a>
                <span style="font-size:11px;color:#999"><?php echo esc_html( $m['xs_author'] ); ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; endif; ?>

<!-- ── 最新更新 ── -->
<div class="container" style="padding:0 16px">
    <div style="padding:10px 4px 6px"><h2 style="font-size:16px;font-weight:700;color:#333;margin:0;display:inline">最新更新</h2></div>
    <div>
        <?php while ( have_posts() ) : the_post(); 
            $second_cat = wpnovc_get_second_level_cat( get_the_ID() );
            $novel_name = $second_cat ? $second_cat->name : '';
            $novel_link = $second_cat ? get_category_link( $second_cat ) : '#';
            $meta = $second_cat ? wpnovc_get_novel_meta( $second_cat->term_id ) : [];
            $parent = $second_cat ? get_category( $second_cat->parent ) : null;
        ?>
        <div style="display:flex;align-items:center;padding:8px 4px;border-bottom:1px solid #f0f0f0;font-size:13px">
            <?php if ( $parent ) : ?>
            <span style="background:#f0f0f0;color:#999;padding:1px 6px;border-radius:2px;font-size:11px;margin-right:6px;white-space:nowrap"><?php echo esc_html( $parent->name ); ?></span>
            <?php endif; ?>
            <a href="<?php echo $novel_link; ?>" style="color:#333;margin-right:4px;white-space:nowrap"><?php echo esc_html( $novel_name ); ?></a>
            <a href="<?php the_permalink(); ?>" style="color:#666;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php the_title(); ?></a>
            <span style="color:#bbb;font-size:11px;white-space:nowrap;margin-left:auto"><?php the_time( 'm-d' ); ?></span>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<?php get_footer(); ?>
