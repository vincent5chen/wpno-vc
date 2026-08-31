<?php
/**
 * 分类自定义字段 - 在分类编辑页添加小说信息表单
 *
 * @package wpno-vc
 */

add_action( 'category_edit_form_fields', 'wpnovc_category_edit_fields', 10, 1 );
function wpnovc_category_edit_fields( $term ) {
    $meta = wpnovc_get_novel_meta( $term->term_id );
    ?>
    <tr class="form-field">
        <th colspan="2"><h3>📖 小说信息</h3></th>
    </tr>
    <tr class="form-field">
        <th><label>作者</label></th>
        <td><input type="text" name="xs_author" value="<?php echo esc_attr( $meta['xs_author'] ); ?>" class="regular-text" /></td>
    </tr>
    <tr class="form-field">
        <th><label>封面图 URL</label></th>
        <td>
            <input type="text" name="xs_cover" value="<?php echo esc_url( $meta['xs_cover'] ); ?>" class="large-text" />
            <p class="description">输入封面图片的完整 URL 地址</p>
        </td>
    </tr>
    <tr class="form-field">
        <th><label>小说简介</label></th>
        <td>
            <textarea name="xs_description" rows="4" class="large-text"><?php echo esc_textarea( $meta['xs_description'] ); ?></textarea>
        </td>
    </tr>
    <tr class="form-field">
        <th><label>连载状态</label></th>
        <td>
            <select name="xs_status">
                <option value="serial" <?php selected( $meta['xs_status'], 'serial' ); ?>>连载中</option>
                <option value="complete" <?php selected( $meta['xs_status'], 'complete' ); ?>>已完结</option>
            </select>
        </td>
    </tr>
    <tr class="form-field">
        <th><label>付费模式</label></th>
        <td>
            <select name="pay_mode">
                <option value="default" <?php selected( $meta['pay_mode'], 'default' ); ?>>默认（全局设置）</option>
                <option value="free" <?php selected( $meta['pay_mode'], 'free' ); ?>>免费</option>
                <option value="category" <?php selected( $meta['pay_mode'], 'category' ); ?>>整本订阅</option>
                <option value="article" <?php selected( $meta['pay_mode'], 'article' ); ?>>单章订阅</option>
            </select>
        </td>
    </tr>
    <tr class="form-field">
        <th><label>单章价格（分）</label></th>
        <td><input type="number" name="article_price" value="<?php echo intval( $meta['article_price'] ); ?>" /> <span>1元=100分</span></td>
    </tr>
    <tr class="form-field">
        <th><label>整本价格（分）</label></th>
        <td><input type="number" name="category_price" value="<?php echo intval( $meta['category_price'] ); ?>" /> <span>1元=100分</span></td>
    </tr>
    <tr class="form-field">
        <th><label>免费章节数</label></th>
        <td><input type="number" name="free_chapter" value="<?php echo intval( $meta['free_chapter'] ); ?>" /></td>
    </tr>
    <tr class="form-field">
        <th colspan="2"><h3>🔍 SEO 优化</h3></th>
    </tr>
    <tr class="form-field">
        <th><label>SEO 标题</label></th>
        <td><input type="text" name="seo_title" value="<?php echo esc_attr( $meta['seo_title'] ); ?>" class="large-text" /></td>
    </tr>
    <tr class="form-field">
        <th><label>SEO 描述</label></th>
        <td><textarea name="seo_description" rows="3" class="large-text"><?php echo esc_textarea( $meta['seo_description'] ); ?></textarea></td>
    </tr>
    <tr class="form-field">
        <th><label>SEO 关键词</label></th>
        <td><input type="text" name="seo_keywords" value="<?php echo esc_attr( $meta['seo_keywords'] ); ?>" class="large-text" /></td>
    </tr>
    <?php
}

add_action( 'edited_term', 'wpnovc_save_category_fields', 10, 3 );
function wpnovc_save_category_fields( $term_id, $tt_id, $taxonomy ) {
    if ( $taxonomy !== 'category' ) return;
    $fields = [ 'xs_author', 'xs_cover', 'xs_description', 'editor_note', 'xs_status', 'pay_mode', 'article_price', 'category_price', 'free_chapter', 'seo_title', 'seo_description', 'seo_keywords' ];
    $data   = [];
    foreach ( $fields as $f ) {
        if ( isset( $_POST[ $f ] ) ) {
            $data[ $f ] = sanitize_text_field( $_POST[ $f ] );
        }
    }
    if ( ! empty( $data ) ) {
        wpnovc_update_novel_meta( $term_id, $data );
    }
}
