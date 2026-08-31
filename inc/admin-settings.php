<?php
require_once __DIR__ . "/admin-shop.php";
require_once __DIR__ . "/admin-appearance.php";

/**
 * wpno-vc 后台管理
 */

// ── 菜单注册 ──
add_action( 'admin_menu', 'wpnovc_admin_menu' );
function wpnovc_admin_menu() {
    add_menu_page( 'WPNOVC设置', 'WPNOVC', 'edit_theme_options', 'wpnovo', '__return_empty_string', 'dashicons-book', 2 );
    add_submenu_page( 'wpnovo', '外观设置', '外观设置', 'edit_theme_options', 'wpnovo', 'wpnovc_page_appearance' );
    add_submenu_page( 'wpnovo', '付费设置', '付费设置', 'edit_theme_options', 'wpnovo_pay', 'wpnovc_page_pay_settings' );
    add_submenu_page( 'wpnovo', '订单列表', '订单列表', 'edit_theme_options', 'wpnovo_order', 'wpnovc_page_orders' );
    add_submenu_page( 'wpnovo', '小说导入', '小说导入', 'edit_theme_options', 'wpnovo_novel_importer', 'wpnovc_page_import' );
    add_submenu_page( 'wpnovo', '批量导入', '批量导入', 'edit_theme_options', 'wpnovo_batch_importer', 'wpnovc_page_batch_import' );
    add_submenu_page( 'wpnovo', '数据库管理', '数据库管理', 'edit_theme_options', 'wpnovo_table', 'wpnovc_page_tables' );
    add_submenu_page( 'wpnovo', '教程', '教程', 'edit_theme_options', 'wpnovo_help', 'wpnovc_page_help' );
    add_submenu_page( 'wpnovo', '编辑导语', '编辑导语', 'edit_theme_options', 'wpnovo_editor', 'wpnovc_page_editor_notes' );
    add_submenu_page( 'wpnovo', '封面生成', '封面生成', 'edit_theme_options', 'wpnovo_cover', 'wpnovc_page_cover' );
    add_submenu_page( 'wpnovo', '首页轮播', '首页轮播', 'edit_theme_options', 'wpnovo_banner', 'wpnovc_page_home_banners' );
    add_submenu_page( 'wpnovo', 'AI 图片设置', 'AI 图片设置', 'edit_theme_options', 'wpnovo_ai_image', 'wpnovc_page_ai_image' );
    add_submenu_page( 'wpnovo', '编辑推荐', '编辑推荐', 'edit_theme_options', 'wpnovo_picks', 'wpnovc_page_editor_picks' );
    add_submenu_page( 'wpnovo', 'AI设置', 'AI设置', 'edit_theme_options', 'wpnovo_ai', 'wpnovc_page_ai' );
    add_menu_page( '商城系统', '商城系统', 'manage_options', 'wptrade', 'wpnovc_shop_page', 'dashicons-cart', 3 );
    add_submenu_page( 'wptrade', '统计分析', '统计分析', 'manage_options', 'wpnovo_analysis', 'wpnovc_page_analysis' );
    add_menu_page( '会员中心', '会员中心', 'read', 'wpnovc_vip', 'wpnovc_page_vip', 'dashicons-businessman', 4 );
    add_submenu_page( 'tools.php', 'WPNOVC工具', 'WPNOVC工具', 'manage_options', 'imwpweb', 'wpnovc_page_tools' );
}

// ── Tab 辅助 ──
function wpnovc_tab_container( $tabs, $active = '' ) {
    if ( ! $active ) $active = key( $tabs );
    echo '<div class="wrap"><nav class="nav-tab-wrapper">';
    foreach ( $tabs as $key => $label ) {
        $class = ( $key === $active ) ? 'nav-tab nav-tab-active' : 'nav-tab';
        echo '<a href="' . esc_url( add_query_arg( '__tab', $key ) ) . '" class="' . $class . '">' . $label . '</a>';
    }
    echo '</nav><div class="wpnovc-tab-content" style="margin-top:15px;background:#fff;padding:15px;border:1px solid #ccd0d4;border-top:none">';
}
function wpnovc_tab_end() { echo '</div></div>'; }
function wpnovc_form_open() { echo '<form method="post">'; wp_nonce_field( 'wpnovc_settings' ); }
function wpnovc_text_field( $key, $label, $value, $desc = '' ) {
    echo '<p><label><strong>' . $label . '</strong><br><input type="text" name="' . $key . '" value="' . esc_attr( $value ) . '" class="regular-text" />';
    if ( $desc ) echo '<br><span class="description">' . $desc . '</span>';
    echo '</label></p>';
}
function wpnovc_textarea_field( $key, $label, $value, $desc = '' ) {
    echo '<p><label><strong>' . $label . '</strong><br><textarea name="' . $key . '" rows="4" class="large-text">' . esc_textarea( $value ) . '</textarea>';
    if ( $desc ) echo '<br><span class="description">' . $desc . '</span>';
    echo '</label></p>';
}
function wpnovc_toggle_field( $key, $label, $value, $desc = '' ) {
    echo '<p><label><strong>' . $label . '</strong><br>';
    echo '<label><input type="radio" name="' . $key . '" value="1" ' . checked( $value, 1, false ) . ' /> 开启</label> &nbsp; ';
    echo '<label><input type="radio" name="' . $key . '" value="0" ' . checked( $value, 0, false ) . ' /> 关闭</label>';
    if ( $desc ) echo '<br><span class="description">' . $desc . '</span>';
    echo '</label></p>';
}
function wpnovc_select_field( $key, $label, $options, $value, $desc = '' ) {
    echo '<p><label><strong>' . $label . '</strong><br><select name="' . $key . '">';
    foreach ( $options as $k => $v ) echo '<option value="' . $k . '" ' . selected( $value, $k, false ) . '>' . $v . '</option>';
    echo '</select>';
    if ( $desc ) echo '<br><span class="description">' . $desc . '</span>';
    echo '</label></p>';
}

// ── 外观设置 ──
function wpnovc_page_appearance() {
    $tabs = ['global'=>'全局设置','pc'=>'PC端设置','mobile'=>'移动端设置','seo'=>'SEO设置','reading'=>'阅读优化','footer'=>'页脚设置'];
    $active = $_GET['__tab'] ?? 'global';
    wpnovc_tab_container( $tabs, $active );

    // PC/Mobile tabs handle their own forms — don't wrap them
    if ( $active === 'pc' ) { wpnovc_appearance_pc(); wpnovc_tab_end(); return; }
    if ( $active === 'mobile' ) { wpnovc_appearance_mobile(); wpnovc_tab_end(); return; }

    // Per-tab save — only saves fields belonging to the current tab
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer( 'wpnovc_settings' ) ) {
        $tab_keys = [
            'global'  => ['cover_thumb','novels_per_page','auto_order','xs_share'],
            'seo'     => ['home_description','home_keywords','single_title_tpl'],
            'reading' => ['auto_postsplit','postsplit_min_len','postsplit_per_page'],
            'footer'  => ['footer_copyright','footer_analytics'],
        ];
        $current = $active;
        $keys    = $tab_keys[ $current ] ?? [];
        foreach ( $keys as $k ) {
            // footer_analytics / footer_copyright / xs_share 需保留 HTML/JS
            $html_fields = [ 'footer_analytics', 'footer_copyright', 'xs_share' ];
            if ( in_array( $k, $html_fields ) ) {
                wpnovc_update_option( $k, wp_unslash( $_POST[$k] ?? '' ) );
            } else {
                wpnovc_update_option( $k, sanitize_text_field( $_POST[$k] ?? '' ) );
            }
        }
        echo '<div class="notice notice-success"><p>设置已保存</p></div>';
    }

    wpnovc_form_open();
    switch ( $active ) {
        case 'global':
            wpnovc_toggle_field('cover_thumb','封面缩略图',wpnovc_option('cover_thumb',0));
            wpnovc_text_field('novels_per_page','小说每页显示数量',wpnovc_option('novels_per_page','12'));
            wpnovc_toggle_field('auto_order','章节智能排序',wpnovc_option('auto_order',0));
            wpnovc_textarea_field('xs_share','三方JS代码',wpnovc_option('xs_share'));
            break;
        case 'seo':
            wpnovc_textarea_field('home_description','首页描述',wpnovc_option('home_description'));
            wpnovc_textarea_field('home_keywords','首页关键词',wpnovc_option('home_keywords'));
            wpnovc_text_field('single_title_tpl','章节标题模板',wpnovc_option('single_title_tpl','{title} - {site_title}'));
            break;
        case 'reading':
            wpnovc_toggle_field('auto_postsplit','超长文章自动分页',wpnovc_option('auto_postsplit',0));
            wpnovc_text_field('postsplit_min_len','最小字数',wpnovc_option('postsplit_min_len','5000'));
            wpnovc_text_field('postsplit_per_page','每页字数',wpnovc_option('postsplit_per_page','3000'));
            break;
        case 'footer':
            wpnovc_textarea_field('footer_copyright','版权声明',wpnovc_option('footer_copyright'));
            wpnovc_textarea_field('footer_analytics','统计代码',wpnovc_option('footer_analytics'));
            break;
    }
    submit_button('保存设置');
    echo '</form>';
    wpnovc_tab_end();
}

