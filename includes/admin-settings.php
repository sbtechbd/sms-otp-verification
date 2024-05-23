<?php
// Admin settings page
function otp_settings_page()
{
    add_options_page(
        'SMS OTP Settings',
        'SMS OTP Settings',
        'manage_options',
        'sms-otp-settings',
        'otp_settings_page_html'
    );
}
add_action('admin_menu', 'otp_settings_page');

function otp_settings_page_html()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['twilio_account_sid'])) {
        update_option('twilio_account_sid', sanitize_text_field($_POST['twilio_account_sid']));
        update_option('twilio_auth_token', sanitize_text_field($_POST['twilio_auth_token']));
        update_option('twilio_phone_number', sanitize_text_field($_POST['twilio_phone_number']));
        echo '<div class="updated">
        <p>Settings saved.</p>
    </div>';
    }

    $twilio_account_sid = get_option('twilio_account_sid');
    $twilio_auth_token = get_option('twilio_auth_token');
    $twilio_phone_number = get_option('twilio_phone_number');
?>
<div class="wrap">
    <h1>SMS OTP Settings</h1>
    <form method="post">
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Twilio Account SID</th>
                <td><input type="text" name="twilio_account_sid" value="<?php echo esc_attr($twilio_account_sid); ?>" />
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Twilio Auth Token</th>
                <td><input type="text" name="twilio_auth_token" value="<?php echo esc_attr($twilio_auth_token); ?>" />
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Twilio Phone Number</th>
                <td><input type="text" name="twilio_phone_number"
                        value="<?php echo esc_attr($twilio_phone_number); ?>" /></td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>
<?php
}