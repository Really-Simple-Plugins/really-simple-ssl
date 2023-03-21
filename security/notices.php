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
	$code = rsssl_parse_htaccess_to_html( get_site_option('rsssl_htaccess_rules','') );
	$uploads_code = rsssl_parse_htaccess_to_html( get_site_option('rsssl_uploads_htaccess_rules','') );

	$notices['application-passwords'] = array(
		'callback' => 'rsssl_wp_is_application_passwords_available',
		'score' => 5,
		'output' => array(
			'true' => array(
				'msg' => __("Disable application passwords.", "really-simple-ssl"),
				'icon' => 'premium',
				'url' => 'https://really-simple-ssl.com/definition/what-are-application-passwords/',
				'dismissible' => false,
				'highlight_field_id' => 'disable_application_passwords',
			),
		),
	);

	$notices['htaccess_status'] = array(
		'callback' => 'rsssl_htaccess_status',
		'score' => 5,
		'output' => array(
			'not-writable' => array(
				'title' => __(".htaccess not writable", "really-simple-ssl"),
				'msg' => __("An option that requires the .htaccess file is enabled, but the file is not writable.", "really-simple-ssl").' '.__("Please add the following lines to your .htaccess, or set it to writable:", "really-simple-ssl").$code,
				'icon' => 'warning',
				'dismissible' => true,
				'plusone' => true,
				'url' => 'https://really-simple-ssl.com/manual/editing-htaccess/',
			),
			'not-exists' => array(
				'title' => __(".htaccess does not exist", "really-simple-ssl"),
				'msg' => __("An option that requires the .htaccess file is enabled, but the file does not exist.", "really-simple-ssl").' '.__("Please add the following lines to your .htaccess, or set it to writable:", "really-simple-ssl").$code,
				'icon' => 'warning',
				'dismissible' => true,
				'plusone' => true,
				'url' => 'https://really-simple-ssl.com/manual/editing-htaccess/',
			),
		),
		'show_with_options' => [
			'disable_indexing',
			'redirect'
		]
	);

	$notices['htaccess_status_uploads'] = array(
		'callback' => 'rsssl_uploads_htaccess_status',
		'score' => 5,
		'output' => array(
			'not-writable' => array(
				'title' => __(".htaccess in uploads not writable", "really-simple-ssl"),
				'msg' => __("An option that requires the .htaccess file in the uploads directory is enabled, but the file is not writable.", "really-simple-ssl").' '.__("Please add the following lines to your .htaccess, or set it to writable:", "really-simple-ssl").$uploads_code,
				'icon' => 'warning',
				'dismissible' => true,
				'plusone' => true,
				'url' => 'https://really-simple-ssl.com/manual/editing-htaccess/',
			),
		),
		'show_with_options' => [
			'block_code_execution_uploads',
		]
	);

	$notices['block_display_is_login_enabled'] = array(
		'condition' => ['NOT option_block_display_is_login'],
		'callback' => '_true_',
		'score' => 5,
		'output' => array(
			'true' => array(
				'highlight_field_id' => 'block_display_is_login',
				'msg' => __("It is currently possible to create an administrator user with the same login and display name.", "really-simple-ssl"),
				'icon' => 'open',
				'dismissible' => true,
			),
		),
	);

	$notices['display_name_is_login_exists'] = array(
		'condition' => ['rsssl_get_users_where_display_name_is_login'],
		'callback' => '_true_',
		'score' => 5,
		'output' => array(
			'true' => array(
				'url' => 'https://really-simple-ssl.com/manual/login-and-display-names-should-be-different-for-wordpress/',
				'msg' => __("We have detected administrator roles where the login and display names are the same.", "really-simple-ssl") . "&nbsp;<b>" . rsssl_list_users_where_display_name_is_login_name() . "</b>",
				'icon' => 'open',
				'dismissible' => true,
			),
		),
	);

	$notices['debug_log'] = array(
		'condition' => ['rsssl_debug_log_file_exists_in_default_location'],
		'callback' => 'rsssl_is_debugging_enabled',
		'score' => 5,
		'output' => array(
			'true' => array(
				'highlight_field_id' => 'change_debug_log_location',
				'title' => __("Debugging", "really-simple-ssl"),
				'msg' => __("Your site logs information to a public debugging file.", "really-simple-ssl"),
				'url' => 'https://really-simple-ssl.com/instructions/about-hardening-features/',
				'icon' => 'premium',
				'dismissible' => true,
			),
		),
		'show_with_options' => [
			'change_debug_log_location',
		],
	);

	$notices['user_id_one'] = array(
		'condition' => ['NOT option_disable_user_enumeration'],
		'callback' => '_true_',
		'score' => 5,
		'output' => array(
			'true' => array(
				'msg' => __("Your site is vulnerable to user enumeration attacks.", "really-simple-ssl"),
				'icon' => 'warning',
				'title' => __('Prevent user enumeration','really-simple-ssl'),
				'url' => 'https://really-simple-ssl.com/what-are-user-enumeration-attacks/',
				'dismissible' => true,
				'highlight_field_id' => 'disable_user_enumeration',
			),
		),
		'show_with_options' => [
			'disable_user_enumeration',
		],
	);

	$notices['username_admin_exists'] = array(
		'condition' => ['rsssl_has_admin_user'],
		'callback' => '_true_',
		'score' => 5,
		'output' => array(
			'true' => array(
				'highlight_field_id' => 'rename_admin_user',
				'title' => __("Username", "really-simple-ssl"),
				'msg' => __("Your site registered a user with the name 'admin'.", "really-simple-ssl"),
				'icon' => 'warning',
				'dismissible' => true,
			),
		),
		'show_with_options' => [
			'rename_admin_user',
		],
	);
	$notices['new_username_empty'] = array(
		'condition' => ['rsssl_has_admin_user', 'option_rename_admin_user', 'NOT rsssl_new_username_valid'],
		'callback' => '_true_',
		'score' => 5,
		'output' => array(
			'true' => array(
				'highlight_field_id' => 'rename_admin_user',
				'title' => __("Username", "really-simple-ssl"),
				'msg' => __("Rename admin user enabled: Please choose a new username of at least 3 characters, which is not in use yet.", "really-simple-ssl"),
				'icon' => 'warning',
				'dismissible' => true,
			),
		),
		'show_with_options' => [
			'new_admin_user_login',
		],
	);
	$notices['code-execution-uploads-allowed'] = array(
		'callback' => 'rsssl_code_execution_allowed',
		'score' => 5,
		'output' => array(
			'true' => array(
				'highlight_field_id' => 'block_code_execution_uploads',
				'msg' => __("Code execution is allowed in the public 'Uploads' folder.", "really-simple-ssl"),
				'icon' => 'open',
				'dismissible' => true,
			),
		),
	);
	$notices['db-prefix-notice'] = array(
		'callback' => 'rsssl_is_default_wp_prefix',
		'score' => 5,
		'output' => array(
			'false' => array(
				'msg' => __("Your database prefix is renamed and randomized. Awesome!", "really-simple-ssl"),
				'icon' => 'success',
				'dismissible' => true,
			),
			'true' => array(
				'msg' => __("Your database prefix is set to the default 'wp_'.", "really-simple-ssl"),
				'icon' => 'premium',
				'dismissible' => true,
				'url' => 'https://really-simple-ssl.com/instructions/about-hardening-features/'
			),
		),
	);

