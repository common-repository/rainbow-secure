<?php 
/**
 * @package RainbowSecure
 */

/*
Plugin Name: Rainbow Secure
Plugin URI: https://rainbowsecure.com
Description: Rainbow Secure MFA and SSO Plugin, allows you to secure your website with an interactive multi-layer security and get single sign on.
Version: 1.0.0
Author: Rainbow Secure
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: rainbow-secure
*/

defined('ABSPATH') or die('Access Denied');

// Define constants for SAML-related cookies
if (false === defined('RAINBOW_SECURE_SAML_LOGIN_COOKIE' )) {
    define('RAINBOW_SECURE_SAML_LOGIN_COOKIE', 'saml_login');
}
if (false === defined('RAINBOW_SECURE_SAML_NAMEID_COOKIE')) {
    define('RAINBOW_SECURE_SAML_NAMEID_COOKIE', 'saml_nameid');
}
if (false === defined('RAINBOW_SECURE_SAML_SESSIONINDEX_COOKIE')) {
    define('RAINBOW_SECURE_SAML_SESSIONINDEX_COOKIE', 'saml_sessionindex');
}
if (false === defined('RAINBOW_SECURE_SAML_NAMEID_FORMAT_COOKIE')) {
    define('RAINBOW_SECURE_SAML_NAMEID_FORMAT_COOKIE', 'saml_nameid_format');
}
if (false === defined('RAINBOW_SECURE_SAML_NAMEID_NAME_QUALIFIER_COOKIE')) {
    define('RAINBOW_SECURE_SAML_NAMEID_NAME_QUALIFIER_COOKIE', 'saml_nameid_name_qualifier');
}
if (false === defined('RAINBOW_SECURE_SAML_NAMEID_SP_NAME_QUALIFIER_COOKIE')) {
    define('RAINBOW_SECURE_SAML_NAMEID_SP_NAME_QUALIFIER_COOKIE', 'saml_nameid_name_sp_qualifier');
}

if (file_exists(dirname(__FILE__). '/vendor/autoload.php')){
    require_once dirname(__FILE__) .  '/vendor/autoload.php';
}
if (file_exists(dirname(__FILE__). '/inc/settings/RainbowSecureSettings.php')){
    require_once dirname(__FILE__) .  '/inc/settings/RainbowSecureSettings.php';
}
if (file_exists(dirname(__FILE__). '/inc/functions.php')){
    require_once dirname(__FILE__) .  '/inc/functions.php';
}


// Define CONSTANTS
define('RAINBOW_SECURE_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('RAINBOW_SECURE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RAINBOW_SECURE_PLUGIN', plugin_basename(__FILE__));

// activation and deactivation hooks
function rainbow_secure_activate_plugin(){
    rainbow_secure_Inc\Base\Activate::activate();
}
register_activation_hook(__FILE__, 'rainbow_secure_activate_plugin');

function rainbow_secure_deactivate_plugin(){
    rainbow_secure_Inc\Base\Deactivate::deactivate();
}
register_activation_hook(__FILE__, 'rainbow_secure_deactivate_plugin');

if (class_exists('rainbow_secure_Inc\\Init')){
    rainbow_secure_Inc\Init::register_services();
}
// function rainbow_secure_check_show_welcome_modal() {
//     if (get_transient('rainbow_secure_show_welcome_modal')) {
//         include plugin_dir_path(__FILE__) . 'templates/welcome-modal.php';
//         // Remove the transient so it doesn't show again
//         delete_transient('rainbow_secure_show_welcome_modal');
//     }
// }
// add_action('admin_footer', 'rainbow_secure_check_show_welcome_modal');



// Localization
add_action('init', 'rainbow_secure_saml_load_translations');

// SAML Message Checker
add_action('init', 'rainbow_secure_saml_checker', 1);


if (!rainbow_secure_is_saml_enabled()) {
    return;
}


// Handle SSO and SLO
$action = sanitize_key(isset($_REQUEST['action']) ? $_REQUEST['action'] : 'login');

if (isset($_COOKIE[RAINBOW_SECURE_SAML_LOGIN_COOKIE]) && get_option('rainbow_secure_single_log_out')) {
    add_action('init', 'rainbow_secure_saml_slo', 1);
}

if (isset($_GET['saml_sso'])) {
    add_action('init', 'rainbow_secure_saml_sso', 1);
} else {
    $execute_sso = false;
    $saml_actions = isset($_GET['saml_metadata']) || (isset($_SERVER['SCRIPT_NAME']) && strpos(sanitize_text_field(wp_unslash($_SERVER['SCRIPT_NAME'])), 'alternative_acs.php') !== FALSE);

    $wp_login_page = (isset($_SERVER['SCRIPT_NAME']) && strpos(sanitize_text_field(wp_unslash($_SERVER['SCRIPT_NAME'])), 'wp-login.php') !== FALSE) && $action == 'login' && !isset($_GET['loggedout']);

    $want_to_local_login = isset($_GET['normal']) || (isset($_POST['log']) && isset($_POST['pwd']));
    $want_to_reset = $action == 'lostpassword' || $action == 'rp' || $action == 'resetpass' || (isset($_GET['checkemail']) &&  $_GET['checkemail'] == 'confirm');

    $local_wp_actions = $want_to_local_login || $want_to_reset;

    if (!$local_wp_actions) {
        if ($wp_login_page) {
            $execute_sso = true;
        } else if (!$saml_actions && !isset($_GET['loggedout'])) {
            if (get_option('rainbow_secure_force_saml_login')) {
                add_action('init', 'rainbow_secure_saml_sso', 1);
            }
        }
    } else if ($local_wp_actions) {
        $prevent_local_login = get_option('rainbow_secure_prevent_use_of_normal', false);

        if (($want_to_local_login && $prevent_local_login) || ($want_to_reset && $prevent_reset_password)) {        
            $execute_sso = true;
        }
    }

    $keep_local_login_form = get_option('rainbow_secure_keep_local_login', false);
    if ($execute_sso && !$keep_local_login_form) {
        add_action('init', 'rainbow_secure_saml_sso', 1);
    } else {
        add_filter('login_message', 'rainbow_secure_saml_custom_login_footer');
    }
}

// Register form handling
add_action('register_form', 'saml_user_register', 1);

function rainbow_secure_enqueue_script() {
    wp_enqueue_script('rainbow-secure-hide-login-form', RAINBOW_SECURE_PLUGIN_URL . 'assets/hide-login-form.js', array('jquery'), null, true);
}

if (isset($_SERVER['SCRIPT_NAME']) && strpos(sanitize_text_field(wp_unslash($_SERVER['SCRIPT_NAME'])), 'wp-login.php') !== FALSE && $action == 'login' && !isset($_GET['normal'])) {
    if (!get_option('rainbow_secure_keep_local_login', false)) {
        add_action('login_enqueue_scripts', 'rainbow_secure_enqueue_script', 10);
    }
}

// welcome modal
function rainbow_secure_enqueue_modal_scripts() {
    wp_enqueue_style('rainbow_secure_modal_css', plugin_dir_url(__FILE__) . 'assets/modal.css');
    wp_enqueue_script('rainbow_secure_modal_js', plugin_dir_url(__FILE__) . 'assets/modal.js', array('jquery'), null, true);
}
add_action('admin_enqueue_scripts', 'rainbow_secure_enqueue_modal_scripts');

?>