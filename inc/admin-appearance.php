<?php
/**
 * 外观设置详细实现 - PC端设置 & 移动端设置
 * wpnovo-dist 中 PC端/移动端各有 ui/home/read/ads 子分组
 *
 * @package wpno-vc
 */

// ── PC端设置 (ui + home + read + ads) ──
function wpnovc_appearance_pc() {
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer( 'wpnovc_settings' ) ) {
        $keys = [ 'pc_logo', 'pc_logo_path', 'pc_logo_id', 'pc_body_bg', 'pc_menu_bg', 'pc_menu_fg',
                  'pc_menu_hover_bg', 'pc_menu_hover_border', 'pc_menu_a_hover', 'pc_a_special',
                  'custom_pc_style', 'pc_single_fontsize', 'new_books_title', 'new_books',
                  'pc_home_category', 'pc_home_category_perpage', 'pc_home_allnovels', 'pc_home_an_num',
                  'ad_below_description', 'ad_single', 'ad_single_position', 'ad_float' ];
        foreach ( $keys as $k ) {
            $html_fields = [ 'custom_pc_style', 'ad_below_description', 'ad_single', 'ad_float' ];
            if ( in_array( $k, $html_fields ) ) {
                wpnovc_update_option( $k, wp_unslash( $_POST[ $k ] ?? '' ) );
            } else {
                wpnovc_update_option( $k, sanitize_text_field( $_POST[ $k ] ?? '' ) );
            }
        }
        echo '<div class="notice notice-success"><p>PC端设置已保存</p></div>';
    }
    ?>
    <form method="post">
    <?php wp_nonce_field( 'wpnovc_settings' ); ?>

    <!-- UI: 外观设置 -->
    <h3>PC端外观设置</h3>
    <table class="form-table">
        <tr><th>Logo URL</th><td><input type="text" name="pc_logo" value="<?php echo esc_attr( wpnovc_option('pc_logo') ); ?>" class="regular-text" /></td></tr>
        <tr><th>网页背景色</th><td><input type="color" name="pc_body_bg" value="<?php echo esc_attr( wpnovc_option('pc_body_bg','#ffffff') ); ?>" /> PC端(除阅读页外)页面背景色</td></tr>
        <tr><th>导航栏背景色</th><td><input type="color" name="pc_menu_bg" value="<?php echo esc_attr( wpnovc_option('pc_menu_bg','') ); ?>" /></td></tr>
        <tr><th>导航栏文字颜色</th><td><input type="color" name="pc_menu_fg" value="<?php echo esc_attr( wpnovc_option('pc_menu_fg','') ); ?>" /></td></tr>
        <tr><th>导航栏悬停背景色</th><td><input type="color" name="pc_menu_hover_bg" value="<?php echo esc_attr( wpnovc_option('pc_menu_hover_bg','') ); ?>" /></td></tr>
        <tr><th>导航栏悬停下划线色</th><td><input type="color" name="pc_menu_hover_border" value="<?php echo esc_attr( wpnovc_option('pc_menu_hover_border','') ); ?>" /></td></tr>
        <tr><th>菜单链接悬停颜色</th><td><input type="color" name="pc_menu_a_hover" value="<?php echo esc_attr( wpnovc_option('pc_menu_a_hover','') ); ?>" /></td></tr>
        <tr><th>重点链接颜色</th><td><input type="color" name="pc_a_special" value="<?php echo esc_attr( wpnovc_option('pc_a_special','') ); ?>" /></td></tr>
        <tr><th>自定义CSS</th><td><textarea name="custom_pc_style" rows="4" class="large-text"><?php echo esc_textarea( wpnovc_option('custom_pc_style') ); ?></textarea></td></tr>
    </table>

    <!-- HOME: 首页数据 -->
    <h3>PC端首页数据</h3>
    <table class="form-table">
        <tr><th>推荐区块标题</th><td><input type="text" name="new_books_title" value="<?php echo esc_attr( wpnovc_option('new_books_title','推荐小说') ); ?>" class="regular-text" /></td></tr>
        <tr><th>推荐小说ID</th><td><input type="text" name="new_books" value="<?php echo esc_attr( wpnovc_option('new_books') ); ?>" class="large-text" /><br>用英文逗号隔开的小说分类ID</td></tr>
        <tr><th>分类展示一级分类</th><td><input type="text" name="pc_home_category" value="<?php echo esc_attr( wpnovc_option('pc_home_category') ); ?>" class="large-text" /><br>填写一级分类ID，逗号分隔</td></tr>
        <tr><th>每分类展示数量</th><td><input type="number" name="pc_home_category_perpage" value="<?php echo esc_attr( wpnovc_option('pc_home_category_perpage','12') ); ?>" /></td></tr>
        <tr><th>横版分类展示</th><td><input type="text" name="pc_home_allnovels" value="<?php echo esc_attr( wpnovc_option('pc_home_allnovels') ); ?>" class="large-text" /><br>一级分类ID，逗号分隔</td></tr>
        <tr><th>横版每分类小说数</th><td><input type="number" name="pc_home_an_num" value="<?php echo esc_attr( wpnovc_option('pc_home_an_num','100') ); ?>" /></td></tr>
    </table>

    <!-- READ: 阅读优化 -->
    <h3>PC端阅读优化</h3>
    <table class="form-table">
        <tr><th>默认文字大小</th><td><select name="pc_single_fontsize">
            <?php for($i=10;$i<70;$i++) echo "<option value='{$i}px' ".selected(wpnovc_option('pc_single_fontsize','18px'), "{$i}px", false).">{$i}px</option>"; ?>
        </select></td></tr>
    </table>

    <!-- ADS: 广告位 -->
    <h3>PC端广告位</h3>
    <table class="form-table">
        <tr><th>简介区域下方</th><td><textarea name="ad_below_description" rows="2" class="large-text"><?php echo esc_textarea( wpnovc_option('ad_below_description') ); ?></textarea></td></tr>
        <tr><th>文章页广告</th><td><textarea name="ad_single" rows="3" class="large-text"><?php echo esc_textarea( wpnovc_option('ad_single') ); ?></textarea></td></tr>
        <tr><th>文章广告位置</th><td><select name="ad_single_position">
            <option value="top" <?php selected(wpnovc_option('ad_single_position'),'top'); ?>>标题下方</option>
            <option value="afterp1" <?php selected(wpnovc_option('ad_single_position'),'afterp1'); ?>>第一段落后</option>
            <option value="afterend" <?php selected(wpnovc_option('ad_single_position'),'afterend'); ?>>文章末尾</option>
            <option value="afterrand" <?php selected(wpnovc_option('ad_single_position'),'afterrand'); ?>>随机段落后</option>
        </select></td></tr>
        <tr><th>全局浮窗广告</th><td><textarea name="ad_float" rows="3" class="large-text"><?php echo esc_textarea( wpnovc_option('ad_float') ); ?></textarea></td></tr>
    </table>

    <?php submit_button( '保存PC端设置' ); ?>
    </form>
    <?php
}

