<?php

/**
 * @package RainbowSecure
 */

namespace rainbow_secure_Inc\Pages;

use Error;
use \rainbow_secure_Inc\Api\SettingsApi;
use rainbow_secure_Inc\Base\BaseController;
use rainbow_secure_Inc\Api\Callbacks\AdminCallbacks;

class Admin extends BaseController
{
    public $settings;
    public $pages = array();
    public $subpages = array();
    public $callbacks;
    public function __construct()
    {
        add_action('admin_init', array($this, 'handle_csv_export'));
        $this->settings = new SettingsApi();
        $this->callbacks = new AdminCallbacks();

        $this->pages = array(
            array(
                'page_title' => 'Rainbow Secure',
                'menu_title' => 'Rainbow',
                'capability' => 'manage_options',
                'menu_slug' => 'rainbow_secure',
                'callback' => function () {
                    return require_once(RAINBOW_SECURE_PLUGIN_PATH . "/templates/admin.php");
                },
                'icon_url' => 'dashicons-shield',
                'position' => null
            )
        );

        $this->subpages = array(
            array(
                'parent_slug' => 'rainbow_secure',
                'page_title' => 'Activate Plugin',
                'menu_title' => 'Activate Plugin',
                'capability' => 'manage_options',
                'menu_slug' => 'rainbow_secure_activate_plugin',
                'callback' => function () {
                    return require_once(RAINBOW_SECURE_PLUGIN_PATH . "/templates/activation-key.php");
                }
            ),
            $this->subpages[] = array(
                'parent_slug' => 'rainbow_secure',
                'page_title' => 'Activation Status',
                'menu_title' => 'Activation Status',
                'capability' => 'manage_options',
                'menu_slug' => 'rainbow_secure_activation_status',
                'callback' => function () {
                    return require_once(RAINBOW_SECURE_PLUGIN_PATH . "/templates/activation-status.php");
                }
            ),
            array(
                'parent_slug' => 'rainbow_secure',
                'page_title' => 'Export Users',
                'menu_title' => 'Export Users',
                'capability' => 'manage_options',
                'menu_slug' => 'export_users',
                'callback' => array($this, 'exportUsersPage')
            ),
            array(
                'parent_slug' => 'rainbow_secure',
                'page_title' => 'CUSTOMIZE',
                'menu_title' => 'Customize Actions',
                'capability' => 'manage_options',
                'menu_slug' => 'customize_actions',
                'callback' => function () {
                    return require_once(RAINBOW_SECURE_PLUGIN_PATH . "/templates/customize-actions.php");
                }
            )
        );
        add_action('init', array($this, 'initialize_custom_filters'));
        add_action('admin_post_upload_metadata', array($this, 'handle_metadata_upload'));
        // download sp metadata
        add_action('admin_post_download_sp_metadata', array($this, 'rainbow_secure_saml_metadata_download'));
    }
    /**
     * Handle CSV export.
     */
    public function handle_csv_export() {
        if (isset($_GET['action']) && sanitize_text_field( wp_unslash( $_GET['action'] ) ) === 'export_users' && current_user_can('export')) {
            $this->exportUsersCSV();
        }
    }

    /**
     * Outputs the users data as a CSV file.
     */
    private function exportUsersCSV() {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="users.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, array('ID', 'First Name', 'Last Name', 'Username', 'Email', 'Registered Date', 'Roles'));

        $users = get_users();
        foreach ($users as $user) {
            $first_name = get_user_meta($user->ID, 'first_name', true);
            $last_name = get_user_meta($user->ID, 'last_name', true);
            $roles = implode(', ', $user->roles); 
            fputcsv($output, array($user->ID,$first_name ,$last_name , $user->user_login, $user->user_email, $user->user_registered,$roles));
        }
        fclose($output);
        exit;
    }

    /**
     * The page content where users can trigger the export.
     */
    public function exportUsersPage() {
        echo '<div class="wrap"><h1>Export Users</h1>';
        echo '<div style="background-color: white; padding: 20px; border-radius: 8px; margin-top: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        echo '<h2>Step 1: Export Users Data</h2>';
        echo '<form action="' . esc_url(admin_url('admin.php')) . '" method="get">';
        echo '<label for="action">Click this button to download CSV file with user data</label><br>';
        echo '<input type="hidden" name="action" value="export_users">';
        submit_button('Export Users to CSV');
        echo '</form>';
        echo '<h2>Step 2: Add Users to IDP</h2>';
        echo '<p>Click here to login to your Rainbow Secure Dashboard and add your existing users to the IDP.</p>';
        echo '<a href="https://rainbowsecure.com" target="_blank" class="button button-primary">Go to Rainbow Secure Dashboard</a>';
        echo '</div>';
        echo '</div>';
    }

