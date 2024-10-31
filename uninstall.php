<?php
/**
 * Trigger this file on Plugin unistall
 * 
 * @package RainbowSecure
 */

if (! defined('WP_UNINSTALL_PLUGIN')){
    die;
}


error_log("unistall was called");
global $wpdb;
$wpdb->query("DELETE FROM `wp_options` WHERE `option_name` LIKE '%rainbow_secure%'");
