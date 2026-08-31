<?php
/**
 * 商城系统 - 完整 13 个 Tab
 *
 * @package wpno-vc
 */

function wpnovc_shop_page() {
    $tabs = [
        'goods'          => '商品列表',
        'category'       => '商品分类',
        'order'          => '订单列表',
        'promotion_order'=> '推广订单',
        'bill'           => '账单列表',
        'cashout_list'   => '提现申请',
        'gateway'        => '支付设置',
        'activity'       => '活动',
        'recharge'       => '手动充值',
        'vip_card'       => '赠送会员',
        'commission'     => '佣金设置',
        'system'         => '系统设置',
        'table'          => '数据表',
    ];
    $active = $_GET['__tab'] ?? 'goods';
    echo '<div class="wrap">';
    echo '<nav class="nav-tab-wrapper" style="margin-bottom:10px">';
    foreach ( $tabs as $key => $label ) {
        $class = ( $key === $active ) ? 'nav-tab nav-tab-active' : 'nav-tab';
        echo '<a href="?page=wptrade&__tab=' . $key . '" class="' . $class . '">' . $label . '</a>';
    }
    echo '</nav>';

    $method = 'wpnovc_shop_' . $active;
    if ( function_exists( $method ) ) {
        $method();
    } else {
        echo '<div class="card"><p>此功能待实现。</p></div>';
    }

    echo '</div>';
}

// ── 商品列表 ──
function wpnovc_shop_goods() {
    global $wpdb;
    $table = $wpdb->prefix . 'wpnovc_goods';

    // 保存/新增
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer( 'wpnovc_shop' ) ) {
        $data = [
            'name'       => sanitize_text_field( $_POST['name'] ?? '' ),
            'price'      => intval( $_POST['price'] ?? 0 ),
            'now_price'  => intval( $_POST['now_price'] ?? 0 ),
            'goods_type' => sanitize_text_field( $_POST['goods_type'] ?? 'category' ),
            'goods_category_id' => intval( $_POST['goods_category_id'] ?? 0 ),
            'status'     => intval( $_POST['status'] ?? 1 ),
            'update_time'=> time(),
        ];
        if ( ! empty( $_POST['goods_id'] ) ) {
            $wpdb->update( $table, $data, [ 'id' => intval( $_POST['goods_id'] ) ] );
            echo '<div class="notice notice-success"><p>商品已更新</p></div>';
        } else {
            $data['create_time'] = time();
            $wpdb->insert( $table, $data );
            echo '<div class="notice notice-success"><p>商品已添加</p></div>';
        }
    }

    // 删除
    if ( isset( $_GET['delete'] ) && check_admin_referer( 'wpnovc_shop_del' ) ) {
        $wpdb->delete( $table, [ 'id' => intval( $_GET['delete'] ) ] );
        echo '<div class="notice notice-success"><p>已删除</p></div>';
    }

    // 编辑表单
    if ( isset( $_GET['edit'] ) ) {
        $item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id=%d", intval( $_GET['edit'] ) ) );
        if ( $item ) {
            wpnovc_goods_form( $item );
            return;
        }
    }
    if ( isset( $_GET['new'] ) ) {
        wpnovc_goods_form( null );
        return;
    }

    // 列表
    $items = $wpdb->get_results( "SELECT * FROM $table ORDER BY id DESC LIMIT 100" );
    echo '<h2>商品列表 <a href="?page=wptrade&__tab=goods&new=1" class="page-title-action">新增商品</a></h2>';
    if ( $items ) {
        echo '<table class="widefat striped"><thead><tr>
            <th>ID</th><th>名称</th><th>原价</th><th>现价</th><th>类型</th><th>关联</th><th>状态</th><th>操作</th>
        </tr></thead><tbody>';
        $types = [ 'category' => '整本', 'article' => '单章', 'recharge' => '充值', 'vip_card' => '会员卡' ];
        foreach ( $items as $g ) {
            $type_label = $types[ $g->goods_type ] ?? $g->goods_type;
            $del_url = wp_nonce_url( "?page=wptrade&__tab=goods&delete={$g->id}", 'wpnovc_shop_del' );
            echo '<tr>
                <td>' . $g->id . '</td>
                <td><a href="?page=wptrade&__tab=goods&edit=' . $g->id . '">' . esc_html( $g->name ) . '</a></td>
                <td>' . number_format( $g->price / 100, 2 ) . '</td>
                <td>' . number_format( $g->now_price / 100, 2 ) . '</td>
                <td>' . $type_label . '</td>
                <td>' . ( $g->goods_category_id ? get_term( $g->goods_category_id, 'category' )->name ?? $g->goods_category_id : '-' ) . '</td>
                <td>' . ( $g->status ? '✅' : '❌' ) . '</td>
                <td><a href="' . $del_url . '" onclick="return confirm(\'确认删除?\')">删除</a></td>
            </tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>暂无商品，<a href="?page=wptrade&__tab=goods&new=1">新增商品</a></p>';
    }
}

