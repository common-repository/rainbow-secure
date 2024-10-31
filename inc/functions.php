<?php
defined('ABSPATH') or die('Access Denied');
if ( !function_exists( 'add_action' ) ) {
    echo 'Access Denied';
    exit;
}

use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Settings;

require_once "compatibility.php";

function rainbow_secure_saml_checker() {
    if (isset($_GET['saml_acs'])) {
        if (empty($_POST['SAMLResponse'])) {
            echo esc_html__("That ACS endpoint expects a SAMLResponse value sent using HTTP-POST binding. Nothing was found", 'rainbow-secure');
            exit();
        }
        rainbow_secure_saml_acs();
    } else if (isset($_GET['saml_sls'])) {
        rainbow_secure_saml_sls();
    } else if (isset($_GET['saml_metadata'])) {
        rainbow_secure_saml_metadata();
    } else if (isset($_GET['saml_validate_config'])) {
        rainbow_secure_saml_validate_config();
    }
}

function rainbow_secure_may_disable_saml() {
    if ((defined('WP_CLI') && WP_CLI) ||
        (function_exists('wp_doing_cron') && wp_doing_cron()) ||
        (function_exists('wp_doing_ajax') && wp_doing_ajax())
    ) {
        return true;
    }
    if (apply_filters('rainbow_secure_disable_saml_sso', false)) {
        return true;
    }

    return false;
}

function rainbow_secure_redirect_to_relaystate_if_trusted($url) {
    $trusted = false;
    $trustedDomainsOpt = get_option('rainbow_secure_trusted_url_domains', "");
    $trustedDomains = explode(",", trim($trustedDomainsOpt));
    $trusted = !empty($trustedDomains) && rainbow_secure_check_is_external_url_allowed($url, $trustedDomains);

    if (!$trusted) {
        $url = wp_validate_redirect($url, home_url());
    }

    wp_redirect($url);
    exit();
}

function rainbow_secure_check_is_external_url_allowed($url, $trustedSites = []) {
    if ($url[0] === '/') {
        $url = WP_Http::make_absolute_url($url, home_url());
    }

    if (!wp_http_validate_url($url)) {
        return false;
    }

    $components = wp_parse_url($url);
    $hostname = $components['host'];

    if ((isset($components['user']) && strpos($components['user'], '\\') !== false) ||
        (isset($components['pass']) && strpos($components['pass'], '\\') !== false)
    ) {
        return false;
    }

    if (isset($components['port']) &&
        (($components['scheme'] === 'http' && $components['port'] !== 80) ||
        ($components['scheme'] === 'https' && $components['port'] !== 443))
    ) {
        if (in_array($hostname.':'.$components['port'], $trustedSites, true)) {
            return true;
        }
    }

    if (in_array($hostname, $trustedSites, true)) {
        return true;
    }
}

if (!function_exists('is_plugin_active')) {
	//error_log("function does Not exists");
    require plugin_dir_path(__FILE__).'/../../../../wp-admin/includes/plugin.php';
}

function rainbow_secure_saml_custom_login_footer() {
    $saml_login_message = get_option('rainbow_secure_saml_link_message');
    if (empty($saml_login_message)) {
        $saml_login_message = "SAML Login";
    }
    $logo_url = get_option('rainbow_secure_saml_logo_url');
    $background_color = get_option('rainbow_secure_background_color', '#fff');

    $login_page = 'wp-login.php';
    if (is_plugin_active('wps-hide-login/wps-hide-login.php')) {
        $login_page = str_replace('wp-login.php', get_site_option('whl_page', 'login'), $login_page) . '/';
    }
    
    $redirect_to = isset($_GET['redirect_to']) ? '&redirect_to=' . urlencode(esc_url_raw(wp_unslash( $_GET['redirect_to'] ))) : '';
    echo '<div style="font-size: 110%;padding:8px;background: #fff;text-align: center;">
        <a href="'.esc_url(get_site_url().'/'.$login_page.'?saml_sso'.$redirect_to).'">';
        if (!empty($logo_url)) {
            echo '<img src="' . esc_url($logo_url) . '" alt="Logo" style="display: block;background: ' . esc_attr($background_color) . '; margin: 0 auto; max-width: 280px; max-height: 120px;"> <br>';
        }
    echo esc_html($saml_login_message). '</a></div>';
}