// ── 付费设置 ──
function wpnovc_page_pay_settings() {
    $tabs = ['gateway'=>'支付网关','pay_read'=>'全局付费','recharge'=>'手动充值','vip'=>'会员设置'];
    $active = $_GET['__tab'] ?? 'gateway';
    wpnovc_tab_container( $tabs, $active );
    switch ( $active ) {
        case 'gateway':
            if ( $_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer( 'wpnovc_settings' ) ) {
                wpnovc_update_option( 'wechat_appid', sanitize_text_field( $_POST['wechat_appid'] ?? '' ) );
                wpnovc_update_option( 'alipay_appid', sanitize_text_field( $_POST['alipay_appid'] ?? '' ) );
                echo '<div class="notice notice-success"><p>支付网关设置已保存</p></div>';
            }
            echo '<h3>支付网关</h3>';
            wpnovc_form_open();
            wpnovc_text_field('wechat_appid','微信AppID',wpnovc_option('wechat_appid'));
            wpnovc_text_field('alipay_appid','支付宝AppID',wpnovc_option('alipay_appid'));
            submit_button('保存'); echo '</form>';
            break;
        case 'pay_read':
            if ( $_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer( 'wpnovc_settings' ) ) {
                wpnovc_update_option( 'pay_mode', sanitize_text_field( $_POST['pay_mode'] ?? 'free' ) );
                echo '<div class="notice notice-success"><p>付费设置已保存</p></div>';
            }
            echo '<h3>全局付费阅读设置</h3>';
            wpnovc_form_open();
            wpnovc_select_field('pay_mode','默认付费模式',['free'=>'免费','category'=>'整本订阅','article'=>'单章订阅'],wpnovc_option('pay_mode','free'));
            submit_button('保存'); echo '</form>';
            break;
        case 'recharge':
            echo '<h3>手动充值</h3>';
            if ( $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['recharge_uid']) && check_admin_referer( 'wpnovc_settings' ) ) {
                wpnovc_add_balance( intval($_POST['recharge_uid']), intval($_POST['recharge_amount']), '', '管理员充值' );
                echo '<div class="notice notice-success"><p>充值成功</p></div>';
            }
            echo '<form method="post">'; wp_nonce_field( 'wpnovc_settings' );
            echo '<p>用户ID: <input type="number" name="recharge_uid" /></p><p>金额(分): <input type="number" name="recharge_amount" /></p>';
            submit_button('充值'); echo '</form>';
            break;
        case 'vip':
            if ( $_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer( 'wpnovc_settings' ) ) {
                wpnovc_update_option( 'vip_discount', sanitize_text_field( $_POST['vip_discount'] ?? '8' ) );
                echo '<div class="notice notice-success"><p>会员设置已保存</p></div>';
            }
            echo '<h3>会员设置</h3>';
            wpnovc_form_open();
            wpnovc_text_field('vip_discount','会员折扣(折)',wpnovc_option('vip_discount','8'));
            submit_button('保存'); echo '</form>';
            break;
    }
    wpnovc_tab_end();
}

// ── 订单列表 ──
function wpnovc_page_orders() {
    global $wpdb; $table = $wpdb->prefix . 'wpnovc_orders';
    if ( $wpdb->get_var("SHOW TABLES LIKE '$table'") != $table ) { echo '<div class="wrap"><p>数据表不存在</p></div>'; return; }
    $per=20; $page=max(1,intval($_GET['paged']??1)); $offset=($page-1)*$per;
    $total=$wpdb->get_var("SELECT COUNT(*) FROM $table");
    $orders=$wpdb->get_results("SELECT * FROM $table ORDER BY create_time DESC LIMIT $offset,$per");
    echo '<div class="wrap"><h1>订单列表</h1><table class="widefat striped"><thead><tr><th>ID</th><th>订单号</th><th>用户</th><th>商品</th><th>金额</th><th>状态</th><th>时间</th></tr></thead><tbody>';
    $sl=['new'=>'新建','paid'=>'已付','finished'=>'完成'];
    foreach($orders as $o) echo '<tr><td>'.$o->id.'</td><td>'.esc_html($o->order_id).'</td><td>'.$o->user_id.'</td><td>'.esc_html($o->goods_title).'</td><td>'.number_format($o->pay_amount/100,2).'</td><td>'.($sl[$o->order_status]??$o->order_status).'</td><td>'.date('Y-m-d H:i',$o->create_time).'</td></tr>';
    echo '</tbody></table></div>';
}

// ── 小说导入 ──
function wpnovc_page_import() {
    ?>
    <div class="wrap">
        <h1>小说导入</h1>
        <div class="card" style="max-width:800px;padding:20px">
            <h3>分步导入：上传后自动分批处理，无超时风险</h3>
            <p style="font-size:12px;color:#666">规则：取第1个有效行作为小说名，重复导入自动跳过已有章节。每批处理 30 章，自动继续直到完成。</p>
            <hr>
            <div id="import-error" class="notice notice-error" style="display:none"><p></p></div>
            <div id="import-result" style="display:none;margin:10px 0"></div>
            <form id="novel-import-form" method="post" enctype="multipart/form-data" action="">
                <p><label>所属顶级分类：</label><?php wp_dropdown_categories(["name"=>"parent_id","id"=>"parent_id","hide_empty"=>false,"hierarchical"=>true,"depth"=>1,"show_option_none"=>"选择分类"]); ?></p>
                <p><label>TXT 文件：</label><input type="file" name="novel_file" id="novel_file" accept=".txt" /></p>
                <p class="submit"><button type="submit" class="button button-primary">导入小说</button></p>
                <p style="font-size:12px;color:#999">支持最大 64M 的 TXT 文件。上传后自动分批处理章节，可随时取消。</p>
            </form>
            <div id="import-progress" style="display:none;margin-top:15px">
                <p id="import-status" style="font-weight:bold"></p>
                <p><span id="import-novel-name"></span> / 共 <span id="import-total-chapters">0</span> 章</p>
                <div style="background:#eee;border-radius:4px;height:24px;margin:10px 0">
                    <div id="import-bar-fill" style="background:#2271b1;color:#fff;text-align:center;height:24px;line-height:24px;border-radius:4px;width:0%;transition:width 0.3s">0%</div>
                </div>
                <button type="button" id="novel-import-cancel" class="button">取消</button>
            </div>
            <div id="import-reset" style="display:none;margin-top:10px">
                <button type="button" class="button" onclick="location.reload()">重新导入</button>
            </div>
        </div>

    </div>
    <style>
        #import-bar-fill.uploading { background: #2271b1; }
        #import-bar-fill.processing { background: #2271b1; }
        #import-bar-fill.done { background: #46b450; }
    </style>
    <?php
}


