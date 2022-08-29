<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

/**
 * @return void
 * Maybe disable XMLRPC
 */
add_filter('xmlrpc_enabled', '__return_false');






