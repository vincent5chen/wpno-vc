<?php get_header();
$term     = get_queried_object();
$per_page = 40;
$page     = max( 1, intval( $_GET['pg'] ?? 1 ) );
$total    = wpnovc_count_child_categories( $term->term_id );
$total_pages = ceil( $total / $per_page );
$children = wpnovc_get_child_categories( $term->term_id, $per_page, ( $page - 1 ) * $per_page );
?>
<div id="single-bar" class="row">
    <div class="container">
        <div class="col-xs-12"><?php wpnovc_breadcrumb(); ?></div>
    </div>
</div>

<div class="container" style="margin-top:16px">
    <h1 style="font-size:22px;margin:0 0 16px"><?php echo esc_html( $term->name ); ?></h1>
    <div class="row">
        <?php foreach ( $children as $novel ) : 
            $meta = wpnovc_get_novel_meta( $novel->term_id );
        ?>
        <div class="col-xs-3" style="margin-bottom:24px">
            <div style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 1px 6px rgba(0,0,0,.06);text-align:center;padding:16px 12px 12px">
                <a href="<?php echo get_category_link( $novel ); ?>" style="display:block;width:140px;height:187px;margin:0 auto 10px;overflow:hidden;border-radius:4px" class="list-cover">
                    <?php echo wpnovc_novel_cover( $meta, $novel->name ); ?>
                </a>
                <h3 style="font-size:14px;margin:0 0 2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><a href="<?php echo get_category_link( $novel ); ?>" style="color:#333;text-decoration:none"><?php echo esc_html( $novel->name ); ?></a></h3>
                <p style="font-size:12px;color:#999;margin:0 0 4px"><?php echo esc_html( $meta['xs_author'] ); ?></p>
                <p style="font-size:12px;color:#666;line-height:1.5;overflow:hidden;max-height:36px"><?php echo wpnovc_strimwidth( $meta['xs_description'] ?: $novel->description, 80 ); ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div style="text-align:center;padding:20px 0">
        <?php for ( $i = 1; $i <= $total_pages; $i++ ) : ?>
            <a href="?pg=<?php echo $i; ?>" style="display:inline-block;padding:6px 12px;margin:0 3px;border:1px solid #ddd;border-radius:3px;text-decoration:none;color:<?php echo $i === $page ? '#fff' : '#333'; ?>;background:<?php echo $i === $page ? '#12345a' : '#fff'; ?>;font-size:13px"><?php echo $i; ?></a>
        <?php endfor; ?>
        <span style="font-size:12px;color:#999;margin-left:10px">共 <?php echo $total_pages; ?> 页 / <?php echo $total; ?> 本</span>
    </div>
</div>
<?php get_footer(); ?>
