<?php get_header();
$author_id = get_queried_object_id();
$author    = wpnovc_get_author_info( $author_id );
$novels    = wpnovc_get_author_novels( $author_id );
?>
<div id="single-bar" class="row">
    <div class="container"><div class="col-xs-12"><?php wpnovc_breadcrumb(); ?></div></div>
</div>
<div class="container">
    <div id="category-list">
        <div class="author-header">
            <h1><?php echo esc_html( $author['display_name'] ); ?> 的作品</h1>
        </div>
        <div class="row category-list">
            <?php foreach ( $novels as $novel ) : $meta = wpnovc_get_novel_meta( $novel->term_id ); ?>
            <div class="col-xs-3">
                <div class="novel-card">
                    <div class="cover"><a href="<?php echo get_category_link( $novel ); ?>"><?php echo wpnovc_novel_cover( $meta, $novel->name ); ?></a></div>
                    <h3><a href="<?php echo get_category_link( $novel ); ?>"><?php echo $novel->name; ?></a></h3>
                    <p class="desc"><?php echo wpnovc_strimwidth( $meta['xs_description'] ?: $novel->description, 60 ); ?></p>
                    <?php echo wpnovc_status_label( $meta ); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php get_footer(); ?>