function wpnovc_goods_form( $item ) {
    $is_new = ! $item;
    echo '<h2>' . ( $is_new ? '新增商品' : '编辑商品' ) . '</h2>';
    echo '<form method="post" style="max-width:600px">';
    wp_nonce_field( 'wpnovc_shop' );
    if ( ! $is_new ) echo '<input type="hidden" name="goods_id" value="' . $item->id . '" />';
    echo '<table class="form-table">';
    echo '<tr><th>名称</th><td><input type="text" name="name" value="' . esc_attr( $item->name ?? '' ) . '" class="regular-text" required /></td></tr>';
    echo '<tr><th>原价(分)</th><td><input type="number" name="price" value="' . ( $item->price ?? 0 ) . '" /></td></tr>';
    echo '<tr><th>现价(分)</th><td><input type="number" name="now_price" value="' . ( $item->now_price ?? 0 ) . '" /></td></tr>';
    echo '<tr><th>商品类型</th><td><select name="goods_type">
        <option value="category" ' . selected( $item->goods_type ?? '', 'category', false ) . '>整本订阅</option>
        <option value="article" ' . selected( $item->goods_type ?? '', 'article', false ) . '>单章订阅</option>
        <option value="recharge" ' . selected( $item->goods_type ?? '', 'recharge', false ) . '>余额充值</option>
        <option value="vip_card" ' . selected( $item->goods_type ?? '', 'vip_card', false ) . '>会员卡</option>
    </select></td></tr>';
    echo '<tr><th>关联分类ID</th><td><input type="number" name="goods_category_id" value="' . ( $item->goods_category_id ?? 0 ) . '" /> (对应的小说分类ID)</td></tr>';
    echo '<tr><th>状态</th><td><select name="status"><option value="1" ' . selected( $item->status ?? 1, 1, false ) . '>启用</option><option value="0" ' . selected( $item->status ?? 1, 0, false ) . '>禁用</option></select></td></tr>';
    echo '</table>';
    submit_button( $is_new ? '添加商品' : '保存修改' );
    echo ' <a href="?page=wptrade&__tab=goods" class="button">返回列表</a>';
    echo '</form>';
}

// ── 商品分类 ──
function wpnovc_shop_category() {
    $terms = get_terms( [ 'taxonomy' => 'category', 'parent' => 0, 'hide_empty' => false ] );
    echo '<h2>商品分类</h2>';
    echo '<p>商品分类即 WordPress 的分类系统。在 <a href="' . admin_url( 'edit-tags.php?taxonomy=category' ) . '">分类管理</a> 中创建和编辑。</p>';
    echo '<table class="widefat striped"><thead><tr><th>ID</th><th>名称</th><th>子分类数</th><th>操作</th></tr></thead><tbody>';
    foreach ( $terms as $t ) {
        $children = get_terms( [ 'taxonomy' => 'category', 'parent' => $t->term_id, 'hide_empty' => false ] );
        echo '<tr><td>' . $t->term_id . '</td><td>' . esc_html( $t->name ) . '</td><td>' . count( $children ) . '</td>
            <td><a href="' . admin_url( 'term.php?taxonomy=category&tag_ID=' . $t->term_id ) . '">编辑</a></td></tr>';
    }
    echo '</tbody></table>';
}

