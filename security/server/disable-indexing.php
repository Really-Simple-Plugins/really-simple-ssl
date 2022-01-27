<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

add_action('rsssl_get_server' , 'rsssl_get_server');

function rsssl_get_server() {
    $server = RSSSL()->rsssl_server->get_server();
}

if ( !function_exists( 'rsssl_disable_indexing' ) ) {
    function rsssl_disable_indexing() {

        error_log(do_action('rsssl_get_server'));

        // Get .htaccess

        // Contains options - indexes

        // Add Options -Indexes
    }
}

rsssl_disable_indexing();