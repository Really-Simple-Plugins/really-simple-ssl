<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );
require_once( trailingslashit(rsssl_path) . 'security/learning-mode.php' );
require_once( trailingslashit(rsssl_path) . 'security/tests.php' );
require_once( trailingslashit(rsssl_path) . 'security/functions.php' );
require_once( trailingslashit(rsssl_path) . 'security/check-requests.php' );
require_once( trailingslashit(rsssl_path) . 'security/sync-settings.php' );

function rsssl_enqueue_integrations_assets( $hook ) {

}
//add_action( 'admin_enqueue_scripts', 'rsssl_enqueue_integrations_assets' );

global $rsssl_integrations_list;
$rsssl_integrations_list = apply_filters( 'rsssl_integrations', array(
	'xmlrpc' => array(
		'label'                => 'XMLRPC',
        'folder'               => 'wordpress',
		'impact'               => 'medium',
		'risk'                 => 'low',
		'learning_mode'        => true,
		'type'                 => 'checkbox',
		'conditions'           => [
			'relation' => 'AND',
			[
				'rsssl_xmlrpc_allowed()' => true,
			]
		],
		'actions'              => array(
			'fix'       => 'rsssl_maybe_disable_xmlrpc',
		),
	),
//
    'user-registration' => array(
        'label'                => 'User registration',
        'folder'               => 'wordpress',
        'impact'               => 'medium',
        'risk'                 => 'medium',
        'learning_mode'        => true,
        'option_id'            => 'rsssl_disable_anyone_can_register',
        'type'                 => 'checkbox',
        'conditions'           => [
	        'relation' => 'AND',
	        [
	            'rsssl_user_registration_allowed()' => true,
	        ]
        ],
        'actions'              => array(
	        'fix'       => 'rsssl_disable_user_registration',
        ),
    ),

	'file-editing' => array(
		'label'                => 'File editing',
		'folder'               => 'wordpress',
		'impact'               => 'medium',
		'risk'                 => 'low',
		'learning_mode'        => false,
		'option_id'            => 'rsssl_disable_file_editing',
		'type'                 => 'checkbox',
		'conditions'           => array(
			'rsssl_file_editing_allowed()',
		),
		'actions'              => array(
			'fix'       => 'rsssl_disable_file_editing',
		),
	),

	'hide-wp-version' => array(
		'label'                => 'Hide WP version',
		'folder'               => 'wordpress',
		'impact'               => 'low',
		'risk'                 => 'low',
		'learning_mode'        => false,
		'option_id'            => 'rsssl_hide_wordpress_version',
		'type'                 => 'checkbox',
		'actions'              => array(
			'fix'       => 'rsssl_remove_wp_version',
		),
	),

	'user-enumeration' => array(
		'label'                => 'User Enumeration',
		'folder'               => 'wordpress',
		'impact'               => 'low',
		'risk'                 => 'medium',
		'learning_mode'        => true,
		'option_id'            => 'rsssl_disable_user_enumeration',
		'type'                 => 'checkbox',
		'actions'              => array(
			'fix'       => 'rsssl_disable_user_enumeration',
		),
	),

    'block-code-execution-uploads' => array(
        'label'                => 'Block code execution in uploads directory',
        'folder'               => 'wordpress',
        'impact'               => 'medium',
        'risk'                 => 'low',
        'learning_mode'        => false,
        'option_id'            => 'rsssl_block_code_execution_uploads',
        'type'                 => 'checkbox',
        'actions'              => array(
			'fix'       => 'rsssl_disable_code_execution_uploads',
        ),
    ),
    'prevent-login-info-leakage' => array(
        'label'                => 'Prevent login error leakage',
        'folder'               => 'wordpress',
        'impact'               => 'low',
        'risk'                 => 'high',
        'learning_mode'        => false,
        'option_id'            => 'rsssl_disable_login_feedback',
        'type'                 => 'checkbox',
        'actions'              => array(
			'fix'       => 'rsssl_no_wp_login_errors',
        ),
    ),
    'disable-http-methods' => array(
        'label'                => 'Disable HTTP methods',
        'folder'               => 'server',
        'impact'               => 'low',
        'risk'                 => 'medium',
        'learning_mode'        => false,
        'type'                 => 'checkbox',
        'conditions'           => [
	        'relation' => 'AND',
	        [
				'rsssl_test_if_http_methods_allowed()' => true,
	        ]
        ],
        'actions'              => array(
			'fix'       => 'rsssl_disable_http_methods',
        ),
    ),
//
    'debug-log' => array(
        'label'                => 'Move debug.log',
        'folder'               => 'wordpress',
        'impact'               => 'medium',
        'risk'                 => 'medium',
        'learning_mode'        => false,
        'option_id'            => 'rsssl_change_debug_log_location',
		'always_include'       => true,
        'type'                 => 'checkbox',
        'conditions'           => [
	        'relation' => 'AND',
	        [
	            'rsssl_is_default_debug_log_location()' => true,
		        'rsssl_is_debug_log_enabled()' => true,
	        ]
        ],
        'actions'              => array(
			'fix'       => 'rsssl_maybe_change_debug_log_location',
        ),
    ),

    'disable-indexing' => array(
        'label'                => 'Disable directory indexing',
        'folder'               => 'server',
        'impact'               => 'low',
        'risk'                 => 'medium',
        'learning_mode'        => false,
		'option_id'            => 'rsssl_disable_indexing',
        'type'                 => 'checkbox',
        'actions'              => array(
			'fix'       => 'rsssl_disable_indexing_wrapper',
        ),
    ),

	'application-passwords' => array(
		'label'                => 'Disable Application passwords',
		'folder'               => 'wordpress',
		'impact'               => 'low',
		'risk'                 => 'high',
		'learning_mode'        => false,
		'option_id'            => 'rsssl_disable_application_passwords',
		'always_include'       => true,
		'type'                 => 'checkbox',
		'conditions'           => [
			'relation' => 'AND',
			[
				'rsssl_application_passwords_available()' => true,
			]
		],
		'actions'              => array(
			'fix'       => 'rsssl_maybe_allow_application_passwords',
		),
	),

	'rename-db-prefix' => array(
		'label'                => 'Rename DB prefix',
		'folder'               => 'wordpress',
		'impact'               => 'high',
		'risk'                 => 'high',
		'learning_mode'        => false,
		'option_id'            => 'rsssl_rename_db_prefix',
		'type'                 => 'checkbox',
		'conditions'           => [
			'relation' => 'AND',
			[
				'rsssl_is_default_wp_prefix()'=>true,
			]
		],
		'actions'              => array(
			'fix'       => 'rsssl_maybe_rename_db_prefix',
		),
	),
    'rename-admin-user' => array(
		'label'                => 'Rename admin user',
		'folder'               => 'wordpress',
		'impact'               => 'high',
		'risk'                 => 'high',
		'learning_mode'        => false,
		'option_id'            => 'rsssl_rename_admin_user',
		'type'                 => 'checkbox',
		'conditions'           => [
			'relation' => 'AND',
			[
				'rsssl_has_admin_user()' => true,
			]
		],
		'actions'              => array(
			'fix'       => 'rsssl_rename_admin_user',
		),
	),

	'display-name-is-login-name' => array(
		'label'                => 'Display name equals login name',
		'folder'               => 'wordpress',
		'impact'               => 'low',
		'risk'                 => 'medium',
		'learning_mode'        => false,
//		'option_id'            => '',
		'type'                 => 'checkbox',
		'conditions'           => array(
			'relation' => 'AND',
			[
				'rsssl_has_admin_user()' => true,
			]
		),
		'actions'              => array(
//			'fix'       => 'rsssl_change_display_name',
		),
	),
) );

