<?php

/**
 * @package SMS_OTP_Verification
 * @version 1.0
 */


/**
 * Plugin Name: SMS OTP Verification
 * Description: A plugin to send OTP via SMS for verification.The OTP verification feature, as the name suggests, verifies the Mobile number or Email address of users by sending a code during registration, login as well as contact form submissions. This feature comes in handy by preventing users from registering with fake mobile numbers or email addresses.
 * Plugin URL: https://sbtechbd.xyz/
 * Author: Surata Debnath
 * Author URL: https://subrata6630.github.io 
 * Version: 1.0
 * Text Domain: sms-otp-verification
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Include the Twilio PHP library
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

use Twilio\Rest\Client;

// Define the OTP expiration time in seconds
define('OTP_EXPIRATION_TIME', 300); // 5 minutes

// Include necessary files
include_once(plugin_dir_path(__FILE__) . 'includes/admin-settings.php');
include_once(plugin_dir_path(__FILE__) . 'includes/auto-updates.php');


// Enqueue the scripts
function enqueue_otp_scripts()
{
    wp_enqueue_script('otp-script', plugin_dir_url(__FILE__) . 'js/otp.js', array('jquery'), '1.0', true);
    wp_localize_script('otp-script', 'otp_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    wp_enqueue_style('intl-tel-input-css', 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/css/intlTelInput.css');
    wp_enqueue_script('intl-tel-input-js', 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/js/intlTelInput.min.js', array('jquery'), null, true);
    wp_enqueue_script('intl-tel-input-utils-js', 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/js/utils.js', array('intl-tel-input-js'), null, true);
}
add_action('login_enqueue_scripts', 'enqueue_otp_scripts');

// Display OTP fields on the login form
function add_otp_fields()
{
?>
<p>
    <label for="phone_number"><?php _e('Phone Number', 'sms-otp') ?><br>
        <input type="tel" name="phone_number" id="phone_number" class="input" value="" size="20"></label>
</p>
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    var input = document.querySelector("#phone_number");
    window.intlTelInput(input, {
        initialCountry: "auto",
        geoIpLookup: function(callback) {
            fetch('https://ipinfo.io/json?token=YOUR_TOKEN_HERE')
                .then(response => response.json())
                .then(data => {
                    var countryCode = (data && data.country) ? data.country : "us";
                    callback(countryCode);
                })
                .catch(() => callback("us"));
        },
        utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/js/utils.js"
    });
});
</script>
<p>
    <label for="otp">OTP<br>
        <input type="text" name="otp" id="otp" class="input" value="" size="20"></label>
</p>
<p>
    <button type="button" id="send_otp_button" class="button">Send OTP</button>
    <button type="button" id="verify_otp_button" class="button">Verify OTP</button>

</p>
<?php
}
add_action('login_form', 'add_otp_fields');


// AJAX handlers for sending and verifying OTP
add_action('wp_ajax_send_otp', 'send_otp');
add_action('wp_ajax_nopriv_send_otp', 'send_otp');
add_action('wp_ajax_verify_otp', 'verify_otp');
add_action('wp_ajax_nopriv_verify_otp', 'verify_otp');

function send_otp()
{
    $phone = sanitize_text_field($_POST['phone']);
    $otp = rand(100000, 999999);

    // Save the OTP in the WordPress options table with an expiration time
    update_option('otp_' . $phone, array('otp' => $otp, 'expires' => time() + OTP_EXPIRATION_TIME));

    // Get Twilio credentials from the options table
    $account_sid = get_option('twilio_account_sid');
    $auth_token = get_option('twilio_auth_token');
    $twilio_number = get_option('twilio_phone_number');

    $client = new Client($account_sid, $auth_token);

    try {
        $client->messages->create(
            $phone,
            array(
                'from' => $twilio_number,
                'body' => 'Your OTP is ' . $otp
            )
        );
        wp_send_json_success(array('message' => 'OTP sent successfully'));
    } catch (Exception $e) {
        wp_send_json_error(array('message' => 'Failed to send OTP', 'error' => $e->getMessage()));
    }
}

function verify_otp()
{
    $phone = sanitize_text_field($_POST['phone']);
    $otp = sanitize_text_field($_POST['otp']);

    // Get the stored OTP and its expiration time
    $stored_otp = get_option('otp_' . $phone);

    if ($stored_otp && $stored_otp['otp'] == $otp && time() < $stored_otp['expires']) {
        delete_option('otp_' . $phone); // OTP is verified, remove it from the store
        wp_send_json_success(array('message' => 'OTP verified successfully'));
    } else {
        wp_send_json_error(array('message' => 'Invalid or expired OTP'));
    }
}

// Check OTP during login
function check_otp_on_login($user, $username, $password)
{
    if (isset($_POST['otp'])) {
        $phone = sanitize_text_field($_POST['phone_number']);
        $otp = sanitize_text_field($_POST['otp']);

        // Get the stored OTP and its expiration time
        $stored_otp = get_option('otp_' . $phone);

        if (!$stored_otp || $stored_otp['otp'] != $otp || time() >= $stored_otp['expires']) {
            return new WP_Error('invalid_otp', __('Invalid or expired OTP.'));
        } else {
            delete_option('otp_' . $phone); // OTP is verified, remove it from the store
            return $user;
        }
    } else {
        return new WP_Error('missing_otp', __('Please enter the OTP sent to your phone.'));
    }
}
add_filter('authenticate', 'check_otp_on_login', 30, 3);


// Added languages

add_action('plugins_loaded', 'load_my_plugin_textdomain');
function load_my_plugin_textdomain() {
    load_plugin_textdomain('your-textdomain', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}