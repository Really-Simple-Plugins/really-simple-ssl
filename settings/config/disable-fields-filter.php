<?php
defined('ABSPATH') or die();

/**
 * @param $fields
 *
 * @return mixed
 */
function rsssl_remove_fields($fields){
	$redirect_index = array_search( 'redirect', array_column( $fields, 'id' ), true );
	if ( !rsssl_uses_htaccess() ){
		unset($fields[$redirect_index]['options']['htaccess']);
	} else {
		$fields[$redirect_index]['warning'] = true;
		$fields[$redirect_index]['tooltip'] = ' '.__('On Apache you can use a .htaccess redirect, which is usually faster, but may cause issues on some configurations. Read the instructions in the sidebar first.', 'really-simple-ssl');
		$fields[$redirect_index]['help'] = [
			'label' => 'warning',
			'title' => __( "Redirect method", 'really-simple-ssl' ),
			'text'  => __( 'Enable .htaccess only if you know how to regain access in case of issues.', 'really-simple-ssl' ).' '.__( 'Redirects your site to https with a SEO friendly 301 redirect if it is requested over http.', 'really-simple-ssl' ),
			'url'  => 'https://really-simple-ssl.com/remove-htaccess-redirect-site-lockout/',
		];
//		$fields[$redirect_index]['email'] = [
//			'title'   => __( ".htaccess redirect", 'really-simple-ssl' ),
//			'message' => __( "The .htaccess redirect has been enabled on your site. If the server configuration is non-standard, this might cause issues. Please check if all pages on your site are functioning properly.", 'really-simple-ssl' ),
//			'url'     => 'https://really-simple-ssl.com/remove-htaccess-redirect-site-lockout/',
//		];
	}

	if ( is_multisite() && !rsssl_is_networkwide_active() ){
		unset($fields[$redirect_index]['options']['htaccess']);
	}

	if ( !rsssl_get_option('do_not_edit_htaccess') ){
		$index = array_search( 'do_not_edit_htaccess', array_column( $fields, 'id' ), true );
		unset($fields[$index]);
	}
	return $fields;
}
add_filter('rsssl_fields', 'rsssl_remove_fields', 10, 1);


