<?php
/**
 * 支付系统初始化
 *
 * @package wpno-vc
 */

// ── 数据库表创建 ──────────────────────────────────
add_action( 'after_switch_theme', 'wpnovc_create_payment_tables' );
add_action( 'admin_init', function () {
    if ( get_option( 'wpnovc_tables_version' ) < 1 ) {
        wpnovc_create_payment_tables();
    }
} );

function wpnovc_create_payment_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    $prefix  = $wpdb->prefix;

    // 商品表
    $sql_goods = "CREATE TABLE IF NOT EXISTS `{$prefix}wpnovc_goods` (
        `id` bigint(20) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL DEFAULT '',
        `price` int(11) NOT NULL DEFAULT 0 COMMENT '原价(分)',
        `now_price` int(11) NOT NULL DEFAULT 0 COMMENT '现价(分)',
        `goods_type` varchar(50) NOT NULL DEFAULT 'category' COMMENT '商品类型',
        `goods_category_id` bigint(20) NOT NULL DEFAULT 0 COMMENT '关联分类ID',
        `ext` text COMMENT '扩展字段JSON',
        `status` tinyint(1) NOT NULL DEFAULT 1,
        `create_time` int(11) NOT NULL DEFAULT 0,
        `update_time` int(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `goods_type` (`goods_type`),
        KEY `goods_category_id` (`goods_category_id`)
    ) $charset;";

    // 订单表
    $sql_orders = "CREATE TABLE IF NOT EXISTS `{$prefix}wpnovc_orders` (
        `id` bigint(20) NOT NULL AUTO_INCREMENT,
        `order_id` varchar(64) NOT NULL DEFAULT '',
        `user_id` bigint(20) NOT NULL DEFAULT 0,
        `goods_id` bigint(20) NOT NULL DEFAULT 0,
        `goods_type` varchar(50) NOT NULL DEFAULT '',
        `goods_title` varchar(255) NOT NULL DEFAULT '',
        `buy_num` int(11) NOT NULL DEFAULT 1,
        `unit_price` int(11) NOT NULL DEFAULT 0,
        `pay_amount` int(11) NOT NULL DEFAULT 0,
        `total_amount` int(11) NOT NULL DEFAULT 0,
        `discount_amount` int(11) NOT NULL DEFAULT 0,
        `coupon_id` bigint(20) NOT NULL DEFAULT 0,
        `coupon_amount` int(11) NOT NULL DEFAULT 0,
        `pay_type` varchar(50) NOT NULL DEFAULT 'balance',
        `order_status` varchar(20) NOT NULL DEFAULT 'new',
        `biz_order_id` varchar(128) NOT NULL DEFAULT '',
        `callback_data` text,
        `ext` text,
        `pay_time` int(11) NOT NULL DEFAULT 0,
        `create_time` int(11) NOT NULL DEFAULT 0,
        `update_time` int(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        UNIQUE KEY `order_id` (`order_id`),
        KEY `user_id` (`user_id`),
        KEY `goods_id` (`goods_id`),
        KEY `order_status` (`order_status`)
    ) $charset;";

    // 余额流水表
    $sql_balance = "CREATE TABLE IF NOT EXISTS `{$prefix}wpnovc_balance_log` (
        `id` bigint(20) NOT NULL AUTO_INCREMENT,
        `user_id` bigint(20) NOT NULL DEFAULT 0,
        `amount` int(11) NOT NULL DEFAULT 0 COMMENT '变动金额(分)',
        `balance` int(11) NOT NULL DEFAULT 0 COMMENT '变动后余额(分)',
        `type` varchar(20) NOT NULL DEFAULT 'recharge' COMMENT '类型: recharge/consume/refund',
        `order_id` varchar(64) NOT NULL DEFAULT '',
        `remark` varchar(255) NOT NULL DEFAULT '',
        `create_time` int(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`)
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql_goods );
    dbDelta( $sql_orders );
    dbDelta( $sql_balance );
    update_option( 'wpnovc_tables_version', 1 );
}

// ── 余额操作 ──────────────────────────────────────
function wpnovc_get_balance( $user_id = 0 ) {
    if ( ! $user_id ) $user_id = get_current_user_id();
    return (int) get_user_meta( $user_id, 'wpnovc_balance', true );
}

function wpnovc_add_balance( $user_id, $amount, $order_id = '', $remark = '' ) {
    $current = wpnovc_get_balance( $user_id );
    $new     = $current + $amount;
    update_user_meta( $user_id, 'wpnovc_balance', $new );

    global $wpdb;
    $wpdb->insert( $wpdb->prefix . 'wpnovc_balance_log', [
        'user_id'     => $user_id,
        'amount'      => $amount,
        'balance'     => $new,
        'type'        => $amount > 0 ? 'recharge' : 'consume',
        'order_id'    => $order_id,
        'remark'      => $remark,
        'create_time' => time(),
    ] );
    return $new;
}

function wpnovc_deduct_balance( $user_id, $amount, $order_id = '', $remark = '' ) {
    if ( wpnovc_get_balance( $user_id ) < $amount ) {
        throw new Exception( '余额不足' );
    }
    return wpnovc_add_balance( $user_id, -$amount, $order_id, $remark );
}

// ── 订单常量 ──────────────────────────────────────
define( 'WPNOVC_ORDER_NEW', 'new' );
define( 'WPNOVC_ORDER_PAID', 'paid' );
define( 'WPNOVC_ORDER_FINISHED', 'finished' );
define( 'WPNOVC_ORDER_CANCEL', 'cancel' );
define( 'WPNOVC_ORDER_REFUNDING', 'refunding' );
define( 'WPNOVC_ORDER_REFUNDED', 'refunded' );

// ── 商品类型常量 ──────────────────────────────────
define( 'WPNOVC_GOODS_CATEGORY', 'category' );
define( 'WPNOVC_GOODS_ARTICLE', 'article' );
define( 'WPNOVC_GOODS_RECHARGE', 'recharge' );
define( 'WPNOVC_GOODS_VIP', 'vip_card' );

// ── 支付方式常量 ──────────────────────────────────
define( 'WPNOVC_PAY_BALANCE', 'balance' );
define( 'WPNOVC_PAY_WECHAT', 'wechat' );
define( 'WPNOVC_PAY_ALIPAY', 'alipay' );
define( 'WPNOVC_PAY_PAYPAL', 'paypal' );

// ── 检查用户是否购买过某小说 ───────────────────────
function wpnovc_has_purchased( $user_id, $novel_id ) {
    global $wpdb;
    $count = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}wpnovc_orders 
         WHERE user_id = %d AND goods_id = %d AND order_status = %s",
        $user_id, $novel_id, WPNOVC_ORDER_FINISHED
    ) );
    return $count > 0;
}

// ── 创建订单 ──────────────────────────────────────
function wpnovc_create_order( $data ) {
    global $wpdb;
    $defaults = [
        'order_id'        => uniqid( 'wpo' ),
        'user_id'         => get_current_user_id(),
        'goods_id'        => 0,
        'goods_type'      => WPNOVC_GOODS_CATEGORY,
        'goods_title'     => '',
        'buy_num'         => 1,
        'unit_price'      => 0,
        'pay_amount'      => 0,
        'total_amount'    => 0,
        'pay_type'        => WPNOVC_PAY_BALANCE,
        'order_status'    => WPNOVC_ORDER_NEW,
        'create_time'     => time(),
        'update_time'     => time(),
    ];
    $data = wp_parse_args( $data, $defaults );
    $wpdb->insert( $wpdb->prefix . 'wpnovc_orders', $data );
    return $wpdb->insert_id;
}
