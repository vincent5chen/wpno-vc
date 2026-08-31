<?php
if ( post_password_required() ) return;
if ( have_comments() ) : ?>
<div class="comments-area">
    <h3><?php comments_number( '暂无评论', '1 条评论', '% 条评论' ); ?></h3>
    <ol class="comment-list"><?php wp_list_comments( [ 'style' => 'ol' ] ); ?></ol>
    <?php the_comments_navigation(); ?>
</div>
<?php endif;
if ( comments_open() ) comment_form();
