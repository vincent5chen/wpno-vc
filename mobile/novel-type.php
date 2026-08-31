<?php get_header(); ?>
<div id="single-bar" class="row"><div class="container"><div class="col-xs-12"><?php wpnovc_breadcrumb(); ?></div></div></div>
<div class="container">
    <h2>小说分类</h2>
    <?php $cats = wpnovc_get_top_categories();
    foreach ( $cats as $cat ) :
        $children = wpnovc_get_child_categories( $cat->term_id, 20 );
    ?>
    <div class="type-section">
        <h3><a href="<?php echo get_category_link( $cat ); ?>"><?php echo $cat->name; ?></a></h3>
        <div class="type-tags">
            <?php foreach ( $children as $c ) : ?>
            <a href="<?php echo get_category_link( $c ); ?>"><?php echo $c->name; ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php get_footer(); ?>