function wpnovc_page_batch_import() {
    ?>
    <div class="wrap">
        <h1>批量导入小说</h1>
        <div class="card" style="max-width:800px;padding:20px">
            <h3>批量上传 TXT 文件</h3>
            <p style="font-size:12px;color:#666">选择多个 TXT 文件：先逐个上传，上传完成后逐本导入章节。支持重复上传，自动跳过已存在章节。每本小说取第 1 个有效行作为书名，若格式为"书名 作者：xxx"，自动拆出书名和作者。</p>
            <hr>
            <div id="batch-error" class="notice notice-error" style="display:none"><p></p></div>
            <div id="batch-result" style="display:none;margin:10px 0"></div>
            <div id="batch-error-log" class="notice notice-warning" style="display:none;margin:10px 0"></div>
            <form id="novel-batch-form" method="post" enctype="multipart/form-data" action="">
                <p><label>所属顶级分类：</label><?php wp_dropdown_categories(["name"=>"batch_parent_id","id"=>"batch_parent_id","hide_empty"=>false,"hierarchical"=>true,"depth"=>1,"show_option_none"=>"选择分类"]); ?></p>
                <p><label>TXT 文件（可多选）：</label><input type="file" name="batch_files[]" id="batch_files" accept=".txt" multiple /></p>
                <p class="submit"><button type="submit" class="button button-primary">批量导入</button></p>
                <p style="font-size:12px;color:#999">每个文件独立上传，避免多文件请求超时。上传完成后自动进入导入阶段。</p>
            </form>
            <div id="batch-progress" style="display:none;margin-top:15px">
                <p id="batch-status" style="font-weight:bold"></p>
                <p id="batch-sub-status" style="color:#666"></p>
                <div style="background:#eee;border-radius:4px;height:24px;margin:10px 0">
                    <div id="batch-bar-fill" style="background:#2271b1;color:#fff;text-align:center;height:24px;line-height:24px;border-radius:4px;width:0%;transition:width 0.3s">0%</div>
                </div>
                <button type="button" id="batch-cancel" class="button">取消</button>
            </div>
            <div id="batch-reset" style="display:none;margin-top:10px">
                <button type="button" class="button" onclick="location.reload()">重新导入</button>
            </div>
        </div>
    </div>
    <style>
        #batch-bar-fill.uploading { background: #2271b1; }
        #batch-bar-fill.processing { background: #2271b1; }
        #batch-bar-fill.done { background: #46b450; }
    </style>
    <?php
}

function wpnovc_page_tables() {
    global $wpdb;
    $tables = ['wpnovc_goods'=>'商品表','wpnovc_orders'=>'订单表','wpnovc_balance_log'=>'余额流水表'];
    if ( isset($_GET['action']) && $_GET['action']=='create' && isset($tables[$_GET['table']]) && check_admin_referer( 'wpnovc_tables' ) ) {
        wpnovc_create_payment_tables();
        echo '<div class="notice notice-success"><p>表已创建</p></div>';
    }
    echo '<div class="wrap"><h1>数据表管理</h1><table class="widefat striped"><thead><tr><th>表名</th><th>说明</th><th>状态</th><th>操作</th></tr></thead><tbody>';
    foreach ( $tables as $t => $l ) {
        $exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}{$t}'") == $wpdb->prefix.$t;
        echo '<tr><td><code>'.$wpdb->prefix.$t.'</code></td><td>'.$l.'</td><td>'.($exists?'<span style="color:green">✓</span>':'<span style="color:red">✗</span>').'</td><td>'.($exists?'—':'<a href="?page=wpnovo_table&action=create&_wpnonce=' . wp_create_nonce('wpnovc_tables') . '&table='.$t.'" class="button button-small">创建</a>').'</td></tr>';
    }
    echo '</tbody></table></div>';
}

// ── AI 设置 ──
function wpnovc_page_ai() {
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer( 'wpnovc_settings' ) ) {
        wpnovc_update_option( 'deepseek_api_key', sanitize_text_field( $_POST['deepseek_api_key'] ?? '' ) );
        wpnovc_update_option( 'ai_enabled', $_POST['ai_enabled'] ?? '0' );
        echo '<div class="notice notice-success"><p>AI设置已保存</p></div>';
    }
    echo '<div class="wrap"><h1>AI 设置</h1>';
    echo '<form method="post">';
    wp_nonce_field( 'wpnovc_settings' );
    echo '<table class="form-table">';
    echo '<tr><th>DeepSeek API Key</th><td><input type="password" name="deepseek_api_key" value="' . esc_attr( wpnovc_option('deepseek_api_key') ) . '" class="large-text" /></td></tr>';
    echo '<tr><th>启用 AI 生成</th><td><label><input type="checkbox" name="ai_enabled" value="1" ' . checked( wpnovc_option('ai_enabled'), 1, false ) . ' /> 导入小说时自动生成简介和SEO信息</label></td></tr>';
    echo '</table>';
    submit_button( '保存设置' );
    echo '</form></div>';
}

