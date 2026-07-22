<?php

defined('ABSPATH') or die();
/**
 * Conditionally we can decide to disable fields, add comments, and manipulate the value here
 * @param array $field
 * @param string $field_id
 *
 * @return array
 */

function rsssl_disable_fields( $field, $field_id ) {
	$current_field_value = $field['value'] ?? false;
	$field_is_checkbox = ( $field['type'] ?? '' ) === 'checkbox';
	$field_value_is_disabled_placeholder = $current_field_value === 'disabled';
	$field_has_enabled_checkbox_value = $field_is_checkbox
		&& ! $field_value_is_disabled_placeholder
		&& in_array( $current_field_value, [ true, 1, '1' ], true );

	/**
	 * If a feature is already enabled, but not by RSSSL, we can simply check for that feature, and if the option in RSSSL is active.
	 * We set is as true, but disabled. Because our React interface only updates changed option, and this option never changes, this won't get set to true in the database.
	 */
	if ( $field_id === 'change_debug_log_location' ) {
		if ( ! rsssl_debug_log_file_exists_in_default_location() ) {
			if ( ! rsssl_is_debugging_enabled() ) {
				if ( ! $field['value'] ) {
					$field['value']    = true;
					$field['disabled'] = true;
				}
			} else if ( ! rsssl_debug_log_value_is_default() ) {
				if ( ! $field['value'] ) {
					$field['value']    = true;
					$field['disabled'] = true;
				}
			}
			//if not the default location
			$location = strstr( rsssl_get_debug_log_value(), 'wp-content' );
			if ( ! empty( $location ) && rsssl_is_debugging_enabled() && ! rsssl_debug_log_value_is_default() ) {
				$field['help'] = [
					'label' => 'default',
					'title' => __( "Debug.log", 'really-simple-ssl' ),
					'text'  => __( "Changed debug.log location to:", 'really-simple-ssl' ) . $location,
				];
			}

		}

	}

	if ( $field_id === 'disable_anyone_can_register' ) {
		if ( ! get_option( 'users_can_register' ) && ! $field_has_enabled_checkbox_value ) {
			$field['value']    = true;
			$field['disabled'] = true;
		}
	}

	if ( $field_id === 'disable_http_methods' ) {
		if ( ! rsssl_http_methods_allowed() && ! $field_has_enabled_checkbox_value ) {
			$field['value']    = true;
			$field['disabled'] = true;
		}
	}

	if ( $field_id === 'disable_indexing' ) {
		return rsssl_maybe_disable_htaccess_managed_field( $field, $field_id );
	}

	if ( $field_id === 'block_code_execution_uploads' ) {
		return rsssl_maybe_disable_htaccess_managed_field( $field, $field_id );
	}

	if ( $field_id === 'disable_file_editing' ) {
		if ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT && ! $field_has_enabled_checkbox_value ) {
			$field['value']    = true;
			$field['disabled'] = true;
		}
	}

	if ( $field_id === 'send_notifications_email' ) {
		$send_notifications_email_block_reason = rsssl_get_send_notifications_email_block_reason();
		if ( $send_notifications_email_block_reason !== '' && ! $field_has_enabled_checkbox_value ) {
			$field['disabled']             = true;
			$field['disabledTooltipText']  = $send_notifications_email_block_reason;
			$field['prerequisite_blocker'] = true;
		}
	}

	if ( $field_id === 'enable_firewall' ) {
		$firewall_block_reason = rsssl_get_enable_firewall_block_reason();
		if ( $firewall_block_reason !== '' && ! $field_has_enabled_checkbox_value ) {
			$field['disabled']             = true;
			$field['disabledTooltipText']  = $firewall_block_reason;
			$field['prerequisite_blocker'] = true;
		}
	}

	if ( $field_id === 'disable_xmlrpc' ) {
		if ( ! rsssl_xmlrpc_enabled() && ! $field_has_enabled_checkbox_value ) {
			$field['value']    = true;
			$field['disabled'] = true;
		}

		$xmlrpc_block_reason = rsssl_get_disable_xmlrpc_block_reason();
		if ( $xmlrpc_block_reason !== '' ) {
			$field['warning'] = true;

			if ( ! $field_has_enabled_checkbox_value ) {
				$field['disabled']             = true;
				$field['disabledTooltipText']  = $xmlrpc_block_reason;
				$field['prerequisite_blocker'] = true;
			}
		}
	}

	if ( $field_id === 'disable_application_passwords' ) {
		$application_passwords_block_reason = rsssl_get_disable_application_passwords_block_reason();
		if ( $application_passwords_block_reason !== '' ) {
			$field['warning'] = true;

			if ( ! $field_has_enabled_checkbox_value ) {
				$field['disabled']             = true;
				$field['disabledTooltipText']  = $application_passwords_block_reason;
				$field['prerequisite_blocker'] = true;
			}
		}
	}

	if ( $field_id === 'login_protection_enabled' ) {
		$login_protection_block_reason = rsssl_get_login_protection_enable_block_reason();
		if ( $login_protection_block_reason !== '' && ! $field_has_enabled_checkbox_value ) {
			$field['disabled']             = true;
			$field['disabledTooltipText']  = $login_protection_block_reason;
			$field['prerequisite_blocker'] = true;
		}
	}

	if ( $field_id === 'enable_passkey_login' ) {
		$passkey_block_reason = rsssl_get_passkey_login_enable_block_reason();
		$ssl_ready_for_passkeys = rsssl_get_option( 'ssl_enabled' ) && rsssl_get_option( 'site_has_ssl' );
		$rest_api_blocked = $ssl_ready_for_passkeys && $passkey_block_reason !== '';

		if ( $rest_api_blocked ) {
			$field['help'] = [
				'label' => 'warning',
				'title' => __( 'REST API blocked', 'really-simple-ssl' ),
				'text'  => $passkey_block_reason,
			];
		}

		if ( $passkey_block_reason !== '' && ! $field_has_enabled_checkbox_value ) {
			$field['disabled']             = true;
			$field['disabledTooltipText']  = $passkey_block_reason;
			$field['prerequisite_blocker'] = true;
		}
	}

	if ( $field_id === 'rename_db_prefix' ) {
		if ( ! rsssl_is_default_wp_prefix() && ! $field_has_enabled_checkbox_value ) {
			$field['value']    = true;
			$field['disabled'] = true;
		}
	}

	return $field;
}
add_filter('rsssl_field', 'rsssl_disable_fields', 10, 2);