// ── 移动端设置 (ui + home + read + ads + shortcuts) ──
function wpnovc_appearance_mobile() {
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer( 'wpnovc_settings' ) ) {
        $keys = [ 'wise_logo', 'wise_logo_path', 'wise_logo_id', 'wise_main_color',
                  'custom_mobile_style', 'wise_single_fontsize', 'click_turning', 'wise_totop',
                  'wise_recommend_title', 'wise_recommend', 'wise_home_category',
                  'wise_home_category_perpage', 'wise_home_index', 'wise_home_index_num',
                  'mobile_shortcuts_1_name', 'mobile_shortcuts_1_link', 'mobile_shortcuts_1_icon',
                  'mobile_shortcuts_2_name', 'mobile_shortcuts_2_link', 'mobile_shortcuts_2_icon',
                  'mobile_shortcuts_3_name', 'mobile_shortcuts_3_link', 'mobile_shortcuts_3_icon',
                  'wise_ad_below_menu', 'wise_ad_above_description', 'wise_ad_below_description',
                  'wise_ad_single', 'wise_ad_single_position', 'wise_ad_below_top_pagenavi',
                  'wise_ad_below_pagenavi', 'wise_ad_float' ];
        foreach ( $keys as $k ) {
            $html_fields = [ 'custom_mobile_style', 'wise_ad_below_menu', 'wise_ad_above_description', 'wise_ad_below_description', 'wise_ad_single', 'wise_ad_below_top_pagenavi', 'wise_ad_below_pagenavi', 'wise_ad_float' ];
            if ( in_array( $k, $html_fields ) ) {
                wpnovc_update_option( $k, wp_unslash( $_POST[ $k ] ?? '' ) );
            } else {
                wpnovc_update_option( $k, sanitize_text_field( $_POST[ $k ] ?? '' ) );
            }
        }
        echo '<div class="notice notice-success"><p>移动端设置已保存</p></div>';
    }
    ?>
    <form method="post">
    <?php wp_nonce_field( 'wpnovc_settings' ); ?>

    <!-- UI -->
    <h3>移动端外观设置</h3>
    <table class="form-table">
        <tr><th>Logo URL</th><td><input type="text" name="wise_logo" value="<?php echo esc_attr(wpnovc_option('wise_logo')); ?>" class="regular-text" /></td></tr>
        <tr><th>主色调</th><td><input type="color" name="wise_main_color" value="<?php echo esc_attr(wpnovc_option('wise_main_color','#e74c3c')); ?>" /></td></tr>
        <tr><th>自定义CSS</th><td><textarea name="custom_mobile_style" rows="4" class="large-text"><?php echo esc_textarea(wpnovc_option('custom_mobile_style')); ?></textarea></td></tr>
    </table>

    <!-- HOME: 首页数据 & 快捷入口 -->
    <h3>移动端首页数据</h3>
    <table class="form-table">
        <tr><th>推荐区块标题</th><td><input type="text" name="wise_recommend_title" value="<?php echo esc_attr(wpnovc_option('wise_recommend_title','推荐')); ?>" /></td></tr>
        <tr><th>推荐小说ID</th><td><input type="text" name="wise_recommend" value="<?php echo esc_attr(wpnovc_option('wise_recommend')); ?>" class="large-text" /><br>逗号分隔</td></tr>
        <tr><th>分类展示ID</th><td><input type="text" name="wise_home_category" value="<?php echo esc_attr(wpnovc_option('wise_home_category')); ?>" class="large-text" /><br>6个一级分类ID，逗号分隔</td></tr>
        <tr><th>每分类展示数</th><td><input type="number" name="wise_home_category_perpage" value="<?php echo esc_attr(wpnovc_option('wise_home_category_perpage','6')); ?>" /></td></tr>
        <tr><th>首页列表数量</th><td><input type="number" name="wise_home_index_num" value="<?php echo esc_attr(wpnovc_option('wise_home_index_num','20')); ?>" /></td></tr>
    </table>

    <h4>快捷入口</h4>
    <table class="form-table">
        <tr><th>快捷入口1-名称</th><td><input type="text" name="mobile_shortcuts_1_name" value="<?php echo esc_attr(wpnovc_option('mobile_shortcuts_1_name','书架')); ?>" /></td></tr>
        <tr><th>快捷入口1-链接</th><td><input type="text" name="mobile_shortcuts_1_link" value="<?php echo esc_attr(wpnovc_option('mobile_shortcuts_1_link','/bookshelf')); ?>" /></td></tr>
        <tr><th>快捷入口1-图标</th><td><input type="text" name="mobile_shortcuts_1_icon" value="<?php echo esc_attr(wpnovc_option('mobile_shortcuts_1_icon','fa-book')); ?>" /></td></tr>
        <tr><th>快捷入口2-名称</th><td><input type="text" name="mobile_shortcuts_2_name" value="<?php echo esc_attr(wpnovc_option('mobile_shortcuts_2_name','分类')); ?>" /></td></tr>
        <tr><th>快捷入口2-链接</th><td><input type="text" name="mobile_shortcuts_2_link" value="<?php echo esc_attr(wpnovc_option('mobile_shortcuts_2_link','/noveltype')); ?>" /></td></tr>
        <tr><th>快捷入口2-图标</th><td><input type="text" name="mobile_shortcuts_2_icon" value="<?php echo esc_attr(wpnovc_option('mobile_shortcuts_2_icon','fa-list')); ?>" /></td></tr>
        <tr><th>快捷入口3-名称</th><td><input type="text" name="mobile_shortcuts_3_name" value="<?php echo esc_attr(wpnovc_option('mobile_shortcuts_3_name','热门')); ?>" /></td></tr>
        <tr><th>快捷入口3-链接</th><td><input type="text" name="mobile_shortcuts_3_link" value="<?php echo esc_attr(wpnovc_option('mobile_shortcuts_3_link','/hot')); ?>" /></td></tr>
        <tr><th>快捷入口3-图标</th><td><input type="text" name="mobile_shortcuts_3_icon" value="<?php echo esc_attr(wpnovc_option('mobile_shortcuts_3_icon','fa-fire-alt')); ?>" /></td></tr>
    </table>

    <!-- READ: 体验优化 -->
    <h3>移动端阅读优化</h3>
    <table class="form-table">
        <tr><th>默认文字大小</th><td><select name="wise_single_fontsize">
            <?php for($i=10;$i<70;$i++) echo "<option value='{$i}px' ".selected(wpnovc_option('wise_single_fontsize','16px'), "{$i}px", false).">{$i}px</option>"; ?>
        </select></td></tr>
        <tr><th>点击翻页</th><td><label><input type="radio" name="click_turning" value="1" <?php checked(wpnovc_option('click_turning'),1); ?> /> 开启</label> <label><input type="radio" name="click_turning" value="0" <?php checked(wpnovc_option('click_turning'),0); ?> /> 关闭</label></td></tr>
        <tr><th>返回顶部/底部</th><td><label><input type="radio" name="wise_totop" value="1" <?php checked(wpnovc_option('wise_totop',1),1); ?> /> 显示</label> <label><input type="radio" name="wise_totop" value="0" <?php checked(wpnovc_option('wise_totop',1),0); ?> /> 隐藏</label></td></tr>
    </table>

    <!-- ADS: 广告位 -->
    <h3>移动端广告位</h3>
    <table class="form-table">
        <tr><th>导航栏下方</th><td><textarea name="wise_ad_below_menu" rows="2" class="large-text"><?php echo esc_textarea(wpnovc_option('wise_ad_below_menu')); ?></textarea></td></tr>
        <tr><th>简介上方</th><td><textarea name="wise_ad_above_description" rows="2" class="large-text"><?php echo esc_textarea(wpnovc_option('wise_ad_above_description')); ?></textarea></td></tr>
        <tr><th>简介下方</th><td><textarea name="wise_ad_below_description" rows="2" class="large-text"><?php echo esc_textarea(wpnovc_option('wise_ad_below_description')); ?></textarea></td></tr>
        <tr><th>文章页广告</th><td><textarea name="wise_ad_single" rows="3" class="large-text"><?php echo esc_textarea(wpnovc_option('wise_ad_single')); ?></textarea></td></tr>
        <tr><th>文章广告位置</th><td><select name="wise_ad_single_position">
            <option value="top" <?php selected(wpnovc_option('wise_ad_single_position'),'top'); ?>>标题下方</option>
            <option value="afterp1" <?php selected(wpnovc_option('wise_ad_single_position'),'afterp1'); ?>>第一段落后</option>
            <option value="afterend" <?php selected(wpnovc_option('wise_ad_single_position'),'afterend'); ?>>文章末尾</option>
            <option value="afterrand" <?php selected(wpnovc_option('wise_ad_single_position'),'afterrand'); ?>>随机段落后</option>
        </select></td></tr>
        <tr><th>章节顶部翻页下方</th><td><textarea name="wise_ad_below_top_pagenavi" rows="2" class="large-text"><?php echo esc_textarea(wpnovc_option('wise_ad_below_top_pagenavi')); ?></textarea></td></tr>
        <tr><th>章节底部翻页下方</th><td><textarea name="wise_ad_below_pagenavi" rows="2" class="large-text"><?php echo esc_textarea(wpnovc_option('wise_ad_below_pagenavi')); ?></textarea></td></tr>
        <tr><th>全局浮窗广告</th><td><textarea name="wise_ad_float" rows="3" class="large-text"><?php echo esc_textarea(wpnovc_option('wise_ad_float')); ?></textarea></td></tr>
    </table>

    <?php submit_button( '保存移动端设置' ); ?>
    </form>
    <?php
}