// ── 订单列表 ──
function wpnovc_shop_order() {
    global $wpdb;
    $table  = $wpdb->prefix . 'wpnovc_orders';
    $per    = 20;
    $page   = max( 1, intval( $_GET['paged'] ?? 1 ) );
    $offset = ( $page - 1 ) * $per;
    $total  = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
    $orders = $wpdb->get_results( "SELECT * FROM $table ORDER BY create_time DESC LIMIT $offset, $per" );

    $status_labels = [ 'new' => '新建', 'paid' => '已付款', 'finished' => '已完成', 'cancel' => '已取消', 'refunding' => '退款中', 'refunded' => '已退款' ];

    echo '<h2>订单列表</h2>';
    echo '<table class="widefat striped"><thead><tr>
        <th>ID</th><th>订单号</th><th>用户</th><th>商品</th><th>金额</th><th>支付方式</th><th>状态</th><th>时间</th>
    </tr></thead><tbody>';
    foreach ( $orders as $o ) {
        $user   = get_userdata( $o->user_id );
        $uname  = $user ? $user->display_name : 'UID:' . $o->user_id;
        $status = $status_labels[ $o->order_status ] ?? $o->order_status;
        echo '<tr>
            <td>' . $o->id . '</td>
            <td>' . esc_html( $o->order_id ) . '</td>
            <td>' . esc_html( $uname ) . '</td>
            <td>' . esc_html( $o->goods_title ) . '</td>
            <td>' . number_format( $o->pay_amount / 100, 2 ) . '元</td>
            <td>' . esc_html( $o->pay_type ) . '</td>
            <td>' . $status . '</td>
            <td>' . date( 'Y-m-d H:i', $o->create_time ) . '</td>
        </tr>';
    }
    echo '</tbody></table>';
    $tp = ceil( $total / $per );
    if ( $tp > 1 ) {
        echo '<div class="tablenav"><div class="tablenav-pages">' . paginate_links( [ 'base' => add_query_arg( 'paged', '%#%' ), 'format' => '', 'current' => $page, 'total' => $tp ] ) . '</div></div>';
    }
}