    public function rainbow_secure_saml_metadata_download()
    {
        require_once plugin_dir_path(__FILE__) . '/../../vendor/onelogin/php-saml/_toolkit_loader.php';
        require plugin_dir_path(__FILE__) . '/../Settings/RainbowSecureSettings.php';

        $samlSettings = new \OneLogin\Saml2\Settings($settings, true);
        $metadata = $samlSettings->getSPMetadata();

        header('Content-Type: application/xml');
        header('Content-Disposition: attachment; filename="sp_metadata.xml"');
        echo esc_html(ent2ncr($metadata));
        exit();
    }

    public function initialize_custom_filters()
    {
        add_filter('upload_mimes', array($this, 'add_custom_upload_mimes'), 1,1);
        //error_log('MIME types filter added');
    }
    // Allow XML file uploads
    public function add_custom_upload_mimes($mimes) {
        //error_log('Before updating: ' . print_r($mimes, true));
        $mimes['xml'] = 'text/xml';
        //error_log('After updating: ' . print_r($mimes, true));
        return $mimes;
    }
    

    public function register()
    {

        $this->setSettings();
        $this->setSections();
        $this->setFields();

        // add_action('admin_menu', array($this,'add_admin_pages'));
        $this->settings->addPages($this->pages)->withSubPages('Dashboard')->addSubPages($this->subpages)->register();
    }

    public function setSettings()
    {
        $option_names = [
            'rainbow_secure_enabled',
            'rainbow_secure_activation_key',
            'rainbow_secure_idp_entity_id',
            'rainbow_secure_single_sign_on_service_url',
            'rainbow_secure_single_log_out_service_url',
            'rainbow_secure_certificate',
            'rainbow_secure_create_user_if_not_exists',
            'rainbow_secure_update_user_data',
            'rainbow_secure_force_saml_login',
            'rainbow_secure_single_log_out',
            'rainbow_secure_keep_local_login',
            'rainbow_secure_alternative_acs_endpoint',
            'rainbow_secure_account_matcher',
            'rainbow_secure_trigger_wp_login_hook',
            'rainbow_secure_multi_role_support',
            'rainbow_secure_trusted_url_domains',
            'rainbow_secure_username',
            'rainbow_secure_email',
            'rainbow_secure_first_name',
            'rainbow_secure_last_name',
            'rainbow_secure_nickname',
            'rainbow_secure_role',
            'rainbow_secure_remember_me',
            'rainbow_secure_role_order_administrator',
            'rainbow_secure_role_order_editor',
            'rainbow_secure_role_order_author',
            'rainbow_secure_role_order_contributor',
            'rainbow_secure_role_order_subscriber',
            'rainbow_secure_multiple_role_one_saml_attribute_value',
            'rainbow_secure_regular_expression',
            'rainbow_secure_administrator_role_precedence',
            'rainbow_secure_editor_role_precedence',
            'rainbow_secure_author_role_precedence',
            'rainbow_secure_contributor_role_precedence',
            'rainbow_secure_subscriber_role_precedence',
            'rainbow_secure_prevent_use_of_normal',
            'rainbow_secure_prevent_reset_password',
            'rainbow_secure_prevent_change_password',
            'rainbow_secure_prevent_change_mail',
            'rainbow_secure_stay_in_wordpress_after_slo',
            'rainbow_secure_user_registration',
            'rainbow_secure_lost_password',
            'rainbow_secure_saml_logo_url',
            'rainbow_secure_background_color',
            'rainbow_secure_saml_link_message',
            'rainbow_secure_debug_mode',
            'rainbow_secure_strict_mode',
            'rainbow_secure_service_provider_entity_id',
            'rainbow_secure_lowercase_url_encoding',
            'rainbow_secure_encrypt_nameid',
            'rainbow_secure_sign_authnrequest',
            'rainbow_secure_sign_logoutrequest',
            'rainbow_secure_sign_logoutresponse',
            'rainbow_secure_reject_unsigned_messages',
            'rainbow_secure_reject_unsigned_assertions',
            'rainbow_secure_reject_unencrypted_assertions',
            'rainbow_secure_retrieve_parameters_from_server',
            'rainbow_secure_nameid_format',
            'rainbow_secure_requestedauthncontext',
            'rainbow_secure_service_provider_certificate',
            'rainbow_secure_service_provider_private_key',
            'rainbow_secure_signature_algorithm',
            'rainbow_secure_digest_algorithm',
        ];

        $args = array();

        foreach ($option_names as $option_name) {
            $args[] = array(
                'option_group' => 'rainbow_secure_options_group',
                'option_name' => $option_name,
                'callback' => array($this->callbacks, 'rainbowSecureOptionsGroup')
            );
        }

        $this->settings->setSettings($args);
    }