function rainbow_secure_saml_load_translations() {
    $domain = 'rainbow-secure';
    $mo_file = plugin_dir_path(dirname(__FILE__)) . 'lang/'.get_locale() . '/' . $domain  . '.mo';

    load_textdomain($domain, $mo_file); 
    load_plugin_textdomain($domain, false, dirname(plugin_basename(__FILE__)) . '/lang/'. get_locale() . '/');
}

function rainbow_secure_saml_lostpassword() {
    $target = get_option('rainbow_secure_lost_password');
    if (!empty($target)) {
        wp_redirect($target);
        exit;
    }
}

function rainbow_secure_saml_user_register() {
    $target = get_option('rainbow_secure_user_registration');
    if (!empty($target)) {
        wp_redirect($target);
        exit;
    }
}

// adding this for debugging
function rainbow_secure_saml_sso() {
    //error_log("Entering rainbow_secure_saml_sso");
    if (rainbow_secure_may_disable_saml()) {
        return true;
    }

    if (is_user_logged_in()) {
        //error_log("User is already logged in");
        return true;
    }
    $auth = rainbow_secure_initialize_saml();
    if ($auth == false) {
        //error_log("SAML Auth initialization failed");
        wp_redirect(home_url());
        exit();
    }

    if (isset($_GET["target"])) {
        // Sanitize the target URL
        $target_url = esc_url_raw( wp_unslash( $_GET["target"] ) );
        //error_log("Redirecting to target: " . $target_url);
        $auth->login($target_url);
    } elseif (isset($_GET['redirect_to'])) {
        // Sanitize the redirect URL
        $redirect_url = esc_url_raw( wp_unslash( $_GET['redirect_to'] ) );
        //error_log("Redirecting to: " . $redirect_url);
        $auth->login($redirect_url);
    } elseif (isset($_SERVER['REQUEST_URI']) && !isset($_GET['saml_sso'])) {
        // Sanitize the request URI
        $request_uri = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
        //error_log("Redirecting to request URI: " . $request_uri);
        $auth->login($request_uri);
    } else {
        //error_log("Initiating SAML login");
        $auth->login();
    }
    exit();
}

function rainbow_secure_saml_slo() {
    if (rainbow_secure_may_disable_saml()) {
        return true;
    }

    $slo = get_option('rainbow_secure_single_log_out');

    if (isset($_GET['action']) && sanitize_text_field(wp_unslash($_GET['action'])) === 'logout') {
        if (!$slo) {
            wp_logout();
            return false;
        } else {
            $nameId = null;
            $sessionIndex = null;
            $nameIdFormat = null;
            $samlNameIdNameQualifier = null;
            $samlNameIdSPNameQualifier = null;

            if (isset($_COOKIE[RAINBOW_SECURE_SAML_NAMEID_COOKIE])) {
                $nameId = sanitize_text_field(wp_unslash($_COOKIE[RAINBOW_SECURE_SAML_NAMEID_COOKIE]));
            }
            if (isset($_COOKIE[RAINBOW_SECURE_SAML_SESSIONINDEX_COOKIE])) {
                $sessionIndex = sanitize_text_field(wp_unslash($_COOKIE[RAINBOW_SECURE_SAML_SESSIONINDEX_COOKIE]));
            }
            if (isset($_COOKIE[RAINBOW_SECURE_SAML_NAMEID_FORMAT_COOKIE])) {
                $nameIdFormat = sanitize_text_field(wp_unslash($_COOKIE[RAINBOW_SECURE_SAML_NAMEID_FORMAT_COOKIE]));
            }
            if (isset($_COOKIE[RAINBOW_SECURE_SAML_NAMEID_NAME_QUALIFIER_COOKIE])) {
                $nameIdNameQualifier = sanitize_text_field(wp_unslash($_COOKIE[RAINBOW_SECURE_SAML_NAMEID_NAME_QUALIFIER_COOKIE]));
            }
            if (isset($_COOKIE[RAINBOW_SECURE_SAML_NAMEID_SP_NAME_QUALIFIER_COOKIE])) {
                $nameIdSPNameQualifier = sanitize_text_field(wp_unslash($_COOKIE[RAINBOW_SECURE_SAML_NAMEID_SP_NAME_QUALIFIER_COOKIE]));
            }

            $auth = rainbow_secure_initialize_saml();
            if ($auth == false) {
                wp_redirect(home_url());
                exit();
            }
            $auth->logout(home_url(), array(), $nameId, $sessionIndex, false, $nameIdFormat, $nameIdNameQualifier, $nameIdSPNameQualifier);
            return false;
        }
    }
}

