<?php
/**
 * @package RainbowSecure
 */

namespace rainbow_secure_Inc\Base;

class Activate
{
    public static function activate() {
        // Flush rewrite rules to update the permalinks structure
        flush_rewrite_rules();
    }
}
