<?php get_header();
$term = get_queried_object();
$meta = wpnovc_get_novel_meta( $term->term_id );
wpnovc_bump_views( $term->term_id );

// 获取所有章节
$chapters = get_posts( [
    'post_type'      => 'post',
    'cat'            => $term->term_id,
    'posts_per_page' => -1,
    'orderby'        => 'date',
    'order'          => 'ASC',
] );
// Use post_date order (file order from import)

$first     = ! empty( $chapters ) ? $chapters[0] : null;
$latest    = ! empty( $chapters ) ? $chapters[ count( $chapters ) - 1 ] : null;
$parent_cat = get_category( $term->parent );
?>
<div id="single-bar" class="row">
    <div class="container">
        <div class="col-xs-12"><?php wpnovc_breadcrumb(); ?></div>
    </div>
</div>

<div class="container">
    <div id="description">
        <div class="cover"><?php echo wpnovc_novel_cover( $meta, $term->name, true ); ?></div>
        <div class="meta">
            <h1><?php echo $term->name; ?></h1>
            <p>
                <span>作者: <?php echo esc_html( $meta['xs_author'] ); ?></span>
                <span>状态: <?php echo $meta['xs_status'] == 'complete' ? '已完结' : '连载中'; ?></span>
                <span>热度: <?php echo wpnovc_get_views( $term->term_id ); ?></span>
            </p>
            <p>
                <span>最新: <a href="<?php echo $latest ? get_permalink( $latest ) : '#'; ?>"><?php echo $latest ? $latest->post_title : '暂无'; ?></a></span>
                <span>更新: <?php echo $latest ? get_the_time( 'Y-m-d', $latest ) : '-'; ?></span>
            </p>
            <div style="display:flex;gap:10px;margin-top:10px">
                <?php if ( $first ) : ?>
                <a class="btn btn-a" href="<?php echo get_permalink( $first ); ?>" style="flex:1;display:block;text-align:center;padding:10px 0;background:#bd0607;color:#fff;border-radius:4px;text-decoration:none;font-size:15px">开始阅读</a>
                <?php endif; ?>
                <a class="btn btn-b add-bookshelf-btn" href="javascript:void(0)" data-id="<?php echo $term->term_id; ?>" onclick="wpnovcAddBookshelf(this)" style="flex:1;display:block;text-align:center;padding:10px 0;border:1px solid #bd0607;color:#bd0607;border-radius:4px;text-decoration:none;font-size:15px">加入书架</a>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div id="category-list">
        <ul class="tab clearfix">
            <li class="current" id="to-content-a">小说简介</li>
            <li id="to-content-b">章节目录</li>
        </ul>
        <div id="content-a" class="description">
            <p><?php echo $meta['xs_description'] ? nl2br( esc_html( $meta['xs_description'] ) ) : '暂无简介'; ?></p>
            <?php if ( ! empty( $meta['editor_note'] ) ) : ?>
            <p class="title">编辑导语</p>
            <div style="background:#fffbf0;padding:12px;border-left:3px solid #e74c3c;font-size:14px;line-height:1.8;margin-bottom:15px"><?php echo wpnovc_md_format( $meta['editor_note'] ); ?></div>
            <?php endif; ?>
            <?php if ( $latest ) : ?>
            <p class="title">最新章节</p>
            <a href="<?php echo get_permalink( $latest ); ?>"><?php echo $latest->post_title; ?></a>
            <?php endif; ?>
        </div>
        <div id="content-b">
            <?php 
            $row_class = 'odd';
            foreach ( $chapters as $i => $ch ) :
                $row_class = ( $row_class == 'odd' ) ? 'even' : 'odd';
            ?>
            <div class="chapter-item <?php echo $row_class; ?>">
                <a href="<?php echo get_permalink( $ch ); ?>"><?php echo esc_html( $ch->post_title ); ?></a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("to-content-a").onclick = function() {
        document.getElementById("content-a").style.display = "block";
        document.getElementById("content-b").style.display = "none";
        this.classList.add("current");
        document.getElementById("to-content-b").classList.remove("current");
    };
    document.getElementById("to-content-b").onclick = function() {
        document.getElementById("content-a").style.display = "none";
        document.getElementById("content-b").style.display = "block";
        this.classList.add("current");
        document.getElementById("to-content-a").classList.remove("current");
    };
    // Default: show content-a, hide content-b
    document.getElementById("content-b").style.display = "none";
});
</script>
<?php get_footer(); ?>