function rainbow_secure_saml_role_order_get($role) {
    static $role_defaults = array(
        'administrator' => 1,
        'editor'        => 2,
        'author'        => 3,
        'contributor'   => 4,
        'subscriber'    => 5);
    $rv = get_option(sanitize_key('rainbow_secure_role_order_'.$role));
    if (empty($rv)) {
        if (isset($role_defaults[$role])) {
            return $role_defaults[$role];
        } else {
            return PHP_INT_MAX;
        }
    } else {
        return (int)$rv;
    }
}

function rainbow_secure_saml_role_order_compare($role1, $role2) {
    $r1 = rainbow_secure_saml_role_order_get($role1);
    $r2 = rainbow_secure_saml_role_order_get($role2);
    if ($r1 > $r2)
        return 1;
    else if ($r1 < $r2)
        return -1;
    else return 0;
}

function rainbow_secure_saml_acs() {
    if (rainbow_secure_may_disable_saml()) {
        return true;
    }

    $auth = rainbow_secure_initialize_saml();
    if ($auth == false) {
        wp_redirect(home_url());
        exit();
    }

    $auth->processResponse();

    $errors = $auth->getErrors();
    if (!empty($errors)) {
        $errorReason = $auth->getLastErrorReason();
        if (strpos($errorReason, 'Responder') !== false && strpos($errorReason, 'Passive') !== false) {
            $relayState = '';
            if (isset($_REQUEST['RelayState'])) {
                $relayState = esc_url_raw(wp_unslash($_REQUEST['RelayState']), ['https','http']);
            }

            if (empty($relayState)) {
                wp_redirect(home_url());
            } else {
                if (strpos($relayState, 'redirect_to') !== false) {
                    $query = wp_parse_url($relayState, PHP_URL_QUERY);
                    parse_str($query, $parameters);
                    rainbow_secure_redirect_to_relaystate_if_trusted(urldecode($parameters['redirect_to']));
                } else {
                    rainbow_secure_redirect_to_relaystate_if_trusted($relayState);
                }
            }
            exit();
        }

        echo '<br>' . esc_html__( 'There was at least one error processing the SAML Response:', 'rainbow-secure' );
        foreach($errors as $error) {
            echo esc_html($error).'<br>';
        }
        echo esc_html__('Contact the administrator', 'rainbow-secure' );
        exit();
    }

    $attrs = $auth->getAttributes();

    if (empty($attrs)) {
        $nameid = $auth->getNameId();
        if (empty($nameid)) {
            echo esc_html__( 'The SAMLResponse may contain NameID or AttributeStatement', 'rainbow-secure' );
            exit();
        }
        $username = sanitize_user($nameid);
        $email = sanitize_email($nameid);
    } else {
        $usernameMapping = get_option('rainbow_secure_username');
        $mailMapping = get_option('rainbow_secure_email'); 

        if (!empty($usernameMapping) && isset($attrs[$usernameMapping]) && !empty($attrs[$usernameMapping][0])){
            $username = sanitize_user($attrs[$usernameMapping][0]);
        }
        if (!empty($mailMapping) && isset($attrs[$mailMapping])  && !empty($attrs[$mailMapping][0])){
            $email = sanitize_email($attrs[$mailMapping][0]);
        }
    }

    if (empty($username)) {
        echo esc_html__('The username could not be retrieved from the IdP and is required', 'rainbow-secure' );
        exit();
    } else if (empty($email)) {
        echo esc_html__('The email could not be retrieved from the IdP and is required', 'rainbow-secure' );
        exit();
    } else if (!is_email($email)) {
        echo esc_html__( 'The email provided is invalid', 'rainbow-secure' );
        exit();
    } else {
        $userdata = array();
        $userdata['user_login'] = wp_slash($username);
        $userdata['user_email'] = wp_slash($email);
    }

    if (!empty($attrs)) {
        $firstNameMapping = get_option('rainbow_secure_first_name');
        $lastNameMapping = get_option('rainbow_secure_last_name');
        $nickNameMapping = get_option('rainbow_secure_nickname');
        $roleMapping = get_option('rainbow_secure_role');

        if (!empty($firstNameMapping) && isset($attrs[$firstNameMapping]) && !empty($attrs[$firstNameMapping][0])){
            $userdata['first_name'] = $attrs[$firstNameMapping][0];
        }

        if (!empty($lastNameMapping) && isset($attrs[$lastNameMapping])  && !empty($attrs[$lastNameMapping][0])){
            $userdata['last_name'] = $attrs[$lastNameMapping][0];
        }
        if (!empty($nickNameMapping) && isset($attrs[$nickNameMapping])  && !empty($attrs[$nickNameMapping][0])){
            $userdata['nickname'] = $attrs[$nickNameMapping][0];
        }

        if (!empty($roleMapping) && isset($attrs[$roleMapping])){
            $multiValued = get_option('rainbow_secure_multiple_role_one_saml_attribute_value', false);
            if ($multiValued && count($attrs[$roleMapping]) == 1) {
                $roleValues = array();
                $pattern = get_option('rainbow_secure_regular_expression');
                if (!empty($pattern)) {
                    preg_match_all($pattern, $attrs[$roleMapping][0], $roleValues);
                    if (!empty($roleValues)) {
                        $attrs[$roleMapping] = $roleValues[1];
                    }
                } else {
                    $roleValues = explode(';', $attrs[$roleMapping][0]);
                    $attrs[$roleMapping] = $roleValues;
                }
            }

            $all_roles = rainbow_secure_wp_roles()->get_names();
            $roles_found = array();

            foreach ($attrs[$roleMapping] as $samlRole) {
                $samlRole = trim($samlRole);
                if (empty($samlRole)) {
                    continue;
                }

                foreach ($all_roles as $role_value => $role_name) {
                    $role_value = sanitize_key($role_value);
                    $matchList = explode(',', get_option('rainbow_secure_role_order_'.$role_value));
                    if (in_array($samlRole, $matchList)) {
                        $roles_found[$role_value] = true;
                    }
                }
            }

            $multirole = get_site_option('rainbow_secure_multi_role_support');
            $userdata['roles'] = [];

            uksort($roles_found, 'rainbow_secure_saml_role_order_compare');
            foreach ($roles_found as $role_value => $_role_found) {
                $userdata['roles'][] = $role_value;
                if (!$multirole || is_multisite()) {
                    break;
                }
            }
        }
    }

    $matcher = get_option('rainbow_secure_account_matcher');
    $newuser = false;

    if (empty($matcher) || $matcher == 'username') {
        $matcherValue = $userdata['user_login'];
        $user_id = username_exists($matcherValue);
    } else {
        $matcherValue = $userdata['user_email'];
        $user_id = email_exists($matcherValue);
    }

    if ($user_id) {
        if (is_multisite()) {
            if (get_site_option('rainbow_secure_network_saml_global_jit')) {
                rainbow_secure_enroll_user_on_sites($user_id, $userdata['roles']);
            } else if (!is_user_member_of_blog($user_id)) {
                if (get_option('rainbow_secure_create_user_if_not_exists')) {
                    $blog_id = get_current_blog_id();
                    rainbow_secure_enroll_user_on_blogs($blog_id, $user_id, $userdata['roles']);
                } else {
                    $user_id = null;
                    echo sprintf(
                        // Translators: %1$s is the matcher value (e.g., username or email).
                        esc_html__( 'User provided by the IdP "%1$s" does not exist in this WordPress site and auto-provisioning is disabled.', 'rainbow-secure' ),
                        esc_html( $matcherValue )
                    );
                    exit();
                }
            }
        }

        if (get_option('rainbow_secure_update_user_data')) {
            $userdata['ID'] = $user_id;
            unset($userdata['$user_pass']);

            $roles = [];
            if (isset($userdata['roles'])) {
                if ($user_id == 1) {
                    unset($userdata['roles']);
                } else {
                    $roles = $userdata['roles'];
                    unset($userdata['roles']);
                }
            }

            $user_id = wp_update_user($userdata);
            if (isset($user_id) && !empty($roles)) {
                rainbow_secure_update_user_role($user_id, $roles);
            }
        }
    } else if (get_option('rainbow_secure_create_user_if_not_exists')) {
        $newuser = true;
        if (!validate_username($username)) {
            printf(
                // Translators: %1$s is the username provided by the IdP.
                esc_html__( 'The username provided by the IdP "%1$s" is not valid and cannot create the user in WordPress.', 'rainbow-secure' ),
                esc_html( $username )
            );
            exit();
        }

        if (!isset($userdata['roles'])) {
            $userdata['roles'] = array();
            $userdata['roles'][] = get_option('default_role');
        }
        $userdata['role'] = array_shift($userdata['roles']);
        $roles = $userdata['roles'];
        unset($userdata['roles']);
        $userdata['user_pass'] = wp_generate_password();
        $user_id = wp_insert_user($userdata);
        if ($user_id && !is_a($user_id, 'WP_Error')) {
            if (is_multisite()) {
                if (get_site_option('rainbow_secure_network_saml_global_jit')) {
                    rainbow_secure_enroll_user_on_sites($user_id, $userdata['roles']);
                } else {
                    $blog_id = get_current_blog_id();
                    rainbow_secure_enroll_user_on_blogs($blog_id, $user_id, $userdata['roles']);
                }
            } else if (!empty($roles)) {
                rainbow_secure_add_roles_to_user($user_id, $roles);
            }
        }
    } else {
        echo sprintf(
            // Translators: %1$s is the matcher value (e.g., username or email).
            esc_html__( 'User provided by the IdP "%1$s" does not exist in WordPress and auto-provisioning is disabled.', 'rainbow-secure' ),
            esc_html( $matcherValue )
        );
        exit();
    }

    if (is_a($user_id, 'WP_Error')) {
        $errors = $user_id->get_error_messages();
        foreach($errors as $error) {
            echo esc_html($error).'<br>';
        }
        exit();
    } else if ($user_id) {
        wp_set_current_user($user_id);
        
        $rememberme = false;
        $remembermeMapping = get_option('rainbow_secure_remember_me');
        if (!empty($remembermeMapping) && isset($attrs[$remembermeMapping]) && !empty($attrs[$remembermeMapping][0])) {
            $rememberme = in_array($attrs[$remembermeMapping][0], array(1, true, '1', 'yes', 'on')) ? true : false;
        }
        wp_set_auth_cookie($user_id, $rememberme);

        $secure = is_ssl();
        setcookie(RAINBOW_SECURE_SAML_LOGIN_COOKIE, 1, time() + MONTH_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN, $secure, true);
        setcookie(RAINBOW_SECURE_SAML_NAMEID_COOKIE, $auth->getNameId(), time() + MONTH_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN, $secure, true);
        setcookie(RAINBOW_SECURE_SAML_SESSIONINDEX_COOKIE, $auth->getSessionIndex(), time() + MONTH_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN, $secure, true);
        setcookie(RAINBOW_SECURE_SAML_NAMEID_FORMAT_COOKIE, $auth->getNameIdFormat(), time() + MONTH_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN, $secure, true);
        setcookie(RAINBOW_SECURE_SAML_NAMEID_NAME_QUALIFIER_COOKIE, $auth->getNameIdNameQualifier(), time() + MONTH_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN, $secure, true);
        setcookie(RAINBOW_SECURE_SAML_NAMEID_SP_NAME_QUALIFIER_COOKIE, $auth->getNameIdSPNameQualifier(), time() + MONTH_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN, $secure, true);
    }

    do_action('rainbow_secure_saml_attrs', $attrs, wp_get_current_user(), get_current_user_id(), $newuser);

    $trigger_wp_login_hook = get_site_option('rainbow_secure_trigger_login_hook');

    if ($trigger_wp_login_hook) {
        $user = get_user_by('id', $user_id);

        if (false !== $user) {
            do_action('wp_login', $user->user_login, $user);
        }
    }

    if (isset($_REQUEST['RelayState'])) {
        $relayState = esc_url_raw(wp_unslash($_REQUEST['RelayState']), ['https','http']);

        if (!empty($relayState) && ((substr($relayState, -strlen('/wp-login.php')) === '/wp-login.php') || (substr($relayState, -strlen('/alternative_acs.php')) === '/alternative_acs.php'))) {
            wp_redirect(home_url());
        } else {
            if (strpos($relayState, 'redirect_to') !== false) {
                $query = wp_parse_url($relayState, PHP_URL_QUERY);
                parse_str($query, $parameters);
                rainbow_secure_redirect_to_relaystate_if_trusted(urldecode($parameters['redirect_to']));
            } else {
                rainbow_secure_redirect_to_relaystate_if_trusted($relayState);
            }
        }
    } else {
        wp_redirect(home_url());
    }
    exit();
}

