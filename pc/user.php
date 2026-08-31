<?php get_header(); 
$uid = intval( $_GET['uid'] ?? get_current_user_id() );
$user = get_userdata( $uid );
?>
<div id="single-bar" class="row">
    <div class="container"><div class="col-xs-12"><?php wpnovc_breadcrumb(); ?></div></div>
</div>
<div class="container">
    <?php if ( ! $user ) : ?>
        <p>用户不存在。</p>
    <?php else : ?>
        <div class="user-profile">
            <div class="avatar"><?php echo get_avatar( $uid, 96 ); ?></div>
            <h2><?php echo $user->display_name; ?></h2>
            <?php if ( $uid == get_current_user_id() ) : ?>
                <p>余额: <?php echo number_format( wpnovc_get_balance( $uid ) / 100, 2 ); ?> 元</p>
            <?php endif; ?>
        </div>
        <?php $novels = wpnovc_get_author_novels( $uid ); ?>
        <?php if ( ! empty( $novels ) ) : ?>
        <h3>TA的作品</h3>
        <div class="row">
            <?php foreach ( $novels as $novel ) : $meta = wpnovc_get_novel_meta( $novel->term_id ); ?>
            <div class="col-xs-3"><div class="novel-card">
                <div class="cover"><a href="<?php echo get_category_link( $novel ); ?>"><?php echo wpnovc_novel_cover( $meta, $novel->name ); ?></a></div>
                <h3><a href="<?php echo get_category_link( $novel ); ?>"><?php echo $novel->name; ?></a></h3>
            </div></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php get_footer(); ?>
