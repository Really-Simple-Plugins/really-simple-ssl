<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

if ( !function_exists( 'rsssl_disable_indexing' ) ) {
    function rsssl_disable_indexing() {

	    if ( rsssl_get_server() == 'apache' ) {
		    // Get .htaccess
		    $htaccess_file = rsssl_htaccess_file();
		    if ( file_exists( $htaccess_file ) && is_writable( $htaccess_file ) ) {
			     $htaccess = file_get_contents($htaccess_file);
				if ( stripos($htaccess, 'options -indexes') !== false ) {
					return;
				} else {
					// insert into .htaccess
					$rules = "\n" . "Options -Indexes" . "\n";
					$htaccess = $htaccess . $rules;
					file_put_contents($htaccess_file, $htaccess);
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
}

rsssl_disable_indexing();