function rainbow_secure_saml_sls() {
    if (rainbow_secure_may_disable_saml()) {
        return true;
    }

    $auth = rainbow_secure_initialize_saml();
    if ($auth == false) {
        wp_redirect(home_url());
        exit();
    }

    $retrieve_parameters_from_server = get_option('rainbow_secure_retrieve_parameters_from_server', false);
    if (isset($_GET) && isset($_GET['SAMLRequest'])) {
        $auth->processSLO(false, null, $retrieve_parameters_from_server, 'wp_logout');
    } else {
        $auth->processSLO(false, null, $retrieve_parameters_from_server);
    }
    $errors = $auth->getErrors();
    if (empty($errors)) {
        wp_logout();
        $secure = is_ssl();
        setcookie(RAINBOW_SECURE_SAML_LOGIN_COOKIE, 0, time() - 3600, SITECOOKIEPATH, COOKIE_DOMAIN, $secure, true);
        setcookie(RAINBOW_SECURE_SAML_NAMEID_COOKIE, null, time() - 3600, SITECOOKIEPATH, COOKIE_DOMAIN, $secure, true);
        setcookie(RAINBOW_SECURE_SAML_SESSIONINDEX_COOKIE, null, time() - 3600, SITECOOKIEPATH, COOKIE_DOMAIN, $secure, true);
        setcookie(RAINBOW_SECURE_SAML_NAMEID_FORMAT_COOKIE, null, time() - 3600, SITECOOKIEPATH, COOKIE_DOMAIN, $secure, true);
        setcookie(RAINBOW_SECURE_SAML_NAMEID_NAME_QUALIFIER_COOKIE, null, time() - 3600, SITECOOKIEPATH, COOKIE_DOMAIN, $secure, true);
        setcookie(RAINBOW_SECURE_SAML_NAMEID_SP_NAME_QUALIFIER_COOKIE, null, time() - 3600, SITECOOKIEPATH, COOKIE_DOMAIN, $secure, true);

        if (get_option('rainbow_secure_force_saml_login') && get_option('rainbow_secure_stay_in_wordpress_after_slo')) {
            wp_redirect(home_url().'/wp-login.php?loggedout=true');
        } else {
            if (isset($_REQUEST['RelayState'])) {
                rainbow_secure_redirect_to_relaystate_if_trusted(esc_url_raw(wp_unslash($_REQUEST['RelayState']), ['https','http']));
            } else {
                wp_redirect(home_url());
            }
        }
        exit();
    } else {
        echo esc_html__( 'SLS endpoint found an error.', 'rainbow-secure' );
        foreach($errors as $error) {
            echo esc_html($error).'<br>';
        }
        exit();
    }
}

