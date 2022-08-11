<?php defined( 'ABSPATH' ) or die();

function rsssl_general_security_notices( $notices ) {
	$code = get_site_option('rsssl_htaccess_rules');
	$code            = '<br><code>' . $code . '</code><br>';

	$notices['application-passwords'] = array(
		'callback' => 'wp_is_application_passwords_available',
		'score' => 5,
		'output' => array(
			'_true_' => array(
				'msg' => __("Application passwords enabled.", "really-simple-ssl"),
				'icon' => 'open',
				'dismissible' => true,
			),
		),
	);

	$notices['htaccess_status'] = array(
		'callback' => 'rsssl_htaccess_status',
		'score' => 5,
		'output' => array(
			'not-writable' => array(
				'msg' => __("An option was enabled which requires the .htaccess to get written, but the .htaccess is not writable.", "really-simple-ssl").' '.__("Please add the following lines to your .htaccess, or set it to writable:", "really-simple-ssl").$code,
				'icon' => 'open',
				'dismissible' => true,
			),
			'not-exists' => array(
				'msg' => __("An option was enabled which requires the .htaccess to get written, but the .htaccess does not exist.", "really-simple-ssl").' '.__("Please add the following lines to your .htaccess, or set it to writable:", "really-simple-ssl").$code,
				'icon' => 'open',
				'dismissible' => true,
			),

			'not-writable-uploads' => array(
				'msg' => __("An option was enabled which requires the .htaccess in the uploads directory to get written, but the .htaccess or directory is not writable.", "really-simple-ssl").' '.__("Please add the following lines to your .htaccess, or set it to writable:", "really-simple-ssl").$code,
				'icon' => 'open',
				'dismissible' => true,
			),
		),
		'show_with_options' => [
			'block_code_execution_uploads',
		]
	);

	$notices['display_name_is_login'] = array(
		'condition' => ['rsssl_display_name_equals_login'],
		'callback' => '_true_',
		'score' => 5,
		'output' => array(
			'true' => array(
				'msg' => __("Your display name is the same as your login. This is a security risk. We recommend to change your display name to something else.", "really-simple-ssl"),
				'icon' => 'open',
				'dismissible' => true,
			),
		),
	);
	$notices['debug-log-notice'] = array(
		'condition' => ['rsssl_is_debug_log_enabled', 'rsssl_debug_log_in_default_location'],
		'callback' => '_true_',
		'score' => 5,
		'output' => array(
			'true' => array(
				'highlight_field_id' => 'change_debug_log_location',
				'msg' => __("Errors are logged to default debug.log location.", "really-simple-ssl"),
				'icon' => 'open',
				'dismissible' => true,
			),
		),
	);
	$notices['user_id_one'] = array(
		'condition' => ['rsssl_id_one_no_enumeration'],
		'callback' => '_true_',
		'score' => 5,
		'output' => array(
			'true' => array(
				'msg' => __("User id 1 exists and user enumeration hasn't been disabled.", "really-simple-ssl"),
				'icon' => 'open',
				'dismissible' => true,
			),
		),
	);
	$notices['username_admin_exists'] = array(
		'condition' => ['rsssl_has_admin_user'],
		'callback' => '_true_',
		'score' => 5,
		'output' => array(
			'true' => array(
				'highlight_field_id' => 'rename_admin_user',
				'msg' => __("A Username 'admin' exists", "really-simple-ssl"),
				'icon' => 'open',
				'dismissible' => true,
			),
		),
	);
	$notices['code-execution-uploads-allowed'] = array(
		'callback' => 'rsssl_code_execution_allowed',
		'score' => 5,
		'output' => array(
			'true' => array(
				'highlight_field_id' => 'block_code_execution_uploads',
				'msg' => __("Code execution allowed in uploads folder.", "really-simple-ssl"),
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
				'msg' => __("Database prefix is not default. Awesome!", "really-simple-ssl"),
				'icon' => 'open',
				'dismissible' => true,
			),
			'true' => array(
				'msg' => __("Database prefix set to default wp_", "really-simple-ssl"),
				'icon' => 'open',
				'dismissible' => true,
			),
		),
	);

	$notices['debug_log'] = array(
		'condition' => ['rsssl_debug_log_in_default_location'],
		'callback' => '_true_',
		'score' => 5,
		'output' => array(
			'true' => array(
				'msg' => __("Warning: debug.log security risk", "really-simple-ssl"),
				'icon' => 'open',
				'dismissible' => true,
			),
		),
	);
	return $notices;
}
add_filter('rsssl_notices', 'rsssl_general_security_notices');

