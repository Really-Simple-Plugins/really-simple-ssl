<?php defined( 'ABSPATH' ) or die();

/**
 * @param $notices
 * @return mixed
 * Notice function
 */
function rsssl_code_execution_errors_notice( $notices ) {
	$notices['code-execution-uploads'] = array(
		'callback' => 'rsssl_code_execution_allowed',
		'score' => 5,
		'output' => array(
			'file-not-found' => array(
				'msg' => __("Could not find code execution test file.", "really-simple-ssl"),
				'icon' => 'open',
				'dismissible' => true,
			),
			'uploads-folder-not-writable' => array(
				'msg' => __("Uploads folder not writable.", "really-simple-ssl"),
				'icon' => 'open',
				'dismissible' => true,
			),
			'could-not-create-test-file' => array(
				'msg' => __("Could not copy code execution test file.", "really-simple-ssl"),
				'icon' => 'open',
				'dismissible' => true,
			),
		),
	);

	if ( rsssl_get_server() === 'nginx') {
		$notices['code-execution-uploads-nginx'] = array(
			'callback' => 'rsssl_code_execution_allowed',
			'score' => 5,
			'output' => array(
				'true' => array(
					'msg' => __("The code to block code execution in the uploads folder cannot be added automatically on nginx. Add the following code to your nginx.conf file:", "really-simple-ssl")
					         . "<br>" . rsssl_get_nginx_code_code_execution_uploads(),
					'icon' => 'open',
					'dismissible' => true,
				),
			),
		);
	}
	return $notices;
}
add_filter('rsssl_notices', 'rsssl_code_execution_errors_notice');


/**
 * Block code execution
 * @param array $rules
 *
 * @return []
 *
 */
function rsssl_disable_code_execution_rules($rules)
{
	if ( !rsssl_get_option('block_code_execution_uploads')) {
		return $rules;
	}

	// Use IfModule to let Apache decide which syntax to use based on loaded modules.
	// mod_authz_core is available in Apache 2.4+, mod_access in Apache 2.2.
	$rule = <<<HTACCESS

<Files *.php>
<IfModule mod_authz_core.c>
Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
Order deny,allow
Deny from all
</IfModule>
</Files>
HTACCESS;

	$rules[] = ['rules' => $rule, 'identifier' => 'Require all denied'];
	return $rules;
}
add_filter('rsssl_htaccess_security_rules_uploads', 'rsssl_disable_code_execution_rules');



function rsssl_get_nginx_code_code_execution_uploads() {
    $code = '<code>location ~* /uploads/.*\.php$ {' . "<br>";
    $code .= '&nbsp;&nbsp;&nbsp;&nbsp;return 503;' . "<br>";
    $code .= '}</code>' . "<br>";

    return $code;
}

