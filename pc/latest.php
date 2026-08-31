<?php
/**
 * 最新小说页 - PC
 */
get_header();
?>
<style>
.editor-picks-cover img,.latest-cover img{width:100%!important;height:auto!important;max-width:100%!important;max-height:none!important;display:block}
</style>
<?php

// 获取最新 20 个二级分类（小说）
$all_children = [];
$top_cats = get_terms(['taxonomy' => 'category', 'parent' => 0, 'hide_empty' => false]);
foreach ($top_cats as $top) {
    $children = get_terms(['taxonomy' => 'category', 'parent' => $top->term_id, 'hide_empty' => false, 'number' => 20]);
    foreach ($children as $c) {
        $c->top_parent = $top;
        $all_children[] = $c;
    }
}
// 按 term_id 倒序（最新创建的在前）
usort($all_children, function($a, $b) { return $b->term_id - $a->term_id; });
$novels = array_slice($all_children, 0, 20);
?>

<div class="container" style="margin-top:20px">
    <h2 style="border-left:4px solid #12345a;padding-left:12px;margin-bottom:20px">最新小说</h2>
    <div class="row">
        <?php foreach ($novels as $novel):
            $meta = wpnovc_get_novel_meta($novel->term_id);
        ?>
        <div class="col-xs-3" style="margin-bottom:20px">
            <div style="background:#fff;border-radius:6px;box-shadow:0 1px 6px rgba(0,0,0,.06);text-align:center;padding:16px 12px 12px;height:100%">
                <a href="<?php echo get_category_link($novel); ?>" style="display:block;width:140px;height:187px;margin:0 auto 10px;overflow:hidden;border-radius:4px" class="latest-cover">
                    <?php echo wpnovc_novel_cover($meta, $novel->name); ?>
                </a>
                <a href="<?php echo get_category_link($novel); ?>" style="font-size:14px;font-weight:600;color:#333;text-decoration:none;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo esc_html($novel->name); ?></a>
                <span style="font-size:12px;color:#999"><?php echo esc_html($meta['xs_author'] ?? ''); ?>&nbsp;</span>
                <span style="display:block;font-size:11px;color:#bbb;margin-top:2px"><?php echo esc_html($novel->top_parent->name ?? ''); ?>&nbsp;</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php get_footer(); ?>