/**
 * Check if this plugin's integration is enabled
 * @param string $plugin
 * @param array $details
 *
 * @return bool
 */
function rsssl_is_integration_enabled( $plugin, $details ) {
	global $rsssl_integrations_list;
	if ( ! array_key_exists( $plugin, $rsssl_integrations_list ) ) {
		return false;
	}
	if ($details['always_include']) {
		return true;
	}

	$field_id = isset($details['option_id']) ? $details['option_id'] : false;
	if ( !$field_id){
		error_log("not field id for $plugin");
	}
	if ($field_id && rsssl_get_option($field_id) ) {
		error_log("setting $field_id is enabled");
		return true;
	} else {
		error_log("setting $field_id is disabled");

	}
	return false;
}

/**
 * code loaded without privileges to allow integrations between plugins and services, when enabled.
 */

function rsssl_integrations() {

	global $rsssl_integrations_list;
	$stored_integrations_count = get_option('rsssl_active_integrations', 0 );
	$actual_integrations_count = 0;

	foreach ( $rsssl_integrations_list as $plugin => $details ) {
		$details = wp_parse_args($details,
			[
				'conditions' => [],
				'option_id' => false,
				'always_include'=>false,
				'folder' => false,
			]
		);

		if ( rsssl_is_integration_enabled( $plugin, $details ) ) {
			error_log("$plugin enabled");
			$actual_integrations_count++;
			$file = rsssl_path . 'security/' . $details['folder'] . "/" . $plugin . '.php';
			$skip = true;

			if ( isset( $details['conditions'] ) ) {
				$skip = !rsssl_conditions_apply($details['conditions']);
			}

			if ( ! file_exists( $file ) || $skip ) {
				continue;
			}

			error_log("load file $file");
			require_once( $file );
			$risk = $details['risk'];
			$impact = $details['impact'];

			// Apply fix on high risk, low impact
			if ( $risk === 'high' && $impact === 'low' ) {
				$fix = isset($details['actions']['fix']) ? $details['actions']['fix']: false;
				if ($fix && function_exists($fix)) {
					$fix();
				} else {
					error_log("Really Simple SSL: fix function $fix not found");
				}
			}
		} else {
			error_log("$plugin not enabled");
		}
	}
	update_option('rsssl_active_integrations',  $actual_integrations_count);
	if ( $stored_integrations_count != $actual_integrations_count) {
		update_option('rsssl_integrations_changed', true );
	}

}

add_action( 'plugins_loaded', 'rsssl_integrations', 10 );