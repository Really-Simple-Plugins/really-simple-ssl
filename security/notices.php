<?php defined( 'ABSPATH' ) or die();
/**
 * Convert htaccess rules to html friendly layout
 *
 * @param string $code
 *
 * @return string
 */
function rsssl_parse_htaccess_to_html( string $code): string {
	if ( strpos($code, "\n")===0 ) {
		$code = 	preg_replace('/\n/', '', $code, 1);
	}
	//split into linebreak separated array, so we can run esc_html on the result
	$code = 	preg_replace('/\n/', '--br--', $code, 1);
	$code = 	preg_replace('/<br>/', '--br--', $code, 1);
	$code_arr = explode('--br--', $code);
	$code_arr = array_map('esc_html', $code_arr);
	$code = implode('<br>', $code_arr);
	return '<br><code>' . $code . '</code><br>';
}

function rsssl_general_security_notices( $notices ) {
	$code                 = rsssl_parse_htaccess_to_html( get_site_option( 'rsssl_htaccess_rules', '' ) );
	$uploads_code         = rsssl_parse_htaccess_to_html( get_site_option( 'rsssl_uploads_htaccess_rules', '' ) );
	$open_hardening_count = rsssl_count_open_hardening_features();

	$notices['htaccess_status'] = array(
		'callback'          => 'rsssl_htaccess_status',
		'score'             => 5,
		'output'            => array(
			'not-writable' => array(
				'title'       => __( ".htaccess not writable", "really-simple-ssl" ),
				'msg'         => __( "An option that requires the .htaccess file is enabled, but the file is not writable.", "really-simple-ssl" ) . ' ' . __( "Please add the following lines to your .htaccess, or set it to writable:", "really-simple-ssl" ) . $code,
				'icon'        => 'warning',
				'dismissible' => true,
				'plusone'     => true,
				'url'         => 'manual/editing-htaccess/',
			),
			'not-exists'   => array(
				'title'       => __( ".htaccess does not exist", "really-simple-ssl" ),
				'msg'         => __( "An option that requires the .htaccess file is enabled, but the file does not exist.", "really-simple-ssl" ) . ' ' . __( "Please add the following lines to your .htaccess, or set it to writable:", "really-simple-ssl" ) . $code,
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
				'title'       => __( ".htaccess in uploads not writable", "really-simple-ssl" ),
				'msg'         => __( "An option that requires the .htaccess file in the uploads directory is enabled, but the file is not writable.", "really-simple-ssl" ) . ' ' . __( "Please add the following lines to your .htaccess, or set it to writable:", "really-simple-ssl" ) . $uploads_code,
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
