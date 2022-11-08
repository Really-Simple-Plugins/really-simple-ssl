<?php
defined( 'ABSPATH' ) or die( );
/**
 * Back-end available only
 */
if ( !function_exists('rsssl_do_fix')) {
	/**
	 * Complete a fix for an issue, either user triggered, or automatic
	 *
	 * @param $fix
	 *
	 * @return void
	 */
	function rsssl_do_fix( $fix ) {
		if ( ! rsssl_user_can_manage() ) {
			return;
		}

		if ( ! rsssl_has_fix( $fix ) && function_exists( $fix ) ) {
			$completed[] = $fix;
			$fix();
			$completed   = get_option( 'rsssl_completed_fixes', [] );
			$completed[] = $fix;
			update_option( 'rsssl_completed_fixes', $completed );
		} else if ( $fix && ! function_exists( $fix ) ) {
		}

	}
}
if ( !function_exists('rsssl_has_fix')) {

	/**
	 * Check if this has been fixed already
	 *
	 * @param $fix
	 *
	 * @return bool
	 */
	function rsssl_has_fix( $fix ) {
		$completed = get_option( 'rsssl_completed_fixes', [] );
		if ( ! in_array( $fix, $completed ) ) {
			return false;
		}

		return true;
	}
}

if ( !function_exists('rsssl_admin_url')) {
	/**
	 * Get admin url, adjusted for multisite
	 * @return string|null
	 */
	function rsssl_admin_url(){
		return is_multisite() && is_network_admin() ? network_admin_url('settings.php') : admin_url("options-general.php");
	}
}