    public function setSections()
    {
        $sections = [
            ['id' => 'rainbow_secure_status', 'title' => 'STATUS', 'text' => "Use this flag for enable or disable the SAML support."],
            ['id' => 'rainbow_secure_activation_status', 'title' => 'ACTIVATION KEY STATUS', 'text' => "Add you Activation Key here and verify its' status."],
            ['id' => 'rainbow_secure_admin_index', 'title' => 'IDENTITY PROVIDER SETTINGS', 'text' => "Set information relating to the IdP that will be connected with our WordPress."],
            ['id' => 'rainbow_secure_attribute_mapping', 'title' => 'ATTRIBUTE MAPPING', 'text' => "Sometimes the names of the attributes sent by the IdP do not match the names used by WordPress for the user accounts. In this section you can set the mapping between IdP fields and WordPress fields."],
            ['id' => 'rainbow_secure_role_mapping', 'title' => 'ROLE MAPPING', 'text' => "The IdP can use its own roles. In this section, you can set the mapping between IdP and WordPress roles. Accepts comma separated values. Example: admin,owner,superuser"],
            ['id' => 'rainbow_secure_role_precedence', 'title' => 'ROLE PRECEDENCE', 'text' => "In some cases, the IdP returns more than one role. In this secion, you can set the precedence of the different roles which makes sense if multi-role support is not enabled. The smallest integer will be the role chosen."],
            ['id' => 'rainbow_secure_customize_actions_and_links', 'title' => 'CUSTOMIZE ACTIONS AND LINKS', 'text' => "When SAML SSO is enabled to be integrated with an IdP, some WordPress actions and links could be changed. In this section, you will be able to enable or disable the ability for users to change their email address, password and reset their password. You can also override the user registration and the lost password links."],
            ['id' => 'rainbow_secure_attribute_advanced_settings', 'title' => 'ADVANCED SETTINGS', 'text' => "Handle some other parameters related to customizations and security issues.<br>If signing/encryption is enabled, then x509 cert and private key for the SP must be provided. There are 2 ways:<br>1. Store them as files named sp.key and sp.crt on the 'certs' folder of the plugin. (Make sure that the /cert folder is read-protected and not exposed to internet.)<br>2. Store them at the database, filling the corresponding textareas."]
        ];

        $args = array();

        foreach ($sections as $section) {
            $args[] = array(
                'id' => $section['id'],
                'title' => $section['title'],
                'callback' => array($this->callbacks, 'rainbowSecureAdminSection'),
                'page' => 'rainbow_secure',
                'args' => array(
                    'text' => $section['text']
                )
            );
        }


        $this->settings->setSections($args);
    }

