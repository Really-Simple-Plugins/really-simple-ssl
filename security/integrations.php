<?php
defined( 'ABSPATH' ) or die();
global $rsssl_integrations_list;
$rsssl_integrations_list = apply_filters( 'rsssl_integrations', array(
	'user-registration' => array(
		'folder'               => 'wordpress',
		'option_id'            => 'disable_anyone_can_register',
	),

	'file-editing' => array(
		'folder'               => 'wordpress',
		'option_id'            => 'disable_file_editing',
	),

	'hide-wp-version' => array(
		'folder'               => 'wordpress',
		'option_id'            => 'hide_wordpress_version',
	),

	'user-enumeration' => array(
		'folder'               => 'wordpress',
		'option_id'            => 'disable_user_enumeration',
	),

	'block-code-execution-uploads' => array(
		'folder'               => 'wordpress',
		'impact'               => 'medium',
		'risk'                 => 'low',
		'option_id'            => 'block_code_execution_uploads',
	),

	'prevent-login-info-leakage' => array(
		'folder'               => 'wordpress',
		'option_id'            => 'disable_login_feedback',
	),
	'disable-indexing' => array(
		'folder'               => 'server',
		'option_id'            => 'disable_indexing',
		'has_deactivation'     => true,
	),

	'rename-admin-user' => array(
		'folder'               => 'wordpress',
		'option_id'            => 'rename_admin_user',
	),
	'display-name-is-login-name' => array(
		'folder'               => 'wordpress',
		'option_id'            => 'block_display_is_login',
	),

	'disable-xmlrpc' => array(
		'folder'               => 'wordpress',
		'option_id'            => 'disable_xmlrpc',
		'always_include'       => false,
	),

	'vulnerabilities' => array(
		'folder'               => 'wordpress',
		'option_id'            => 'enable_vulnerability_scanner',
		'admin_only'           => true,
	),
) );

/**
 * Check if this plugin's integration is enabled
 * @param string $plugin
 * @param array $details
 *
 * @return bool
 */
if ( ! function_exists('rsssl_is_integration_enabled') ) {
	function rsssl_is_integration_enabled( $plugin, $details ) {
		global $rsssl_integrations_list;
		if ( ! array_key_exists( $plugin, $rsssl_integrations_list ) ) {
			return false;
		}
		if ( $details['always_include'] ) {
			return true;
		}

		//if an integration was just enabled, we keep it enabled until it removes itself from the list.
		//only for admin users
		if ( rsssl_is_in_deactivation_list( $plugin ) ) {
			return true;
		}

		$field_id = $details['option_id'] ?? false;
		if ( ! $field_id ) {
			return false;
		}

		$field_value  = $details['option_value'] ?? false;
		$stored_value = rsssl_get_option( $field_id );
		if ( $field_value ) {
			if ( $stored_value === $field_value ) {
				return true;
			}
		} else if ( $stored_value ) {
			return true;
		}

		return false;
	}
}
/**
 * code loaded without privileges to allow integrations between plugins and services, when enabled.
 */
if ( ! function_exists('rsssl_integrations') ) {
	function rsssl_integrations() {
		global $rsssl_integrations_list;
		foreach ( $rsssl_integrations_list as $plugin => $details ) {
			$details = wp_parse_args( $details,
				[
					'option_id'      => false,
					'always_include' => false,
					'folder'         => false,
					'admin_only'     => false,
				]
			);

			if ( $details['admin_only'] && ! rsssl_admin_logged_in() ) {
				continue;
			}

			if ( rsssl_is_integration_enabled( $plugin, $details ) ) {
				$path = apply_filters( 'rsssl_integrations_path', rsssl_path, $plugin );
				$file = $path . 'security/' . $details['folder'] . "/" . $plugin . '.php';
				if ( ! file_exists( $file ) ) {
					continue;
				}
				require_once( $file );
			}
		}
	}
}
add_action( 'plugins_loaded', 'rsssl_integrations', 10 );
add_action( 'rsssl_after_saved_fields', 'rsssl_integrations', 20 );

/**
 * Check if a plugin is on the deactivation list
 *
 * @param string $plugin
 *
 * @return bool
 */
if ( ! function_exists('rsssl_is_in_deactivation_list') ) {
	function rsssl_is_in_deactivation_list( string $plugin ): bool {
		if ( ! is_admin() || ! is_user_logged_in() ) {
			return false;
		}

		if ( ! is_array( get_option( 'rsssl_deactivate_list', [] ) ) ) {
			delete_option( 'rsssl_deactivate_list' );
		}

		return in_array( $plugin, get_option( 'rsssl_deactivate_list', [] ) );
	}
}