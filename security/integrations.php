<?php
defined( 'ABSPATH' ) or die();
require_once( trailingslashit(rsssl_path) . 'security/learning-mode.php' );
require_once( trailingslashit(rsssl_path) . 'security/tests.php' );
require_once( trailingslashit(rsssl_path) . 'security/cron.php' );
require_once( trailingslashit(rsssl_path) . 'security/check-requests.php' );

/**
 * Load only on back-end
 */
if (is_admin() || rsssl_is_logged_in_rest() ) {
	require_once( trailingslashit(rsssl_path) . 'security/notices.php' );
	require_once( trailingslashit(rsssl_path) . 'security/functions.php' );
	require_once( trailingslashit(rsssl_path) . 'security/sync-settings.php' );
}

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
		'option_id'            => 'xmlrpc',
		'type'                 => 'checkbox',
		'conditions'           => [
			'relation' => 'AND',
			[
				'rsssl_xmlrpc_allowed()' => true,
			]
		],
	),

    'user-registration' => array(
        'label'                => 'User registration',
        'folder'               => 'wordpress',
        'impact'               => 'medium',
        'risk'                 => 'medium',
        'learning_mode'        => false,
        'option_id'            => 'disable_anyone_can_register',
        'type'                 => 'checkbox',
        'conditions'           => [
	        'relation' => 'AND',
	        [
	            'rsssl_user_registration_allowed()' => true,
	        ]
        ],
    ),

	'file-editing' => array(
		'label'                => __('File editing', 'really-simple-ssl'),
		'folder'               => 'wordpress',
		'impact'               => 'medium',
		'risk'                 => 'low',
		'learning_mode'        => false,
		'option_id'            => 'disable_file_editing',
		'type'                 => 'checkbox',
		'conditions'           => array(
			'rsssl_file_editing_allowed()' => true,
		),
	),

	'hide-wp-version' => array(
		'label'                => __('Hide WP version','really-simple-ssl'),
		'folder'               => 'wordpress',
		'impact'               => 'low',
		'risk'                 => 'low',
		'learning_mode'        => false,
		'option_id'            => 'hide_wordpress_version',
		'type'                 => 'checkbox',
	),

	'user-enumeration' => array(
		'label'                => __('User Enumeration','really-simple-ssl'),
		'folder'               => 'wordpress',
		'impact'               => 'low',
		'risk'                 => 'medium',
		'learning_mode'        => true,
		'option_id'            => 'disable_user_enumeration',
		'type'                 => 'checkbox',
	),

    'block-code-execution-uploads' => array(
        'label'                => __('Block code execution in uploads directory','really-simple-ssl'),
        'folder'               => 'wordpress',
        'impact'               => 'medium',
        'risk'                 => 'low',
        'learning_mode'        => false,
        'option_id'            => 'block_code_execution_uploads',
        'type'                 => 'checkbox',
    ),

    'prevent-login-info-leakage' => array(
        'label'                => __('Prevent login error leakage','really-simple-ssl'),
        'folder'               => 'wordpress',
        'impact'               => 'low',
        'risk'                 => 'high',
        'learning_mode'        => false,
        'option_id'            => 'disable_login_feedback',
        'type'                 => 'checkbox',
    ),
    'disable-http-methods' => array(
        'label'                => __('Disable HTTP methods', 'really-simple-ssl'),
        'folder'               => 'server',
        'impact'               => 'low',
        'risk'                 => 'medium',
        'learning_mode'        => false,
        'type'                 => 'checkbox',
        'option_id'            => 'disable_http_methods',
        'conditions'           => [
	        'relation' => 'AND',
	        [
				'rsssl_http_methods_allowed()' => true,
	        ]
        ],
    ),

    'debug-log' => array(
        'label'                => __('Move debug.log', 'really-simple-ssl'),
        'folder'               => 'wordpress',
        'impact'               => 'medium',
        'risk'                 => 'medium',
        'learning_mode'        => false,
        'option_id'            => 'change_debug_log_location',
		'always_include'       => true,
        'type'                 => 'checkbox',
        'conditions'           => [
	        'relation' => 'AND',
	        [
	            'rsssl_debug_log_in_default_location()' => true,
		        'rsssl_is_debug_log_enabled()' => true,
	        ]
        ],
    ),

    'disable-indexing' => array(
        'label'                => __('Disable directory indexing', 'really-simple-ssl'),
        'folder'               => 'server',
        'impact'               => 'low',
        'risk'                 => 'medium',
        'learning_mode'        => false,
		'option_id'            => 'disable_indexing',
        'type'                 => 'checkbox',
        'actions'              => array(
			'fix'       => 'rsssl_disable_indexing_wrapper',
        ),
    ),

	'application-passwords' => array(
		'label'                => __('Disable Application passwords', 'really-simple-ssl'),
		'folder'               => 'wordpress',
		'impact'               => 'low',
		'risk'                 => 'high',
		'learning_mode'        => false,
		'option_id'            => 'disable_application_passwords',
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
		'label'                => __('Rename DB prefix', 'really-simple-ssl'),
		'folder'               => 'wordpress',
		'impact'               => 'high',
		'risk'                 => 'high',
		'learning_mode'        => false,
		'option_id'            => 'rename_db_prefix',
		'type'                 => 'checkbox',
		'conditions'           => [
			'relation' => 'AND',
			[
				'rsssl_is_default_wp_prefix()'=>true,
			]
		],
	),

    'rename-admin-user' => array(
		'label'                => __('Do not allow users with admin username', 'really-simple-ssl'),
		'folder'               => 'wordpress',
		'impact'               => 'high',
		'risk'                 => 'high',
		'learning_mode'        => false,
		'option_id'            => 'rename_admin_user',
		'type'                 => 'checkbox',
	),

	'display-name-is-login-name' => array(
		'label'                => __('Display name equals login name', 'really-simple-ssl'),
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
	if ($field_id && rsssl_get_option($field_id) ) {
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
		$details = wp_parse_args($details,
			[
				'conditions' => [],
				'option_id' => false,
				'always_include'=>false,
				'folder' => false,
			]
		);

		if ( rsssl_is_integration_enabled( $plugin, $details ) ) {
			$actual_integrations_count++;
			$file = rsssl_path . 'security/' . $details['folder'] . "/" . $plugin . '.php';
			$skip = true;
			if ( isset( $details['conditions'] ) ) {
				$skip = !rsssl_conditions_apply($details['conditions']);
			}

			if ( ! file_exists( $file ) || $skip ) {
				continue;
			}
			require_once( $file );
			$risk = $details['risk'];
			$impact = $details['impact'];

			// Apply fix automatically on high risk, low impact
			//check if already executed
//			if ( $risk === 'high' && $impact === 'low' ) {
//				$fix = isset($details['actions']['fix']) ? $details['actions']['fix']: false;
//				rsssl_do_fix($fix);
//			}
		}
	}

	if ( $stored_integrations_count != $actual_integrations_count) {
		update_option('rsssl_active_integrations',  $actual_integrations_count);
		update_option('rsssl_integrations_changed', true );
	}

}

add_action( 'plugins_loaded', 'rsssl_integrations', 10 );