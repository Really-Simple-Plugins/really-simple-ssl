<?php

use RSSSL\Security\RSSSL_Htaccess_File_Manager;

defined( 'ABSPATH' ) or die();

/**
 * Convert htaccess rules to html friendly layout
 *
 * @param string $code
 *
 * @return string
 */
function rsssl_parse_htaccess_to_html( string $code): string {
	$normalized_code = preg_replace( "/\r\n?|\r/", "\n", $code );
	if ( is_string( $normalized_code ) ) {
		$code = $normalized_code;
	}

	$code = ltrim( $code, "\n" );
	$code = str_replace( '<br>', "\n", $code );
	$code_arr = explode( "\n", $code );
	$code_arr = array_map('esc_html', $code_arr);
	$code = implode('<br>', $code_arr);
	return '<br><code>' . $code . '</code><br>';
}

function rsssl_general_security_notices( $notices ) {
	$code                 = rsssl_parse_htaccess_to_html( get_site_option( 'rsssl_htaccess_rules', '' ) );
	$uploads_code         = rsssl_parse_htaccess_to_html( get_site_option( 'rsssl_uploads_htaccess_rules', '' ) );
	$open_hardening_count = rsssl_count_open_hardening_features();

	// Unified error message format for .htaccess issues
	// Note: 'not-supported' (file doesn't exist) is handled silently - no notice shown
	// as the plugin falls back to PHP redirect / advanced-headers.php
	$notices['htaccess_status'] = array(
		'callback'          => 'rsssl_htaccess_status',
		'score'             => 5,
		'output'            => array(
			RSSSL_Htaccess_File_Manager::ERROR_NOT_WRITABLE => array(
				'title'       => __( "Failed to update .htaccess", "really-simple-ssl" ),
				'msg'         => __( "Failed to update security setting in your .htaccess file: file is not writable.", "really-simple-ssl" ) . '<br><br>'
				                 . '<strong>' . __( "Resolution:", "really-simple-ssl" ) . '</strong><br>'
				                 . __( "1. Update file permissions to make .htaccess writable, or", "really-simple-ssl" ) . '<br>'
				                 . __( "2. Add the following code manually:", "really-simple-ssl" ) . $code,
				'clear_cache_id' => 'managed_htaccess',
				'icon'        => 'warning',
				'dismissible' => true,
				'plusone'     => true,
				'url'         => 'manual/editing-htaccess/',
			),
			RSSSL_Htaccess_File_Manager::ERROR_NOT_READABLE => array(
				'title'       => __( "Failed to update .htaccess", "really-simple-ssl" ),
				'msg'         => __( "Failed to update security setting in your .htaccess file: file is not readable.", "really-simple-ssl" ) . '<br><br>'
				                 . '<strong>' . __( "Resolution:", "really-simple-ssl" ) . '</strong><br>'
				                 . __( "1. Update file permissions to make .htaccess readable and writable, or", "really-simple-ssl" ) . '<br>'
				                 . __( "2. Add the following code manually:", "really-simple-ssl" ) . $code,
				'clear_cache_id' => 'managed_htaccess',
				'icon'        => 'warning',
				'dismissible' => true,
				'plusone'     => true,
				'url'         => 'manual/editing-htaccess/',
			),
		),
		'show_with_options' => [
			'disable_indexing',
			'redirect'
		]
	);

	$notices['htaccess_status_uploads'] = array(
		'callback'          => 'rsssl_uploads_htaccess_status',
		'score'             => 5,
		'output'            => array(
			'not-writable' => array(
				'title'       => __( "Failed to update uploads .htaccess", "really-simple-ssl" ),
				'msg'         => __( "Failed to update security setting in your uploads .htaccess file: file is not writable.", "really-simple-ssl" ) . '<br><br>'
				                 . '<strong>' . __( "Resolution:", "really-simple-ssl" ) . '</strong><br>'
				                 . __( "1. Update file permissions to make the uploads .htaccess writable, or", "really-simple-ssl" ) . '<br>'
				                 . __( "2. Add the following code manually:", "really-simple-ssl" ) . $uploads_code,
				'icon'        => 'warning',
				'dismissible' => true,
				'plusone'     => true,
				'url'         => 'manual/editing-htaccess/',
			),
		),
		'show_with_options' => [
			'block_code_execution_uploads',
		]
	);

	$notices['display_name_is_login_exists'] = array(
		'condition' => [ 'rsssl_get_users_where_display_name_is_login' ],
		'callback'  => '_true_',
		'score'     => 5,
		'output'    => array(
			'true' => array(
				'url'         => 'manual/login-and-display-names-should-be-different-for-wordpress/',
				'msg'         => __( "We have detected administrator roles where the login and display names are the same.", "really-simple-ssl" ) . "&nbsp;<b>" . rsssl_list_users_where_display_name_is_login_name() . "</b>",
				'icon'        => 'open',
				'dismissible' => true,
			),
		),
	);

	$notices['new_username_empty'] = array(
		'condition'         => [ 'rsssl_has_admin_user', 'option_rename_admin_user', 'NOT rsssl_new_username_valid' ],
		'callback'          => '_true_',
		'score'             => 5,
		'output'            => array(
			'true' => array(
				'highlight_field_id' => 'rename_admin_user',
				'title'              => __( "Username", "really-simple-ssl" ),
				'msg'                => __( "Rename admin user enabled: Please choose a new username of at least 3 characters, which is not in use yet.", "really-simple-ssl" ),
				'icon'               => 'warning',
				'dismissible'        => true,
			),
		),
		'show_with_options' => [
			'new_admin_user_login',
		],
	);

	$notices['enable_vulnerability_scanner'] = array(
		'callback' => 'option_enable_vulnerability_scanner',
		'score'    => 5,
		'output'   => array(
			'false' => array(
				'highlight_field_id' => 'enable_vulnerability_scanner',
				'msg'                => __( "Enable the Vulnerability scan to detect possible vulnerabilities.", 'really-simple-ssl' ),
				'icon'               => 'open',
				'admin_notice'       => false,
				'dismissible'        => true,
				'plusone'            => false,
			),
			'true'  => array(
				'msg'  => __( "Vulnerability scanning is enabled.", 'really-simple-ssl' ),
				'icon' => 'success',
			),
		),
	);

	$notices['count_open_hardening_features'] = array(
		'callback' => 'rsssl_has_open_hardening_features',
		'score'    => 5,
		'output'   => array(
			'true'  => array(
				'highlight_field_id' => 'disable_anyone_can_register',
				'msg'                => sprintf(
					_n(
						"You have %s open hardening feature.",
						"You have %s open hardening features.",
						$open_hardening_count,
						"really-simple-ssl"
					),
					$open_hardening_count
				),
				'icon'               => 'open',
				'dismissible'        => true,
			),
			'false' => array(
				'msg'  => __( "All recommended hardening features enabled.", "really-simple-ssl" ),
				'icon' => 'success',
			),
		),
	);

    $notices['lock_file_exists'] = array(
        'callback' => 'rsssl_lock_file_exists',
        'score'    => 5,
        'output'   => array(
            'true'  => array(
                'msg'  => __( 'The Firewall, LLA and 2FA are currently inactive, as you have activated Safe Mode with the rsssl-safe-mode.lock file. Remove the file from your /wp-content folder after you have finished debugging.', 'really-simple-ssl' ),
                'icon' => 'warning',
            ),
        ),
    );

	return $notices;
}
add_filter('rsssl_notices', 'rsssl_general_security_notices');
