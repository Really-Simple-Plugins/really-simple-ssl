<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

if ( ! function_exists( 'hide_debug_log_notice' ) ) {
    function hide_debug_log_notice( $notices ) {
        $notices['debug-log-notice'] = array(
            'callback' => 'rsssl_debug_log_notice',
            'score' => 5,
            'output' => array(
                'default' => array(
                    'msg' => __("Errors are logged to default debug.log location.", "really-simple-ssl"),
                    'icon' => 'open',
                    'dismissible' => true,
                ),
            ),
        );

        return $notices;
    }
}

if ( !function_exists( 'hide_debug_log_notice' ) ) {
    function hide_debug_log_notice() {
        $wpconfig_path = find_wp_config_path();
        $wpconfig = file_get_contents($wpconfig_path);

        if ( strpos($wpconfig, "'WP_DEBUG_LOG'," ) !== FALSE) {
            return 'default';
        }

    }
}

if ( !function_exists( 'rsssl_change_debug_log_location' ) ) {
    function rsssl_change_debug_log_location() {

        $wpconfig_path = find_wp_config_path();
        $wpconfig = file_get_contents($wpconfig_path);

        if ((strlen($wpconfig)!=0) && is_writable($wpconfig_path)) {

            $old ="'WP_DEBUG_LOG', true";
            $new = "'WP_DEBUG_LOG', 'wp-content/uploads/debug.log'";

            //now replace these urls
            $wpconfig = str_replace($old, $new, $wpconfig);

            file_put_contents($wpconfig_path, $wpconfig);
        }

    }
}

