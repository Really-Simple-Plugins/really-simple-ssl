<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

/**
 * @return string
 * Delete transients
 */
//if ( ! function_exists('rsssl_delete_transients' ) ) {
//    function rsssl_delete_transients()
//    {
//        $transients = array(
//	        'rsssl_xmlrpc_allowed',
//			'rsssl_wp_version_detected',
//			'rsssl_http_options_allowed',
//        );
//
//        foreach ( $transients as $transient ) {
//            delete_transient( $transient );
//        }
//    }
//}
/**
 * Complete a fix for an issue, either user triggered, or automatic
 * @param $fix
 *
 * @return void
 */
function rsssl_do_fix($fix){
	if ( !current_user_can('manage_options')) {
		return;
	}

	if ( !rsssl_has_fix($fix) && function_exists($fix)) {
		$completed[]=$fix;
		$success = $fix();
		$completed = get_option('rsssl_completed_fixes', []);
		if ($success) {
			$completed[] = $fix;
			update_option('rsssl_completed_fixes', $completed, false );
		}
	} elseif ($fix && !function_exists($fix) ) {
		error_log("Really Simple SSL: fix function $fix not found");
	}

}

function rsssl_has_fix($fix){
	$completed = get_option('rsssl_completed_fixes', []);
	if ( !in_array($fix, $completed)) {
		return false;
	}
	return true;
}

//error_log(print_r($_SERVER,true));
//error_log(print_r($_POST,true));
//error_log(print_r($_GET,true));
if ( !function_exists('rsssl_remove_htaccess_security_edits') ) {
	/**
	 * Clean up on deactivation
	 *
	 * @return void
	 */
	function rsssl_remove_htaccess_security_edits(){
		if ( ! current_user_can( 'manage_options' )  ) {
			return;
		}

		if ( rsssl_get_server() !== 'apache' ) {
			return;
		}

		$start = "\n" . '#Begin Really Simple Security';
		$end   = "\n" . '#End Really Simple Security' . "\n";
		$pattern = '/'.$start.'(.*?)'.$end.'/is';

		/**
		 * htaccess in uploads dir
		 */
		$upload_dir = wp_get_upload_dir();
		$htaccess_file_uploads = trailingslashit( $upload_dir['basedir']).'.htaccess';
		$content_htaccess_uploads = file_exists($htaccess_file_uploads ) ? file_get_contents($htaccess_file_uploads) : '';
		if (preg_match($pattern, $content_htaccess_uploads) && is_writable( $htaccess_file_uploads )) {
			$content_htaccess_uploads = preg_replace($pattern, "", $content_htaccess_uploads);
			file_put_contents( $htaccess_file_uploads, $content_htaccess_uploads );
		}

		/**
		 * htaccess in root dir
		 */

		$htaccess_file = RSSSL()->really_simple_ssl->htaccess_file();
		$content_htaccess = file_get_contents($htaccess_file);
		if (preg_match($pattern, $content_htaccess) && is_writable( $htaccess_file ) ) {
			$content_htaccess = preg_replace($pattern, "", $content_htaccess);
			file_put_contents( $htaccess_file, $content_htaccess );
		}
	}
}


/**
 * Wrap the security headers
 */

