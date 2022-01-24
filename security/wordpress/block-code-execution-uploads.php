<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

// Add notice in backend
if ( is_admin() ) {
    add_filter('rsssl_notices', 'code_execution_uploads', 50, 3);
}

add_action('admin_init' , 'rsssl_get_server');

function rsssl_get_server() {
	$server = RSSSL()->rsssl_server->get_server();
}

if ( ! function_exists( 'code_execution_uploads' ) ) {
    function code_execution_uploads( $notices ) {
        $notices['code-execution-uploads'] = array(
            'callback' => 'rsssl_code_execution_uploads',
            'score' => 5,
            'output' => array(
                'allowed' => array(
                    'msg' => __("Code execution allowed in uploads folder.", "really-simple-ssl"),
                    'icon' => 'open',
                    'dismissible' => true,
                ),
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
}

/**
 * @return string
 * Test if code execution is allowed in /uploads folder
 */
if ( ! function_exists('rsssl_code_execution_uploads' ) ) {
    function rsssl_code_execution_uploads()
    {

        $return = '';

        $upload_dir = wp_get_upload_dir();
        $test_file = $upload_dir['basedir'] . '/' . 'code-execution.php';

        if ( is_writable($upload_dir['basedir'] ) && ! file_exists( $test_file ) ) {
            // copy from tests
            copy(rsssl_path . 'security/tests/code-execution.php' , $test_file );

            if ( file_exists( $test_file ) ) {

                require_once( $test_file );

                if ( function_exists( 'rsssl_test_code_execution' ) && rsssl_test_code_execution() ) {
                    $return = 'allowed';
                }

                unlink( $test_file );
            } else {
                if ( ! is_writable( $upload_dir['basedir'] ) ) $return = 'uploads-folder-not-writable';
                if ( ! file_exists( $test_file ) ) $return = 'could-not-create-test-file';
            }
        } else {
            if ( function_exists( 'rsssl_test_code_execution' ) && rsssl_test_code_execution() ) {
                $return = 'allowed';
            } else {
                $return = 'not-allowed';
            }

            unlink( $test_file );
        }

        return $return;

    }
}

/**
 * @return string
 * Add add an .htaccess file to block code execution in the /uploads directory
 */
if ( ! function_exists('rsssl_disable_code_execution_uploads' ) ) {
    function rsssl_disable_code_execution_uploads()
    {

        $upload_dir = wp_get_upload_dir();

//        if (RSSSL()->rsssl_server->get_server() === 'apache') {

            if (!is_writable($upload_dir['basedir'])) return;

            if (!file_exists($upload_dir['basedir'] . '/' . '.htaccess')) {
                // create .htaccess file
                rsssl_insert_disable_code_execution_rules($upload_dir);
            } else {
                $htaccess = file_get_contents($upload_dir['basedir'] . '/' . '.htaccess');

                $regex = "/(<Files [ *](\.php)>)[\n](deny from all)/m";
                preg_match_all($regex, $htaccess, $matches);

                // Group 0 contains str
                if ( ! $matches[0] ) {
                    // Has .htaccess not no *.php rules, enter
                    rsssl_insert_disable_code_execution_rules( $upload_dir );
                }

            }
//        }
//        if ( RSSSL()->rsssl_server->get_server() === 'nginx' ) {
            //location ~* /your_directory/.*\.php$ {
            //return 503;
            //}
//        }
    }
}

/**
 * Enter .htaccess file to disable code execution in /uploads directory
 */
if ( ! function_exists('rsssl_insert_disable_code_execution_rules' ) ) {
    function rsssl_insert_disable_code_execution_rules( $upload_dir )
    {
        $rules = "<Files *.php>" . "\n";
        $rules .= "deny from all" . "\n";
        $rules .= "</Files>" . "\n";

        file_put_contents($upload_dir['basedir'] . '/' . '.htaccess', $rules);
    }
}
