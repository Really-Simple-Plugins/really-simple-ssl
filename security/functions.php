<?php

use RSSSL\Security\RSSSL_Htaccess_File_Manager;

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
	 * @param array $args //query args
	 * @param string $path //hash slug for the settings pages (e.g. #dashboard)
	 * @return string
	 */
	function rsssl_admin_url(array $args = [], string $path = ''): string {
		$url = is_multisite() ? network_admin_url('admin.php') : admin_url('admin.php');
		$args = wp_parse_args($args, ['page' => 'really-simple-security']);
		return add_query_arg($args, $url) . $path;
	}
}

if ( !function_exists('rsssl_maybe_clear_transients')) {
	/**
	 * If the corresponding setting has been changed, clear the test cache and re-run it.
	 *
	 * @return void
	 */
	function rsssl_maybe_clear_transients( $field_id, $field_value, $prev_value, $field_type ) {
		if ( $field_id === 'mixed_content_fixer' && $field_value ) {
			delete_transient( 'rsssl_mixed_content_fixer_detected' );
			RSSSL()->admin->mixed_content_fixer_detected();
		}

		//expire in five minutes
		$headers = get_transient('rsssl_can_use_curl_headers_check');
		set_transient('rsssl_can_use_curl_headers_check', $headers, 5 * MINUTE_IN_SECONDS);

		//no change
		if ( $field_value === $prev_value ) {
			return;
		}

		if ( $field_id === 'disable_http_methods' ) {
			delete_option( 'rsssl_http_methods_allowed' );
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
			delete_option( 'rsssl_wp_version_detected' );
			rsssl_src_contains_wp_version();
		}
		if ( $field_id === 'rename_admin_user' ) {
			delete_transient('rsssl_admin_user_count');
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
	function rsssl_remove_htaccess_security_edits() {

		if ( ! rsssl_user_can_manage()  ) {
			return;
		}

		if ( ! rsssl_uses_htaccess() ) {
			return;
		}

		$htaccess_file = RSSSL()->admin->htaccess_file();
		if ( ! file_exists( $htaccess_file ) ) {
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
		$content_htaccess_uploads = is_file($htaccess_file_uploads ) ? file_get_contents($htaccess_file_uploads) : '';
		if (preg_match($pattern, $content_htaccess_uploads) && is_writable( $htaccess_file_uploads )) {
			$content_htaccess_uploads = preg_replace($pattern, "", $content_htaccess_uploads);
			error_log('Removing security edits from uploads .htaccess file');
			file_put_contents( $htaccess_file_uploads, $content_htaccess_uploads );
		}
		// Uses the new conversion of the htaccess file manager
		$root_htaccess_file = RSSSL()->admin->htaccess_file();

		$root_manager = RSSSL_Htaccess_File_Manager::get_instance();

		/*
		 * This is the root .htaccess file, which is used for security rules.
		 * We will clear the security rules from this file.
		 * This is done by clearing the rules that were added by the plugin.
		 * The rules are identified by their marker, which is a comment line in the .htaccess file.
		 * The marker is used to identify the rules that were added by the plugin.
		 *
		 * note: Only this is for the root .htaccess file, not the uploads .htaccess file.
		 */
		if ( $root_manager->validate_htaccess_file_path() ) {
			// Clear redirect rules block
			$root_manager->clear_rule( 'Really Simple Security Redirect', 'clear redirect 1' );
			//Legacy rules
			$root_manager->clear_legacy_rule( 'Really Simple Security Redirect' );
			// Clear any remaining security rules block
			$root_manager->clear_legacy_rule( 'Really Simple Security' );
			// Clear no-indexing block
			$root_manager->clear_rule( 'Really Simple Security No Index', 'clear no index' );
			// Clear legacy Really Simple SSL block
			$root_manager->clear_legacy_rule( 'rlrssslReallySimpleSSL' );
		}
	}
}


/**
 * Wrap the security headers
 */
if ( ! function_exists('rsssl_wrap_htaccess' ) ) {
	function rsssl_wrap_htaccess() {
		if ( ! rsssl_htaccess_should_wrap() ) {
			return;
		}
		update_option( 'rsssl_htaccess_should_wrap', true, false );

		rsssl_htaccess_clear_errors();
		rsssl_handle_uploads_htaccess();
		rsssl_handle_root_htaccess();
		rsssl_htaccess_finalize();
	}
	add_action('admin_init', 'rsssl_wrap_htaccess' );
	add_action('rsssl_after_saved_fields', 'rsssl_wrap_htaccess', 30);
}

/**
 * Check whether we should wrap htaccess.
 *
 * @return bool
 */
function rsssl_htaccess_should_wrap(): bool {
	if ( ! rsssl_user_can_manage() || ! rsssl_uses_htaccess() ) {
		return false;
	}
	if ( rsssl_get_option('do_not_edit_htaccess') ) {
		delete_site_option('rsssl_htaccess_error');
		delete_site_option('rsssl_htaccess_rules');
		return false;
	}

	if ( get_option('rsssl_updating_htaccess') ) {
		return false;
	}
	return true;
}

/**
 * Finalize htaccess wrapping by removing the updating flag.
 */
function rsssl_htaccess_finalize(): void {
	delete_option('rsssl_updating_htaccess');
}

/**
 * Handle root directory .htaccess wrapping.
 */
function rsssl_handle_root_htaccess(): void {
	$rules = apply_filters( 'rsssl_htaccess_security_rules', [] );
	$htaccess_file = RSSSL()->admin->htaccess_file();
	// If there are no rules at all, nothing to do (or record an error)
	if ( empty( $rules ) ) {
		delete_site_option( 'rsssl_htaccess_error' );
		delete_site_option( 'rsssl_htaccess_rules' );
		return;
	}

	// If file doesn’t exist yet, record that and cache the rules for later
	if ( ! is_file( $htaccess_file ) ) {
		update_site_option( 'rsssl_htaccess_error', 'not-exists' );
		update_site_option( 'rsssl_htaccess_rules', implode( '', array_column( $rules, 'rules' ) ) );
		return;
	}

	if ( is_file( $htaccess_file ) ) {
		// Main path: file exists and we have rules
		$manager = new RSSSL_Htaccess_File_Manager();
		$manager->set_htaccess_file_path( $htaccess_file );

		$definition = '';
		$no_index_definition = '';

		// 1) Drop any legacy blocks
		rsssl_clear_legacy_rules( $manager );

        // 2) Build the new redirect‐rules block
        foreach ( $rules as $idx => $rule ) {
            if ( isset( $rule['identifier'] ) && $rule['identifier'] === 'RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1' ) {
				// removing the identifier from the rule, as it is not used in the new htaccess file manager
	            unset( $rule['identifier'] );
                // 2.2) Add the redirect block
                $definition = rsssl_build_redirect_block( $manager, $rule );
                // remove this rule
                unset( $rules[ $idx ] );
                break;  // stop after first match
            }
        }

        foreach ( $rules as $idx => $rule ) {
            if ( isset( $rule['identifier'] ) && $rule['identifier'] === 'Options -Indexes' ) {
	            // removing the identifier from the rule, as it is not used in the new htaccess file manager
	            unset( $rule['identifier'] );
                // 2.1) Add the no-indexing block
                $no_index_definition = rsssl_build_no_index_block( $manager );
                // remove this rule
                unset( $rules[ $idx ] );
                break;  // stop after first match
            }
        }

		// 3) If the file isn’t writable, record an error; otherwise write it
		if ( ! is_writable( $htaccess_file ) ) {
			update_site_option( 'rsssl_htaccess_error', 'not-writable' );

			if (is_array($definition) && !empty($definition['lines'])) {
                update_site_option( 'rsssl_htaccess_rules', implode( "\n", $definition['lines']));
            }
            return;
		}

        delete_site_option( 'rsssl_htaccess_error' );
        delete_site_option( 'rsssl_htaccess_rules' );

        if( !empty( $no_index_definition['lines'] )  ) {
            // If we have a no-indexing block, write it first
            $manager->write_rule( $no_index_definition, 'Writing no index block' );
        } elseif( ! rsssl_get_option( 'disable_indexing', false ) ) {
            // If we don’t have a no-indexing block, clear it
            $manager->clear_rule( 'Really Simple Security No Index', 'clear no index' );
        }
//			// 4) Write the redirect block but only if it’s not empty
        if ( ! empty( $definition['lines'] ) ) {
            $manager->write_rule( $definition, 'Writing redirect block' );
        }
        if ( rsssl_get_option('redirect') !== 'htaccess' ) {
            $manager->clear_rule( 'Really Simple Security Redirect', 'clear redirect 2 and value of config:' . rsssl_get_option('redirect') );
        }
	}
}

/**
 * Build the redirect block for the .htaccess file.
 *
 * @param RSSSL_Htaccess_File_Manager $m
 * @param array $lines the lines for the redirect block.
 *
 * @return array
 */
function rsssl_build_redirect_block( RSSSL_Htaccess_File_Manager $m, array $lines = [] ): array
{
    if ( empty($lines) ) {
        return [
            'marker' => 'Really Simple Security Redirect',
            'lines'  => [],
        ];
    }

    // In case legacy markers are present, skip the rule. They should be
    // cleared before this function is called.
    $legacyMarkerPresent = $m->are_markers_present([
        '#BEGIN Really Simple Security Redirect',
        '#END Really Simple Security Redirect',
    ]);

	return [
		'marker' => 'Really Simple Security Redirect',
		'lines'  => $lines,
	];
}

function rsssl_build_no_index_block( RSSSL_Htaccess_File_Manager $m ): array {
	$content   = $m->get_htaccess_content() ?: '';
	$no_index = 'Options -Indexes';
	if ( strpos( $content, $no_index ) !== false ) {
		return [];
	}

	return [
		'marker' => 'Really Simple Security No Index',
		'lines'  => [ $no_index ],
	];
}

/**
 * Handle uploads directory .htaccess wrapping.
 * TODO also needs to convert to the new file manager.
 */
function rsssl_handle_uploads_htaccess(): void {
	$start            = '#Begin Really Simple Security';
	$end              = "\n" . '#End Really Simple Security' . "\n";
	$pattern_content  = '/' . preg_quote( $start, '/' ) . '(.*?)' . preg_quote( $end, '/' ) . '/is';
	$pattern          = '/' . preg_quote( $start, '/' ) . '.*?' . preg_quote( $end, '/' ) . '/is';
	$rules_uploads    = apply_filters( 'rsssl_htaccess_security_rules_uploads', [] );
	$upload_dir       = wp_get_upload_dir();
	$htaccess_uploads = trailingslashit( $upload_dir['basedir'] ) . '.htaccess';

	if ( ! is_file( $htaccess_uploads ) && count( $rules_uploads ) > 0 ) {
		if ( is_writable( trailingslashit( $upload_dir['basedir'] ) ) ) {
			file_put_contents( $htaccess_uploads, '' );
		} else {
			update_site_option( 'rsssl_uploads_htaccess_error', 'not-writable' );
			$rules_uploads_result = implode( '', array_column( $rules_uploads, 'rules' ) );
			update_site_option( 'rsssl_uploads_htaccess_rules', $rules_uploads_result );
		}
	}

	if ( is_file( $htaccess_uploads ) ) {
		$content = file_get_contents( $htaccess_uploads );
		preg_match( $pattern_content, $content, $matches );

		if ( ( ! empty( $matches[1] ) && empty( $rules_uploads ) ) || ! empty( $rules_uploads ) ) {
			$rules_uploads_result = '';
			foreach ( $rules_uploads as $rule ) {
				if ( strpos( $content, $rule['identifier'] ) !== false && ! preg_match( '/' . preg_quote( $start, '/' ) . '.*?(' . preg_quote( $rule['identifier'], '/' ) . ').*?' . preg_quote( $end, '/' ) . '/is', $content ) ) {
					continue;
				}
				$rules_uploads_result .= $rule['rules'];
			}

			$has_block = preg_match( '/#Begin Really Simple Security.*?#End Really Simple Security/is', $content );
			if ( ! empty( $rules_uploads_result ) || $has_block ) {
				if ( ! is_file( $htaccess_uploads ) ) {
					file_put_contents( $htaccess_uploads, '' );
				}
				$new_block = empty( $rules_uploads_result ) ? '' : $start . $rules_uploads_result . $end;

				if ( ! is_writable( $htaccess_uploads ) ) {
					update_site_option( 'rsssl_uploads_htaccess_error', 'not-writable' );
					update_site_option( 'rsssl_uploads_htaccess_rules', $rules_uploads_result );
				} else {
					delete_site_option( 'rsssl_uploads_htaccess_error' );
					delete_site_option( 'rsssl_uploads_htaccess_rules' );
					$cleaned = preg_replace( $pattern, '', $content );
					$new     = $cleaned . "\n" . $new_block;
					$new     = preg_replace( "/\n{3,}/", "\n\n", $new );
					if ( file_get_contents( $htaccess_uploads ) !== $new ) {
						file_put_contents( $htaccess_uploads, $new );
					}
				}
			}
		}
	}
}

/**
 * Clear any stored htaccess errors/options.
 */
function rsssl_htaccess_clear_errors(): void {
	delete_site_option('rsssl_htaccess_error');
	delete_site_option('rsssl_htaccess_rules');
	delete_site_option('rsssl_uploads_htaccess_error');
	delete_site_option('rsssl_uploads_htaccess_rules');
}

function rsssl_clear_legacy_rules( RSSSL_Htaccess_File_Manager $m ) {
	foreach ( [
		'rlrssslReallySimpleSSL',
		'Really Simple Security',
		'Really Simple Security Redirect',
	] as $marker ) {
		$m->clear_legacy_rule( $marker );
	}
}

/**
 * Store warning blocks for later use in the mailer
 *
 * @param array $changed_fields
 *
 * @return void
 */
function rsssl_gather_warning_blocks_for_mail( array $changed_fields ){
	if (!rsssl_user_can_manage() ) {
		return;
	}

	if ( !rsssl_get_option('send_notifications_email') ) {
		return;
	}

    $fields = array_filter($changed_fields, static function($field) {
        // Check if email_condition exists and call the function, else assume true
	    if ( !isset($field['email']['condition']) ) {
			$email_condition_result = true;
	    } else if (is_array($field['email']['condition'])) {
			//rsssl option check
		    $fieldname = array_key_first($field['email']['condition']);
			$value = $field['email']['condition'][$fieldname];
			$email_condition_result = rsssl_get_option($fieldname) === $value;
	    } else {
			//function check
		    $function  = $field['email']['condition'];
		    $email_condition_result = function_exists($function) && $function();
	    }
        return isset($field['email']['message']) && $field['value'] && $email_condition_result;
    });

	if ( count($fields)===0 ) {
		return;
	}
	$current_fields = get_option('rsssl_email_warning_fields', []);
	//if it's empty, we start counting time. 30 mins later we send a mail.
	update_option('rsssl_email_warning_fields_saved', time(), false );

	$current_ids = array_column($current_fields, 'id');
	foreach ($fields as $field){
		if ( !in_array( $field['id'], $current_ids, true ) ) {
			$current_fields[] = $field;
		}
	}
	update_option('rsssl_email_warning_fields', $current_fields, false);
}
add_action('rsssl_after_saved_fields', 'rsssl_gather_warning_blocks_for_mail', 40);

/**
 * Check if server uses .htaccess
 * @return bool
 */
function rsssl_uses_htaccess() {
	//when using WP CLI, the get_server check does not work, so we assume .htaccess is being used
	//and rely on the file exists check to catch if not.
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return true;
	}
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
function rsssl_find_wp_config_path() {
	if ( ! rsssl_user_can_manage() ) {
		return null;
	}

	// Allow the wp-config.php path to be overridden via a filter.
	$filtered_path = apply_filters( 'rsssl_wpconfig_path', '' );

	// If a filtered path is provided, validate it.
	if ( ! empty( $filtered_path ) ) {
		$directory = dirname( $filtered_path );

		// Ensure the directory exists before checking for the file.
		if ( is_dir( $directory ) && file_exists( $filtered_path ) ) {
			return $filtered_path;
		}
	}

	// Limit number of iterations to 10
	$i   = 0;
	$dir = __DIR__;
	do {
		$i ++;
		if ( file_exists( $dir . "/wp-config.php" ) ) {
			return $dir . "/wp-config.php";
		}
	} while ( ( $dir = realpath( "$dir/.." ) ) && ( $i < 10 ) );

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

	$server_raw = strtolower( htmlspecialchars( $_SERVER['SERVER_SOFTWARE'], ENT_QUOTES | ENT_HTML5 ) );

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

	for ( $i = 0; $i < $length; $i++ ) {
		$index = rand(0, strlen($characters) - 1);
		$randomString .= $characters[$index];
	}

	return $randomString;
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

/**
 * Check if user e-mail is verified
 * @return bool
 */
function rsssl_is_email_verified() {
    $verificationStatus = get_option('rsssl_email_verification_status');
    if (rsssl_user_can_manage() && $verificationStatus == 'completed') {
        return true;
    }

    // User cannot manage or status is ['started', 'email_changed']
    return false;
}

function rsssl_remove_prefix_from_version($version) {
	return preg_replace('/^[^\d]*(?=\d)/', '', $version);
}
function rsssl_version_compare($version, $compare_to, $operator = null) {
	$version = rsssl_remove_prefix_from_version($version);
	$compare_to = rsssl_remove_prefix_from_version($compare_to);
	return version_compare($version, $compare_to, $operator);
}

function rsssl_maybe_disable_404_blocking() {
	$option_value = get_option( 'rsssl_homepage_contains_404_resources', false );
	// Explicitly check for boolean true or string "true"
	return $option_value === true || $option_value === "true";
}

function rsssl_lock_file_exists() {
	if ( file_exists( trailingslashit( WP_CONTENT_DIR ) . 'rsssl-safe-mode.lock' ) ) {
		return true;
	}

	return false;
}