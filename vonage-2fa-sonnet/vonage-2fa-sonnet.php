<?php
/*
Plugin Name: Vonage 2FA 
Description: Two-Factor Authentication using Vonage Verify API - Clean implementation based on working Python code
Version: 1.0.0
Author: Zimrri Gudino
Author URI: https://zimrri.com
License: Apache 2.0
License URI: https://www.apache.org/licenses/LICENSE-2.0
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('VONAGE_2FA_SONNET_VERSION', '1.0.0');
define('VONAGE_2FA_SONNET_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VONAGE_2FA_SONNET_PLUGIN_PATH', plugin_dir_path(__FILE__));

class Vonage2FASonnet {
    
    private $api_key;
    private $api_secret;
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('show_user_profile', array($this, 'user_profile_fields'));
        add_action('edit_user_profile', array($this, 'user_profile_fields'));
        add_action('personal_options_update', array($this, 'save_user_profile_fields'));
        add_action('edit_user_profile_update', array($this, 'save_user_profile_fields'));
        add_action('authenticate', array($this, 'authenticate_user'), 50, 3);
        
        // Load API credentials
        $options = get_option('vonage_2fa_sonnet_settings');
        $this->api_key = isset($options['api_key']) ? $options['api_key'] : '';
        $this->api_secret = isset($options['api_secret']) ? $options['api_secret'] : '';
    }
    
    public function init() {
        if (!session_id()) {
            session_start();
        }
    }
    
    public function admin_menu() {
        add_options_page(
            'Vonage 2FA Sonnet Settings',
            'Vonage 2FA Sonnet',
            'manage_options',
            'vonage-2fa-sonnet',
            array($this, 'admin_page')
        );
    }
    
    public function admin_init() {
        register_setting('vonage_2fa_sonnet_settings', 'vonage_2fa_sonnet_settings');
        
        add_settings_section(
            'vonage_2fa_sonnet_main',
            'Vonage API Settings',
            array($this, 'settings_section_callback'),
            'vonage-2fa-sonnet'
        );
        
        add_settings_field(
            'api_key',
            'API Key',
            array($this, 'api_key_callback'),
            'vonage-2fa-sonnet',
            'vonage_2fa_sonnet_main'
        );
        
        add_settings_field(
            'api_secret',
            'API Secret',
            array($this, 'api_secret_callback'),
            'vonage-2fa-sonnet',
            'vonage_2fa_sonnet_main'
        );
    }
    
    public function settings_section_callback() {
        echo '<p>Enter your Vonage API credentials. You can get these from your <a href="https://dashboard.nexmo.com/" target="_blank">Vonage Dashboard</a>.</p>';
    }
    
    public function api_key_callback() {
        $options = get_option('vonage_2fa_sonnet_settings');
        $value = isset($options['api_key']) ? $options['api_key'] : '';
        echo '<input type="text" name="vonage_2fa_sonnet_settings[api_key]" value="' . esc_attr($value) . '" class="regular-text" />';
    }
    
    public function api_secret_callback() {
        $options = get_option('vonage_2fa_sonnet_settings');
        $value = isset($options['api_secret']) ? $options['api_secret'] : '';
        echo '<input type="text" name="vonage_2fa_sonnet_settings[api_secret]" value="' . esc_attr($value) . '" class="regular-text" />';
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Vonage 2FA Sonnet Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('vonage_2fa_sonnet_settings');
                do_settings_sections('vonage-2fa-sonnet');
                submit_button();
                ?>
            </form>
            
            <h2>Test API Connection</h2>
            <p>Use this to test your API credentials:</p>
            <button type="button" id="test-api" class="button">Test API Connection</button>
            <div id="test-result"></div>
            
            <script>
            document.getElementById('test-api').addEventListener('click', function() {
                var resultDiv = document.getElementById('test-result');
                resultDiv.innerHTML = 'Testing...';
                
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=vonage_2fa_test_api&nonce=<?php echo wp_create_nonce('vonage_2fa_test'); ?>'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        resultDiv.innerHTML = '<div class="notice notice-success"><p>✅ API connection successful!</p></div>';
                    } else {
                        resultDiv.innerHTML = '<div class="notice notice-error"><p>❌ API test failed: ' + data.data + '</p></div>';
                    }
                })
                .catch(error => {
                    resultDiv.innerHTML = '<div class="notice notice-error"><p>❌ Error: ' + error + '</p></div>';
                });
            });
            </script>
        </div>
        <?php
    }
    
    public function user_profile_fields($user) {
        $phone = get_user_meta($user->ID, 'vonage_2fa_phone', true);
        $enabled = get_user_meta($user->ID, 'vonage_2fa_enabled', true);
        ?>
        <h3>Vonage Two-Factor Authentication</h3>
        <table class="form-table">
            <tr>
                <th><label for="vonage_2fa_phone">Phone Number</label></th>
                <td>
                    <input type="tel" name="vonage_2fa_phone" id="vonage_2fa_phone" value="<?php echo esc_attr($phone); ?>" class="regular-text" />
                    <p class="description">Enter your phone number with country code (e.g., 1234567890 for US numbers)</p>
                </td>
            </tr>
            <tr>
                <th><label for="vonage_2fa_enabled">Enable 2FA</label></th>
                <td>
                    <input type="checkbox" name="vonage_2fa_enabled" id="vonage_2fa_enabled" value="1" <?php checked($enabled, '1'); ?> />
                    <label for="vonage_2fa_enabled">Enable two-factor authentication for my account</label>
                </td>
            </tr>
        </table>
        <?php
    }
    
    public function save_user_profile_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }
        
        $phone = sanitize_text_field($_POST['vonage_2fa_phone']);
        $enabled = isset($_POST['vonage_2fa_enabled']) ? '1' : '0';
        
        // Validate phone number
        if ($enabled === '1' && !$this->validate_phone_number($phone)) {
            add_action('user_profile_update_errors', function($errors) {
                $errors->add('vonage_2fa_phone_error', 'Please enter a valid phone number with country code (digits only, e.g., 16193278653)');
            });
            return false;
        }
        
        update_user_meta($user_id, 'vonage_2fa_phone', $phone);
        update_user_meta($user_id, 'vonage_2fa_enabled', $enabled);
    }
    
    private function validate_phone_number($phone) {
        // Remove any non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        // Check if it's between 10-15 digits (international format)
        return preg_match('/^[0-9]{10,15}$/', $phone);
    }
    
    public function authenticate_user($user, $username, $password) {
        // Check if we're verifying a 2FA code first
        if (isset($_POST['vonage_2fa_code']) && isset($_POST['vonage_2fa_request_id'])) {
            $wp_user = get_user_by('login', $username);
            if ($wp_user) {
                return $this->verify_2fa_code($wp_user, $_POST['vonage_2fa_code'], $_POST['vonage_2fa_request_id']);
            }
        }
        
        // Let WordPress validate the password first
        // Only proceed with 2FA if password validation was successful
        if (is_wp_error($user) || !$user) {
            return $user; // Let WordPress handle the error
        }
        
        // At this point, username/password are correct
        $wp_user = $user; // Use the validated user object
        
        $enabled = get_user_meta($wp_user->ID, 'vonage_2fa_enabled', true);
        
        if ($enabled !== '1') {
            return $user;
        }
        
        // Send 2FA code
        return $this->send_2fa_code($wp_user);
    }
    
    private function send_2fa_code($user) {
        $phone = get_user_meta($user->ID, 'vonage_2fa_phone', true);
        
        if (empty($phone)) {
            wp_die('Please configure your phone number in your profile before using 2FA.');
        }
        
        // Make API call to Vonage (using working Nexmo endpoint)
        // The old api.nexmo.com endpoint still works while api.vonage.com gives 403
        $url = 'https://api.nexmo.com/verify/json';
        $data = array(
            'api_key' => $this->api_key,
            'api_secret' => $this->api_secret,
            'number' => $phone,
            'brand' => 'VonageWordPress2FA'
        );
        
        // Optional: Enable for debugging
        // error_log('Vonage 2FA Sonnet: Sending verification to ' . substr($phone, 0, -4) . '****');
        
        $response = wp_remote_post($url, array(
            'body' => $data,
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
                'User-Agent' => 'WordPress-Vonage-2FA-Sonnet/' . VONAGE_2FA_SONNET_VERSION
            )
        ));
        
        if (is_wp_error($response)) {
            error_log('Vonage 2FA Sonnet: WP Error: ' . $response->get_error_message());
            wp_die('Failed to send verification code. Please try again.');
        }
        
        $body = wp_remote_retrieve_body($response);
        $http_code = wp_remote_retrieve_response_code($response);
        
        // Optional: Enable for debugging
        // error_log('Vonage 2FA Sonnet: HTTP Code: ' . $http_code);
        // error_log('Vonage 2FA Sonnet: Response: ' . $body);
        
        $result = json_decode($body, true);
        
        if (!$result || !isset($result['status'])) {
            wp_die('Invalid response from verification service.');
        }
        
        if ($result['status'] !== '0') {
            $error = isset($result['error_text']) ? $result['error_text'] : 'Unknown error';
            // Optional: Enable for debugging
            // error_log('Vonage 2FA Sonnet: API Error: ' . $error);
            
            // Handle concurrent verification error specially
            if (strpos($error, 'Concurrent verifications') !== false) {
                // Try to find the existing request ID from the error response
                // Sometimes Vonage returns the existing request_id in concurrent error responses
                if (isset($result['request_id'])) {
                    $request_id = $result['request_id'];
                    $_SESSION['vonage_2fa_request_id'] = $request_id;
                    $_SESSION['vonage_2fa_user_id'] = $user->ID;
                    // Optional: Enable for debugging
                    // error_log('Vonage 2FA Sonnet: Using existing request ID from concurrent error: ' . $request_id);
                    $this->show_2fa_form($user, $phone, 'A verification code was already sent to your phone. Please enter the code you received.');
                    exit;
                } else {
                    $this->show_2fa_form($user, $phone, 'A verification code was already sent to your phone. Please enter the code you received, or wait 2 minutes to request a new one.');
                    exit;
                }
            }
            
            wp_die('Failed to send verification code: ' . $error);
        }
        
        $request_id = $result['request_id'];
        $_SESSION['vonage_2fa_request_id'] = $request_id;
        $_SESSION['vonage_2fa_user_id'] = $user->ID;
        
        // Optional: Enable for debugging
        // error_log('Vonage 2FA Sonnet: Verification sent successfully. Request ID: ' . $request_id);
        
        // Show 2FA form
        $this->show_2fa_form($user, $phone);
        exit;
    }
    
    private function verify_2fa_code($user, $code, $request_id) {
        $saved_request_id = isset($_SESSION['vonage_2fa_request_id']) ? $_SESSION['vonage_2fa_request_id'] : '';
        $saved_user_id = isset($_SESSION['vonage_2fa_user_id']) ? $_SESSION['vonage_2fa_user_id'] : '';
        
        if ($request_id !== $saved_request_id || $user->ID != $saved_user_id) {
            wp_die('Invalid verification session.');
        }
        
        // Verify code with Vonage API (using working Nexmo endpoint)
        $url = 'https://api.nexmo.com/verify/check/json';
        $data = array(
            'api_key' => $this->api_key,
            'api_secret' => $this->api_secret,
            'request_id' => $request_id,
            'code' => $code
        );
        
        // Optional: Enable for debugging
        // error_log('Vonage 2FA Sonnet: Verifying code for request ID: ' . $request_id);
        
        $response = wp_remote_post($url, array(
            'body' => $data,
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
                'User-Agent' => 'WordPress-Vonage-2FA-Sonnet/' . VONAGE_2FA_SONNET_VERSION
            )
        ));
        
        if (is_wp_error($response)) {
            error_log('Vonage 2FA Sonnet: Verification WP Error: ' . $response->get_error_message());
            wp_die('Failed to verify code. Please try again.');
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        // Optional: Enable for debugging
        // error_log('Vonage 2FA Sonnet: Verification response: ' . $body);
        
        if (!$result || !isset($result['status'])) {
            wp_die('Invalid response from verification service.');
        }
        
        if ($result['status'] === '0') {
            // Success! Clear session and allow login
            unset($_SESSION['vonage_2fa_request_id']);
            unset($_SESSION['vonage_2fa_user_id']);
            // Optional: Enable for debugging
            // error_log('Vonage 2FA Sonnet: Verification successful for user ' . $user->user_login);
            return $user;
        } else {
            // Failed verification
            $error = isset($result['error_text']) ? $result['error_text'] : 'Invalid code';
            // Optional: Enable for debugging
            // error_log('Vonage 2FA Sonnet: Verification failed: ' . $error);
            $this->show_2fa_form($user, get_user_meta($user->ID, 'vonage_2fa_phone', true), $error);
            exit;
        }
    }
    
    private function show_2fa_form($user, $phone, $error = '') {
        $request_id = $_SESSION['vonage_2fa_request_id'];
        $masked_phone = substr($phone, 0, -4) . '****';
        
        wp_logout();
        nocache_headers();
        
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Two-Factor Authentication - <?php bloginfo('name'); ?></title>
            <?php wp_head(); ?>
            <style>
                body { background: #f1f1f1; font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif; }
                .login { width: 400px; padding: 8% 0 0; margin: auto; }
                .login form { background: #fff; max-width: 400px; margin: 0 auto 100px; padding: 45px; text-align: center; box-shadow: 0 0 20px 0 rgba(0, 0, 0, 0.2), 0 5px 5px 0 rgba(0, 0, 0, 0.24); border-radius: 8px; }
                .login input[type="text"] {
                    outline: 0;
                    background: #f9f9f9;
                    width: 100%;
                    border: 2px solid #ddd;
                    margin: 0 0 20px;
                    padding: 18px 20px;
                    box-sizing: border-box;
                    font-size: 18px;
                    text-align: center;
                    letter-spacing: 2px;
                    border-radius: 6px;
                    transition: border-color 0.3s ease;
                }
                .login input[type="text"]:focus {
                    border-color: #0073aa;
                    background: #fff;
                }
                .login button {
                    text-transform: uppercase;
                    outline: 0;
                    background: #0073aa;
                    width: 100%;
                    border: 0;
                    padding: 18px;
                    color: #FFFFFF;
                    font-size: 16px;
                    font-weight: 600;
                    cursor: pointer;
                    border-radius: 6px;
                    transition: background-color 0.3s ease;
                }
                .login button:hover { background: #005a87; }
                .error { color: #d63638; margin-bottom: 20px; padding: 12px; background: #ffeaea; border-radius: 4px; }
                .message { color: #00a32a; margin-bottom: 20px; padding: 12px; background: #eafaea; border-radius: 4px; line-height: 1.5; }
                h1 { margin: 0 0 30px; color: #333; font-size: 28px; font-weight: 600; }
                .code-hint { font-size: 12px; color: #666; margin-top: 8px; }
            </style>
        </head>
        <body>
            <div class="login">
                <form method="post" action="<?php echo esc_url(site_url('wp-login.php', 'login_post')); ?>">
                    <h1>Two-Factor Authentication</h1>
                    
                    <?php if ($error): ?>
                        <div class="error"><?php echo esc_html($error); ?></div>
                    <?php else: ?>
                        <div class="message">A verification code has been sent to your phone ending in <?php echo esc_html($masked_phone); ?></div>
                    <?php endif; ?>
                    
                    <input type="text" name="vonage_2fa_code" placeholder="000000" required maxlength="6" autocomplete="off" pattern="[0-9]{4,6}">
                    <div class="code-hint">Enter the 4-6 digit code sent to your phone</div>
                    
                    <input type="hidden" name="vonage_2fa_request_id" value="<?php echo esc_attr($request_id); ?>">
                    <input type="hidden" name="log" value="<?php echo esc_attr($user->user_login); ?>">
                    <input type="hidden" name="pwd" value="<?php echo esc_attr($user->user_pass); ?>">
                    <input type="hidden" name="redirect_to" value="<?php echo esc_attr(admin_url()); ?>">
                    
                    <button type="submit">Verify Code</button>
                </form>
            </div>
        </body>
        </html>
        <?php
    }
}

// AJAX handler for API testing
add_action('wp_ajax_vonage_2fa_test_api', 'vonage_2fa_test_api_handler');

function vonage_2fa_test_api_handler() {
    if (!wp_verify_nonce($_POST['nonce'], 'vonage_2fa_test')) {
        wp_die('Security check failed');
    }
    
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }
    
    $options = get_option('vonage_2fa_sonnet_settings');
    $api_key = isset($options['api_key']) ? $options['api_key'] : '';
    $api_secret = isset($options['api_secret']) ? $options['api_secret'] : '';
    
    if (empty($api_key) || empty($api_secret)) {
        wp_send_json_error('Please enter both API Key and API Secret');
    }
    
    // Test with a dummy phone number (this won't actually send SMS)
    // Use working Nexmo endpoint
    $url = 'https://api.nexmo.com/verify/json';
    $data = array(
        'api_key' => $api_key,
        'api_secret' => $api_secret,
        'number' => '1234567890', // Dummy number for testing
        'brand' => 'VonageWordPress2FA'
    );
    
    // Optional: Enable for debugging
    // error_log('Vonage 2FA Sonnet: Test API URL: ' . $url);
    // error_log('Vonage 2FA Sonnet: Test data: ' . json_encode(array_merge($data, ['api_secret' => '****'])));
    
    $response = wp_remote_post($url, array(
        'body' => $data,
        'timeout' => 10,
        'headers' => array(
            'Content-Type' => 'application/x-www-form-urlencoded',
            'User-Agent' => 'WordPress-Vonage-2FA-Sonnet/' . VONAGE_2FA_SONNET_VERSION
        )
    ));
    
    if (is_wp_error($response)) {
        wp_send_json_error('Connection failed: ' . $response->get_error_message());
    }
    
    $body = wp_remote_retrieve_body($response);
    $http_code = wp_remote_retrieve_response_code($response);
    $result = json_decode($body, true);
    
    if ($http_code === 200 && isset($result['status'])) {
        if ($result['status'] === '0' || $result['status'] === '3') {
            // Status 0 = success, Status 3 = invalid number (expected for dummy number)
            wp_send_json_success('API credentials are valid!');
        } else {
            $error = isset($result['error_text']) ? $result['error_text'] : 'Unknown error';
            wp_send_json_error('API Error: ' . $error);
        }
    } else {
        wp_send_json_error('Invalid response from API (HTTP ' . $http_code . ')');
    }
}

// Initialize the plugin
new Vonage2FASonnet();