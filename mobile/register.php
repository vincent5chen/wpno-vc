<?php
/**
 * 用户注册页
 */
$error   = '';
$success = '';

// 先处理表单提交（在任何输出之前）
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['wpnovc_register'] ) ) {
    check_admin_referer( 'wpnovc_register', 'register_nonce' );
    $username = sanitize_user( $_POST['user_login'] ?? '' );
    $email    = sanitize_email( $_POST['user_email'] ?? '' );
    $password = $_POST['user_pass'] ?? '';
    $pwd2     = $_POST['user_pass2'] ?? '';

    if ( empty( $username ) || empty( $email ) || empty( $password ) ) {
        $error = '所有字段为必填项。';
    } elseif ( username_exists( $username ) ) {
        $error = '用户名已存在。';
    } elseif ( email_exists( $email ) ) {
        $error = '邮箱已注册。';
    } elseif ( $password !== $pwd2 ) {
        $error = '两次密码不一致。';
    } elseif ( strlen( $password ) < 6 ) {
        $error = '密码至少6位。';
    } else {
        $user_id = wp_insert_user( [
            'user_login'   => $username,
            'user_email'   => $email,
            'user_pass'    => $password,
            'nickname'     => $username,
            'display_name' => $username,
            'role'         => 'subscriber',
        ] );
        if ( is_wp_error( $user_id ) ) {
            $error = $user_id->get_error_message();
        } else {
            wp_set_current_user( $user_id );
            wp_set_auth_cookie( $user_id );
            wp_redirect( home_url() );
            exit;
        }
    }
}

get_header();
?>

<div class="container" style="max-width:400px;margin:40px auto;padding:0 16px">
    <h2>用户注册</h2>

    <?php if ( $error ) : ?>
        <div style="background:#f8d7da;padding:15px;border-radius:4px;margin-bottom:15px">
            <?php echo esc_html( $error ); ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field( 'wpnovc_register', 'register_nonce' ); ?>
        <input type="hidden" name="wpnovc_register" value="1" />
        <p><input type="text" name="user_login" placeholder="用户名" required style="width:100%;padding:12px;font-size:16px;box-sizing:border-box;box-sizing:border-box" /></p>
        <p><input type="email" name="user_email" placeholder="邮箱" required style="width:100%;padding:12px;font-size:16px;box-sizing:border-box;box-sizing:border-box" /></p>
        <p><input type="password" name="user_pass" placeholder="密码（至少6位）" required style="width:100%;padding:12px;font-size:16px;box-sizing:border-box;box-sizing:border-box" /></p>
        <p><input type="password" name="user_pass2" placeholder="确认密码" required style="width:100%;padding:12px;font-size:16px;box-sizing:border-box;box-sizing:border-box" /></p>
        <p><button type="submit" style="width:100%;padding:12px;font-size:16px;box-sizing:border-box;background:#e74c3c;color:#fff;border:none;border-radius:4px;cursor:pointer">注册</button></p>
    </form>

    <p style="text-align:center;margin-top:15px">
        已有账号？<a href="<?php echo home_url( '/login/' ); ?>">登录</a>
    </p>
</div>

<?php get_footer(); ?>