// ── 编辑推荐设置 ──
function wpnovc_page_editor_picks() {
    wp_enqueue_media();

    // ── 保存期次 ──
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer( 'wpnovc_settings' ) && isset( $_POST['save_issue'] ) ) {
        $data = [
            'issue_id'      => intval( $_POST['issue_id'] ?? 0 ),
            'title'         => sanitize_text_field( $_POST['issue_title'] ?? '' ),
            'post_date'     => sanitize_text_field( $_POST['issue_date'] ?? '' ),
            'banner_pc'     => esc_url_raw( $_POST['banner_pc'] ?? '' ),
            'banner_mobile' => esc_url_raw( $_POST['banner_mobile'] ?? '' ),
            'novels'        => array_filter( array_map( 'intval', (array) ( $_POST['pick_novels'] ?? [] ) ) ),
            'slug'          => sanitize_title( $_POST['issue_slug'] ?? '' ),
            'excerpt'       => sanitize_textarea_field( $_POST['issue_excerpt'] ?? '' ),
        ];

        // 保存手动推荐语
        if ( ! empty( $_POST['brief_rec'] ) && is_array( $_POST['brief_rec'] ) ) {
            foreach ( $_POST['brief_rec'] as $id => $text ) {
                $id = intval( $id );
                if ( ! $id ) continue;
                $meta = wpnovc_get_novel_meta( $id );
                $meta['brief_rec'] = sanitize_textarea_field( stripslashes( $text ) );
                wpnovc_update_novel_meta( $id, $meta );
            }
        }

        $result = wpnovc_save_editor_pick_issue( $data );
        if ( is_wp_error( $result ) ) {
            echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
        } else {
            echo '<div class="notice notice-success"><p>期次已保存</p></div>';
        }
    }

    // ── 删除期次 ──
    if ( isset( $_GET['action'], $_GET['issue_id'] ) && $_GET['action'] === 'delete' && check_admin_referer( 'wpnovc_delete_issue_' . intval( $_GET['issue_id'] ) ) ) {
        $del_id = intval( $_GET['issue_id'] );
        if ( get_post_type( $del_id ) === 'editor_pick' ) {
            wp_delete_post( $del_id, true );
            echo '<div class="notice notice-success"><p>期次已删除</p></div>';
        }
    }

    // ── 判断编辑模式 ──
    $is_edit  = isset( $_GET['action'] ) && $_GET['action'] === 'new';
    $edit_id  = 0;
    if ( isset( $_GET['issue_id'] ) && intval( $_GET['issue_id'] ) > 0 ) {
        $edit_id = intval( $_GET['issue_id'] );
        $is_edit = true;
    }

    // ── 所有小说数据 ──
    $all = [];
    $top_cats = get_terms( [ 'taxonomy' => 'category', 'parent' => 0, 'hide_empty' => false ] );
    foreach ( $top_cats as $t ) {
        $children = get_terms( [ 'taxonomy' => 'category', 'parent' => $t->term_id, 'hide_empty' => false ] );
        foreach ( $children as $ch ) $all[] = $ch;
    }
    $recommended_ids = wpnovc_get_recommended_novel_ids();

    $novels_data = [];
    foreach ( $all as $novel ) {
        $meta = wpnovc_get_novel_meta( $novel->term_id );
        $novels_data[] = [
            'id'          => $novel->term_id,
            'name'        => $novel->name,
            'author'      => $meta['xs_author'] ?? '',
            'cover'       => $meta['xs_cover'] ?? '',
            'recommended' => in_array( $novel->term_id, $recommended_ids, true ),
        ];
    }
    $novels_json = wp_json_encode( $novels_data );

    echo '<div class="wrap"><h1>编辑推荐期次管理</h1>';

    if ( $is_edit ) {
        $issue = $edit_id ? get_post( $edit_id ) : null;
        $selected_novels = $edit_id ? wpnovc_get_editor_pick_novels( $edit_id ) : [];
        $banner_pc = $edit_id ? get_post_meta( $edit_id, 'pick_banner_pc', true ) : '';
        $banner_mobile = $edit_id ? get_post_meta( $edit_id, 'pick_banner_mobile', true ) : '';
        $issue_title = $issue ? $issue->post_title : '';
        $issue_date  = $issue ? get_the_date( 'Y-m-d', $issue ) : date( 'Y-m-d' );
        $issue_excerpt = $issue ? $issue->post_excerpt : '';

        echo '<h2>' . ( $edit_id ? '编辑期次' : '新增期次' ) . '</h2>';
        echo '<form method="post">';
        wp_nonce_field( 'wpnovc_settings' );
        echo '<input type="hidden" name="save_issue" value="1">';
        echo '<input type="hidden" name="issue_id" value="' . intval( $edit_id ) . '">';
        echo '<table class="form-table">';
        echo '<tr><th>期次标题</th><td><input type="text" name="issue_title" id="issue_title" value="' . esc_attr( $issue_title ) . '" class="regular-text" placeholder="例如：第 1 期"></td></tr>';
        echo '<tr><th>期次摘要</th><td><textarea name="issue_excerpt" id="issue_excerpt" rows="3" class="large-text" placeholder="一句话介绍本期书单（60~100字），显示在编辑推荐列表页">' . esc_textarea( $issue_excerpt ) . '</textarea><p class="description">留空则自动生成"本期编辑推荐 N 部小说：……"的列表文案</p></td></tr>';
        echo '<tr><th>发布时间</th><td><input type="date" name="issue_date" id="issue_date" value="' . esc_attr( $issue_date ) . '"></td></tr>';
        $issue_slug = $issue ? get_post_meta( $issue->ID, 'pick_slug', true ) : '';
        echo '<tr><th>URL 别名</th><td><input type="text" name="issue_slug" id="issue_slug" value="' . esc_attr( $issue_slug ) . '" class="regular-text" placeholder="如 di-3-qi（留空自动用日期-序号）"><p class="description">完整链接：/editor-picks/日期-别名/</p></td></tr>';
        echo '<tr><th>PC Banner</th><td><input type="text" name="banner_pc" id="banner_pc" value="' . esc_url( $banner_pc ) . '" class="large-text" placeholder="图片 URL 或通过媒体库选择"><button type="button" class="button" id="pick-banner-pc-btn">选择图片</button> <button type="button" class="button ai-banner-generate" data-target="banner_pc" data-device="pc">AI 生成</button><p class="description">建议比例约 16:5，如 1200×375</p></td></tr>';
        echo '<tr><th>Mobile Banner</th><td><input type="text" name="banner_mobile" id="banner_mobile" value="' . esc_url( $banner_mobile ) . '" class="large-text" placeholder="留空则使用 PC Banner 自动裁剪"><button type="button" class="button" id="pick-banner-mobile-btn">选择图片</button> <button type="button" class="button ai-banner-generate" data-target="banner_mobile" data-device="mobile">AI 生成</button><p class="description">建议比例约 2:1，如 750×375</p></td></tr>';
        echo '</table>';

        echo '<h3>推荐小说（最多 10 本）</h3>';
        echo '<div id="pick-selected-list" style="margin-bottom:10px">';
        foreach ( $selected_novels as $idx => $novel_id ) {
            $term = get_term( $novel_id, 'category' );
            if ( ! $term || is_wp_error( $term ) ) continue;
            echo '<div class="pick-selected-item" data-id="' . intval( $novel_id ) . '" style="background:#f5f7fa;padding:6px 10px;margin-bottom:5px;border-radius:4px"><span class="pick-order">' . ( $idx + 1 ) . '</span> <strong>' . esc_html( $term->name ) . '</strong> <button type="button" class="button-link pick-remove-novel">移除</button></div>';
        }
        echo '</div>';
        echo '<button type="button" class="button button-primary" id="pick-select-novel-btn">选择小说</button>';
        echo '<div id="pick-novel-fields"></div>';

        // 推荐语编辑
        if ( ! empty( $selected_novels ) ) {
            echo '<h3>推荐语编辑</h3>';
            echo '<p><label><input type="checkbox" id="pick-rec-select-all" checked> 全选</label> <button type="button" class="button" id="pick-batch-ai">批量 AI 生成推荐语</button> <span id="pick-ai-progress" style="color:#666;margin-left:8px"></span></p>';
            echo '<div id="pick-rec-editor">';
            foreach ( $selected_novels as $idx => $novel_id ) {
                $term = get_term( $novel_id, 'category' );
                if ( ! $term || is_wp_error( $term ) ) continue;
                $meta = wpnovc_get_novel_meta( $novel_id );
                echo '<div class="pick-rec-item" data-id="' . intval( $novel_id ) . '" style="background:#fff;padding:12px;margin-bottom:10px;border:1px solid #ddd;border-radius:4px">';
                echo '<label><input type="checkbox" class="pick-rec-check" checked> <strong>' . esc_html( $term->name ) . '</strong></label>';
                echo '<button type="button" class="button button-small pick-ai-one">AI 生成</button>';
                echo '<textarea name="brief_rec[' . intval( $novel_id ) . ']" rows="4" class="large-text" style="margin-top:6px">' . esc_textarea( $meta['brief_rec'] ?? '' ) . '</textarea>';
                echo '</div>';
            }
            echo '</div>';
        }

        submit_button( '保存期次' );
        echo '</form>';
        echo '<p><a href="' . admin_url( 'admin.php?page=wpnovo_picks' ) . '">返回期次列表</a></p>';
    } else {
        // 期次列表
        $paged = max( 1, intval( $_GET['paged'] ?? 1 ) );
        $per_page = 20;
        $issues = get_posts( [
            'post_type'      => 'editor_pick',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $paged,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ] );
        $total = wpnovc_count_editor_pick_issues();
        $pages = max( 1, ceil( $total / $per_page ) );

        echo '<p><a href="' . admin_url( 'admin.php?page=wpnovo_picks&action=new' ) . '" class="button button-primary">新增一期</a></p>';
        echo '<table class="widefat striped"><thead><tr><th>期次</th><th>Banner</th><th>小说数</th><th>发布时间</th><th>操作</th></tr></thead><tbody>';
        if ( empty( $issues ) ) {
            echo '<tr><td colspan="5">暂无期次</td></tr>';
        } else {
            foreach ( $issues as $issue ) {
                $novel_count = count( wpnovc_get_editor_pick_novels( $issue->ID ) );
                $banner = get_post_meta( $issue->ID, 'pick_banner_pc', true );
                $del_url = wp_nonce_url( admin_url( 'admin.php?page=wpnovo_picks&action=delete&issue_id=' . $issue->ID ), 'wpnovc_delete_issue_' . $issue->ID );
                echo '<tr>';
                echo '<td><strong>' . esc_html( $issue->post_title ) . '</strong></td>';
                echo '<td>' . ( $banner ? '<img src="' . esc_url( $banner ) . '" style="max-width:120px;max-height:50px">' : '—' ) . '</td>';
                echo '<td>' . $novel_count . ' 本</td>';
                echo '<td>' . get_the_date( 'Y-m-d', $issue ) . '</td>';
                echo '<td><a href="' . admin_url( 'admin.php?page=wpnovo_picks&issue_id=' . $issue->ID ) . '">编辑</a> | <a href="' . esc_url( $del_url ) . '" onclick="return confirm(\'确定删除该期？\')">删除</a></td>';
                echo '</tr>';
            }
        }
        echo '</tbody></table>';

        if ( $pages > 1 ) {
            echo '<div style="margin-top:15px">';
            for ( $i = 1; $i <= $pages; $i++ ) {
                $cls = $i === $paged ? 'button' : 'button button-secondary';
                echo '<a href="' . admin_url( 'admin.php?page=wpnovo_picks&paged=' . $i ) . '" class="' . $cls . '" style="margin-right:3px">' . $i . '</a>';
            }
            echo '</div>';
        }
    }

    echo '</div>';

    // 弹窗和 JS
    if ( $is_edit ) {
        $ajax_url = admin_url( 'admin-ajax.php' );
        echo '<div id="pick-novel-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;overflow:auto"><div style="background:#fff;max-width:760px;margin:40px auto;border-radius:8px;padding:20px">';
        echo '<div style="display:flex;justify-content:space-between;align-items:center"><h3 style="margin:0">选择小说</h3><button type="button" class="button-link" id="pick-modal-close">关闭</button></div>';
        echo '<input type="text" id="pick-novel-search" placeholder="搜索书名或作者" class="regular-text" style="width:100%;margin:12px 0">';
        echo '<div id="pick-novel-list" style="max-height:420px;overflow-y:auto;border:1px solid #ddd;border-radius:4px"></div>';
        echo '<div style="margin-top:12px;display:flex;justify-content:space-between;align-items:center"><span id="pick-novel-count">已选 0 / 10</span><div><button type="button" class="button" id="pick-modal-cancel">取消</button> <button type="button" class="button button-primary" id="pick-modal-confirm">确认选择</button></div></div>';
        echo '</div></div>';

        echo '<div id="ai-banner-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:10000;overflow:auto"><div style="background:#fff;max-width:640px;margin:40px auto;border-radius:8px;padding:20px">';
        echo '<div style="display:flex;justify-content:space-between;align-items:center"><h3 style="margin:0">AI 生成 Banner</h3><button type="button" class="button-link" id="ai-banner-close">关闭</button></div>';
        echo '<input type="hidden" id="ai-banner-device" value="pc">';
        echo '<p style="margin-top:12px"><label>提示词</label></p>';
        echo '<textarea id="ai-banner-prompt" rows="5" class="large-text" placeholder="例如：玄幻小说推荐 banner，深色背景，金色标题"></textarea>';
        echo '<p style="color:#666;font-size:12px">当前模型：Qwen-Image-3.0，使用统一的 AI 图片设置。</p>';
        echo '<p><button type="button" class="button button-primary" id="ai-banner-generate-btn">生成图片</button> <span id="ai-banner-progress" style="color:#666;margin-left:8px"></span></p>';
        echo '<div id="ai-banner-preview" style="display:none;margin-top:12px"><img id="ai-banner-preview-img" src="" style="max-width:100%;max-height:260px;border-radius:4px"></div>';
        echo '<div style="margin-top:14px;display:flex;justify-content:flex-end;gap:8px"><button type="button" class="button" id="ai-banner-cancel">取消</button> <button type="button" class="button" id="ai-banner-regenerate" style="display:none">再次生成</button> <button type="button" class="button button-primary" id="ai-banner-confirm" style="display:none">确定使用这张图</button></div>';
        echo '</div></div>';

        echo '<script>';
        echo 'window.pickNovelsData = ' . $novels_json . ';';
        echo 'window.pickAjaxUrl = ' . wp_json_encode( $ajax_url ) . ';';
        echo 'window.pickNonce = ' . wp_json_encode( wp_create_nonce( 'wpnovc_ajax' ) ) . ';';
        echo <<<'JS'
jQuery(function($){
    var novelById = {};
    pickNovelsData.forEach(function(n){ novelById[n.id] = n; });

    var selectedIds = [];
    $(".pick-selected-item").each(function(){ selectedIds.push(parseInt($(this).data("id"),10)); });
    var tempSelected = selectedIds.slice();

    function escapeHtml(s) {
        return $("<div>").text(s == null ? "" : s).html();
    }

    function renderSelectedList() {
        var html = "";
        selectedIds.forEach(function(id, idx){
            var n = novelById[id];
            if (!n) return;
            html += '<div class="pick-selected-item" data-id="' + id + '" style="background:#f5f7fa;padding:6px 10px;margin-bottom:5px;border-radius:4px"><span class="pick-order">' + (idx+1) + '</span> <strong>' + escapeHtml(n.name) + '</strong> <button type="button" class="button-link pick-remove-novel">移除</button></div>';
        });
        $("#pick-selected-list").html(html);
        var fields = "";
        selectedIds.forEach(function(id){ fields += '<input type="hidden" name="pick_novels[]" value="' + id + '">'; });
        $("#pick-novel-fields").html(fields);
    }

    function renderRecEditor() {
        var existing = {};
        $(".pick-rec-item textarea").each(function(){
            existing[$(this).closest(".pick-rec-item").data("id")] = $(this).val();
        });
        var html = "";
        selectedIds.forEach(function(id){
            var n = novelById[id];
            if (!n) return;
            var val = typeof existing[id] !== "undefined" ? existing[id] : "";
            html += '<div class="pick-rec-item" data-id="' + id + '" style="background:#fff;padding:12px;margin-bottom:10px;border:1px solid #ddd;border-radius:4px">';
            html += '<label><input type="checkbox" class="pick-rec-check" checked> <strong>' + escapeHtml(n.name) + '</strong></label> ';
            html += '<button type="button" class="button button-small pick-ai-one">AI 生成</button>';
            html += '<textarea name="brief_rec[' + id + ']" rows="4" class="large-text" style="margin-top:6px">' + escapeHtml(val) + '</textarea>';
            html += '</div>';
        });
        $("#pick-rec-editor").html(html);
    }

    function renderModalList(filter) {
        filter = (filter || "").toLowerCase();
        var html = "", count = 0;
        pickNovelsData.forEach(function(n){
            if (filter && n.name.toLowerCase().indexOf(filter) === -1 && (n.author||"").toLowerCase().indexOf(filter) === -1) return;
            var checked = tempSelected.indexOf(n.id) !== -1;
            if (checked) count++;
            html += '<label style="display:flex;gap:10px;padding:8px;border-bottom:1px solid #eee;cursor:pointer">';
            html += '<input type="checkbox" class="pick-modal-check" value="' + n.id + '" ' + (checked ? "checked" : "") + '>';
            html += '<span style="flex:1">' + escapeHtml(n.name) + ' · ' + escapeHtml(n.author) + '</span>';
            if (n.recommended) html += '<span style="color:#c00;font-size:12px">已推荐过</span>';
            html += '</label>';
        });
        $("#pick-novel-list").html(html);
        $("#pick-novel-count").text("已选 " + count + " / 10");
    }

    $("#pick-select-novel-btn").click(function(){
        tempSelected = selectedIds.slice();
        renderModalList($("#pick-novel-search").val());
        $("#pick-novel-modal").show();
    });

    $("#pick-modal-close, #pick-modal-cancel").click(function(){
        $("#pick-novel-modal").hide();
    });

    $("#pick-novel-search").on("input", function(){
        renderModalList($(this).val());
    });

    $(document).on("change", ".pick-modal-check", function(){
        var id = parseInt($(this).val(), 10);
        if ($(this).is(":checked")) {
            if (tempSelected.indexOf(id) === -1) tempSelected.push(id);
        } else {
            tempSelected = tempSelected.filter(function(x){ return x !== id; });
        }
        renderModalList($("#pick-novel-search").val());
    });

    $("#pick-modal-confirm").click(function(){
        selectedIds = tempSelected.slice(0, 10);
        renderSelectedList();
        renderRecEditor();
        $("#pick-novel-modal").hide();
    });

    $(document).on("click", ".pick-remove-novel", function(){
        var id = $(this).closest(".pick-selected-item").data("id");
        selectedIds = selectedIds.filter(function(x){ return x !== id; });
        renderSelectedList();
        renderRecEditor();
    });

    function pickAiGenerate(id, cb) {
        $.post(pickAjaxUrl, {
            action: "wpnovc_ai_brief_rec",
            nonce: pickNonce,
            novel_id: id
        }, function(res){
            if (res && res.success) {
                $('.pick-rec-item[data-id="' + id + '"] textarea').val(res.data.brief_rec);
            }
            if (cb) cb(res && res.success);
        });
    }

    $(document).on("click", ".pick-ai-one", function(){
        var item = $(this).closest(".pick-rec-item");
        var id = item.data("id");
        var btn = $(this);
        btn.text("生成中...").prop("disabled", true);
        pickAiGenerate(id, function(ok){
            btn.text(ok ? "已生成" : "重试").prop("disabled", false);
        });
    });

    $("#pick-rec-select-all").change(function(){
        $(".pick-rec-check").prop("checked", $(this).is(":checked"));
    });

    $("#pick-batch-ai").click(function(){
        var ids = $(".pick-rec-check:checked").map(function(){ return $(this).closest(".pick-rec-item").data("id"); }).get();
        if (ids.length === 0) { alert("请先勾选小说"); return; }
        var i = 0, done = 0;
        var progress = $("#pick-ai-progress");
        function next() {
            if (i >= ids.length) {
                progress.text("完成 " + done + "/" + ids.length);
                return;
            }
            progress.text("AI 生成中 " + (i+1) + "/" + ids.length);
            pickAiGenerate(ids[i], function(ok){
                if (ok) done++;
                i++;
                setTimeout(next, 1500);
            });
        }
        next();
    });

    $("#pick-banner-pc-btn").click(function(){
        var frame = wp.media({ title: "选择 PC Banner", multiple: false });
        frame.on("select", function(){
            var att = frame.state().get("selection").first().toJSON();
            $("#banner_pc").val(att.url);
        });
        frame.open();
    });

    $("#pick-banner-mobile-btn").click(function(){
        var frame = wp.media({ title: "选择 Mobile Banner", multiple: false });
        frame.on("select", function(){
            var att = frame.state().get("selection").first().toJSON();
            $("#banner_mobile").val(att.url);
        });
        frame.open();
    });

    var aiBannerTarget = "";

    $(".ai-banner-generate").click(function(){
        aiBannerTarget = $(this).data("target");
        var device = $(this).data("device");
        $("#ai-banner-device").val(device);
        $("#ai-banner-prompt").val("");
        $("#ai-banner-preview").hide();
        $("#ai-banner-confirm").hide();
        $("#ai-banner-regenerate").hide();
        $("#ai-banner-progress").text("");
        $("#ai-banner-modal").show();
    });

    $("#ai-banner-close, #ai-banner-cancel").click(function(){
        $("#ai-banner-modal").hide();
    });

    function aiBannerGenerate() {
        var prompt = $("#ai-banner-prompt").val().trim();
        if (!prompt) { alert("请输入提示词"); return; }
        var device = $("#ai-banner-device").val();
        var progress = $("#ai-banner-progress");
        progress.text("AI 生成中...");
        $("#ai-banner-generate-btn").prop("disabled", true);
        $.post(pickAjaxUrl, {
            action: "wpnovc_ai_banner_generate",
            nonce: pickNonce,
            prompt: prompt,
            device: device
        }, function(res){
            $("#ai-banner-generate-btn").prop("disabled", false);
            if (res && res.success) {
                $("#ai-banner-preview-img").attr("src", res.data.url);
                $("#ai-banner-preview").show();
                $("#ai-banner-confirm").show();
                $("#ai-banner-regenerate").show();
                progress.text("生成成功");
            } else {
                progress.text(res && res.data && res.data.msg ? res.data.msg : "生成失败");
            }
        }).fail(function(){
            $("#ai-banner-generate-btn").prop("disabled", false);
            progress.text("生成失败，请重试");
        });
    }

    $("#ai-banner-generate-btn").click(aiBannerGenerate);
    $("#ai-banner-regenerate").click(aiBannerGenerate);

    $("#ai-banner-confirm").click(function(){
        var url = $("#ai-banner-preview-img").attr("src");
        if (url) {
            $("#" + aiBannerTarget).val(url);
        }
        $("#ai-banner-modal").hide();
    });

    renderSelectedList();
});
JS;
        echo '</script>';
    }
}
// ── 首页轮播管理 ──
function wpnovc_page_home_banners() {
    $banners = wpnovc_option( 'home_banners', [] );
    if ( ! is_array( $banners ) ) $banners = [];

    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer( 'wpnovc_settings' ) ) {
        if ( isset( $_POST['add_banner'] ) ) {
            $image = esc_url_raw( $_POST['banner_image'] ?? '' );
            $link  = esc_url_raw( $_POST['banner_link'] ?? '' );
            $order = intval( $_POST['banner_order'] ?? 0 );
            if ( $image ) {
                $banners[] = [ 'image' => $image, 'link' => $link, 'order' => $order ];
                wpnovc_update_option( 'home_banners', $banners );
                echo '<div class="notice notice-success"><p>轮播已添加</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>请选择轮播图片</p></div>';
            }
        }
        if ( isset( $_POST['save_orders'] ) ) {
            foreach ( $_POST['banner_order'] ?? [] as $index => $order ) {
                $index = intval( $index );
                if ( isset( $banners[ $index ] ) ) {
                    $banners[ $index ]['order'] = intval( $order );
                }
            }
            wpnovc_update_option( 'home_banners', $banners );
            echo '<div class="notice notice-success"><p>排序已保存</p></div>';
        }
        $banners = wpnovc_option( 'home_banners', [] );
        if ( ! is_array( $banners ) ) $banners = [];
    }

    if ( isset( $_GET['delete_banner'] ) && check_admin_referer( 'wpnovc_delete_banner_' . intval( $_GET['delete_banner'] ) ) ) {
        $index = intval( $_GET['delete_banner'] );
        if ( isset( $banners[ $index ] ) ) {
            unset( $banners[ $index ] );
            $banners = array_values( $banners );
            wpnovc_update_option( 'home_banners', $banners );
            echo '<div class="notice notice-success"><p>轮播已删除</p></div>';
        }
    }

    echo '<div class="wrap"><h1>首页轮播管理</h1>';
    echo '<form method="post" style="margin-bottom:30px">';
    wp_nonce_field( 'wpnovc_settings' );
    echo '<table class="form-table">';
    echo '<tr><th>轮播图片</th><td><input type="text" name="banner_image" id="banner_image" class="large-text" placeholder="图片 URL"><button type="button" class="button" id="banner-image-btn">选择图片</button><p class="description">建议尺寸 1200×375</p></td></tr>';
    echo '<tr><th>跳转链接</th><td><input type="text" name="banner_link" class="large-text" placeholder="如 /editor-picks/ 或 https://..."></td></tr>';
    echo '<tr><th>显示序号</th><td><input type="number" name="banner_order" value="0" style="width:120px"><p class="description">数字越大越靠前显示</p></td></tr>';
    echo '</table>';
    echo '<button type="submit" name="add_banner" value="1" class="button button-primary">添加轮播</button>';
    echo '</form>';

    echo '<h2>现有轮播</h2>';
    if ( empty( $banners ) ) {
        echo '<p>暂无轮播</p>';
    } else {
        echo '<table class="widefat striped"><thead><tr><th>图片</th><th>链接</th><th>显示序号</th><th>操作</th></tr></thead><tbody>';
        foreach ( $banners as $i => $b ) {
            $del_url = wp_nonce_url( admin_url( 'admin.php?page=wpnovo_banner&delete_banner=' . $i ), 'wpnovc_delete_banner_' . $i );
            echo '<tr>';
            echo '<td><img src="' . esc_url( $b['image'] ) . '" style="max-width:240px;max-height:75px"></td>';
            echo '<td><a href="' . esc_url( $b['link'] ) . '" target="_blank">' . esc_html( $b['link'] ) . '</a></td>';
            echo '<td><input type="number" name="banner_order[' . $i . ']" form="banner-sort-form" value="' . intval( $b['order'] ?? 0 ) . '" style="width:100px"></td>';
            echo '<td><a href="' . esc_url( $del_url ) . '" class="button button-link-delete" onclick="return confirm(\'确定删除该轮播？\')">删除</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '<form id="banner-sort-form" method="post">';
        wp_nonce_field( 'wpnovc_settings' );
        echo '<p><button type="submit" name="save_orders" value="1" class="button button-primary">保存排序</button></p>';
        echo '</form>';
    }

    echo '<script>
    jQuery(function($){
        $("#banner-image-btn").click(function(){
            var frame = wp.media({ title: "选择轮播图片", multiple: false });
            frame.on("select", function(){
                var att = frame.state().get("selection").first().toJSON();
                $("#banner_image").val(att.url);
            });
            frame.open();
        });
    });
    </script>';
    echo '</div>';
}

