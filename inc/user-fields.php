<?php
/**
 * 用户自定义字段 - 余额等
 *
 * @package wpno-vc
 */

add_action( 'show_user_profile', 'wpnovc_user_profile_fields' );
add_action( 'edit_user_profile', 'wpnovc_user_profile_fields' );
function wpnovc_user_profile_fields( $user ) {
    $balance = get_user_meta( $user->ID, 'wpnovc_balance', true ) ?: 0;
    ?>
    <h3>💎 WPNOVC 用户信息</h3>
    <table class="form-table">
        <tr>
            <th><label>账户余额（分）</label></th>
            <td>
                <input type="number" name="wpnovc_balance" value="<?php echo intval( $balance ); ?>" />
                <span>当前余额: <?php echo number_format( $balance / 100, 2 ); ?> 元</span>
            </td>
        </tr>
    </table>
    <?php
}

add_action( 'personal_options_update', 'wpnovc_save_user_fields' );
add_action( 'edit_user_profile_update', 'wpnovc_save_user_fields' );
function wpnovc_save_user_fields( $user_id ) {
    if ( current_user_can( 'edit_user', $user_id ) && isset( $_POST['wpnovc_balance'] ) ) {
        update_user_meta( $user_id, 'wpnovc_balance', intval( $_POST['wpnovc_balance'] ) );
    }
}