// ── 推广订单 ──
function wpnovc_shop_promotion_order() {
    global $wpdb;
    $table  = $wpdb->prefix . 'wpnovc_orders';
    $per    = 20;
    $page   = max( 1, intval( $_GET['paged'] ?? 1 ) );
    $offset = ( $page - 1 ) * $per;
    $total  = $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE ext LIKE '%promoter%'" );
    $orders = $wpdb->get_results( "SELECT * FROM $table WHERE ext LIKE '%promoter%' ORDER BY create_time DESC LIMIT $offset, $per" );

    echo '<h2>推广订单</h2>';
    echo '<p>推广订单包含佣金信息。</p>';
    if ( $orders ) {
        echo '<table class="widefat striped"><thead><tr><th>ID</th><th>订单号</th><th>用户</th><th>商品</th><th>金额</th><th>时间</th></tr></thead><tbody>';
        foreach ( $orders as $o ) {
            echo '<tr><td>' . $o->id . '</td><td>' . esc_html( $o->order_id ) . '</td><td>' . $o->user_id . '</td><td>' . esc_html( $o->goods_title ) . '</td><td>' . number_format( $o->pay_amount / 100, 2 ) . '</td><td>' . date( 'Y-m-d', $o->create_time ) . '</td></tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>暂无推广订单。</p>';
    }
}

// ── 账单列表 ──
function wpnovc_shop_bill() {
    global $wpdb;
    $table = $wpdb->prefix . 'wpnovc_balance_log';
    $items = $wpdb->get_results( "SELECT * FROM $table ORDER BY create_time DESC LIMIT 100" );
    echo '<h2>账单列表（余额流水）</h2>';
    if ( $items ) {
        echo '<table class="widefat striped"><thead><tr><th>ID</th><th>用户</th><th>变动</th><th>余额</th><th>类型</th><th>备注</th><th>时间</th></tr></thead><tbody>';
        foreach ( $items as $l ) {
            echo '<tr>
                <td>' . $l->id . '</td><td>' . $l->user_id . '</td>
                <td>' . ( $l->amount >= 0 ? '+' : '' ) . number_format( $l->amount / 100, 2 ) . '</td>
                <td>' . number_format( $l->balance / 100, 2 ) . '</td>
                <td>' . $l->type . '</td><td>' . esc_html( $l->remark ) . '</td>
                <td>' . date( 'Y-m-d H:i', $l->create_time ) . '</td></tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>暂无账单。</p>';
    }
}

// ── 提现申请 ──
function wpnovc_shop_cashout_list() {
    echo '<h2>提现申请</h2><p>提现功能待实现，请手动处理。</p>';
}

// ── 支付设置 ──
function wpnovc_shop_gateway() {
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer( 'wpnovc_shop' ) ) {
        $keys = [ 'wechat_appid', 'wechat_mchid', 'wechat_key', 'wechat_serial', 'wechat_private_key',
                  'alipay_appid', 'alipay_private_key', 'alipay_public_key',
                  'paypal_client_id', 'paypal_secret', 'paypal_mode',
                  'hupijiao_appid', 'hupijiao_key', 'pay_mode', 'only_balance' ];
        foreach ( $keys as $k ) {
            wpnovc_update_option( $k, sanitize_text_field( $_POST[ $k ] ?? '' ) );
        }
        echo '<div class="notice notice-success"><p>支付设置已保存</p></div>';
    }

    echo '<h2>支付设置</h2>';
    echo '<form method="post">';
    wp_nonce_field( 'wpnovc_shop' );

    echo '<h3>全局设置</h3><table class="form-table">';
    echo '<tr><th>支付模式</th><td><select name="pay_mode">
        <option value="" ' . selected( wpnovc_option( 'pay_mode' ), '', false ) . '>标准模式</option>
        <option value="only_balance" ' . selected( wpnovc_option( 'pay_mode' ), 'only_balance', false ) . '>仅余额支付</option>
    </select></td></tr>';
    echo '<tr><th>仅余额支付</th><td><label><input type="checkbox" name="only_balance" value="1" ' . checked( wpnovc_option( 'only_balance' ), 1, false ) . ' /> 启用</label></td></tr>';
    echo '</table>';

    echo '<h3>微信支付</h3><table class="form-table">';
    echo '<tr><th>AppID</th><td><input type="text" name="wechat_appid" value="' . esc_attr( wpnovc_option( 'wechat_appid' ) ) . '" class="regular-text" /></td></tr>';
    echo '<tr><th>商户号(MchID)</th><td><input type="text" name="wechat_mchid" value="' . esc_attr( wpnovc_option( 'wechat_mchid' ) ) . '" class="regular-text" /></td></tr>';
    echo '<tr><th>APIv3密钥</th><td><input type="text" name="wechat_key" value="' . esc_attr( wpnovc_option( 'wechat_key' ) ) . '" class="regular-text" /></td></tr>';
    echo '<tr><th>证书序列号</th><td><input type="text" name="wechat_serial" value="' . esc_attr( wpnovc_option( 'wechat_serial' ) ) . '" class="regular-text" /></td></tr>';
    echo '<tr><th>商户私钥</th><td><textarea name="wechat_private_key" rows="4" class="large-text">' . esc_textarea( wpnovc_option( 'wechat_private_key' ) ) . '</textarea></td></tr>';
    echo '</table>';

    echo '<h3>支付宝</h3><table class="form-table">';
    echo '<tr><th>AppID</th><td><input type="text" name="alipay_appid" value="' . esc_attr( wpnovc_option( 'alipay_appid' ) ) . '" class="regular-text" /></td></tr>';
    echo '<tr><th>应用私钥</th><td><textarea name="alipay_private_key" rows="4" class="large-text">' . esc_textarea( wpnovc_option( 'alipay_private_key' ) ) . '</textarea></td></tr>';
    echo '<tr><th>支付宝公钥</th><td><textarea name="alipay_public_key" rows="4" class="large-text">' . esc_textarea( wpnovc_option( 'alipay_public_key' ) ) . '</textarea></td></tr>';
    echo '</table>';

    echo '<h3>PayPal</h3><table class="form-table">';
    echo '<tr><th>Client ID</th><td><input type="text" name="paypal_client_id" value="' . esc_attr( wpnovc_option( 'paypal_client_id' ) ) . '" class="regular-text" /></td></tr>';
    echo '<tr><th>Secret</th><td><input type="text" name="paypal_secret" value="' . esc_attr( wpnovc_option( 'paypal_secret' ) ) . '" class="regular-text" /></td></tr>';
    echo '<tr><th>模式</th><td><select name="paypal_mode"><option value="sandbox" ' . selected( wpnovc_option( 'paypal_mode' ), 'sandbox', false ) . '>Sandbox</option><option value="live" ' . selected( wpnovc_option( 'paypal_mode' ), 'live', false ) . '>Live</option></select></td></tr>';
    echo '</table>';

    echo '<h3>虎皮椒</h3><table class="form-table">';
    echo '<tr><th>AppID</th><td><input type="text" name="hupijiao_appid" value="' . esc_attr( wpnovc_option( 'hupijiao_appid' ) ) . '" class="regular-text" /></td></tr>';
    echo '<tr><th>Key</th><td><input type="text" name="hupijiao_key" value="' . esc_attr( wpnovc_option( 'hupijiao_key' ) ) . '" class="regular-text" /></td></tr>';
    echo '</table>';

    submit_button( '保存支付设置' );
    echo '</form>';
}

// ── 活动 ──
function wpnovc_shop_activity() {
    echo '<h2>活动管理</h2>';
    echo '<p>创建和管理折扣活动。例如：新用户首单折扣、节日促销等。</p>';

    global $wpdb;
    $table = $wpdb->prefix . 'wpnovc_balance_log'; // 复用作为活动存储

    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer( 'wpnovc_shop' ) ) {
        wpnovc_update_option( 'activity_discount', sanitize_text_field( $_POST['discount_rule'] ?? '' ) );
        wpnovc_update_option( 'activity_name', sanitize_text_field( $_POST['activity_name'] ?? '' ) );
        echo '<div class="notice notice-success"><p>活动已保存</p></div>';
    }

    echo '<form method="post">';
    wp_nonce_field( 'wpnovc_shop' );
    echo '<table class="form-table">';
    echo '<tr><th>活动名称</th><td><input type="text" name="activity_name" value="' . esc_attr( wpnovc_option( 'activity_name', '' ) ) . '" class="regular-text" /></td></tr>';
    echo '<tr><th>折扣规则</th><td><input type="text" name="discount_rule" value="' . esc_attr( wpnovc_option( 'activity_discount', '' ) ) . '" class="regular-text" placeholder="例如: 5-8,10-7 (5个8折,10个7折)" /></td></tr>';
    echo '</table>';
    submit_button( '保存活动' );
    echo '</form>';
}

