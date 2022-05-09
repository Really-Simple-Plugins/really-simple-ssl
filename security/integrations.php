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
		'constant_or_function' => 'rsssl_xmlrpc',
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
        'constant_or_function' => 'rsssl_user_registration',
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
		'constant_or_function' => 'rsssl_file_editing_registration',
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
		'constant_or_function' => 'rsssl_hide_wp_version',
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
		'constant_or_function' => 'rsssl_user_enumeration',
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
        'constant_or_function' => 'rsssl_block_code_execution_uploads',
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
        'constant_or_function' => 'rsssl_prevent_info_login_leakage',
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
        'constant_or_function' => 'rsssl_http_methods',
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
        'constant_or_function' => 'rsssl_debug_log',
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
        'constant_or_function' => 'rsssl_disable_indexing_wrapper',
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
		'constant_or_function' => 'rsssl_application_passwords',
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
		'constant_or_function' => 'rsssl_maybe_rename_db_prefix',
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
		'constant_or_function' => 'rsssl_rename_admin_user',
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
		'constant_or_function' => 'rsssl_display_name',
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
 *
 * @return bool
 */
function rsssl_is_integration_enabled( $plugin ) {
	global $rsssl_integrations_list;
	if ( ! array_key_exists( $plugin, $rsssl_integrations_list ) ) {
		return false;
	}
	$field_id = $plugin['option_id'];
	if (rsssl_get_option($field_id)) {
		return true;
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
		if ( rsssl_is_integration_enabled( $plugin ) ) {
			$actual_integrations_count++;
			$details = wp_parse_args($details,
				[
				    'conditions' => [],
				    'option_id' => false,
					'always_include'=>false,
					'folder' => false,
					'constant_or_function' => false
				]
			);
			$file = rsssl_path . 'security/' . $details['folder'] . "/" . $plugin . '.php';
			$skip = false;

			if ( isset( $details['conditions'] ) ) {
				error_log(print_r($details['conditions'], true));
				$skip = !rsssl_conditions_apply($details['conditions']);
				$test = $skip ? "do skip" : "do not skip";

				error_log("Skip value $test");
			}

			if ( ! file_exists( $file ) ) {
				continue;
			}

			// Always include if always_include is true
			if ( $details['always_include'] !== false ) {
				error_log("load file $file");
				require_once( $file );
			} elseif ( file_exists( $file ) && $skip !== true ) {
				error_log("load file $file");
				require_once( $file );
			} elseif ( $skip !== false ) {
				error_log("$plugin skipped, conditions not met");
			} else {
				error_log( "searched for $plugin integration at $file, but did not find it" );
			}

			$risk = $details['risk'];
			$impact = $details['impact'];

			// Apply fix on high risk, low impact, OR when option has been enabled
			if (
				( $risk === 'high' && $impact === 'low' )
				|| ( isset( $details['option_id']) && rsssl_get_option($details['option_id'] ) !== false )
			) {
				$fix = isset($details['actions']['fix']) ? $details['actions']['fix']: false;
				if ($fix && function_exists($fix)) {
					$fix();
				} else {
					error_log("Really Simple SSL: fix function $fix not found");
				}
			}
		}
	}
	update_option('rsssl_active_integrations',  $actual_integrations_count);

	if ( $stored_integrations_count != $actual_integrations_count) {
		update_option('rsssl_integrations_changed', true );
	}

}

//add_action( 'plugins_loaded', 'rsssl_integrations', 10 );