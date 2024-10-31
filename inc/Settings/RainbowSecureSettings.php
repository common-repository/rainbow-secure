<?php
defined('ABSPATH') or die('Access Denied');
// Ensure the script is not accessed directly
if ( !function_exists( 'add_action' ) ) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

use rainbow_secure_Inc\Base\BaseController;
require_once plugin_dir_path( __FILE__ ) . '../../vendor/onelogin/php-saml/_toolkit_loader.php';
use OneLogin\Saml2\Constants;

$possibleNameIdFormatValues = array(
    'unspecified' => Constants::NAMEID_UNSPECIFIED,
    'emailAddress' => Constants::NAMEID_EMAIL_ADDRESS,
    'transient' => Constants::NAMEID_TRANSIENT,
    'persistent' => Constants::NAMEID_PERSISTENT,
    'entity' => Constants::NAMEID_ENTITY,
    'encrypted' => Constants::NAMEID_ENCRYPTED,
    'kerberos' => Constants::NAMEID_KERBEROS,
    'x509subjectname' => Constants::NAMEID_X509_SUBJECT_NAME,
    'windowsdomainqualifiedname' => Constants::NAMEID_WINDOWS_DOMAIN_QUALIFIED_NAME
);

$possibleRequestedAuthnContextValues = array(
    'unspecified' => Constants::AC_UNSPECIFIED,
    'password' => Constants::AC_PASSWORD,
    'passwordprotectedtransport' => "urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport",
    'x509' => Constants::AC_X509,
    'smartcard' => Constants::AC_SMARTCARD,
    'kerberos' => Constants::AC_KERBEROS,
);

$options = array(
    'strict' => get_option('rainbow_secure_strict_mode', 'on'),
    'debug' => get_option('rainbow_secure_debug_mode', 'on'),
    'sp_entity_id' => get_option('rainbow_secure_service_provider_entity_id', 'php-saml'),

    'nameIdEncrypted' => get_option('rainbow_secure_encrypt_nameid', false),
    'authnRequestsSigned' => get_option('rainbow_secure_sign_authnrequest', false),
    'logoutRequestSigned' => get_option('rainbow_secure_sign_logoutrequest', false),
    'logoutResponseSigned' => get_option('rainbow_secure_sign_logoutresponse', false),
    'wantMessagesSigned' => get_option('rainbow_secure_reject_unsigned_messages', false),
    'wantAssertionsSigned' => get_option('rainbow_secure_reject_unsigned_assertions', false),
    'wantAssertionsEncrypted' => get_option('rainbow_secure_reject_unencrypted_assertions', false)
);

$nameIDFormat = get_option('rainbow_secure_nameid_format', 'unspecified');
error_log(print_r($nameIDFormat,true));
$options['NameIDFormat'] = $possibleNameIdFormatValues[$nameIDFormat];

$requestedAuthnContextValues = get_option('rainbow_secure_requestedauthncontext', array());
if ((is_array($requestedAuthnContextValues) && empty(array_filter($requestedAuthnContextValues))) || empty($requestedAuthnContextValues)) {
    $options['requestedAuthnContext'] = false;
} else {
    $options['requestedAuthnContext'] = array();
    foreach ($requestedAuthnContextValues as $value) {
        if (isset($possibleRequestedAuthnContextValues[$value])) {
            $options['requestedAuthnContext'][] = $possibleRequestedAuthnContextValues[$value];
        }
    }
}

$acsEndpoint = get_option('rainbow_secure_alternative_acs_endpoint', false)
    ? plugins_url('alternative_acs.php', dirname(__FILE__))
    : add_query_arg(['saml_acs' => ''], wp_login_url());

$settings = array(
    'strict' => $options['strict'] == 'on' ? true : false,
    'debug' => $options['debug'] == 'on' ? true : false,

    'sp' => array(
        'entityId' => (!empty($options['sp_entity_id']) ? $options['sp_entity_id'] : 'php-saml'),
        'assertionConsumerService' => array(
            'url' => $acsEndpoint
        ),
        'singleLogoutService' => array(
            'url' => add_query_arg(['saml_sls' => ''], wp_login_url())
        ),
        'NameIDFormat' => $options['NameIDFormat'],
        'x509cert' => get_option('rainbow_secure_service_provider_certificate'),
        'privateKey' => get_option('rainbow_secure_service_provider_private_key'),
    ),

    'idp' => array(
        'entityId' => get_option('rainbow_secure_idp_entity_id'),
        'singleSignOnService' => array(
            'url' => get_option('rainbow_secure_single_sign_on_service_url'),
        ),
        'singleLogoutService' => array(
            'url' => get_option('rainbow_secure_single_log_out_service_url'),
        ),
        'x509cert' => get_option('rainbow_secure_certificate'),
    ),

    'security' => array(
        'signMetadata' => false,
        'nameIdEncrypted' => $options['nameIdEncrypted'] == 'on' ? true : false,
        'authnRequestsSigned' => $options['authnRequestsSigned'] == 'on' ? true : false,
        'logoutRequestSigned' => $options['logoutRequestSigned'] == 'on' ? true : false,
        'logoutResponseSigned' => $options['logoutResponseSigned'] == 'on' ? true : false,
        'wantMessagesSigned' => $options['wantMessagesSigned'] == 'on' ? true : false,
        'wantAssertionsSigned' => $options['wantAssertionsSigned'] == 'on' ? true : false,
        'wantAssertionsEncrypted' => $options['wantAssertionsEncrypted'] == 'on' ? true : false,
        'wantNameId' => false,
        'requestedAuthnContext' => $options['requestedAuthnContext'],
        'relaxDestinationValidation' => true,
        'lowercaseUrlencoding' => get_option('rainbow_secure_lowercase_url_encoding', false),
        'signatureAlgorithm' => get_option('rainbow_secure_signature_algorithm', 'http://www.w3.org/2000/09/xmldsig#rsa-sha1'),
        'digestAlgorithm' => get_option('rainbow_secure_digest_algorithm', 'http://www.w3.org/2000/09/xmldsig#sha1'),
    )
);
// echo var_dump($settings);