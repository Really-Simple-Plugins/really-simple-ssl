<?php

/**
 * @return string
 * Delete transients
 */
if ( ! function_exists('rsssl_delete_transients' ) ) {
    function rsssl_delete_transients()
    {

        $transients = array(
          'rsssl_xmlrpc_allowed',
        );

        foreach ( $transients as $transient ) {
            delete_transient( $transient );
        }
    }
}