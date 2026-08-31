<?php get_header();
$term     = get_queried_object();
$children = wpnovc_get_child_categories( $term->term_id, 20 );
?>
<style>
.m-novel-grid{display:flex;flex-wrap:wrap;gap:10px;padding:0 16px}
.m-novel-card{width:calc(50% - 5px);margin-bottom:12px}
.m-novel-card .cover{overflow:hidden;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,.1)}
.m-novel-card .cover img{display:block;width:100%!important;max-width:100%!important;height:auto!important;aspect-ratio:3/4;object-fit:cover}
.m-novel-card h3{font-size:14px;margin:6px 0 2px;line-height:1.4;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;padding:0 4px}
.m-novel-card h3 a{color:#333;text-decoration:none}
.m-novel-card .author{font-size:12px;color:#999;margin:0;padding:0 4px}
.m-novel-card .desc{display:none}
</style>

<div id="single-bar" class="row">
    <div class="container">
        <div class="col-xs-12"><?php wpnovc_breadcrumb(); ?></div>
    </div>
</div>

<div class="container">
    <h1 style="font-size:20px;margin:0 0 14px;padding:0 4px"><?php echo esc_html( $term->name ); ?></h1>
    <div class="m-novel-grid">
        <?php foreach ( $children as $novel ) : 
            $meta = wpnovc_get_novel_meta( $novel->term_id );
        ?>
        <div class="m-novel-card">
            <div class="cover">
                <a href="<?php echo get_category_link( $novel ); ?>"><?php echo wpnovc_novel_cover( $meta, $novel->name ); ?></a>
            </div>
            <h3><a href="<?php echo get_category_link( $novel ); ?>"><?php echo $novel->name; ?></a></h3>
            <p class="author"><?php echo esc_html( $meta['xs_author'] ); ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php get_footer(); ?>
