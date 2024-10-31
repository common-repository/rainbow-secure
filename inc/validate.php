<?php 
defined('ABSPATH') or die('Access Denied');
?>
<?php

if ( !function_exists( 'add_action' ) ) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    header( 'HTTP/1.0 403 Forbidden' );
    echo '<h1>' . esc_html__( 'Access Forbidden!', 'rainbow-secure' ) . '</h1>';
    exit();
}

require_once plugin_dir_path(__FILE__)."/../vendor/onelogin/php-saml/_toolkit_loader.php";
use OneLogin\Saml2\Settings;

require_once "compatibility.php";

function rainbow_secure_enqueue_styles() {
    // Check if we're on the page with the ?saml_validate_config parameter
    if (isset($_GET['saml_validate_config'])) {
        wp_enqueue_style('rainbow-secure-custom-style', plugins_url('/../assets/validate-style.css', __FILE__));
    }
}
add_action('wp_enqueue_scripts', 'rainbow_secure_enqueue_styles');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title><?php echo esc_html__('SSO/SAML Settings &lsaquo; Rainbow Secure &#8212; WordPress', 'rainbow-secure'); ?></title>
    <?php wp_head(); // Ensure that all enqueued scripts and styles are included ?>
</head>
<body>

<div class="wrap">
    <h1><?php echo esc_html__('Rainbow Secure SSO/SAML Settings validation', 'rainbow-secure'); ?></h1>
    <div class="section">
        <h2><?php echo esc_html__('General Settings', 'rainbow-secure'); ?></h2>
        <p><?php echo esc_html__('Debug mode', 'rainbow-secure') . ' ' . ($settings['debug'] ? '<strong>on</strong>. ' . esc_html__("In production turn it off", 'rainbow-secure') : '<strong>off</strong>'); ?></p>
        <p><?php echo esc_html__('Strict mode', 'rainbow-secure') . ' ' . ($settings['strict'] ? '<strong>on</strong>' : '<strong>off</strong>. ' . esc_html__("In production we recommend to turn it on.", 'rainbow-secure')); ?></p>
    </div>

    <?php
    $spPrivatekey = $settings['sp']['x509cert'];
    $spCert = $settings['sp']['privateKey'];

    try {
        $samlSettings = new Settings($settings);
        echo '<div class="section"><h2>' . esc_html__("SAML settings status", 'rainbow-secure') . '</h2><p>' . esc_html__("SAML settings are", 'rainbow-secure') . ' <strong>ok</strong>.</p></div>';
    } catch (\Exception $e) {
        echo '<div class="section"><h2>' . esc_html__("SAML settings error", 'rainbow-secure') . '</h2><p>' . esc_html__("Found errors while validating SAML settings info.", 'rainbow-secure') . '</p><p>' . esc_html($e->getMessage()) . '</p></div>';
    }

    $forcelogin = get_option('rainbow_secure_force_saml_login');
    if ($forcelogin) {
        echo '<div class="section"><h2>' . esc_html__("Force SAML Login", 'rainbow-secure') . '</h2><p>' . esc_html__("Force SAML Login is enabled, that means that the user will be redirected to the IdP before getting access to WordPress.", 'rainbow-secure') . '</p></div>';
    }

    $slo = get_option('rainbow_secure_single_log_out');
    if ($slo) {
        echo '<div class="section"><h2>' . esc_html__("Single Log Out", 'rainbow-secure') . '</h2><p>' . esc_html__("Single Log Out is enabled. If the SLO process fails, close your browser to be sure that session of the apps are closed.", 'rainbow-secure') . '</p></div>';
    } else {
        echo '<div class="section"><h2>' . esc_html__("Single Log Out Disabled", 'rainbow-secure') . '</h2><p>' . esc_html__("Single Log Out is disabled. If you log out from WordPress your session at the IdP keeps alive.", 'rainbow-secure') . '</p></div>';
    }

    $fileSystemKeyExists = file_exists(plugin_dir_path(__FILE__).'certs/sp.key');
    $fileSystemCertExists = file_exists(plugin_dir_path(__FILE__).'certs/sp.crt');
    if ($fileSystemKeyExists) {
        $privatekey_url = plugins_url('php/certs/sp.key', __DIR__);
        echo '<div class="section"><h2>' . esc_html__("Filesystem Private Key", 'rainbow-secure') . '</h2><p>' . esc_html__("There is a private key stored at the filesystem. Protect the 'certs' path. Nobody should be allowed to access:", 'rainbow-secure') . '</p><code>' . esc_html($privatekey_url) . '</code></div>';
    }

    if ($spPrivatekey && !empty($spPrivatekey)) {
        echo '<div class="section"><h2>' . esc_html__("Database Private Key", 'rainbow-secure') . '</h2><p>' . esc_html__("There is a private key stored at the database. (An attacker could own your database and get it. Take care)", 'rainbow-secure') . '</p></div>';
    }

    if (($spPrivatekey && !empty($spPrivatekey) && $fileSystemKeyExists) ||
        ($spCert && !empty($spCert) && $fileSystemCertExists)) {
        echo '<div class="section"><h2>' . esc_html__("Private Key Priority", 'rainbow-secure') . '</h2><p>' . esc_html__("Private key/certs stored on database have priority over the private key/cert stored at filesystem", 'rainbow-secure') . '</p></div>';
    }

    $autocreate = get_option('rainbow_secure_user_registration');
    $updateuser = get_option('rainbow_secure_update_user_data');

    if ($autocreate) {
        echo '<div class="section"><h2>' . esc_html__("User Auto-Creation", 'rainbow-secure') . '</h2><p>' . esc_html__("User will be created if not exists, based on the data sent by the IdP.", 'rainbow-secure') . '</p></div>';
    } else {
        echo '<div class="section"><h2>' . esc_html__("User Creation Disabled", 'rainbow-secure') . '</h2><p>' . esc_html__("If the user not exists, access is prevented.", 'rainbow-secure') . '</p></div>';
    }

    if ($updateuser) {
        echo '<div class="section"><h2>' . esc_html__("User Auto-Update", 'rainbow-secure') . '</h2><p>' . esc_html__("User account will be updated with the data sent by the IdP.", 'rainbow-secure') . '</p></div>';
    }

    if ($autocreate || $updateuser) {
        echo '<div class="section"><h2>' . esc_html__("Attribute and Role Mapping", 'rainbow-secure') . '</h2><p>' . esc_html__("It is important to set the attribute and the role mapping before auto-provisioning or updating an account.", 'rainbow-secure') . '</p></div>';
    }

    $attr_mappings = array (
        'rainbow_secure_username' => esc_html__('Username', 'rainbow-secure'),
        'rainbow_secure_email' => esc_html__('E-mail', 'rainbow-secure'),
        'rainbow_secure_first_name' => esc_html__('First Name', 'rainbow-secure'),
        'rainbow_secure_last_name' => esc_html__('Last Name', 'rainbow-secure'),
        'rainbow_secure_nickname' => esc_html__('Nickname', 'rainbow-secure'),
        'rainbow_secure_role' => esc_html__('Role', 'rainbow-secure'),
    );

    $account_matcher = get_option('rainbow_secure_account_matcher', 'username');

    $lacked_attr_mappings = array();
    foreach ($attr_mappings as $field => $name) {
        $value = get_option($field);
        if (empty($value)) {
            if ($account_matcher == 'username' && $field == 'rainbow_secure_username') {
                echo '<div class="section"><h2>' . esc_html__("Missing Username Mapping", 'rainbow-secure') . '</h2><p>' . esc_html__("Username mapping is required in order to enable the SAML Single Sign On", 'rainbow-secure') . '</p></div>';
            }
            if ($account_matcher == 'email' && $field == 'rainbow_secure_email') {
                echo '<div class="section"><h2>' . esc_html__("Missing Email Mapping", 'rainbow-secure') . '</h2><p>' . esc_html__("E-mail mapping is required in order to enable the SAML Single Sign On", 'rainbow-secure') . '</p></div>';
            }
            $lacked_attr_mappings[] = $name;
        }
    }

    if (!empty($lacked_attr_mappings)) {
        echo '<div class="section"><h2>' . esc_html__("Attributes without Mapping", 'rainbow-secure') . '</h2><p>' . esc_html__("Notice that there are attributes without mapping:", 'rainbow-secure') . '</p><p>' . wp_kses(implode('<br>', $lacked_attr_mappings), array('br' => array())) . '</p></div>';
    }

    $lacked_role_mappings = array();
    $lacked_role_orders = array();
    foreach (rainbow_secure_wp_roles()->get_names() as $roleid => $name) {
        $value = get_option('rainbow_secure_role_mapping_'.$roleid);
        if (empty($value)) {
            $lacked_role_mappings[] = $name;
        }
        $value = get_option('rainbow_secure_role_order_'.$roleid);
        if (empty($value)) {
            $lacked_role_orders[] = $name;
        }
    }

    if (!empty($lacked_role_mappings)) {
        echo '<div class="section"><h2>' . esc_html__("Roles without Mapping", 'rainbow-secure') . '</h2><p>' . esc_html__("Notice that there are roles without mapping:", 'rainbow-secure') . '</p><p>' . wp_kses(implode('<br>', $lacked_role_mappings), array('br' => array())) . '</p></div>';
    }

    if (!empty($lacked_role_orders)) {
        echo '<div class="section"><h2>' . esc_html__("Roles without Ordering", 'rainbow-secure') . '</h2><p>' . esc_html__("Notice that there are roles without ordering:", 'rainbow-secure') . '</p><p>' . wp_kses(implode('<br>', $lacked_role_orders), array('br' => array())) . '</p></div>';
    }
    ?>
</div>
<?php wp_footer(); // Ensure that all necessary footer scripts are included ?>

</body>
</html>
