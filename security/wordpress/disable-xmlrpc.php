<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

/**
 * Disable XMLRPC when this integration is activated
 */

add_filter('xmlrpc_enabled', '__return_false');
/**
 * Remove html link
 */
remove_action( 'wp_head', 'rsd_link' );
/**
 * stop all requests to xmlrpc.php for RSD per XML-RPC:
 */
if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST )
    exit;






