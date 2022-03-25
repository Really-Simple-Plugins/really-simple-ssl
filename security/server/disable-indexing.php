<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

/**
 * @return void
 * Disable indexing
 */
function rsssl_disable_indexing() {
    if ( rsssl_get_server() == 'apache' ) {
	    // Get .htaccess
	    $htaccess_file = RSSSL()->really_simple_ssl->htaccess_file();
	    if ( file_exists( $htaccess_file ) && is_writable( $htaccess_file ) ) {
		     $htaccess = file_get_contents($htaccess_file);
			if ( stripos($htaccess, 'options -indexes') !== false ) {
				update_option('rsssl_sec_disabled_indexing', false);
				return;
			} else {
			    update_option('rsssl_sec_disabled_indexing', true);
				rsssl_wrap_headers();
			}
	    }
    }

    if ( rsssl_get_server() == 'nginx' ) {

//			wrap_headers('nginx') {
//
//			}
//		    server {
//			    location /{anydir} {
//				    autoindex off;
		//  }
		//}

    }
}

add_action('admin_init', 'rsssl_disable_indexing');