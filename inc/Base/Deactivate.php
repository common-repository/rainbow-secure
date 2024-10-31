<?php
/**
 * @package RainbowSecure
 */

namespace rainbow_secure_Inc\Base;

class Deactivate
{
    public static function deactivate(){
        flush_rewrite_rules();
    }
}