// ── 手动充值 ──
function wpnovc_shop_recharge() {
    echo '<h2>手动充值</h2>';
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer( 'wpnovc_shop' ) ) {
        $uid    = intval( $_POST['user_id'] );
        $amount = intval( $_POST['amount'] );
        if ( $uid && $amount ) {
            wpnovc_add_balance( $uid, $amount, '', '管理员手动充值' );
            echo '<div class="notice notice-success"><p>充值成功！用户 ' . $uid . ' 余额增加 ' . number_format( $amount / 100, 2 ) . ' 元</p></div>';
        }
    }
    echo '<form method="post">';
    wp_nonce_field( 'wpnovc_shop' );
    echo '<table class="form-table">';
    echo '<tr><th>用户ID</th><td><input type="number" name="user_id" required class="regular-text" /></td></tr>';
    echo '<tr><th>金额(分)</th><td><input type="number" name="amount" required class="regular-text" placeholder="1元=100分" /></td></tr>';
    echo '</table>';
    submit_button( '确认充值' );
    echo '</form>';
}

// ── 赠送会员 ──
function wpnovc_shop_vip_card() {
    echo '<h2>赠送会员</h2>';
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer( 'wpnovc_shop' ) ) {
        $uid      = intval( $_POST['user_id'] );
        $discount = intval( $_POST['discount'] );
        $days     = intval( $_POST['days'] );
        if ( $uid && $days ) {
            $expire = time() + $days * 86400;
            update_user_meta( $uid, 'wpnovc_vip', [ 'discount' => $discount, 'expire' => $expire, 'created' => time() ] );
            echo '<div class="notice notice-success"><p>会员已赠送！用户 ' . $uid . ' 有效期至 ' . date( 'Y-m-d', $expire ) . '</p></div>';
        }
    }
    echo '<form method="post">';
    wp_nonce_field( 'wpnovc_shop' );
    echo '<table class="form-table">';
    echo '<tr><th>用户ID</th><td><input type="number" name="user_id" required class="regular-text" /></td></tr>';
    echo '<tr><th>折扣(折)</th><td><input type="number" name="discount" value="8" required /> (8=八折)</td></tr>';
    echo '<tr><th>有效期(天)</th><td><input type="number" name="days" value="365" required /></td></tr>';
    echo '</table>';
    submit_button( '赠送会员' );
    echo '</form>';
}