if ( !function_exists('rsssl_maybe_clear_transients')) {
	/**
	 * If the corresponding setting has been changed, clear the test cache and re-run it.
	 *
	 * @return void
	 */
	function rsssl_maybe_clear_transients( $field_id, $field_value, $prev_value, $field_type ) {
		if ( $field_id === ' mixed_content_fixer' && $field_value ) {
			delete_transient( 'rsssl_can_use_curl_headers_check' );
			delete_transient( 'rsssl_mixed_content_fixer_detected' );
			RSSSL()->admin->mixed_content_fixer_detected();
		}

		//no change
		if ( $field_value === $prev_value ) {
			return;
		}

		if ( $field_id === 'disable_http_methods' ) {
			delete_transient( 'rsssl_http_methods_allowed' );
			rsssl_http_methods_allowed();
		}
		if ( $field_id === 'xmlrpc' ) {
			delete_transient( 'rsssl_xmlrpc_allowed' );
			rsssl_xmlrpc_allowed();
		}
		if ( $field_id === 'disable_indexing' ) {
			delete_transient( 'rsssl_directory_indexing_status' );
			rsssl_directory_indexing_allowed();
		}
		if ( $field_id === 'block_code_execution_uploads' ) {
			delete_transient( 'rsssl_code_execution_allowed_status' );
			rsssl_code_execution_allowed();
		}
		if ( $field_id === 'hide_wordpress_version' ) {
			delete_transient( 'rsssl_wp_version_detected' );
			rsssl_src_contains_wp_version();
		}
		if ( $field_id === 'rename_admin_user' ) {
			wp_cache_delete('rsssl_admin_user_count');
			rsssl_has_admin_user();
		}

	}

	add_action( "rsssl_after_save_field", 'rsssl_maybe_clear_transients', 100, 4 );
}

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
		$end   =  '#End Really Simple Security' . "\n";
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
		//remove old style rules
		$pattern_1 = "/#\s?BEGIN\s?rlrssslReallySimpleSSL.*?#\s?END\s?rlrssslReallySimpleSSL/s";
		$pattern_2 = "/#\s?BEGIN\s?Really Simple SSL Redirect.*?#\s?END\s?Really Simple SSL Redirect/s";
		$content_htaccess = preg_replace([$pattern_1, $pattern_2], "", $content_htaccess);
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
		if ( !rsssl_user_can_manage() ) {
			return;
		}

		if ( ! rsssl_uses_htaccess() ) {
			return;
		}

		if ( rsssl_get_option('do_not_edit_htaccess') ) {
			return;
		}

		if (
			!rsssl_is_logged_in_rest() &&
			!RSSSL()->admin->is_settings_page() &&
		     current_filter() !== 'rocket_activation' &&
		     current_filter() !== 'rocket_deactivation'
		) {
			return;
		}

		if ( get_site_option('rsssl_htaccess_error') ) {
			delete_site_option( 'rsssl_htaccess_error' );
			delete_site_option( 'rsssl_htaccess_rules' );
		}

		if ( get_site_option('rsssl_uploads_htaccess_error') ) {
			delete_site_option( 'rsssl_uploads_htaccess_error' );
			delete_site_option( 'rsssl_uploads_htaccess_rules' );
		}

		if ( get_option('rsssl_updating_htaccess') ) {
			return;
		}

		update_option('rsssl_updating_htaccess', true, false );

		$start = '#Begin Really Simple Security';
		$end   = "\n" . '#End Really Simple Security' . "\n";
		$pattern_content = '/'.$start.'(.*?)'.$end.'/is';

		$pattern = '/'.$start.'.*?'.$end.'/is';
		/**
		 * htaccess in uploads dir
		 */
		$rules_uploads = apply_filters( 'rsssl_htaccess_security_rules_uploads', []);
		$upload_dir = wp_get_upload_dir();
		$htaccess_file_uploads = trailingslashit( $upload_dir['basedir']).'.htaccess';

		if ( ! file_exists( $htaccess_file_uploads ) && count($rules_uploads)>0 ) {
			if ( is_writable(trailingslashit( $upload_dir['basedir'])) ) {
				file_put_contents($htaccess_file_uploads, '');
			} else {
				update_site_option( 'rsssl_uploads_htaccess_error', 'not-writable' );
				$rules_uploads_result = implode( '', array_column( $rules_uploads, 'rules' ) );
				update_site_option( 'rsssl_uploads_htaccess_rules', $rules_uploads_result );
			}
		}

		if ( file_exists( $htaccess_file_uploads ) ) {
			$content_htaccess_uploads = file_exists( $htaccess_file_uploads ) ? file_get_contents( $htaccess_file_uploads ) : '';
			preg_match( $pattern_content, $content_htaccess_uploads, $matches );
			if ( ( ! empty( $matches[1] ) && empty( $rules_uploads ) ) || ! empty( $rules_uploads ) ) {
				$rules_uploads_result = '';
				foreach ( $rules_uploads as $rule_uploads ) {
					//check if the rule exists outside RSSSL, but not within
					if ( strpos($content_htaccess_uploads, $rule_uploads['identifier'])!==false && !preg_match('/#Begin Really Simple Security.*?('.preg_quote($rule_uploads['identifier'],'/').').*?#End Really Simple Security/is', $content_htaccess_uploads, $matches) ) {
						continue;
					}
					$rules_uploads_result .= $rule_uploads['rules'];
				}
				//We differ between missing rules, and a complete set. As we don't want the replace all rules with just the missing set.

				//should replace if rules is not empty, OR if rules is empty and htaccess is not.
				$htaccess_has_rsssl_rules = preg_match( '/#Begin Really Simple Security(.*?)#End Really Simple Security/is', $content_htaccess_uploads, $matches);
				if ( ! empty( $rules_uploads_result ) || $htaccess_has_rsssl_rules ) {
					if ( ! file_exists( $htaccess_file_uploads ) ) {
						file_put_contents( $htaccess_file_uploads, '' );
					}

					$new_rules = empty($rules_uploads_result) ? '' : $start . $rules_uploads_result . $end;
					if ( ! is_writable( $htaccess_file_uploads ) ) {
						update_site_option( 'rsssl_uploads_htaccess_error', 'not-writable' );
						update_site_option( 'rsssl_uploads_htaccess_rules', $rules_uploads_result );
					} else {
						delete_site_option( 'rsssl_uploads_htaccess_error' );
						delete_site_option( 'rsssl_uploads_htaccess_rules' );
						//remove current rules
						$content_htaccess_uploads = preg_replace( $pattern, '', $content_htaccess_uploads );
						//add rules as new block
						$new_htaccess = $content_htaccess_uploads . "\n" . $new_rules;
						#clean up
						if (strpos($new_htaccess, "\n" ."\n" . "\n" )!==false) {
							$new_htaccess = str_replace("\n" . "\n" . "\n", "\n" ."\n", $new_htaccess);
						}
						file_put_contents( $htaccess_file_uploads, $new_htaccess );
					}
				}
			}
		}

		/**
		 * htaccess in root dir
		 */
		$rules = apply_filters( 'rsssl_htaccess_security_rules', [] );
		$htaccess_file = RSSSL()->admin->htaccess_file();

		if ( !file_exists( $htaccess_file ) && count($rules)>0 ) {
			update_site_option('rsssl_htaccess_error', 'not-exists');
			$rules_result = implode('',array_column($rules, 'rules'));
			update_site_option('rsssl_htaccess_rules', $rules_result);
		}

		if ( file_exists( $htaccess_file ) ) {
			$content_htaccess = file_get_contents( $htaccess_file );

			//remove old style rules
			//we do this beforehand, so we don't accidentally assume redirects are already in place
			$content_htaccess = preg_replace(
				[
					"/#\s?BEGIN\s?rlrssslReallySimpleSSL.*?#\s?END\s?rlrssslReallySimpleSSL/s",
					"/#\s?BEGIN\s?Really Simple SSL Redirect.*?#\s?END\s?Really Simple SSL Redirect/s"
				], "", $content_htaccess);
			preg_match( $pattern_content, $content_htaccess, $matches );

			if ( ( ! empty( $matches[1] ) && empty( $rules ) ) || ! empty( $rules ) ) {
				$rules_result = '';
				foreach ( $rules as $rule ) {
					//check if the rule exists outside RSSSL, but not within
					if ( strpos($content_htaccess, $rule['identifier'])!==false && !preg_match('/#Begin Really Simple Security.*?('.preg_quote($rule['identifier'],'/').').*?#End Really Simple Security/is', $content_htaccess, $matches) ) {
						continue;
					}
					$rules_result .= $rule['rules'];
				}
				//should replace if rules is not empty, OR if rules is empty and htaccess is not.
				$htaccess_has_rsssl_rules = preg_match( '/#Begin Really Simple Security(.*?)#End Really Simple Security/is', $content_htaccess, $matches );
				if ( ! empty( $rules_result ) || $htaccess_has_rsssl_rules ) {
					if ( ! is_writable( $htaccess_file ) ) {
						update_site_option( 'rsssl_htaccess_error', 'not-writable' );
						update_site_option( 'rsssl_htaccess_rules', get_site_option( 'rsssl_htaccess_rules' ) . $rules_result );
					} else {
						delete_site_option( 'rsssl_htaccess_error' );
						delete_site_option( 'rsssl_htaccess_rules' );
						$new_rules = empty($rules_result) ? '' : $start . $rules_result . $end;

						//remove current rules
						$content_htaccess = preg_replace( $pattern, '', $content_htaccess );

						//add rules as new block
						if ( strpos($content_htaccess, '# BEGIN WordPress')!==false ) {
							$new_htaccess = str_replace('# BEGIN WordPress', "\n" . $new_rules.'# BEGIN WordPress', $content_htaccess);
						} else {
							$new_htaccess = "\n" . $new_rules . $content_htaccess;
						}

						#clean up
						if (strpos($new_htaccess, "\n" ."\n" . "\n" )!==false) {
							$new_htaccess = str_replace("\n" . "\n" . "\n", "\n" ."\n", $new_htaccess);
						}

						file_put_contents( $htaccess_file, $new_htaccess );
					}
				}
			}
		}
		delete_option('rsssl_updating_htaccess');
	}
	add_action('admin_init', 'rsssl_wrap_htaccess' );
	add_action('rsssl_after_saved_fields', 'rsssl_wrap_htaccess', 30);
}

