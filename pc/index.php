<?php
/**
 * PC 端首页
 * 
 * @package wpno-vc
 */
get_header();
wpnovc_render_home_banner();
?>
<!-- SEO：首页唯一 H1（视觉隐藏，语义供搜索引擎与读屏器使用） -->
<h1 class="wpnovc-sr-only" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;">快看小说 - 免费小说在线阅读网，海量热门小说每日更新</h1>
<?php

// ── 推荐小说区 ──
$recommend_cats = array_filter( explode( ',', wpnovc_option( 'home_recommend_cats', '' ) ) );
if ( empty( $recommend_cats ) ) {
    // 默认取前几个有内容的顶级分类
    $top_cats = wpnovc_get_top_categories();
    $recommend_cats = array_slice( array_map( function( $c ) { return $c->term_id; }, $top_cats ), 0, 3 );
}
if ( ! empty( $recommend_cats ) ) :
    // 每个推荐分类取前12个子分类(小说)
    foreach ( $recommend_cats as $parent_id ) :
        $novels   = wpnovc_get_child_categories( $parent_id, 10 );
        if ( empty( $novels ) ) continue;
        $parent   = get_term( $parent_id, 'category' );
?>
<div id="home-recommend" class="container">
    <div class="row">
        <div class="type-imgtext block">
            <h2 class="title"><?php echo esc_html( $parent->name ); ?> <span class="pull-right"><a href="<?php echo get_category_link( $parent ); ?>">更多 &raquo;</a></span></h2>
            <?php $first = array_shift( $novels ); $meta = wpnovc_get_novel_meta( $first->term_id ); ?>
            <div class="col-xs-3">
                <div class="novel-special">
                    <div class="center"><?php echo wpnovc_novel_cover( $meta, $first->name ); ?></div>
                    <div class="novel-special-content">
                        <h3><?php echo esc_html( $first->name ); ?></h3>
                        <p class="center"><?php echo esc_html( $meta['xs_author'] ); ?>&nbsp;</p>
                        <div class="description"><?php echo wpnovc_strimwidth( $meta['xs_description'] ?: $first->description, 200 ); ?></div>
                        <a class="read" href="<?php echo get_category_link( $first ); ?>">开始阅读</a>
                    </div>
                </div>
            </div>
            <div class="col-xs-9">
                <div class="row">
                    <?php foreach ( $novels as $novel ) : $m = wpnovc_get_novel_meta( $novel->term_id ); ?>
                    <div class="col-xs-4">
                        <div class="novel-item clearfix">
                            <div class="cover">
                                <a href="<?php echo get_category_link( $novel ); ?>" title="<?php echo esc_attr( $novel->name ); ?>">
                                    <?php echo wpnovc_novel_cover( $m, $novel->name ); ?>
                                </a>
                            </div>
                            <div class="xs-info">
                                <p class="name"><a href="<?php echo get_category_link( $novel ); ?>"><?php echo esc_html( $novel->name ); ?></a></p>
                                <p class="author"><?php echo esc_html( $m['xs_author'] ); ?>&nbsp;</p>
                                <div class="description"><?php echo wpnovc_strimwidth( $m['xs_description'] ?: $novel->description, 80 ); ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; endif; ?>

<!-- ── 分类小说列表 ── -->
<?php
$display_cats = array_filter( explode( ',', wpnovc_option( 'home_display_cats', '' ) ) );
if ( empty( $display_cats ) ) {
    $top_cats = wpnovc_get_top_categories();
    $display_cats = array_slice( array_map( function( $c ) { return $c->term_id; }, $top_cats ), 0, 8 );
}
if ( ! empty( $display_cats ) ) :
?>
<div class="container">
    <div id="home-category">
        <div class="row">
        <?php 
        $cols = wpnovc_option( 'home_cat_cols', 4 );
        foreach ( $display_cats as $parent_id ) :
            $parent = get_term( $parent_id, 'category' );
            if ( ! $parent || is_wp_error( $parent ) ) continue;
            $children = wpnovc_get_child_categories( $parent_id, 5 );
            if ( empty( $children ) ) continue;
            $first = $children[0];
            $meta  = wpnovc_get_novel_meta( $first->term_id );
        ?>
            <div class="col-xs-<?php echo 12 / intval( $cols ); ?>">
                <div class="block rec-block">
                    <h2 class="title"><a href="<?php echo get_category_link( $parent ); ?>"><?php echo $parent->name; ?> <span class="pull-right">更多 &raquo;</span></a></h2>
                    <ul>
                        <li class="clearfix">
                            <?php echo wpnovc_novel_cover( $meta, $first->name ); ?>
                            <a href="<?php echo get_category_link( $first ); ?>"><?php echo $first->name; ?></a>
                            <p class="desc"><?php echo wpnovc_strimwidth( $meta['xs_description'] ?: $first->description, 100 ); ?></p>
                        </li>
                        <?php foreach ( array_slice( $children, 1 ) as $c ) : ?>
                        <li class="single-line"><a href="<?php echo get_category_link( $c ); ?>"><?php echo $c->name; ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── 最新更新 ── -->
<div id="newest" class="container">
    <div class="row">
        <div class="col-xs-12">
            <div class="newest-post">
                <h2 class="title">最新更新</h2>
                <ul>
                    <?php while ( have_posts() ) : the_post(); 
                        $second_cat = wpnovc_get_second_level_cat( get_the_ID() );
                        $novel_name = $second_cat ? $second_cat->name : '';
                        $novel_link = $second_cat ? get_category_link( $second_cat ) : '#';
                        $meta = $second_cat ? wpnovc_get_novel_meta( $second_cat->term_id ) : [];
                        $parent = $second_cat ? get_category( $second_cat->parent ) : null;
                    ?>
                    <li>
                        <?php if ( $parent ) : ?>
                        <span class="parent">[<a href="<?php echo get_category_link( $parent ); ?>"><?php echo $parent->name; ?></a>]</span>
                        <?php endif; ?>
                        <span class="novelname"><a href="<?php echo $novel_link; ?>"><?php echo $novel_name; ?></a></span>
                        <span class="name"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></span>
                        <span class="author"><?php echo $meta['xs_author'] ?? ''; ?>&nbsp;</span>
                        <span class="time"><?php the_time( 'm-d' ); ?></span>
                    </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