function rainbow_secure_saml_metadata() {
    require_once plugin_dir_path(__FILE__).'/../vendor/onelogin/php-saml/_toolkit_loader.php';
    require plugin_dir_path(__FILE__).'/Settings/RainbowSecureSettings.php';

    $samlSettings = new Settings($settings, true);
    $metadata = $samlSettings->getSPMetadata();

    header('Content-Type: text/xml');
    echo esc_html(ent2ncr($metadata));
    exit();
}

function rainbow_secure_saml_validate_config() {
    rainbow_secure_saml_load_translations();
    require_once plugin_dir_path(__FILE__).'/../vendor/onelogin/php-saml/_toolkit_loader.php';
    require plugin_dir_path(__FILE__).'/Settings/RainbowSecureSettings.php';
    require_once plugin_dir_path(__FILE__)."validate.php";
    exit();
}

// debugging
function rainbow_secure_initialize_saml() {
    //error_log("Initializing SAML...");
    require_once plugin_dir_path(__FILE__).'/../vendor/onelogin/php-saml/_toolkit_loader.php';
    require plugin_dir_path(__FILE__).'/Settings/RainbowSecureSettings.php';

    if (!rainbow_secure_is_saml_enabled()) {
        //error_log("SAML is not enabled");
        return false;
    }

    try {
        $auth = new Auth($settings);
        //error_log("SAML Auth initialized successfully");
    } catch (\Exception $e) {
        //error_log("SAML Auth initialization error: " . $e->getMessage());
        echo '<br>'.esc_html__("The Rainbow Secure SSO/SAML plugin is not correctly configured.", 'rainbow-secure').'<br>';
        echo esc_html($e->getMessage());
        echo '<br>'.esc_html__("If you are the administrator", 'rainbow-secure').', <a href="'.esc_url(get_site_url().'/wp-login.php?normal').'">'.esc_html__("access using your wordpress credentials", 'rainbow-secure').'</a> '.esc_html__("and fix the problem", 'rainbow-secure');
        exit();
    }

    return $auth;
}


