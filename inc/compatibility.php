<?php

if (!function_exists('rainbow_secure_wp_roles'))
{
    function rainbow_secure_wp_roles() {
        global $wp_roles;
     
        if ( ! isset( $wp_roles ) ) {
            $wp_roles = new WP_Roles(); // Override ok.
        }
        return $wp_roles;
    }
}