    public function setFields()
    {
        $common_args = array('class' => 'example-class');

        $options = [
            ['id' => 'rainbow_secure_enabled', 'title' => 'Enable', 'callback' => 'rainbowSecureCheckBox', 'section' => 'rainbow_secure_status'],
            ['id' => 'rainbow_secure_activation_key', 'title' => 'Activation Key', 'callback' => 'rainbowSecureText', 'toolTipInfo' => '', 'section' => 'rainbow_secure_activation_status'],
            ['id' => 'rainbow_secure_idp_entity_id', 'title' => 'IdP Entity Id', 'callback' => 'rainbowSecureText', 'toolTipInfo' => 'Identifier of the IdP entity. ("Issuer URL")', 'section' => 'rainbow_secure_admin_index'],
            ['id' => 'rainbow_secure_single_sign_on_service_url', 'title' => 'Single Sign On Service Url', 'callback' => 'rainbowSecureText', 'toolTipInfo' => 'SSO endpoint info of the IdP. URL target of the IdP where the SP will send the Authentication Request. ("SAML 2.0 Endpoint (HTTP)")', 'section' => 'rainbow_secure_admin_index'],
            ['id' => 'rainbow_secure_single_log_out_service_url', 'title' => 'Single Log Out Service Url', 'callback' => 'rainbowSecureText', 'toolTipInfo' => 'SLO endpoint info of the IdP. URL target of the IdP where the SP will send the SLO Request. ("SLO Endpoint (HTTP)")', 'section' => 'rainbow_secure_admin_index'],
            ['id' => 'rainbow_secure_certificate', 'title' => 'X.509 Certificate', 'callback' => 'rainbowSecureTextArea', 'toolTipInfo' => 'Public x509 certificate of the IdP. ("X.509 certificate")', 'section' => 'rainbow_secure_admin_index'],
            ['id' => 'rainbow_secure_create_user_if_not_exists', 'title' => 'Create user if not exists', 'callback' => 'rainbowSecureCheckBox','toolTipInfo' => 'Auto-provisioning. If user not exists, WordPress will create a new user with the data provided by the IdP. Review the Mapping section.', 'section' => 'rainbow_secure_admin_index'],
            ['id' => 'rainbow_secure_update_user_data', 'title' => 'Update user data', 'callback' => 'rainbowSecureCheckBox','toolTipInfo' => 'Auto-update. WordPress will update the account of the user with the data provided by the IdP. Review the Mapping section.', 'section' => 'rainbow_secure_admin_index'],
            ['id' => 'rainbow_secure_force_saml_login', 'title' => 'Force SAML login', 'callback' => 'rainbowSecureCheckBox','toolTipInfo' => 'Protect WordPress and force the user to authenticate at the IdP in order to access when any WordPress page is loaded and no active session.', 'section' => 'rainbow_secure_admin_index'],
            ['id' => 'rainbow_secure_single_log_out', 'title' => 'Single Log Out', 'callback' => 'rainbowSecureCheckBox','toolTipInfo' => 'Enable/disable Single Log Out. SLO is a complex functionality, the most common SLO implementation is based on front-channel (redirections), sometimes if the SLO workflow fails a user can be blocked in an unhandled view. If the admin does not control the set of apps involved in the SLO process, you may want to disable this functionality to avoid more problems than benefits.', 'section' => 'rainbow_secure_admin_index'],
            ['id' => 'rainbow_secure_keep_local_login', 'title' => 'Keep Local login', 'callback' => 'rainbowSecureCheckBox','toolTipInfo' => 'Enable/disable the normal login form. If disabled, instead of the WordPress login form, WordPress will excecute the SP-initiated SSO flow. If enabled the normal login form is displayed and a link to initiate that flow is displayed.', 'section' => 'rainbow_secure_admin_index'],
            ['id' => 'rainbow_secure_alternative_acs_endpoint', 'title' => 'Alternative ACS Endpoint', 'callback' => 'rainbowSecureCheckBox','toolTipInfo' => 'Enable if you want to use a different Assertion Consumer Endpoint than /wp-login.php?saml_acs (Required if using WPEngine or any similar hosting service that prevents POST on wp-login.php). You must update the IdP with the new value after enabling/disabling this setting.', 'section' => 'rainbow_secure_admin_index'],
            ['id' => 'rainbow_secure_account_matcher', 'title' => 'Match WordPress account by', 'callback' => 'rainbowSecureDropdown','toolTipInfo' => 'Select what field will be used in order to find the user account. If "email", the plugin will prevent the user from changing their email address in their user profile.', 'section' => 'rainbow_secure_admin_index', 'optionsForDropdown' => [
                'username' => 'Username',
                'email' => 'E-mail'
            ]],
            ['id' => 'rainbow_secure_trigger_wp_login_hook', 'title' => 'Trigger wp_login hook', 'callback' => 'rainbowSecureCheckBox','toolTipInfo' => 'When enabled, the wp_login hook will be triggered.', 'section' => 'rainbow_secure_admin_index'],
            ['id' => 'rainbow_secure_multi_role_support', 'title' => 'Multi Role Support', 'callback' => 'rainbowSecureCheckBox','toolTipInfo' => 'Enable/disable the support of multiple roles. Not available in multi-site wordpress', 'section' => 'rainbow_secure_admin_index'],
            ['id' => 'rainbow_secure_trusted_url_domains', 'title' => 'Trust URL domains on RelayState', 'callback' => 'rainbowSecureTextArea', 'toolTipInfo' => "List here any domain (comma- separated) that you want to be trusted in the RelayState parameter, otherwise the parameter will be ignored. You do not need to include the domain of the wordpress instance", 'section' => 'rainbow_secure_admin_index'],
            ['id' => 'rainbow_secure_username', 'title' => 'Username', 'callback' => 'rainbowSecureText', 'toolTipInfo' => '', 'section' => 'rainbow_secure_attribute_mapping'],
            ['id' => 'rainbow_secure_email', 'title' => 'E-mail', 'callback' => 'rainbowSecureText', 'toolTipInfo' => '', 'section' => 'rainbow_secure_attribute_mapping'],
            ['id' => 'rainbow_secure_first_name', 'title' => 'First Name', 'callback' => 'rainbowSecureText', 'toolTipInfo' => '', 'section' => 'rainbow_secure_attribute_mapping'],
            ['id' => 'rainbow_secure_last_name', 'title' => 'Last Name', 'callback' => 'rainbowSecureText', 'toolTipInfo' => '', 'section' => 'rainbow_secure_attribute_mapping'],
            ['id' => 'rainbow_secure_nickname', 'title' => 'Nickname', 'callback' => 'rainbowSecureText', 'toolTipInfo' => "If not provided, default value is the user's username.", 'section' => 'rainbow_secure_attribute_mapping'],
            ['id' => 'rainbow_secure_role', 'title' => 'Role', 'callback' => 'rainbowSecureText', 'toolTipInfo' => "Regular expression that extract roles from complex multivalued data (required to active the previous option).<br>E.g. If the SAMLResponse has a role attribute like: CN=admin;CN=superuser;CN=europe-admin; , use the regular expression <code>/CN=([A-Z0-9\s _-]*);/i</code> to retrieve the values. Or use <code>/CN=([^,;]*)/</code>", 'section' => 'rainbow_secure_attribute_mapping'],
            ['id' => 'rainbow_secure_remember_me', 'title' => 'Remember Me', 'callback' => 'rainbowSecureText', 'toolTipInfo' => '', 'section' => 'rainbow_secure_attribute_mapping'],
            ['id' => 'rainbow_secure_role_order_administrator', 'title' => 'Administrator', 'callback' => 'rainbowSecureText', 'toolTipInfo' => '', 'section' => 'rainbow_secure_role_mapping'],
            ['id' => 'rainbow_secure_role_order_editor', 'title' => 'Editor', 'callback' => 'rainbowSecureText', 'toolTipInfo' => '', 'section' => 'rainbow_secure_role_mapping'],
            ['id' => 'rainbow_secure_role_order_author', 'title' => 'Author', 'callback' => 'rainbowSecureText', 'toolTipInfo' => '', 'section' => 'rainbow_secure_role_mapping'],
            ['id' => 'rainbow_secure_role_order_contributor', 'title' => 'Contributor', 'callback' => 'rainbowSecureText', 'toolTipInfo' => '', 'section' => 'rainbow_secure_role_mapping'],
            ['id' => 'rainbow_secure_role_order_subscriber', 'title' => 'Subscriber', 'callback' => 'rainbowSecureText', 'toolTipInfo' => '', 'section' => 'rainbow_secure_role_mapping'],
            ['id' => 'rainbow_secure_multiple_role_one_saml_attribute_value', 'title' => 'Multiple role values in one saml attribute value','toolTipInfo' => 'Sometimes role values are provided in an unique attribute statement (instead multiple attribute statements). If that is the case, activate this and the plugin will try to split those values by ; 
            Use a regular expression pattern in order to extract complex data.', 'callback' => 'rainbowSecureCheckBox', 'section' => 'rainbow_secure_role_mapping'],
            ['id' => 'rainbow_secure_regular_expression', 'title' => 'Regular expression for multiple role values', 'callback' => 'rainbowSecureText', 'toolTipInfo' => '', 'section' => 'rainbow_secure_role_mapping'],
            ['id' => 'rainbow_secure_administrator_role_precedence', 'title' => 'Administrator', 'callback' => 'rainbowSecureText', 'toolTipInfo' => '', 'section' => 'rainbow_secure_role_precedence'],
            ['id' => 'rainbow_secure_editor_role_precedence', 'title' => 'Editor', 'callback' => 'rainbowSecureText', 'toolTipInfo' => '', 'section' => 'rainbow_secure_role_precedence'],
            ['id' => 'rainbow_secure_author_role_precedence', 'title' => 'Author', 'callback' => 'rainbowSecureText', 'toolTipInfo' => '', 'section' => 'rainbow_secure_role_precedence'],
            ['id' => 'rainbow_secure_contributor_role_precedence', 'title' => 'Contributor', 'callback' => 'rainbowSecureText', 'toolTipInfo' => '', 'section' => 'rainbow_secure_role_precedence'],
            ['id' => 'rainbow_secure_subscriber_role_precedence', 'title' => 'Subscriber', 'callback' => 'rainbowSecureText', 'toolTipInfo' => '', 'section' => 'rainbow_secure_role_precedence'],
            ['id' => 'rainbow_secure_prevent_use_of_normal', 'title' => 'Prevent use of ?normal	', 'callback' => 'rainbowSecureCheckBox', 'toolTipInfo' => 'Check to disable the <code>?normal</code> option and offer the local login when it is not enabled.', 'section' => 'rainbow_secure_customize_actions_and_links'],
            ['id' => 'rainbow_secure_prevent_reset_password', 'title' => 'Prevent reset password', 'callback' => 'rainbowSecureCheckBox', 'toolTipInfo' => 'Check to disable resetting passwords in WordPress.', 'section' => 'rainbow_secure_customize_actions_and_links'],
            ['id' => 'rainbow_secure_prevent_change_password', 'title' => 'Prevent change password', 'callback' => 'rainbowSecureCheckBox', 'toolTipInfo' => 'Check to disable changing passwords in WordPress.', 'section' => 'rainbow_secure_customize_actions_and_links'],
            ['id' => 'rainbow_secure_prevent_change_mail', 'title' => 'Prevent change mail', 'callback' => 'rainbowSecureCheckBox', 'toolTipInfo' => 'Check to disable changing the email addresses in WordPress (recommended if you are using email to match accounts).', 'section' => 'rainbow_secure_customize_actions_and_links'],
            ['id' => 'rainbow_secure_stay_in_wordpress_after_slo', 'title' => 'Stay in WordPress after SLO', 'callback' => 'rainbowSecureCheckBox', 'toolTipInfo' => 'If SLO and Force SAML login are enabled, after the SLO process you will be redirected to the WordPress main page and a SAML SSO process will start. Check this to prevent that and stay at the WordPress login form.', 'section' => 'rainbow_secure_customize_actions_and_links'],
            ['id' => 'rainbow_secure_user_registration', 'title' => 'User Registration', 'callback' => 'rainbowSecureText', 'toolTipInfo' => 'Override the user registration link.', 'section' => 'rainbow_secure_customize_actions_and_links'],
            ['id' => 'rainbow_secure_lost_password', 'title' => 'Lost Password', 'callback' => 'rainbowSecureText', 'toolTipInfo' => 'Override the lost password link. (Prevent reset password must be deactivated or the SAML SSO will be used.)', 'section' => 'rainbow_secure_customize_actions_and_links'],
            ['id' => 'rainbow_secure_saml_logo_url', 'title' => 'SAML Logo URL', 'callback' => 'rainbowSecureText', 'toolTipInfo' => "", 'section' => 'rainbow_secure_customize_actions_and_links'],
            ['id' => 'rainbow_secure_background_color', 'title' => 'Background Color', 'callback' => 'rainbowSecureText', 'section' => 'rainbow_secure_customize_actions_and_links', 'toolTipInfo' => 'Select a background color for the Logo.'],
            ['id' => 'rainbow_secure_saml_link_message', 'title' => 'SAML Link Message', 'callback' => 'rainbowSecureText', 'toolTipInfo' => "If 'Keep Local login' enabled, this will be showed as message at the SAML link.", 'section' => 'rainbow_secure_customize_actions_and_links'],
            ['id' => 'rainbow_secure_debug_mode', 'title' => 'Debug Mode', 'callback' => 'rainbowSecureCheckBox', 'toolTipInfo' => 'Enable for debugging the SAML workflow. Errors and Warnings will be shown.', 'section' => 'rainbow_secure_attribute_advanced_settings'],
            ['id' => 'rainbow_secure_strict_mode', 'title' => 'Strict Mode', 'callback' => 'rainbowSecureCheckBox', 'toolTipInfo' => 'If Strict Mode is enabled, WordPress will reject unsigned or unencrypted messages if it expects them signed or encrypted. It will also reject messages if not strictly following the SAML standard: Destination, NameId, Conditions ... are also validated.', 'section' => 'rainbow_secure_attribute_advanced_settings'],
            ['id' => 'rainbow_secure_service_provider_entity_id', 'title' => 'Service Provider Entity Id', 'callback' => 'rainbowSecureText', 'toolTipInfo' => "Set the Entity ID for the Service Provider. If not provided, 'php-saml' will be used.", 'section' => 'rainbow_secure_attribute_advanced_settings'],
            ['id' => 'rainbow_secure_lowercase_url_encoding', 'title' => 'Lowercase URL encoding?', 'callback' => 'rainbowSecureCheckBox', 'toolTipInfo' => 'Some IdPs like ADFS can use lowercase URL encoding, but the plugin expects uppercase URL encoding, enable it to fix incompatibility issues.', 'section' => 'rainbow_secure_attribute_advanced_settings'],
            ['id' => 'rainbow_secure_encrypt_nameid', 'title' => 'Encrypt nameID', 'callback' => 'rainbowSecureCheckBox', 'toolTipInfo' => 'The nameID sent by this SP will be encrypted.', 'section' => 'rainbow_secure_attribute_advanced_settings'],
            ['id' => 'rainbow_secure_sign_authnrequest', 'title' => 'Sign AuthnRequest', 'callback' => 'rainbowSecureCheckBox', 'toolTipInfo' => 'The samlp:AuthnRequest messages sent by this SP will be signed.', 'section' => 'rainbow_secure_attribute_advanced_settings'],
            ['id' => 'rainbow_secure_sign_logoutrequest', 'title' => 'Sign LogoutRequest', 'callback' => 'rainbowSecureCheckBox', 'toolTipInfo' => 'The samlp:logoutRequest messages sent by this SP will be signed.', 'section' => 'rainbow_secure_attribute_advanced_settings'],
            ['id' => 'rainbow_secure_sign_logoutresponse', 'title' => 'Sign LogoutResponse', 'callback' => 'rainbowSecureCheckBox', 'toolTipInfo' => 'The samlp:logoutResponse messages sent by this SP will be signed.', 'section' => 'rainbow_secure_attribute_advanced_settings'],
            ['id' => 'rainbow_secure_reject_unsigned_messages', 'title' => 'Reject Unsigned Messages', 'callback' => 'rainbowSecureCheckBox', 'toolTipInfo' => 'Reject unsigned samlp:Response, samlp:LogoutRequest and samlp:LogoutResponse received', 'section' => 'rainbow_secure_attribute_advanced_settings'],
            ['id' => 'rainbow_secure_reject_unsigned_assertions', 'title' => 'Reject Unsigned Assertions', 'callback' => 'rainbowSecureCheckBox', 'toolTipInfo' => 'Reject unsigned saml:Assertion received', 'section' => 'rainbow_secure_attribute_advanced_settings'],
            ['id' => 'rainbow_secure_reject_unencrypted_assertions', 'title' => 'Reject Unencrypted Assertions', 'callback' => 'rainbowSecureCheckBox', 'toolTipInfo' => 'Reject unencrypted saml:Assertion received', 'section' => 'rainbow_secure_attribute_advanced_settings'],
            ['id' => 'rainbow_secure_retrieve_parameters_from_server', 'title' => 'Retrieve Parameters From Server', 'callback' => 'rainbowSecureCheckBox', 'toolTipInfo' => 'Sometimes when the app is behind a firewall or proxy, the query parameters can be modified an this affects the signature validation process on HTTP-Redirectbinding. Active this if you are seeing signature validation failures. The plugin will try to extract the original query parameters.', 'section' => 'rainbow_secure_attribute_advanced_settings'],
            ['id' => 'rainbow_secure_nameid_format', 'title' => 'NameID Format', 'callback' => 'rainbowSecureDropdown', 'section' => 'rainbow_secure_attribute_advanced_settings', 'optionsForDropdown' => [
                'unspecified' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',
                'emailAddress' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
                'transient' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
                'persistent' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
                'entity' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:entity',
                'encrypted' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:encrypted',
                'kerberos' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:kerberos',
                'x509subjecname' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:X509SubjectName',
                'windowsdomainqualifiedname' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:WindowsDomainQualifiedName',
            ]],
            ['id' => 'rainbow_secure_requestedauthncontext', 'title' => 'Requested Authn Context', 'callback' => 'rainbowSecureMultipleDropdown', 'section' => 'rainbow_secure_attribute_advanced_settings', 'optionsForDropdown' => [
                '' => '',
                'unspecified' => 'urn:oasis:names:tc:SAML:2.0:ac:classes:unspecified',
                'password' => 'urn:oasis:names:tc:SAML:2.0:ac:classes:Password',
                'passwordprotectedtransport' => 'urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport',
                'x509' => 'urn:oasis:names:tc:SAML:2.0:ac:classes:X509',
                'smartcard' => 'urn:oasis:names:tc:SAML:2.0:ac:classes:Smartcard',
                'kerberos' => 'urn:oasis:names:tc:SAML:2.0:ac:classes:Kerberos',
            ]],
            ['id' => 'rainbow_secure_service_provider_certificate', 'title' => 'Service Provider X.509 Certificate', 'callback' => 'rainbowSecureTextArea', 'section' => 'rainbow_secure_attribute_advanced_settings'],
            ['id' => 'rainbow_secure_service_provider_private_key', 'title' => 'Service Provider Private Key', 'callback' => 'rainbowSecureTextArea', 'section' => 'rainbow_secure_attribute_advanced_settings'],
            ['id' => 'rainbow_secure_signature_algorithm', 'title' => 'Signature Algorithm', 'callback' => 'rainbowSecureDropdown', 'section' => 'rainbow_secure_attribute_advanced_settings', 'optionsForDropdown' => [
                'http://www.w3.org/2000/09/xmldsig#rsa-sha1' => 'http://www.w3.org/2000/09/xmldsig#rsa-sha1',
                'http://www.w3.org/2000/09/xmldsig#dsa-sha1' => 'http://www.w3.org/2000/09/xmldsig#dsa-sha1',
                'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
                'http://www.w3.org/2001/04/xmldsig-more#rsa-sha384' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha384',
                'http://www.w3.org/2001/04/xmldsig-more#rsa-sha512' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha512',
            ]],
            ['id' => 'rainbow_secure_digest_algorithm', 'title' => 'Digest Algorithm', 'callback' => 'rainbowSecureDropdown', 'section' => 'rainbow_secure_attribute_advanced_settings', 'optionsForDropdown' => [
                'http://www.w3.org/2000/09/xmldsig#sha1' => 'http://www.w3.org/2000/09/xmldsig#sha1',
                'http://www.w3.org/2001/04/xmlenc#sha256' => 'http://www.w3.org/2001/04/xmlenc#sha256',
                'http://www.w3.org/2001/04/xmldsig-more#sha384' => 'http://www.w3.org/2001/04/xmldsig-more#sha384',
                'http://www.w3.org/2001/04/xmlenc#sha512' => 'http://www.w3.org/2001/04/xmlenc#sha512',
            ]],
        ];

        $args = array();

        foreach ($options as $option) {
            $field_args = array_merge(['label_for' => $option['id']], $common_args);
            
            if (isset($option['optionsForDropdown'])) {
                $field_args['options'] = $option['optionsForDropdown'];
            }

            if (isset($option['toolTipInfo'])) {
                $field_args['toolTipInfo'] = $option['toolTipInfo'];
            }

            $args[] = array(
                'id' => $option['id'],
                'title' => $option['title'],
                'callback' => array($this->callbacks, $option['callback']),
                'page' => 'rainbow_secure',
                'section' => $option['section'],
                'args' => $field_args
            );

        }

        $this->settings->setFields($args);
    }


    // Handle metadata file upload
    public function handle_metadata_upload() {
        if (isset($_POST['upload_metadata'])) {
            // Verify nonce
            if (!isset($_POST['upload_metadata_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['upload_metadata_nonce'])), 'upload_metadata_action')) {
                wp_die(esc_html__('Nonce verification failed', 'rainbow-secure'));
            }

            // Include WordPress file handling functions
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            
            // Ensure that the file upload is properly sanitized and validated
            if (empty($_FILES['metadata_file']['name'])) {
                wp_die(esc_html__('No file uploaded', 'rainbow-secure'));
            }

            // Validate the file type
            $file_type = wp_check_filetype(sanitize_file_name($_FILES['metadata_file']['name']));
            $allowed_mime_types = array('text/xml');
            
            if (!$file_type['type'] || !in_array($file_type['type'], $allowed_mime_types)) {
                wp_die(esc_html__('Invalid file type. Only XML files are allowed.', 'rainbow-secure'));
            }

            // Sanitize tmp_name
            $_FILES['metadata_file']['tmp_name'] = sanitize_text_field($_FILES['metadata_file']['tmp_name']);
            
            // allowed MIME types for the upload
            $upload_overrides = array('test_form' => false, 'mimes' => array('xml' => 'text/xml')); 

            // file upload handler
            $movefile = wp_handle_upload($_FILES['metadata_file'], $upload_overrides);

            // Checks if the file was uploaded successfully
            if ($movefile && !isset($movefile['error'])) {
                // Process the metadata file
                $this->process_metadata_file($movefile['file']);
                exit;
            } else {
                // Redirects with error message if upload fails
                $error_message = isset($movefile['error']) ? $movefile['error'] : esc_html__('Unknown error occurred during file upload.', 'rainbow-secure');
                wp_safe_redirect(esc_url_raw(admin_url('admin.php?page=rainbow_secure&upload=error&message=' . urlencode(sanitize_text_field($error_message)))));
                exit;
            }
        }
    }

    public function process_metadata_file($file_path) {
        // Read file content
        $file_content = file_get_contents($file_path);
        if ($file_content !== false) {
            $document = new \DOMDocument();
            
            // Trys to load the XML file content and handle errors
            if (!$document->loadXML($file_content, LIBXML_NOBLANKS)) {
                wp_safe_redirect(esc_url_raw(admin_url('admin.php?page=rainbow_secure&upload=error&message=' . urlencode("Failed to parse XML content."))));
                exit;
            }

            $xpath = new \DOMXPath($document);
            // Register namespaces to use in XPath queries
            $xpath->registerNamespace('md', 'urn:oasis:names:tc:SAML:2.0:metadata');
            $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

            // X509 Certificate
            $query = '//md:IDPSSODescriptor/md:KeyDescriptor[1]/ds:KeyInfo/ds:X509Data/ds:X509Certificate';
            $certNodes = $xpath->query($query);
            if ($certNodes->length > 0) {
                $x509Certificate = trim($certNodes->item(0)->textContent);
                update_option('rainbow_secure_certificate', $x509Certificate);
            }

            // Entity ID
            $entityId = $xpath->evaluate('string(/md:EntityDescriptor/@entityID)');
            if ($entityId) {
                update_option('rainbow_secure_idp_entity_id', sanitize_text_field($entityId));
            }

            // SingleSignOnService URL
            $ssoURL = $xpath->evaluate('string(//md:IDPSSODescriptor/md:SingleSignOnService[1]/@Location)');
            if ($ssoURL) {
                update_option('rainbow_secure_single_sign_on_service_url', esc_url_raw($ssoURL));
            }

            // SingleLogOut URL
            $sloURL = $xpath->evaluate('string(//md:IDPSSODescriptor/md:SingleLogoutService[1]/@Location)');
            if ($sloURL) {
                update_option('rainbow_secure_single_log_out_service_url', esc_url_raw($sloURL));
            }

            // NameIDFormat
            $nameIDFormat = $xpath->evaluate('string(//md:IDPSSODescriptor/md:NameIDFormat)');
            if ($nameIDFormat) {
                $parts = explode(':', $nameIDFormat);
                $formatKey = strtolower(end($parts));
                if ($formatKey == 'emailaddress') {
                    $formatKey = 'emailAddress';
                }
                update_option('rainbow_secure_nameid_format', sanitize_text_field($formatKey));
            }

            // Delete the file after successful processing
            wp_delete_file($file_path);
            
            // Redirect with success message
            wp_safe_redirect(esc_url_raw(admin_url('admin.php?page=rainbow_secure&upload=success')));
            exit;
        } else {
            // Handle file read error
            wp_safe_redirect(esc_url_raw(admin_url('admin.php?page=rainbow_secure&upload=error&message=' . urlencode("Failed to read file content."))));
            exit;
        }
    }

    
}