// ── AI 图片设置 ──
function wpnovc_page_ai_image() {
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer( 'wpnovc_settings' ) ) {
        $model = sanitize_text_field( $_POST['ai_image_model'] ?? 'qwen-image-3.0' );
        $key   = sanitize_text_field( $_POST['ai_image_api_key'] ?? '' );
        wpnovc_update_option( 'ai_image_model', $model );
        if ( $key ) wpnovc_update_option( 'ai_image_api_key', $key );
        echo '<div class="notice notice-success"><p>AI 图片设置已保存</p></div>';
    }

    $models = wpnovc_ai_image_models();
    $current_model = wpnovc_option( 'ai_image_model', 'qwen-image-3.0' );
    $current_key = wpnovc_option( 'ai_image_api_key', '' );

    echo '<div class="wrap"><h1>AI 图片设置</h1>';
    echo '<form method="post">';
    wp_nonce_field( 'wpnovc_settings' );
    echo '<table class="form-table">';
    echo '<tr><th>图片生成模型</th><td><select name="ai_image_model">';
    foreach ( $models as $id => $m ) {
        echo '<option value="' . $id . '" ' . selected( $current_model, $id, false ) . '>' . esc_html( $m['name'] ) . '</option>';
    }
    echo '</select></td></tr>';
    echo '<tr><th>API Key</th><td><input type="password" name="ai_image_api_key" value="' . esc_attr( $current_key ) . '" class="large-text" placeholder="留空则保持原 Key"></td></tr>';
    echo '</table>';
    submit_button( '保存设置' );
    echo '</form>';
    echo '<p style="color:#666">此设置由封面生成、PC Banner、Mobile Banner 三个功能共用。</p>';
    echo '</div>';
}

