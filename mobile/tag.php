<?php get_header(); ?>
<div id="single-bar" class="row"><div class="container"><div class="col-xs-12"><?php wpnovc_breadcrumb(); ?></div></div></div>
<div class="container"><?php if (have_posts()) : while (have_posts()) : the_post(); ?><p><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></p><?php endwhile; wpnovc_pagination(); endif; ?></div>
<?php get_footer(); ?>
