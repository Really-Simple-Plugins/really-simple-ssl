<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

/**
 * Disable XMLRPC when this integration is activated
 */

add_filter('xmlrpc_enabled', '__return_false');