// ── 封面生成 ──
function wpnovc_page_cover() {
    // 保存 AI 设置
    // 处理单个/批量生成请求
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer( 'wpnovc_settings' ) && isset( $_POST['novel_ids'] ) ) {
        $ids    = array_filter( array_map( 'intval', $_POST['novel_ids'] ?? [] ) );
        $method = $_POST['cover_method'] ?? 'gd';
        $count  = 0;
        foreach ( $ids as $id ) {
            $term = get_term( $id, 'category' );
            if ( ! $term || is_wp_error( $term ) ) continue;
            $meta = wpnovc_get_novel_meta( $id );
            if ( $method === 'ai_model' || $method === 'ai' ) {
                $url = wpnovc_generate_ai_cover( $term->name, $id );
            } else {
                $url = wpnovc_generate_cover( $term->name, $id );
            }
            if ( $url ) {
                $meta['xs_cover'] = $url;
                wpnovc_update_novel_meta( $id, $meta );
                $count++;
            }
        }
        echo '<div class="notice notice-success"><p>成功生成 ' . $count . ' 个封面</p></div>';
    }

    // ── 翻页（直接 SQL）──
    global $wpdb;
    $per_page = 100;
    $page     = max( 1, intval( $_GET['cp'] ?? 1 ) );
    $offset   = ( $page - 1 ) * $per_page;
    $total    = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->term_taxonomy} tt INNER JOIN {$wpdb->term_taxonomy} tt2 ON tt.parent=tt2.term_id WHERE tt2.parent=0 AND tt.taxonomy='category'" );
    $total_pages = ceil( $total / $per_page );
    
    $all = $wpdb->get_results( $wpdb->prepare(
        "SELECT t.term_id, t.name FROM {$wpdb->terms} t
         INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id=tt.term_id
         INNER JOIN {$wpdb->term_taxonomy} tt2 ON tt.parent=tt2.term_id
         WHERE tt2.parent=0 AND tt.taxonomy='category'
         ORDER BY t.term_id DESC LIMIT %d OFFSET %d",
        $per_page, $offset
    ) );
    
    echo '<div class="wrap"><h1>封面生成</h1>';
    echo '<p style="color:#666">共 ' . $total . ' 本，每页 ' . $per_page . ' 本</p>';
    
    // 翻页导航
    if ( $total_pages > 1 ) {
        echo '<div style="margin-bottom:15px">';
        for ( $i = 1; $i <= $total_pages; $i++ ) {
            $cls = $i === $page ? 'button' : 'button button-secondary';
            echo '<a href="?page=wpnovo_cover&cp=' . $i . '" class="' . $cls . '" style="margin-right:3px">' . $i . '</a>';
        }
        echo '</div>';
    }
    
    echo '<form method="post">';
    wp_nonce_field( 'wpnovc_settings' );
    
    echo '<table class="form-table">';
    echo '<tr><th>生成方式</th><td><select name="cover_method">';
    echo '<option value="gd">GD 库生成（快速，本地字体）</option>';
    echo '<option value="ai_model">AI 模型生成</option>';
    echo '</select></td></tr>';
    
    echo '<p style="font-size:12px;color:#666">AI 模型与 API Key 在「AI 图片设置」页面统一配置。</p>';
    
    echo '<tr><th>选择小说</th><td>';
    echo '<div style="max-height:400px;overflow-y:auto;border:1px solid #ddd;padding:10px">';
    echo '<label><input type="checkbox" id="select-all" onclick="var cbs=document.querySelectorAll(\'.novel-cb\');cbs.forEach(function(cb){cb.checked=this.checked}.bind(this))"> 全选</label><br><br>';
    foreach ($all as $c) {
        $meta = wpnovc_get_novel_meta( $c->term_id );
        $has = !empty($meta['xs_cover']);
        echo '<label style="display:inline-block;width:32%;margin-bottom:4px"><input type="checkbox" name="novel_ids[]" value="' . $c->term_id . '" class="novel-cb"> ' . esc_html($c->name) . ($has ? ' ✅' : '') . '</label>';
    }
    echo '</div>';
    echo '</td></tr></table>';
    
    echo '<p>
        <button type="button" class="button button-primary" id="btn-gen-cover">生成选中封面</button>
        <span id="cover-progress" style="display:none;margin-left:15px;font-weight:600;color:#12345a"></span>
        <span style="color:#999;margin-left:10px">⚠ 生成中请勿离开本页面</span>
    </p></form>';

    $ajax_url = admin_url( 'admin-ajax.php' );
    echo '<script>
    document.getElementById("btn-gen-cover").addEventListener("click", function(){
        var cbs = document.querySelectorAll("input[name=\"novel_ids[]\"]:checked");
        if (cbs.length === 0) { alert("请先勾选要生成的小说"); return; }
        if (!confirm("确认生成 " + cbs.length + " 个封面？")) return;
        var btn = this; btn.disabled = true; btn.textContent = "生成中...";
        var info = document.getElementById("cover-progress"); info.style.display = "inline";
        var ids = Array.from(cbs).map(function(c){return c.value});
        var method = document.querySelector("select[name=\"cover_method\"]").value;
        var done = 0, fail = 0, total = ids.length;
        function next(i, retry) {
            retry = retry || 0;
            if (i >= total) {
                btn.disabled = false; btn.textContent = "生成选中封面";
                info.textContent = "完成！成功 " + done + "/" + total + (fail > 0 ? "，失败 " + fail : "");
                cbs.forEach(function(cb){ if (cb.checked) { var lbl = cb.parentElement; if (lbl) lbl.innerHTML = lbl.innerHTML.replace("⬜","✅"); } });
                return;
            }
            info.textContent = "生成中 " + (i+1) + "/" + total + (retry > 0 ? "（重试 " + retry + "）" : "") + "...";
            var fd = new FormData();
            fd.append("action", "wpnovc_gen_cover");
            fd.append("novel_id", ids[i]);
            fd.append("method", method);
            fetch("' . $ajax_url . '", {method:"POST", body:fd})
            .then(function(r){return r.json()})
            .then(function(d){
                if (d.success) {
                    done++;
                    setTimeout(function(){ next(i+1, 0); }, 3500);
                } else if (retry < 2) {
                    // 429 限流时，等待 3 秒后重试
                    setTimeout(function(){ next(i, retry+1); }, 3000);
                } else {
                    fail++;
                    next(i+1, 0);
                }
            })
            .catch(function(){
                if (retry < 2) {
                    setTimeout(function(){ next(i, retry+1); }, 3000);
                } else {
                    fail++;
                    next(i+1, 0);
                }
            });
        }
        next(0, 0);
    });
    </script>';
    
    // 底部翻页
    if ( $total_pages > 1 ) {
        echo '<div style="margin-top:15px">';
        for ( $i = 1; $i <= $total_pages; $i++ ) {
            $cls = $i === $page ? 'button' : 'button button-secondary';
            echo '<a href="?page=wpnovo_cover&cp=' . $i . '" class="' . $cls . '" style="margin-right:3px">' . $i . '</a>';
        }
        echo '</div>';
    }
    
    echo '</div>';
}