if ( ! function_exists('rsssl_wrap_htaccess' ) ) {
	function rsssl_wrap_htaccess() {
		if ( ! current_user_can( 'manage_options' )  ) {
			return;
		}

		if ( rsssl_get_server() !== 'apache' ) {
			return;
		}

		if ( !RSSSL()->really_simple_ssl->is_settings_page() && !rsssl_is_logged_in_rest() ) {
			return;
		}
		$start = "\n" . '#Begin Really Simple Security';
		$end   = "\n" . '#End Really Simple Security' . "\n";
		$pattern = '/'.$start.'(.*?)'.$end.'/is';

		/**
		 * htaccess in uploads dir
		 */
		$rules_uploads = apply_filters( 'rsssl_htaccess_security_rules_uploads', []);
		$upload_dir = wp_get_upload_dir();
		$htaccess_file_uploads = trailingslashit( $upload_dir['basedir']).'.htaccess';
		$content_htaccess_uploads = file_exists($htaccess_file_uploads ) ? file_get_contents($htaccess_file_uploads) : '';

		preg_match($pattern, $content_htaccess_uploads, $matches );
		if ( (!empty($matches[1]) && empty($rules_uploads)) || !empty($rules_uploads) ) {
			$rules_uploads_result = '';
			foreach ($rules_uploads as $rule_uploads ) {
				if ( strpos($content_htaccess_uploads, $rule_uploads['identifier'])!==false ) {
					continue;
				}
				$rules_uploads_result .= $rule_uploads['rules'];
			}
			//might be empty, if already in .htaccess
			if ( !empty($rules_uploads_result) ) {
				if ( !is_writable( $htaccess_file_uploads )) {
					update_site_option( 'rsssl_htaccess_error', 'not-writable-uploads' );
					update_site_option( 'rsssl_htaccess_rules', $rules_uploads_result );
				} else {
					//get current rules with regex
					if ( strpos( $content_htaccess_uploads, $start ) !== false ) {
						$new_htaccess = preg_replace( $pattern, $start . $rules_uploads_result . $end, $content_htaccess_uploads );
					} else {
						//add rules as new block
						$new_htaccess = $content_htaccess_uploads . $start . $rules_uploads_result . $end;
					}
					file_put_contents( $htaccess_file_uploads, $new_htaccess );
				}
			}
		}

		/**
		 * htaccess in root dir
		 */

		$rules = apply_filters( 'rsssl_htaccess_security_rules', [] );
		$htaccess_file = RSSSL()->really_simple_ssl->htaccess_file();
		if ( !file_exists( $htaccess_file ) ) {
			update_site_option('rsssl_htaccess_error', 'not-exists');
			update_site_option('rsssl_htaccess_rules', $rules);
			return;
		}

		$content_htaccess = file_get_contents($htaccess_file);
		preg_match($pattern, $content_htaccess, $matches );
		if ( (!empty($matches[1]) && empty($rules)) || !empty($rules) ) {
			$rules_result = '';
			foreach ($rules as $rule ) {
				if ( strpos($content_htaccess, $rule['identifier'])!==false ) {
					continue;
				}
				$rules_result .= $rule['rules'];
			}

			//might be empty, if already in .htaccess
			if ( !empty($rules_result) ) {
				if (  !is_writable( $htaccess_file ) ) {
					update_site_option('rsssl_htaccess_error', 'not-writable');
					update_site_option('rsssl_htaccess_rules', get_site_option('rsssl_htaccess_rules').$rules_result);
				} else {
					//get current rules with regex
					if (strpos( $content_htaccess, $start ) !== false ) {
						$new_htaccess = preg_replace($pattern, $start.$rules_result.$end, $content_htaccess);
					} else {
						//add rules as new block
						$new_htaccess = $content_htaccess . $start . $rules_result . $end;
					}
					file_put_contents($htaccess_file, $new_htaccess);
				}
			}
		}
	}
	add_action('admin_init', 'rsssl_wrap_htaccess' );
	add_action('rsssl_after_saved_fields', 'rsssl_wrap_htaccess', 30);
}

/**
 * Get htaccess status
 * @return string | bool
 */
function rsssl_htaccess_status(){
	return get_site_option('rsssl_htaccess_error');
}

/**
 * @return string|null
 * Get the wp-config.php path
 */
function rsssl_find_wp_config_path()
{
    //limit nr of iterations to 20
    $i = 0;
    $maxiterations = 20;
    $dir = dirname(__FILE__);
    do {
        $i++;
        if (file_exists($dir . "/wp-config.php")) {
            return $dir . "/wp-config.php";
        }
    } while (($dir = realpath("$dir/..")) && ($i < $maxiterations));
    return null;
}

/**
 * Returns the server type of the plugin user.
 *
 * @return string|bool server type the user is using of false if undetectable.
 */

function rsssl_get_server() {
	//Allows to override server authentication for testing or other reasons.
	if ( defined( 'RSSSL_SERVER_OVERRIDE' ) ) {
		return RSSSL_SERVER_OVERRIDE;
	}

	$server_raw = strtolower( htmlspecialchars( $_SERVER['SERVER_SOFTWARE'] ) );

	//figure out what server they're using
	if ( strpos( $server_raw, 'apache' ) !== false ) {
		return 'apache';
	} elseif ( strpos( $server_raw, 'nginx' ) !== false ) {
		return 'nginx';
	} elseif ( strpos( $server_raw, 'litespeed' ) !== false ) {
		return 'litespeed';
	} else { //unsupported server
		return false;
	}
}

/**
 * @return string
 * Generate a random prefix
 */

function rsssl_generate_random_string($length) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$randomString = '';

	for ($i = 0; $i < $length; $i++) {
		$index = rand(0, strlen($characters) - 1);
		$randomString .= $characters[$index];
	}

	return $randomString;
}