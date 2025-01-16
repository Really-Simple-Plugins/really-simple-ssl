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
		if ( rsssl_get_option('redirect' ) !== 'htaccess' ) {
			$fields[ $redirect_index ]['help'] = [
				'label' => 'warning',
				'title' => __( "Redirect method", 'really-simple-ssl' ),
				'text'  => __( 'Enable .htaccess only if you know how to regain access in case of issues.', 'really-simple-ssl' ) . ' ' . __( 'Redirects your site to https with a SEO friendly 301 redirect if it is requested over http.', 'really-simple-ssl' ),
				'url'   => 'remove-htaccess-redirect-site-lockout',
			];
		}
	}

	if ( is_multisite() && !rsssl_is_networkwide_active() ){
		unset($fields[$redirect_index]['options']['htaccess']);
		$fields = array_values($fields);
	}

	// 2FA and LLA e-mail verification help texts
	if ( ! rsssl_is_email_verified() ) {
		$index = array_search( 'send_verification_email', array_column( $fields, 'id' ), true );
		$fields[$index]['help'] = rsssl_email_help_text();
		$fields = array_values($fields);
	}

	if ( ! rsssl_is_email_verified() && rsssl_get_option('two_fa_enabled_email') == '1' ) {
		$index = array_search( 'two_fa_enabled_email', array_column( $fields, 'id' ), true );
		$fields[$index]['help'] = rsssl_email_help_text();
		$fields = array_values($fields);
	}

	if ( ! rsssl_is_email_verified() && rsssl_get_option('enable_limited_login_attempts') == '1' ) {
		$index = array_search( 'limit_login_attempts_amount', array_column( $fields, 'id' ), true );
		//if LLA is not included yet, this index will be false.
		if ( $index !== false ) {
			$fields[$index]['help'] = rsssl_email_help_text();
			$fields = array_values($fields);
		}

	}

	if ( rsssl_maybe_disable_404_blocking() ) {
		$index = array_search( '404_blocking_threshold', array_column( $fields, 'id' ), true );
		//if LLA is not included yet, this index will be false.
		if ( $index !== false ) {
			$fields[$index]['help'] = [
				'label' => 'warning',
				'title' => __( "404 errors detected on your homepage", 'really-simple-ssl' ),
				'url'   => '404-not-found-errors',
				'text'  => '404 errors detected on your homepage. 404 blocking is unavailable, to prevent blocking of legitimate visitors. It is strongly recommended to resolve these errors.',
			];

			$fields = array_values($fields);
		}
	}

	return $fields;
}
add_filter('rsssl_fields', 'rsssl_remove_fields', 500, 1);

function rsssl_email_help_text() {

	return [
		'label' => rsssl_is_email_verified() ? 'success' : 'warning',
		'title' => __( "Email validation", 'really-simple-ssl' ),
		'url'   => 'instructions/email-verification',
		'text'  => rsssl_is_email_verified()
			? __( "Email validation completed", 'really-simple-ssl' )
			: ( rsssl_check_if_email_essential_feature()
				? __( "You're using a feature where email is an essential part of the functionality. Please validate that you can send emails on your server.", 'really-simple-ssl' )
				: __("Email not verified yet. Verify your email address to get the most out of Really Simple Security.", "really-simple-ssl")
			),
	];
}
