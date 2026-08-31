<?php get_header(); ?>
<div id="single-bar" class="row">
    <div class="container"><div class="col-xs-12"><?php wpnovc_breadcrumb(); ?></div></div>
</div>
<div class="container">
    <?php if ( ! is_user_logged_in() ) : ?>
        <p>请先<a href="<?php echo wp_login_url(); ?>">登录</a>后使用作家中心。</p>
    <?php else : 
        $user_id = get_current_user_id();
        $novels  = wpnovc_get_author_novels( $user_id );
    ?>
        <h2>作家中心</h2>
        <div class="writer-tools">
            <p>发布新章节：在 WordPress 后台 <a href="<?php echo admin_url( 'post-new.php' ); ?>">添加文章</a>，选择对应的小说分类即可。</p>
        </div>
        <h3>我的小说</h3>
        <?php if ( empty( $novels ) ) : ?>
            <p>还没有小说，请在后台创建分类（小说）。</p>
        <?php else : ?>
            <table class="widefat striped">
                <thead><tr><th>小说名</th><th>章节数</th><th>状态</th><th>操作</th></tr></thead>
                <tbody>
                <?php foreach ( $novels as $novel ) : $meta = wpnovc_get_novel_meta( $novel->term_id ); ?>
                <tr>
                    <td><a href="<?php echo get_category_link( $novel ); ?>"><?php echo $novel->name; ?></a></td>
                    <td><?php echo $novel->count; ?></td>
                    <td><?php echo $meta['xs_status'] == 'complete' ? '已完结' : '连载中'; ?></td>
                    <td><a href="<?php echo admin_url( 'term.php?taxonomy=category&tag_ID=' . $novel->term_id ); ?>">编辑</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php get_footer(); ?>
