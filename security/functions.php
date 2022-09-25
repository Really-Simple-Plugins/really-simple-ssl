<?php
defined( 'ABSPATH' ) or die( );
/**
 * Back-end available only
 */

if ( !function_exists('rsssl_remove_htaccess_security_edits') ) {
	/**
	 * Clean up on deactivation
	 *
	 * @return void
	 */
	function rsssl_remove_htaccess_security_edits(){
		if ( ! rsssl_user_can_manage()  ) {
			return;
		}

		if ( ! rsssl_uses_htaccess() ) {
			return;
		}

		$htaccess_file = RSSSL()->admin->htaccess_file();
		if ( !file_exists( $htaccess_file ) ) {
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

		$htaccess_file = RSSSL()->admin->htaccess_file();
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
		if ( ! rsssl_user_can_manage()  ) {
			return;
		}

		if ( !rsssl_uses_htaccess() ) {
			return;
		}

		if ( rsssl_get_option('do_not_edit_htaccess') ) {
			return;
		}

		if ( !RSSSL()->admin->is_settings_page() && !rsssl_is_logged_in_rest() ) {
			return;
		}
		delete_site_option( 'rsssl_htaccess_error' );
		delete_site_option( 'rsssl_htaccess_rules' );

		$start = "\n" . '#Begin Really Simple Security';
		$end   = "\n" . '#End Really Simple Security' . "\n";
		$pattern = '/'.$start.'(.*?)'.$end.'/is';

		/**
		 * htaccess in uploads dir
		 */
		$rules_uploads = apply_filters( 'rsssl_htaccess_security_rules_uploads', []);
		$upload_dir = wp_get_upload_dir();
		$htaccess_file_uploads = trailingslashit( $upload_dir['basedir']).'.htaccess';
		if ( count($rules_uploads)>0 ) {
			if ( ! file_exists( $htaccess_file_uploads ) ) {
				if ( is_writable(trailingslashit( $upload_dir['basedir'])) ) {
					file_put_contents($htaccess_file_uploads, '');
				} else {
					update_site_option( 'rsssl_htaccess_error', 'not-writable-uploads' );
					$rules_uploads_result = implode( '', array_column( $rules_uploads, 'rules' ) );
					update_site_option( 'rsssl_htaccess_rules', $rules_uploads_result );
				}
			}

			if ( file_exists( $htaccess_file_uploads ) ) {
				$content_htaccess_uploads = file_exists( $htaccess_file_uploads ) ? file_get_contents( $htaccess_file_uploads ) : '';
				preg_match( $pattern, $content_htaccess_uploads, $matches );
				if ( ( ! empty( $matches[1] ) && empty( $rules_uploads ) ) || ! empty( $rules_uploads ) ) {
					$rules_uploads_result = '';
					foreach ( $rules_uploads as $rule_uploads ) {
						if ( strpos( $content_htaccess_uploads, $rule_uploads['identifier'] ) !== false ) {
							continue;
						}
						$rules_uploads_result .= $rule_uploads['rules'];
					}
					//should replace if rules is not empty, OR if rules is empty and htaccess is not.
					$htaccess_has_rsssl_rules = ! preg_match( "/#Begin Really Simple Security[ \n\t]+#End Really Simple Security/", $content_htaccess_uploads );
					if ( ! empty( $rules_uploads_result ) || $htaccess_has_rsssl_rules ) {
						if ( ! file_exists( $htaccess_file_uploads ) ) {
							file_put_contents( $htaccess_file_uploads, '' );
						}

						if ( ! is_writable( $htaccess_file_uploads ) ) {
							update_site_option( 'rsssl_htaccess_error', 'not-writable-uploads' );
							update_site_option( 'rsssl_htaccess_rules', $rules_uploads_result );
						} else {
							delete_site_option( 'rsssl_htaccess_error' );
							delete_site_option( 'rsssl_htaccess_rules' );
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
			}
		}
		/**
		 * htaccess in root dir
		 */
		$rules = apply_filters( 'rsssl_htaccess_security_rules', [] );
		$htaccess_file = RSSSL()->admin->htaccess_file();
		if ( count($rules)>0 ) {
			if ( !file_exists( $htaccess_file ) ) {
				update_site_option('rsssl_htaccess_error', 'not-exists');
				$rules_result = implode('',array_column($rules, 'rules'));
				update_site_option('rsssl_htaccess_rules', $rules_result);
			}

			if ( file_exists( $htaccess_file ) ) {
				$content_htaccess = file_get_contents( $htaccess_file );
				preg_match( $pattern, $content_htaccess, $matches );
				if ( ( ! empty( $matches[1] ) && empty( $rules ) ) || ! empty( $rules ) ) {
					$rules_result = '';
					foreach ( $rules as $rule ) {
						if ( strpos( $content_htaccess, $rule['identifier'] ) !== false ) {
							continue;
						}
						$rules_result .= $rule['rules'];
					}

					//should replace if rules is not empty, OR if rules is empty and htaccess is not.
					$htaccess_has_rsssl_rules = ! preg_match( "/#Begin Really Simple Security[ \n\t]+#End Really Simple Security/", $content_htaccess );
					if ( ! empty( $rules_result ) || $htaccess_has_rsssl_rules ) {
						if ( ! is_writable( $htaccess_file ) ) {
							update_site_option( 'rsssl_htaccess_error', 'not-writable' );
							update_site_option( 'rsssl_htaccess_rules', get_site_option( 'rsssl_htaccess_rules' ) . $rules_result );
						} else {
							delete_site_option( 'rsssl_htaccess_error' );
							delete_site_option( 'rsssl_htaccess_rules' );
							//get current rules with regex
							if ( strpos( $content_htaccess, $start ) !== false ) {
								$new_htaccess = preg_replace( $pattern, $start . $rules_result . $end, $content_htaccess );
							} else {
								//add rules as new block
								$new_htaccess = $start . $rules_result . $end . $content_htaccess;
							}
							file_put_contents( $htaccess_file, $new_htaccess );

						}
					}
				}
			}
		}
	}
	error_log("load  function htaccess");

	add_action('admin_init', 'rsssl_wrap_htaccess' );
	add_action('rsssl_after_saved_fields', 'rsssl_wrap_htaccess', 30);
}

/**
 * Check if server uses .htaccess
 * @return bool
 */
function rsssl_uses_htaccess() {
	if ( rsssl_get_server() === 'apache' || rsssl_get_server() === 'litespeed' ) {
		return true;
	}

	return false;
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
    //limit nr of iterations to 5
    $i = 0;
    $dir = dirname(__FILE__);
    do {
        $i++;
        if (file_exists($dir . "/wp-config.php")) {
            return $dir . "/wp-config.php";
        }
    } while (($dir = realpath("$dir/..")) && ($i < 10));
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

/**
 * Wrapper for admin user renamed but user enumeration enabled check
 * @return bool
 */
function check_admin_user_renamed_and_enumeration_disabled() {
	// Check if rename-admin-user has been loaded, while user-enumeration hasn't been loaded
	if ( function_exists( 'rsssl_username_admin_changed' ) && ! function_exists( 'rsssl_disable_user_enumeration' ) ) {
		if ( rsssl_username_admin_changed() !== false ) {
			return true;
		}
	}

	return false;
}

/**
 * @return string
 *
 * Get users as string to display
 */
function rsssl_list_users_where_display_name_is_login_name() {
	$users = rsssl_get_users_where_display_name_is_login( true );
	if ( is_array( $users ) ) {
		$ext  = count($users)>=10 ? '...' : '';
		$users = array_slice($users, 0, 10);
		return implode( ', ', $users ).$ext;
	}

	return '';
}

/**
 * Create a generic read more text with link for help texts.
 *
 * @param string $url
 * @param bool   $add_space
 *
 * @return string
 */

function rsssl_read_more( $url, $add_character = ' ' ) {
	$html = sprintf( __( "For more information, please read this %sarticle%s",
		'really-simple-ssl' ), '<a target="_blank" href="' . $url . '">',
		'</a>' );
	if ( is_string($add_character) ) {
		$html = $add_character . $html;
	}

	return $html;
}
