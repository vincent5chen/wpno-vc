<?php get_header(); ?>
<div id="single-bar" class="row">
    <div class="container"><div class="col-xs-12"><?php wpnovc_breadcrumb(); ?></div></div>
</div>
<div class="container">
    <h2>搜索小说</h2>
    <form method="get" class="search-form-large">
        <input type="text" name="keyword" value="<?php echo esc_attr( $_GET['keyword'] ?? '' ); ?>" placeholder="输入小说名..." class="large-search-input" />
        <button type="submit" class="btn btn-a">搜索</button>
    </form>
    <hr>
    <?php 
    $keyword = sanitize_text_field( $_GET['keyword'] ?? '' );
    if ( $keyword ) :
        $results = get_terms( [ 'taxonomy' => 'category', 'name__like' => $keyword, 'hide_empty' => false, 'number' => 20 ] );
        if ( empty( $results ) ) : ?>
            <p>没有找到与 "<?php echo esc_html( $keyword ); ?>" 相关的小说。</p>
        <?php else : ?>
            <div class="row">
            <?php foreach ( $results as $term ) : $meta = wpnovc_get_novel_meta( $term->term_id ); ?>
                <div class="col-xs-3"><div class="novel-card">
                    <div class="cover"><a href="<?php echo get_category_link( $term ); ?>"><?php echo wpnovc_novel_cover( $meta, $term->name ); ?></a></div>
                    <h3><a href="<?php echo get_category_link( $term ); ?>"><?php echo $term->name; ?></a></h3>
                    <p class="author"><?php echo esc_html( $meta['xs_author'] ); ?></p>
                </div></div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php get_footer(); ?>