// ── 佣金设置 ──
function wpnovc_shop_commission() {
    echo '<h2>佣金设置</h2>';
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer( 'wpnovc_shop' ) ) {
        wpnovc_update_option( 'commission_rate', intval( $_POST['commission_rate'] ?? 10 ) );
        wpnovc_update_option( 'min_cashout', intval( $_POST['min_cashout'] ?? 10000 ) );
        echo '<div class="notice notice-success"><p>佣金设置已保存</p></div>';
    }
    echo '<form method="post">';
    wp_nonce_field( 'wpnovc_shop' );
    echo '<table class="form-table">';
    echo '<tr><th>佣金比例(%)</th><td><input type="number" name="commission_rate" value="' . wpnovc_option( 'commission_rate', 10 ) . '" /> 销售额的百分比</td></tr>';
    echo '<tr><th>最低提现(分)</th><td><input type="number" name="min_cashout" value="' . wpnovc_option( 'min_cashout', 10000 ) . '" /> 1元=100分</td></tr>';
    echo '</table>';
    submit_button( '保存' );
    echo '</form>';
}

// ── 系统设置 ──
function wpnovc_shop_system() {
    echo '<h2>系统设置</h2>';
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer( 'wpnovc_shop' ) ) {
        $keys = [ 'wptrade_logo', 'wptrade_logo_path', 'wptrade_logo_id',
                  'login_url', 'not_login_click_buy', 'discount',
                  'buy_vip_url', 'recharge_url', 'coupon_url', 'enable_promotion' ];
        foreach ( $keys as $k ) {
            wpnovc_update_option( $k, sanitize_text_field( $_POST[ $k ] ?? '' ) );
        }
        echo '<div class="notice notice-success"><p>设置已保存</p></div>';
    }
    echo '<form method="post">';
    wp_nonce_field( 'wpnovc_shop' );
    echo '<table class="form-table">';
    echo '<tr><th>Logo URL</th><td><input type="text" name="wptrade_logo" value="' . esc_attr( wpnovc_option( 'wptrade_logo', '' ) ) . '" class="regular-text" /></td></tr>';
    echo '<tr><th>登录地址</th><td><input type="text" name="login_url" value="' . esc_attr( wpnovc_option( 'login_url', wp_login_url() ) ) . '" class="regular-text" /><br>未登录时提示登录的链接</td></tr>';
    echo '<tr><th>未登录点击购买</th><td><textarea name="not_login_click_buy" rows="2" class="large-text">' . esc_textarea( wpnovc_option( 'not_login_click_buy' ) ) . '</textarea><br>一段JS代码, onclick 执行</td></tr>';
    echo '<tr><th>折扣规则</th><td><input type="text" name="discount" value="' . esc_attr( wpnovc_option( 'discount', '' ) ) . '" class="regular-text" placeholder="5-8,10-7" /><br>5个8折10个7折: <code>5-8,10-7</code></td></tr>';
    echo '<tr><th>会员卡购买地址</th><td><input type="text" name="buy_vip_url" value="' . esc_attr( wpnovc_option( 'buy_vip_url', '' ) ) . '" class="regular-text" /></td></tr>';
    echo '<tr><th>充值地址</th><td><input type="text" name="recharge_url" value="' . esc_attr( wpnovc_option( 'recharge_url', '' ) ) . '" class="regular-text" /></td></tr>';
    echo '<tr><th>优惠券领取地址</th><td><input type="text" name="coupon_url" value="' . esc_attr( wpnovc_option( 'coupon_url', '' ) ) . '" class="regular-text" /></td></tr>';
    echo '<tr><th>启用推广</th><td><label><input type="checkbox" name="enable_promotion" value="1" ' . checked( wpnovc_option( 'enable_promotion' ), 1, false ) . ' /> 启用</label></td></tr>';
    echo '</table>';
    submit_button( '保存设置' );
    echo '</form>';
}