// Checks SAML - Returns Bool
function rainbow_secure_is_saml_enabled() {
    $saml_enabled = get_option('rainbow_secure_enabled', 'not defined');
    //$activation_key = get_option('rainbow_secure_activation_key', '');
    $activation_key_valid = rainbow_secure_check_activation_key(); // Function to validate the key
    error_log($activation_key_valid);
    if ($saml_enabled == 'not defined') {
        if (get_option('rainbow_secure_idp_entity_id', 'not defined') == 'not defined') {
            $saml_enabled = false;  // SAML is disabled if IDP entity ID is not defined
        } else {
            $saml_enabled = true;   // SAML is enabled if IDP entity ID is defined
        }
    } else {
        $saml_enabled = ($saml_enabled == "1"); // Check if option is explicitly set to '1'
    }

    // Combine checks for SAML enabled and valid activation key
    if ($saml_enabled && $activation_key_valid) {
        return true;
    } else {
        error_log("SAML Enabled: " . ($saml_enabled ? 'true' : 'false') . ", Activation Key Valid: " . ($activation_key_valid ? 'true' : 'false'));
        return false;
    }
}

// rainbow_secure_check_activation_key
function rainbow_secure_check_activation_key() {
    $key = get_option('rainbow_secure_activation_key');
    $site_url = get_site_url();
    $request_url = "https://www.rsecureoffice.com/sso/rs_activatewebsiteplugin.aspx?ReqSiteURL={$site_url}&ReqSiteType=Wordpress&ReqSiteActivationKey={$key}&ReqMode=Activation";
    error_log($request_url);

    $response = wp_remote_get($request_url);
    error_log(print_r($response['response'], true));

    if ($response['response']['code'] != 200) {
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    error_log($body);

    if (strpos($body, 'AlreadyActivated|') !== false) {
        list($status, $valid_upto) = explode('|', $body);
        $valid_upto = trim($valid_upto);

        // Check if the valid_upto date is valid and in the future
        $valid_upto_timestamp = strtotime($valid_upto);
        if ($valid_upto_timestamp && $valid_upto_timestamp > time()) {
            return true;
        }
    }

    return false;
}


function rainbow_secure_enroll_user_on_sites($user_id, $roles) {
    $opts = array('number' => 1000);
    $sites = get_sites($opts);
    foreach ($sites as $site) {
        if (get_blog_option($site_id, "rainbow_secure_autocreate") && !is_user_member_of_blog($user_id, $site->id)) {
            foreach($roles as $role) {
                add_user_to_blog($site->id, $user_id, $role);
            }
        }
    }
}


function rainbow_secure_enroll_user_on_blogs($blog_id, $user_id, $roles) {
    foreach($roles as $role) {
        add_user_to_blog($blog_id, $user_id, $role);
    }
}

function rainbow_secure_update_user_role($user_id, $roles) {
    $user = get_user_by('id', $user_id);
    $role = array_shift($roles);
    $user->set_role($role);

    foreach($roles as $role) {
        $user->add_role($role);
    }
}

function rainbow_secure_add_roles_to_user($user_id, $roles) {
    $user = get_user_by('id', $user_id);

    foreach($roles as $role) {
        $user->add_role($role);
    }
}

class RainbowSecure_PreventLocalChanges  {
    function __construct() {
        if (get_option('rainbow_secure_prevent_change_mail', false)) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_email_script'));
        }
        if (get_option('rainbow_secure_prevent_change_password', false)) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_password_script'));
        }
    }

    function enqueue_email_script($hook_suffix) {
        global $pagenow;
        if ($pagenow == 'profile.php' && !current_user_can('manage_options')) {
            // Enqueue a dummy script handle to add inline script
            wp_enqueue_script('rainbow-secure-custom-admin-script', '');
            
            // Add inline script to make email field readonly
            $disable_email_script = "
                jQuery(document).ready(function ($) {
                    if ($('input[name=email]').length) {
                        $('input[name=email]').attr('readonly', 'readonly');
                    }
                });
            ";
            wp_add_inline_script('rainbow-secure-custom-admin-script', $disable_email_script);
        }
    }

    function enqueue_password_script($hook_suffix) {
        global $pagenow;
        if ($pagenow == 'profile.php' && !current_user_can('manage_options')) {
            // Enqueue a dummy script handle to add inline script
            wp_enqueue_script('rainbow-secure-custom-admin-script', '');

            // Add inline script to hide password fields
            $disable_password_script = "
                jQuery(document).ready(function ($) {
                    $('tr[id=password]').hide();
                    $('tr[id=password]').next().hide();
                });
            ";
            wp_add_inline_script('rainbow-secure-custom-admin-script', $disable_password_script);
        }
    }
}

$RainbowSecure_PreventLocalChanges  = new RainbowSecure_PreventLocalChanges ();
