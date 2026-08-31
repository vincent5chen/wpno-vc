<?php
/**
 * 最新小说页 - Mobile
 */
get_header();
?>
<style>
.editor-picks-cover img,.latest-cover img{width:100%!important;height:auto!important;max-width:100%!important;max-height:none!important;display:block}
</style>
<?php

$all_children = [];
$top_cats = get_terms(['taxonomy' => 'category', 'parent' => 0, 'hide_empty' => false]);
foreach ($top_cats as $top) {
    $children = get_terms(['taxonomy' => 'category', 'parent' => $top->term_id, 'hide_empty' => false, 'number' => 20]);
    foreach ($children as $c) {
        $c->top_parent = $top;
        $all_children[] = $c;
    }
}
usort($all_children, function($a, $b) { return $b->term_id - $a->term_id; });
$novels = array_slice($all_children, 0, 20);
?>

<div style="padding:16px 16px 0">
    <h2 style="font-size:18px;border-left:4px solid #12345a;padding-left:10px;margin-bottom:16px">最新小说</h2>
    <div style="display:flex;flex-wrap:wrap;gap:10px">
        <?php foreach ($novels as $novel):
            $meta = wpnovc_get_novel_meta($novel->term_id);
        ?>
        <div style="width:calc(50% - 5px);background:#fff;border-radius:6px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.06);text-align:center;margin-bottom:4px">
            <a href="<?php echo get_category_link($novel); ?>" style="display:block;aspect-ratio:3/4;overflow:hidden" class="latest-cover">
                <?php echo wpnovc_novel_cover($meta, $novel->name); ?>
            </a>
            <div style="padding:6px">
                <a href="<?php echo get_category_link($novel); ?>" style="font-size:13px;font-weight:600;color:#333;text-decoration:none;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo esc_html($novel->name); ?></a>
                <span style="font-size:11px;color:#999"><?php echo esc_html($meta['xs_author'] ?? ''); ?>&nbsp;</span>
                <span style="display:block;font-size:10px;color:#bbb"><?php echo esc_html($novel->top_parent->name ?? ''); ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php get_footer(); ?>
