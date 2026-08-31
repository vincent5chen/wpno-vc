<?php get_header(); ?>
<div id="single-bar" class="row"><div class="container"><div class="col-xs-12"><?php wpnovc_breadcrumb(); ?></div></div></div>
<div class="container">
    <h2>热门小说</h2>
    <div class="row">
    <?php
    $cats = get_terms( [ 'taxonomy' => 'category', 'parent' => 0, 'hide_empty' => false ] );
    foreach ( $cats as $cat ) :
        $children = wpnovc_get_child_categories( $cat->term_id, 12 );
        foreach ( $children as $novel ) :
            $meta = wpnovc_get_novel_meta( $novel->term_id );
    ?>
        <div class="col-xs-3"><div class="novel-card">
            <div class="cover"><a href="<?php echo get_category_link( $novel ); ?>"><?php echo wpnovc_novel_cover( $meta, $novel->name ); ?></a></div>
            <h3><a href="<?php echo get_category_link( $novel ); ?>"><?php echo $novel->name; ?></a></h3>
            <p class="author"><?php echo esc_html( $meta['xs_author'] ); ?>&nbsp;</p>
        </div></div>
    <?php endforeach; endforeach; ?>
    </div>
</div>
<?php get_footer(); ?>