function rsssl_clear_rest_api_accessible_cache_on_security_option_change( string $field_id ): void {
	if ( ! function_exists( 'rsssl_clear_rest_api_accessible_cache' ) ) {
		return;
	}

	if ( in_array( $field_id, [ 'login_protection_enabled', 'enable_passkey_login' ], true ) ) {
		rsssl_clear_rest_api_accessible_cache();
	}
}
add_action( 'rsssl_before_save_option', 'rsssl_clear_rest_api_accessible_cache_on_security_option_change', 10, 1 );

function rsssl_maybe_disable_htaccess_managed_field( array $field, string $field_id ): array {
	if ( $field['value'] ?? false ) {
		return $field;
	}

	if ( ! function_exists( 'rsssl_is_htaccess_field_externally_managed' ) ) {
		return $field;
	}

	if ( ! rsssl_is_htaccess_field_externally_managed( $field_id ) ) {
		return $field;
	}

	$field['value']    = true;
	$field['disabled'] = true;
	return $field;
}

/**
 * Prevent enabling blocked features through the save route when prerequisites are not met.
 *
 * @param mixed  $value
 * @param string $field_id
 * @param string $field_type
 *
 * @return mixed
 */
function rsssl_prevent_enabling_blocked_security_features( $value, string $field_id, string $field_type ) {
	if ( $field_type !== 'checkbox' ) {
		return $value;
	}

	$normalized_value = (int) $value;
	if ( $normalized_value !== 1 ) {
		return $value;
	}

	$field_block_reason_callbacks = [
		'send_notifications_email'   => 'rsssl_get_send_notifications_email_block_reason',
		'disable_application_passwords' => 'rsssl_get_disable_application_passwords_block_reason',
		'disable_xmlrpc'             => 'rsssl_get_disable_xmlrpc_block_reason',
		'enable_firewall'            => 'rsssl_get_enable_firewall_block_reason',
		'hsts'                       => 'rsssl_get_hsts_enable_block_reason',
		'login_protection_enabled'   => 'rsssl_get_login_protection_enable_block_reason',
		'enable_passkey_login'       => 'rsssl_get_passkey_login_enable_block_reason',
	];

	if ( in_array( $field_id, [ 'login_protection_enabled', 'enable_passkey_login' ], true ) && function_exists( 'rsssl_clear_rest_api_accessible_cache' ) ) {
		rsssl_clear_rest_api_accessible_cache();
	}

	if (
		isset( $field_block_reason_callbacks[ $field_id ] )
		&& $field_block_reason_callbacks[ $field_id ]() !== ''
	) {
		return 0;
	}

	return $value;
}
add_filter( 'rsssl_fieldvalue', 'rsssl_prevent_enabling_blocked_security_features', 20, 3 );

/**
 * When email verification is incomplete, preserve stored values for specific
 * email-gated fields instead of accepting incoming updates.
 *
 * If a field has not been saved yet, fall back to its configured default for
 * the current field type.
 */
function rsssl_preserve_stored_values_for_email_verification_blocked_fields( $value, string $field_id, string $field_type ) {
	if ( rsssl_is_email_verified() ) {
		return $value;
	}

	$email_verification_blocked_field_defaults = [
		'two_fa_enabled_roles_email'            => [],
		'vulnerability_notification_email_admin' => 'c',
	];

	if ( ! array_key_exists( $field_id, $email_verification_blocked_field_defaults ) ) {
		return $value;
	}

	if ( is_multisite() && rsssl_is_networkwide_active() ) {
		$stored_options = get_site_option( 'rsssl_options', [] );
	} else {
		$stored_options = get_option( 'rsssl_options', [] );
	}

	if ( array_key_exists( $field_id, $stored_options ) ) {
		return $stored_options[ $field_id ];
	}

	return rsssl_sanitize_field( $email_verification_blocked_field_defaults[ $field_id ], $field_type, $field_id );
}
add_filter( 'rsssl_fieldvalue', 'rsssl_preserve_stored_values_for_email_verification_blocked_fields', 25, 3 );
