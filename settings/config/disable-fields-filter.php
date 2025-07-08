<?php
defined('ABSPATH') or die();

// Add actions for email verification and resend logi
if ( get_option('rsssl_email_verification_status' ) !== 'completed' ) {
	add_action( 'admin_footer', 'rsssl_generate_email_verification_buttons_js' );
	add_action( 'wp_ajax_rsssl_force_confirm_email', 'rsssl_handle_force_confirm_email' );
	add_action( 'wp_ajax_rsssl_resend_verification_email', 'rsssl_resend_verification_email' );
}

/**
 * @param $fields
 *
 * @return mixed
 */
function rsssl_modify_fields($fields){
	$redirect_index = array_search( 'redirect', array_column( $fields, 'id' ), true );

	$htaccess_redirect_allowed = RSSSL()->admin->htaccess_redirect_allowed();
	if ( !rsssl_uses_htaccess() || ! $htaccess_redirect_allowed ) {
		unset($fields[$redirect_index]['options']['htaccess']);
	} else {
		$fields[$redirect_index]['warning'] = true;
		$fields[$redirect_index]['tooltip'] = ' '.esc_html__('On Apache you can use a .htaccess redirect, which is usually faster, but may cause issues on some configurations. Read the instructions in the sidebar first.', 'really-simple-ssl');
		if ( rsssl_get_option('redirect' ) !== 'htaccess' ) {
			$fields[ $redirect_index ]['help'] = [
				'label' => 'warning',
				'title' => esc_html__( "Redirect method", 'really-simple-ssl' ),
				'text'  => esc_html__( 'Enable .htaccess only if you know how to regain access in case of issues.', 'really-simple-ssl' ) . ' ' . esc_html__( 'Redirects your site to https with a SEO friendly 301 redirect if it is requested over http.', 'really-simple-ssl' ),
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

	if ( ! rsssl_is_email_verified() && rsssl_get_option('login_protection_enabled') == '1' ) {
		$index = array_search( 'login_protection_enabled', array_column( $fields, 'id' ), true );
		$fields[$index]['help'] = rsssl_email_help_text();
//		$fields[$index]['disabled'] = true;
		$fields = array_values($fields);
	}

	if ( ! rsssl_is_email_verified() && rsssl_get_option('enable_vulnerability_scanner') == '1' ) {
		$index = array_search( 'vulnerability_notification_email_admin', array_column( $fields, 'id' ), true );
		$fields[$index]['help'] = rsssl_email_help_text();
		$fields = array_values($fields);
	}

	if ( rsssl_maybe_disable_404_blocking() ) {
		$index = array_search( '404_blocking_threshold', array_column( $fields, 'id' ), true );
		//if LLA is not included yet, this index will be false.
		if ( $index !== false ) {
			$fields[$index]['help'] = [
				'label' => 'warning',
				'title' => esc_html__( "404 errors detected on your homepage", 'really-simple-ssl' ),
				'url'   => '404-not-found-errors',
				'text'  => '404 errors detected on your homepage. 404 blocking is unavailable, to prevent blocking of legitimate visitors. It is strongly recommended to resolve these errors.',
			];

			$fields = array_values($fields);
		}
	}

	if ( get_option('rsssl_email_verification_status' ) !== 'completed' ) {
		$email_notifications_index = array_search( 'notifications_email_address', array_column( $fields, 'id' ), true );
		$fields[ $email_notifications_index ]['help'] = [
			'label' => 'default',
			'title' => esc_html__( "Verification email sent", 'really-simple-ssl' ),
			'text' => sprintf(
				esc_html__( 'Please check your inbox and click the confirm button to confirm that your site is correctly configured to send emails. Didn\'t receive the e-mail? %1$s, or try to: %2$s %3$s %4$s', 'really-simple-ssl' ),
				'<a href="https://really-simple-ssl.com/email-notifications/" target="_blank">' . esc_html__( 'Check your site\'s SMTP settings', 'really-simple-ssl' ) . '</a>',
				'<br><br>',
				'<a class="rsssl-text-link" id="rsssl-resend-verification">' . esc_html__( 'Resend email', 'really-simple-ssl' ) . '</a>',
				'<a class="rsssl-text-link" id="rsssl-force-confirm">' . esc_html__( 'Force confirm email', 'really-simple-ssl' ) . '</a>'
			),
		];
	}

	return $fields;
}
add_filter('rsssl_fields', 'rsssl_modify_fields', 500, 1);

function rsssl_email_help_text() {
	$verifiedText = esc_html__("Email validation completed", 'really-simple-ssl');
	$nonVerifiedText = esc_html__("Email not verified yet. Verify your email address to get the most out of Really Simple Security.", "really-simple-ssl");

	if (rsssl_check_if_email_essential_feature()) {
		$nonVerifiedText = esc_html__("You're using a feature where email is an essential part of the functionality. Please validate that you can send emails on your server.", 'really-simple-ssl');
	}

	$text = rsssl_is_email_verified() ? $verifiedText : $nonVerifiedText;

	return [
		'label' => rsssl_is_email_verified() ? 'success' : 'warning',
		'title' => esc_html__("Email validation", 'really-simple-ssl'),
		'url'   => 'instructions/email-verification',
		'text'  => wp_kses_post($text),
	];
}