<?php
/**
 * 编辑推荐页 - PC
 * 支持期次入口和期次详情
 */
get_header();

$page_type = get_query_var( 'wpnovc_page' );
$issue_slug = sanitize_title( get_query_var( 'wpnovc_issue_slug' ) );
?>
<style>
.editor-picks-cover img{width:100%!important;height:auto!important;max-width:100%!important;max-height:none!important;display:block}
.pick-issue-banner{position:relative;display:block;border-radius:8px;overflow:hidden;background:#eee;margin-bottom:18px;text-decoration:none}
.pick-issue-banner img{width:100%;height:280px;object-fit:cover;display:block}
.pick-issue-banner .pick-issue-meta{position:absolute;left:0;right:0;bottom:0;padding:14px 18px;background:linear-gradient(transparent,rgba(0,0,0,.72));color:#fff}
.pick-issue-banner .pick-issue-title{font-size:22px;font-weight:700;margin:0 0 4px}
.pick-issue-banner .pick-issue-date{font-size:13px;color:#d7dde5}
.pick-detail-banner{border-radius:8px;overflow:hidden;margin-bottom:24px;background:#eee}
.pick-detail-banner img{width:100%;height:320px;object-fit:cover;display:block}
.pick-novel-item{display:flex;gap:20px;background:#fff;border-radius:8px;padding:20px;margin-bottom:16px;box-shadow:0 1px 6px rgba(0,0,0,.06)}
.pick-novel-item .pick-novel-cover{width:120px;flex-shrink:0;border-radius:4px;overflow:hidden}
.pick-pagination{text-align:center;margin:24px 0}
.pick-pagination a,.pick-pagination span{display:inline-block;min-width:34px;padding:6px 10px;margin:0 4px;border:1px solid #ddd;border-radius:4px;color:#333;text-decoration:none}
.pick-pagination span.current{background:#12345a;color:#fff;border-color:#12345a}
</style>

<?php if ( $page_type === 'editor_pick_detail' && $issue_slug ): ?>
    <?php
    $issue = wpnovc_get_editor_pick_by_slug( $issue_slug );
    if ( ! $issue ) {
        echo '<div class="container" style="margin-top:20px"><p style="color:#999;text-align:center;padding:60px 0">期次不存在</p></div>';
        get_footer();
        exit;
    }
    $issue_id = $issue->ID;
    $novels = wpnovc_get_editor_pick_novels( $issue_id );
    $banner = wpnovc_get_editor_pick_banner( $issue_id, 'pc' );
    ?>
    <div class="container" style="margin-top:20px">
        <nav style="font-size:13px;color:#777;margin-bottom:14px"><a href="<?php echo home_url( '/' ); ?>" style="color:#777;text-decoration:none">首页</a> / <a href="<?php echo home_url( '/editor-picks/' ); ?>" style="color:#777;text-decoration:none">编辑推荐</a> / <span style="color:#333"><?php echo esc_html( $issue->post_title ); ?></span></nav>
        <div class="pick-detail-banner">
            <?php if ( $banner ): ?>
                <img src="<?php echo esc_url( $banner ); ?>" alt="<?php echo esc_attr( $issue->post_title ); ?>">
            <?php else: ?>
                <div style="height:320px;background:linear-gradient(135deg,#12345a,#274b6d);display:flex;align-items:center;justify-content:center;color:#fff;font-size:28px;font-weight:700"><?php echo esc_html( $issue->post_title ); ?></div>
            <?php endif; ?>
        </div>
        <h1 style="border-left:4px solid #12345a;padding-left:12px;margin:0 0 20px"><?php echo esc_html( $issue->post_title ); ?></h1>
        <?php if ( empty( $novels ) ): ?>
            <p style="color:#999;text-align:center;padding:60px 0">本期暂无推荐小说</p>
        <?php else: ?>
            <?php foreach ( $novels as $i => $id ):
                $term = get_term( $id, 'category' );
                if ( ! $term || is_wp_error( $term ) ) continue;
                $meta = wpnovc_get_novel_meta( $id );
            ?>
            <div class="pick-novel-item">
                <div class="pick-novel-cover editor-picks-cover">
                    <a href="<?php echo get_category_link( $term ); ?>"><?php echo wpnovc_novel_cover( $meta, $term->name ); ?></a>
                </div>
                <div style="flex:1;min-width:0">
                    <span style="display:inline-block;background:#12345a;color:#fff;font-size:12px;padding:2px 10px;border-radius:3px;margin-bottom:8px">推荐 #<?php echo $i + 1; ?></span>
                    <h3 style="margin:0 0 6px;font-size:18px"><a href="<?php echo get_category_link( $term ); ?>" style="color:#333;text-decoration:none"><?php echo esc_html( $term->name ); ?></a></h3>
                    <p style="color:#999;font-size:13px;margin:0 0 10px">作者：<?php echo esc_html( $meta['xs_author'] ?? '佚名' ); ?> | <?php echo esc_html( $meta['xs_status'] === 'complete' ? '已完结' : '连载中' ); ?></p>
                    <p style="color:#555;font-size:14px;line-height:1.8;margin:0"><?php echo esc_html( $meta['brief_rec'] ?? ( $meta['editor_note'] ? wpnovc_strimwidth( $meta['editor_note'], 200 ) : '暂无推荐语' ) ); ?></p>
                    <a href="<?php echo get_category_link( $term ); ?>" style="display:inline-block;margin-top:10px;color:#12345a;text-decoration:none;font-size:14px;font-weight:600">开始阅读 →</a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php else: ?>
    <?php
    $paged  = max( 1, intval( get_query_var( 'paged' ) ?: 1 ) );
    $issues = wpnovc_get_editor_pick_issues( $paged, 5 );
    $total  = wpnovc_count_editor_pick_issues();
    $pages  = max( 1, ceil( $total / 5 ) );
    ?>
    <div class="container" style="margin-top:20px">
        <h1 style="border-left:4px solid #12345a;padding-left:12px;margin:0 0 20px">编辑推荐</h1>
        <?php if ( empty( $issues ) ): ?>
            <p style="color:#999;text-align:center;padding:60px 0">暂无推荐期次</p>
        <?php else: ?>
            <?php foreach ( $issues as $issue ): ?>
                <a class="pick-issue-banner" href="<?php echo wpnovc_get_editor_pick_url( $issue->ID ); ?>">
                    <?php
                    $banner = wpnovc_get_editor_pick_banner( $issue->ID, 'pc' );
                    if ( $banner ) {
                        echo '<img src="' . esc_url( $banner ) . '" alt="' . esc_attr( $issue->post_title ) . '">';
                    } else {
                        echo '<div style="height:280px;background:linear-gradient(135deg,#12345a,#274b6d)"></div>';
                    }
                    ?>
                    <span class="pick-issue-meta">
                        <span class="pick-issue-title"><?php echo esc_html( $issue->post_title ); ?></span>
                        <span class="pick-issue-date"><?php echo get_the_date( 'Y-m-d', $issue ); ?></span>
                    </span>
                </a>
                <?php
                // 每期文字摘要（A 优化）：优先用后台「摘录」字段（整期介绍），为空则自动生成整期书单摘要（列书名）
                $issue_ex = $issue->post_excerpt;
                if ( ! $issue_ex ) {
                    $ids    = function_exists( 'wpnovc_get_editor_pick_novels' ) ? wpnovc_get_editor_pick_novels( $issue->ID ) : array();
                    $names  = array();
                    foreach ( $ids as $bid ) {
                        $t = get_term( $bid, 'category' );
                        if ( $t && ! is_wp_error( $t ) ) {
                            $names[] = $t->name;
                        }
                    }
                    if ( $names ) {
                        $shown = implode( '、', array_slice( $names, 0, 6 ) );
                        $issue_ex = '本期编辑推荐 ' . count( $names ) . ' 部小说：' . $shown . ( count( $names ) > 6 ? ' 等' : '' ) . '，全站免费在线阅读。';
                    }
                }
                if ( $issue_ex ) : ?>
                <div style="margin:-4px 0 18px;padding:12px 16px;background:#f7f8fa;border-radius:6px;font-size:14px;color:#555;line-height:1.8">
                    <a href="<?php echo wpnovc_get_editor_pick_url( $issue->ID ); ?>" style="color:#444;text-decoration:none">
                        <?php echo esc_html( wpnovc_strimwidth( wp_strip_all_tags( $issue_ex ), 120, '…' ) ); ?>
                        <span style="color:#12345a;font-weight:600">查看本期 →</span>
                    </a>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
            <div class="pick-pagination">
                <?php for ( $i = 1; $i <= $pages; $i++ ): ?>
                    <?php if ( $i === $paged ): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="<?php echo home_url( '/editor-picks/page/' . $i . '/' ); ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php get_footer(); ?>