/**
 * Check if server uses .htaccess
 * @return bool
 */
function rsssl_uses_htaccess() {
	return rsssl_get_server() === 'apache' || rsssl_get_server() === 'litespeed';
}

/**
 * Get htaccess status
 * @return string | bool
 */
function rsssl_htaccess_status(){
	if ( empty(get_site_option('rsssl_htaccess_rules','')) ) {
		return false;
	}
	return get_site_option('rsssl_htaccess_error');
}

/**
 * Get htaccess status
 * @return string | bool
 */

function rsssl_uploads_htaccess_status(){
	if ( empty(get_site_option('rsssl_uploads_htaccess_rules','')) ) {
		return false;
	}
	return get_site_option('rsssl_uploads_htaccess_error');
}

/**
 * @return string|null
 * Get the wp-config.php path
 */
function rsssl_find_wp_config_path()
{
	if ( !rsssl_user_can_manage() ) {
		return null;
	}
    //limit nr of iterations to 5
    $i = 0;
    $dir = __DIR__;
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
	if ( !rsssl_user_can_manage() ) {
		return '';
	}
	$users = rsssl_get_users_where_display_name_is_login( true );
	if ( is_array( $users ) ) {
		$ext  = count($users)>=10 ? '...' : '';
		$users = array_slice($users, 0, 10);
		return implode( ', ', $users ).$ext;
	}

	return '';
}