function wpnovc_page_editor_notes() {
    global $wpdb;
    // ── 处理 POST ──
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer( 'wpnovc_settings' ) ) {
        if ( isset( $_POST['editor_note'] ) && is_array( $_POST['editor_note'] ) ) {
            foreach ( $_POST['editor_note'] as $id => $text ) {
                $meta = wpnovc_get_novel_meta( intval($id) );
                $meta['editor_note'] = sanitize_textarea_field( stripslashes( $text ) );
                wpnovc_update_novel_meta( intval($id), $meta );
            }
            echo '<div class="notice notice-success"><p>导语已保存</p></div>';
        }
        if ( ! empty( $_POST['ai_generate'] ) && wpnovc_option( 'ai_enabled' ) ) {
            $ids = array_filter( array_map( 'intval', (array) ( $_POST['gen_ids'] ?? [] ) ) );
            $cnt = 0;
            foreach ( $ids as $id ) {
                $novel = $wpdb->get_row( $wpdb->prepare( "SELECT t.name, tm.meta_value FROM {$wpdb->terms} t LEFT JOIN {$wpdb->termmeta} tm ON t.term_id=tm.term_id AND tm.meta_key='wpnovc_novel_meta' WHERE t.term_id=%d", $id ) );
                if ( ! $novel ) continue;
                $meta   = $novel->meta_value ? maybe_unserialize( $novel->meta_value ) : [];
                $author = $meta['xs_author'] ?? '佚名';
                $rec    = wpnovc_ai_generate_editor_note_full( $novel->name, $author );
                if ( $rec ) { $meta['editor_note'] = $rec; wpnovc_update_novel_meta( $id, $meta ); $cnt++; }
            }
            echo '<div class="notice notice-success"><p>AI 生成完成：' . $cnt . ' 篇</p></div>';
        }
    }
    
    // ── 单 SQL 查询小说列表 ──
    $per_page = 50;
    $page     = max( 1, intval( $_GET['ep'] ?? 1 ) );
    $offset   = ( $page - 1 ) * $per_page;
    
    $total = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->term_taxonomy} tt INNER JOIN {$wpdb->term_taxonomy} tt2 ON tt.parent=tt2.term_id WHERE tt2.parent=0 AND tt.taxonomy='category'" );
    $total_pages = ceil( $total / $per_page );
    
    $novels = $wpdb->get_results( $wpdb->prepare(
        "SELECT t.term_id, t.name, tt.parent FROM {$wpdb->terms} t
         INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id=tt.term_id
         INNER JOIN {$wpdb->term_taxonomy} tt2 ON tt.parent=tt2.term_id
         WHERE tt2.parent=0 AND tt.taxonomy='category'
         ORDER BY t.term_id DESC LIMIT %d OFFSET %d",
        $per_page, $offset
    ) );
    
    echo '<div class="wrap"><h1>编辑导语管理</h1><p>共 ' . $total . ' 本，每页 ' . $per_page . ' 本</p>';
    
    // 翻页
    if ( $total_pages > 1 ) {
        echo '<div style="margin-bottom:15px">';
        for ( $i = 1; $i <= $total_pages; $i++ ) {
            $cls = $i === $page ? 'button' : 'button button-secondary';
            echo '<a href="?page=wpnovo_editor&ep=' . $i . '" class="' . $cls . '" style="margin-right:3px">' . $i . '</a>';
        }
        echo '</div>';
    }
    
    echo '<form method="post">'; wp_nonce_field( 'wpnovc_settings' );
    echo '<table class="widefat striped"><thead><tr><th style="width:40px">选</th><th style="width:60px">ID</th><th>小说名</th><th>作者</th><th>导语字数</th></tr></thead><tbody>';
    
    foreach ( $novels as $n ) {
        $meta = wpnovc_get_novel_meta( $n->term_id );
        $len  = mb_strlen( $meta['editor_note'] ?? '' );
        echo '<tr>';
        echo '<td><input type="checkbox" name="gen_ids[]" value="' . $n->term_id . '"></td>';
        echo '<td>' . $n->term_id . '</td>';
        echo '<td>' . esc_html( $n->name ) . '</td>';
        echo '<td>' . esc_html( $meta['xs_author'] ?? '' ) . '</td>';
        echo '<td>' . ( $len ? '<span style="color:green">' . $len . '字</span>' : '<span style="color:red">无</span>' ) . '</td>';
        echo '</tr>';
        echo '<tr><td colspan="5"><textarea name="editor_note[' . $n->term_id . ']" rows="4" class="large-text">' . esc_textarea( $meta['editor_note'] ?? '' ) . '</textarea></td></tr>';
    }
    
    echo '</tbody></table>';
    echo '<p>
        <button type="button" class="button button-primary" id="btn-batch-ai">AI 批量生成选中的导语</button>
        <button type="submit" class="button button-secondary" name="save" value="1">保存手动编辑</button>
        <span id="progress-info" style="display:none;margin-left:15px;font-weight:600;color:#12345a"></span>
        <span style="color:#999;margin-left:10px">⚠ 生成中请勿离开本页面</span>
    </p></form>';

    $ajax_url = admin_url( 'admin-ajax.php' );
    echo '<script>
    document.getElementById("btn-batch-ai").addEventListener("click", function(){
        var cbs = document.querySelectorAll("input[name=\"gen_ids[]\"]:checked");
        if (cbs.length === 0) { alert("请先勾选要生成的小说"); return; }
        if (!confirm("确认AI生成 " + cbs.length + " 篇导语？约需 " + (cbs.length*30) + " 秒")) return;
        var btn = this; btn.disabled = true; btn.textContent = "生成中...";
        var info = document.getElementById("progress-info"); info.style.display = "inline";
        var ids = Array.from(cbs).map(function(c){return c.value});
        var done = 0, fail = 0, total = ids.length;
        function next(i) {
            if (i >= total) {
                btn.disabled = false; btn.textContent = "AI 批量生成选中的导语";
                info.textContent = "完成！成功 " + done + "/" + total + (fail > 0 ? "，失败 " + fail : "");
                return;
            }
            info.textContent = "生成中 " + (i+1) + "/" + total + "...";
            var fd = new FormData();
            fd.append("action", "wpnovc_gen_editor_note");
            fd.append("novel_id", ids[i]);
            fetch("' . $ajax_url . '", {method:"POST", body:fd})
            .then(function(r){return r.json()})
            .then(function(d){
                if (d.success) {
                    done++;
                    // update textarea
                    var ta = document.querySelector("textarea[name=\"editor_note[" + ids[i] + "]\"]");
                    if (ta && d.data && d.data.note) ta.value = d.data.note;
                } else { fail++; }
                next(i+1);
            })
            .catch(function(){ fail++; next(i+1); });
        }
        next(0);
    });
    </script>';
    
    if ( $total_pages > 1 ) {
        echo '<div style="margin-top:15px">';
        for ( $i = 1; $i <= $total_pages; $i++ ) {
            $cls = $i === $page ? 'button' : 'button button-secondary';
            echo '<a href="?page=wpnovo_editor&ep=' . $i . '" class="' . $cls . '" style="margin-right:3px">' . $i . '</a>';
        }
        echo '</div>';
    }
    
    echo '</div>';
}

