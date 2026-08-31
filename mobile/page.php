<?php get_header(); ?>
<div id="single-bar" class="row"><div class="container"><div class="col-xs-12"><?php wpnovc_breadcrumb(); ?></div></div></div>
<div class="container">
    <article class="content">
        <h1><?php the_title(); ?></h1>
        <?php the_content(); ?>
    </article>
</div>
<?php get_footer(); ?>
