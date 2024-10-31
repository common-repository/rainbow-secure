<?php
/**
 * @package RainbowSecure
 */

namespace rainbow_secure_Inc\Base;

class SettingsLinks
{
    public function register() {
        add_filter("plugin_action_links_" . RAINBOW_SECURE_PLUGIN , array( $this, 'settings_link'));
    }

    public function settings_link($links){
        $settings_link = '<a href="admin.php?page=rainbow_secure">Settings</a>';
        array_push($links,$settings_link);
        return $links;
    }

}