function wpnovc_page_help() { echo '<div class="wrap"><h1>教程</h1><p>在分类管理中创建三级分类：顶级→二级(小说名)→三级(卷)。文章发布时选择对应分类即可。</p></div>'; }

function wpnovc_page_vip() {
    $uid = get_current_user_id(); $balance = wpnovc_get_balance( $uid );
    $tabs = ['default'=>'会员中心','order'=>'我的订单']; $active = $_GET['__tab'] ?? 'default';
    wpnovc_tab_container( $tabs, $active );
    if ( $active == 'default' ) {
        echo '<h3>会员中心</h3><p>余额: ' . number_format($balance/100,2) . ' 元</p>';
        echo '<p><a href="'.esc_url(wpnovc_option('recharge_url','#')).'" class="button">充值</a></p>';
    } else {
        global $wpdb; $uid = get_current_user_id(); $table = $wpdb->prefix.'wpnovc_orders';
        $orders = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE user_id=%d ORDER BY create_time DESC LIMIT 50", $uid));
        echo '<h3>我的订单</h3>';
        if ($orders) { echo '<table class="widefat"><thead><tr><th>订单号</th><th>商品</th><th>金额</th><th>时间</th></tr></thead><tbody>';
            foreach($orders as $o) echo '<tr><td>'.esc_html($o->order_id).'</td><td>'.esc_html($o->goods_title).'</td><td>'.number_format($o->pay_amount/100,2).'</td><td>'.date('Y-m-d',$o->create_time).'</td></tr>';
            echo '</tbody></table>'; } else echo '<p>暂无订单</p>';
    }
    wpnovc_tab_end();
}

function wpnovc_page_tools() {
    $tabs = ['default'=>'设置','phplog'=>'PHP日志','log'=>'业务日志']; $active = $_GET['__tab'] ?? 'default';
    wpnovc_tab_container( $tabs, $active );
    if ( $active == 'phplog' ) { $f = ABSPATH.'wp-content/logs/php_error.log'; echo '<h3>PHP错误日志</h3><pre>'.(file_exists($f)?esc_html(file_get_contents($f)):'暂无').'</pre>'; }
    elseif ( $active == 'log' ) { $f = ABSPATH.'wp-content/logs/service.log'; echo '<h3>业务日志</h3><pre>'.(file_exists($f)?esc_html(file_get_contents($f)):'暂无').'</pre>'; }
    else { echo '<h3>工具</h3><p>主题版本: '.WPNOVC_VERSION.'</p>'; }
    wpnovc_tab_end();
}


function wpnovc_page_analysis() { echo '<div class="wrap"><h1>统计分析</h1><p>功能开发中</p></div>'; }
