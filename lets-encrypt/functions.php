<?php

defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );

if ( ! function_exists( 'rsssl_user_can_manage' ) ) {
    function rsssl_user_can_manage() {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return false;
        }

        return true;
    }
}

if (!function_exists('rsssl_read_more')) {
    /**
     * Create a generic read more text with link for help texts.
     *
     * @param string $url
     * @param bool   $add_space
     *
     * @return string
     */
    function rsssl_read_more( $url, $add_space = true ) {
        $html
            = sprintf( __( "For more information on this subject, please read this %sarticle%s",
            'complianz-terms-conditions' ), '<a target="_blank" href="' . $url . '">',
            '</a>' );
        if ( $add_space ) {
            $html = '&nbsp;' . $html;
        }

        return $html;
    }
}