<?php defined( 'ABSPATH' ) or die();

function rsssl_maybe_disable_code_execution_uploads() {
	if ( rsssl_code_execution_allowed() ){
		rsssl_disable_code_execution_uploads();
	}
}
add_action('admin_init','rsssl_maybe_disable_code_execution_uploads');

/**
 * Run check once a day
 */
add_action( 'rsssl_every_day_hook', 'rsssl_disable_code_execution_uploads' );

/**
 * @param $notices
 * @return mixed
 * Notice function
 */
function rsssl_code_execution_errors_notice( $notices ) {
	$notices['code-execution-uploads'] = array(
		'callback' => 'rsssl_code_execution_uploads_test',
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
	return $notices;
}
add_filter('rsssl_notices', 'rsssl_code_execution_errors_notice');


/**
 * @param $notices
 *
 * @return mixed
 *
 * Show NGINX code execution notice
 */

function rsssl_code_execution_nginx_notice( $notices ) {
    $notices['code-execution-uploads-nginx'] = array(
        'callback' => '_true_',
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

    return $notices;
}

/**
 * @return string
 * Test if code execution is allowed in /uploads folder
 */

function rsssl_code_execution_uploads_test()
{
    $upload_dir = wp_get_upload_dir();
	$return = '';
    $test_file = $upload_dir['basedir'] . '/' . 'code-execution.php';
	if ( is_writable($upload_dir['basedir'] )  ) {
		if ( ! file_exists( $test_file ) ) {
			copy( rsssl_path . 'security/tests/code-execution.php', $test_file );
		}
	}

    if ( file_exists( $test_file ) ) {
        require_once( $test_file );
        if ( function_exists( 'rsssl_test_code_execution' ) && rsssl_test_code_execution() ) {
            $return = 'allowed';
        }
    } else {
        if ( ! is_writable( $upload_dir['basedir'] ) ) $return = 'uploads-folder-not-writable';
        if ( ! file_exists( $test_file ) ) $return = 'could-not-create-test-file';
    }

    return $return;
}

/**
 * @return string
 * Add add an .htaccess file to block code execution in the /uploads directory
 */
function rsssl_disable_code_execution_uploads()
{
    $upload_dir = wp_get_upload_dir();
    if ( rsssl_get_server() === 'apache' ) {
        if ( !is_writable($upload_dir['basedir'])) return;
        if ( !file_exists($upload_dir['basedir'] . '/' . '.htaccess')) {
	        // create .htaccess file
            rsssl_insert_disable_code_execution_rules($upload_dir);
        } else {
	        $htaccess = file_get_contents($upload_dir['basedir'] . '/' . '.htaccess');
            $regex = "/<Files \*\.php>\ndeny from all/s";
            preg_match_all($regex, $htaccess, $matches);
            // Group 0 contains str
            if ( ! $matches[0] ) {
                // Does .htaccess not contain *.php rules, enter them
                rsssl_insert_disable_code_execution_rules( $upload_dir );
            }
        }
    }

    if ( rsssl_get_server() === 'nginx') {
        add_filter('rsssl_notices', 'rsssl_code_execution_nginx_notice' );
    }
}

function rsssl_get_nginx_code_code_execution_uploads() {
    $code = '<code>location ~* /uploads/.*\.php$ {' . "<br>";
    $code .= '&nbsp;&nbsp;&nbsp;&nbsp;return 503;' . "<br>";
    $code .= '}</code>' . "<br>";

    return $code;
}

/**
 * Enter .htaccess file to disable code execution in /uploads directory
 */
function rsssl_insert_disable_code_execution_rules( $upload_dir )
{
    $rules = "<Files *.php>" . "\n";
    $rules .= "deny from all" . "\n";
    $rules .= "</Files>" . "\n";
    file_put_contents($upload_dir['basedir'] . '/' . '.htaccess', $rules);
}