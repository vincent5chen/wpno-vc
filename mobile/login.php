<?php
/**
 * 用户登录页 - Mobile
 * 支持用户名或邮箱登录
 */
$error = '';

if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['wpnovc_login'] ) ) {
    check_admin_referer( 'wpnovc_login', 'login_nonce' );
    $login_input = trim( $_POST['user_login'] ?? '' );
    $password    = $_POST['user_pass'] ?? '';

    if ( empty( $login_input ) || empty( $password ) ) {
        $error = '请输入用户名/邮箱和密码。';
    } else {
        $user_login = $login_input;
        if ( strpos( $login_input, '@' ) !== false ) {
            $user = get_user_by( 'email', $login_input );
            if ( $user ) {
                $user_login = $user->user_login;
            }
        }

        $creds = [
            'user_login'    => $user_login,
            'user_password' => $password,
            'remember'      => ! empty( $_POST['remember'] ),
        ];

        $user = wp_signon( $creds, is_ssl() );
        if ( is_wp_error( $user ) ) {
            $error = '用户名或密码错误。';
        } else {
            $redirect_to = ! empty( $_REQUEST['redirect_to'] )
                ? $_REQUEST['redirect_to'] : home_url();
            wp_safe_redirect( $redirect_to );
            exit;
        }
    }
}

get_header();
?>

<div class="container" style="max-width:400px;margin:40px auto;padding:0 16px">
    <h2>用户登录</h2>

    <?php if ( $error ) : ?>
        <div style="background:#f8d7da;padding:15px;border-radius:4px;margin-bottom:15px">
            <?php echo esc_html( $error ); ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field( 'wpnovc_login', 'login_nonce' ); ?>
        <input type="hidden" name="wpnovc_login" value="1" />
        <?php if ( ! empty( $_REQUEST['redirect_to'] ) ) : ?>
            <input type="hidden" name="redirect_to" value="<?php echo esc_url( $_REQUEST['redirect_to'] ); ?>" />
        <?php endif; ?>
        <p><input type="text" name="user_login" placeholder="用户名或邮箱" required style="width:100%;padding:10px;font-size:16px;box-sizing:border-box" /></p>
        <p><input type="password" name="user_pass" placeholder="密码" required style="width:100%;padding:10px;font-size:16px;box-sizing:border-box" /></p>
        <p style="font-size:13px">
            <label><input type="checkbox" name="remember" value="1" /> 记住我</label>
        </p>
        <p><button type="submit" style="width:100%;padding:12px;font-size:16px;box-sizing:border-box;background:#e74c3c;color:#fff;border:none;border-radius:4px;cursor:pointer">登录</button></p>
    </form>

    <p style="text-align:center;margin-top:15px">
        没有账号？<a href="<?php echo home_url( '/register/' ); ?>">请注册</a>
    </p>

    <div style="margin-top:30px;padding-top:20px;border-top:1px solid #ddd;font-size:12px;text-align:center">
        <?php the_privacy_policy_link( '<div>', '</div>' ); ?>

        <?php
        $languages = get_available_languages();
        if ( ! empty( $languages ) && apply_filters( 'login_display_language_dropdown', true ) ) : ?>
            <div style="margin-top:10px">
                <?php wp_dropdown_languages( array(
                    'id'                          => 'language-switcher-locales',
                    'name'                        => 'wp_lang',
                    'selected'                    => determine_locale(),
                    'show_available_translations' => false,
                    'explicit_option_en_us'       => true,
                    'languages'                   => $languages,
                ) ); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