// ── 数据表 ──
function wpnovc_shop_table() {
    global $wpdb;
    $tables = [
        'wpnovc_goods'      => '商品表',
        'wpnovc_orders'     => '订单表',
        'wpnovc_balance_log'=> '余额流水表',
        'wpnovc_activity'   => '活动表',
        'wpnovc_bill'       => '账单表',
        'wpnovc_cashout'    => '提现表',
    ];

    if ( isset( $_GET['action'] ) && $_GET['action'] == 'update' && isset( $tables[ $_GET['table'] ] ) && check_admin_referer( 'wpnovc_shop_table' ) ) {
        wpnovc_create_payment_tables();
        echo '<div class="notice notice-success"><p>表结构已更新!</p></div>';
    }

    echo '<h2>数据表</h2>';
    echo '<table class="widefat striped"><thead><tr><th>表名</th><th>说明</th><th>状态</th><th>操作</th></tr></thead><tbody>';
    foreach ( $tables as $table => $label ) {
        $full = $wpdb->prefix . $table;
        $exists = $wpdb->get_var( "SHOW TABLES LIKE '$full'" ) == $full;
        echo '<tr><td><code>' . $full . '</code></td><td>' . $label . '</td>';
        echo '<td>' . ( $exists ? '<span style="color:green">✓</span>' : '<span style="color:red">✗</span>' ) . '</td>';
        echo '<td><a href="?page=wptrade&__tab=table&action=update&_wpnonce=' . wp_create_nonce('wpnovc_shop_table') . '&table=' . $table . '" class="button button-small">' . ( $exists ? '更新结构' : '创建表' ) . '</a></td></tr>';
    }
    echo '</tbody></table>';
}
