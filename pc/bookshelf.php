<?php get_header(); ?>
<div id="single-bar" class="row">
    <div class="container"><div class="col-xs-12"><?php wpnovc_breadcrumb(); ?></div></div>
</div>
<div class="container">
    <h2 style="margin:16px 0">我的书架</h2>
    <?php if ( ! is_user_logged_in() ) : ?>
        <p>请先<a href="<?php echo home_url( '/login/' ); ?>">登录</a>后查看书架。</p>
    <?php else :
        $user_id = get_current_user_id();
        $shelf   = get_user_meta( $user_id, 'wpnovc_bookshelf', true ) ?: [];
        if ( empty( $shelf ) ) : ?>
            <p>书架是空的，去<a href="<?php echo home_url(); ?>">首页</a>找小说吧！</p>
        <?php else : ?>
        <div class="row">
            <?php foreach ( $shelf as $term_id ) :
                $cat  = get_term( $term_id, 'category' );
                if ( ! $cat || is_wp_error( $cat ) ) continue;
                $meta = wpnovc_get_novel_meta( $term_id );
            ?>
            <div class="col-xs-6" style="margin-bottom:16px">
                <div style="display:flex;gap:12px;background:#fff;padding:12px;border-radius:6px;box-shadow:0 1px 4px rgba(0,0,0,.06)">
                    <div style="width:80px;flex-shrink:0" class="bookshelf-cover">
                        <a href="<?php echo get_category_link( $cat ); ?>"><?php echo wpnovc_novel_cover( $meta, $cat->name ); ?></a>
                    </div>
                    <div style="flex:1;min-width:0">
                        <a href="<?php echo get_category_link( $cat ); ?>" style="font-size:15px;font-weight:600;color:#333;text-decoration:none;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo esc_html( $cat->name ); ?></a>
                        <p style="font-size:12px;color:#999;margin:4px 0"><?php echo esc_html( $meta['xs_author'] ); ?>&nbsp;</p>
                        <p style="font-size:13px;color:#666;line-height:1.5;overflow:hidden;max-height:40px"><?php echo wpnovc_strimwidth( $meta['xs_description'] ?: $cat->description, 80 ); ?></p>
                        <button class="btn-remove-shelf" data-id="<?php echo $term_id; ?>" style="font-size:12px;padding:2px 12px;border:1px solid #ddd;background:#fff;border-radius:3px;color:#999;cursor:pointer">移出书架</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.btn-remove-shelf').forEach(function(btn){
        btn.addEventListener('click', function(){
            var id = this.getAttribute('data-id');
            var card = this.closest('.col-xs-6');
            fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=remove_book_from_shelf&book_id=' + id + '&nonce=' + (typeof admin_ajax !== 'undefined' ? admin_ajax.nonce : '')
            }).then(function(r){ return r.json(); }).then(function(d){
                if(d.success && card) card.remove();
            });
        });
    });
});
</script>
<?php get_footer(); ?>
