<?php
get_header();
$user_id = get_current_user_id();
$history = $user_id ? get_user_meta( $user_id, 'wpnovc_reading_history', true ) : [];
if ( ! is_array( $history ) ) $history = [];
?>
<div style="padding:16px">
    <h2 style="font-size:18px;border-left:4px solid #12345a;padding-left:10px;margin:0 0 16px">我的阅读记录</h2>
    <?php if ( ! $user_id ): ?>
        <p style="color:#999;text-align:center;padding:60px 0">请先登录</p>
    <?php elseif ( empty( $history ) ): ?>
        <p style="color:#999;text-align:center;padding:60px 0">暂无阅读记录</p>
    <?php else: ?>
        <?php foreach ( $history as $book_id ):
            $book_id = intval( $book_id );
            $term = get_term( $book_id, 'category' );
            if ( ! $term || is_wp_error( $term ) ) continue;
            $post_id = (int) get_user_meta( $user_id, 'wpnovc_last_read_' . $book_id, true );
            $page = (int) get_user_meta( $user_id, 'wpnovc_last_read_page_' . $post_id, true );
            $page = $page ?: 1;
            $time = get_user_meta( $user_id, 'wpnovc_last_read_time_' . $book_id, true );
            $chapter = $post_id ? get_post( $post_id ) : null;
            $meta = wpnovc_get_novel_meta( $book_id );
            $link = $post_id ? add_query_arg( 'page', $page, get_permalink( $post_id ) ) : get_category_link( $term );
        ?>
        <div style="display:flex;gap:12px;background:#fff;border-radius:8px;padding:14px;margin-bottom:12px;box-shadow:0 1px 4px rgba(0,0,0,.06)">
            <div style="width:72px;flex-shrink:0;border-radius:4px;overflow:hidden">
                <a href="<?php echo get_category_link( $term ); ?>"><?php echo wpnovc_novel_cover( $meta, $term->name ); ?></a>
            </div>
            <div style="flex:1;min-width:0">
                <h3 style="margin:0 0 4px;font-size:15px"><a href="<?php echo get_category_link( $term ); ?>" style="color:#333;text-decoration:none"><?php echo esc_html( $term->name ); ?></a></h3>
                <p style="color:#999;margin:0 0 4px;font-size:12px"><?php echo $chapter ? esc_html( $chapter->post_title ) : '—'; ?><?php if ( $page > 1 ) echo ' 第 ' . $page . ' 页'; ?></p>
                <p style="color:#bbb;margin:0 0 8px;font-size:11px"><?php echo $time ? esc_html( $time ) : ''; ?></p>
                <a href="<?php echo esc_url( $link ); ?>" style="display:inline-block;background:#12345a;color:#fff;padding:4px 12px;border-radius:3px;text-decoration:none;font-size:13px">继续阅读 →</a>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php get_footer(); ?>