//	$notices['xmlrpc'] = array(
//		'callback' => 'rsssl_xmlrpc_allowed',
//		'score' => 10,
//		'output' => array(
//			'true' => array(
//				'highlight_field_id' => 'xmlrpc',
//				'msg' => __("XMLRPC is enabled on your site.", "really-simple-ssl"),
//				'icon' => 'warning',
//				'plusone' => true,
//			),
//		),
//		'show_with_options' => [
//			'xmlrpc',
//		],
//	);

	$notices['file-editing'] = array(
		'callback' => 'rsssl_file_editing_allowed',
		'score' => 5,
		'output' => array(
			'true' => array(
				'highlight_field_id' => 'disable_file_editing',
				'msg' => __("The built-in file editors are accessible to others.", "really-simple-ssl"),
//					'url' => 'https://wordpress.org/support/article/editing-wp-config-php/#disable-the-plugin-and-theme-editor',
				'icon' => 'open',
				'dismissible' => true,
			),
		),
	);

	$notices['registration'] = array(
		'callback' => 'rsssl_user_registration_allowed',
		'score' => 5,
		'output' => array(
			'true' => array(
				'highlight_field_id' => 'disable_anyone_can_register',
				'msg' => __("Anyone can register an account on your site. Consider disabling this option in the WordPress general settings.", "really-simple-ssl"),
				'icon' => 'open',
				'plusone' => false,
				'dismissible' => true,
			),
		),
	);

	$notices['hide-wp-version'] = array(
		'callback' => 'rsssl_src_contains_wp_version',
		'score' => 5,
		'output' => array(
			'true' => array(
				'highlight_field_id' => 'hide_wordpress_version',
				'msg' => __("Your WordPress version is visible to others.", "really-simple-ssl"),
				'icon' => 'open',
				'dismissible' => true,
			),
		),
	);

//	$notices['login-url-not-working'] = array(
//		'callback' => 'NOT rsssl_new_login_url_working',
//		'score' => 5,
//		'output' => array(
//			'true' => array(
//				'msg' => __("Your new login URL does not seem to work. Still using /wp-admin and /wp-login.php.", "really-simple-ssl"),
//				'url' => 'https://really-simple-ss.com/',
//				'icon' => 'warning',
//				'dismissible' => true,
//			),
//		),
//	);

	return $notices;
}
add_filter('rsssl_notices', 'rsssl_general_security_notices');
