<?php get_header();
$term = get_queried_object();
$parent = get_category( $term->parent );
?>
<div id="single-bar" class="row"><div class="container"><div class="col-xs-12"><?php wpnovc_breadcrumb(); ?></div></div></div>
<div class="container">
    <h2><?php echo $parent ? '<a href="' . get_category_link( $parent ) . '">' . $parent->name . '</a> &raquo; ' : ''; ?><?php echo $term->name; ?></h2>
    <?php if ( have_posts() ) : ?>
        <?php while ( have_posts() ) : the_post(); ?>
            <div class="chapter-item"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></div>
        <?php endwhile; wpnovc_pagination(); ?>
    <?php else : ?>
        <p>暂无章节</p>
    <?php endif; ?>
</div>
<?php get_footer(); ?>
