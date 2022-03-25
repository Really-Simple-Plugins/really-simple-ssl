<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );
if ( is_admin() ) {
//	require_once( 'integrations-menu.php' );
}

require_once( trailingslashit(rsssl_path) . 'security/learning-mode.php' );
require_once( trailingslashit(rsssl_path) . 'security/functions.php' );
require_once( trailingslashit(rsssl_path) . 'security/check-requests.php' );

function rsssl_enqueue_integrations_assets( $hook ) {
//	wp_register_script( ' rsssl-pagify', trailingslashit( rsssl_url ) . 'assets/pagify/pagify.min.js', array( "jquery" ), rsssl_version );
//	wp_enqueue_script( ' rsssl-pagify' );
//
//	wp_register_style( ' rsssl-pagify', trailingslashit( rsssl_url ) . 'assets/pagify/pagify.css', false, rsssl_version );
//	wp_enqueue_style( ' rsssl-pagify' );
}
add_action( 'admin_enqueue_scripts', 'rsssl_enqueue_integrations_assets' );

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
		'conditions'           => array(
			'rsssl_xmlrpc_allowed',
		),
		'actions'              => array(
			'fix'       => 'rsssl_maybe_disable_xmlrpc',
			'ignore'    => 'disable_checkbox',
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
        'option_id'            => 'anyone_can_register',
        'type'                 => 'checkbox',
        'conditions'           => array(
//	        'rsssl_user_registration_allowed',
        ),
        'actions'              => array(
	        'fix'       => 'rsssl_maybe_disable_user_registration',
	        'ignore'    => 'disable_checkbox',
        ),
    ),

	'file-editing' => array(
		'constant_or_function' => 'rsssl_file_editing_registration',
		'label'                => 'File editing',
		'folder'               => 'wordpress',
		'impact'               => 'medium',
		'risk'                 => 'low',
		'learning_mode'        => false,
		'option_id'            => 'file_editing',
		'type'                 => 'checkbox',
		'conditions'           => array(
//			'rsssl_file_editing_allowed',
		),
		'actions'              => array(
			'fix'       => 'rsssl_disable_file_editing',
//			'ignore'    => 'disable_checkbox',
		),
	),

	'hide-wp-version' => array(
		'constant_or_function' => 'rsssl_hide_wp_version',
		'label'                => 'Hide WP version',
		'folder'               => 'wordpress',
		'impact'               => 'low',
		'risk'                 => 'low',
		'learning_mode'        => false,
		'option_id'            => 'hide_wp_version',
		'type'                 => 'checkbox',
		'conditions'           => array(
//			'rsssl_file_editing_allowed',
		),
		'actions'              => array(
			'fix'       => 'rsssl_remove_wp_version',
//			'ignore'    => 'disable_checkbox',
		),
	),

	'user-enumeration' => array(
		'constant_or_function' => 'rsssl_user_enumeration',
		'label'                => 'User Enumeration',
		'folder'               => 'wordpress',
		'impact'               => 'low',
		'risk'                 => 'medium',
		'learning_mode'        => true,
		'type'                 => 'checkbox',
		'conditions'           => array(
//			'rsssl_file_editing_allowed',
		),
		'actions'              => array(
			'fix'       => 'rsssl_disable_user_enumeration',
//			'ignore'    => 'disable_checkbox',
		),
	),

    'block-code-execution-uploads' => array(
        'constant_or_function' => 'rsssl_block_code_execution_uploads',
        'label'                => 'Block code execution in uploads directory',
        'folder'               => 'wordpress',
        'impact'               => 'medium',
        'risk'                 => 'low',
        'learning_mode'        => false,
        'option_id'            => 'code_execution_uploads',
        'type'                 => 'checkbox',
        'conditions'           => array(
//			'rsssl_file_editing_allowed',
        ),
        'actions'              => array(
			'fix'       => 'rsssl_disable_code_execution_uploads',
//			'ignore'    => 'disable_checkbox',
        ),
    ),
    'prevent-login-info-leakage' => array(
        'constant_or_function' => 'rsssl_prevent_info_login_leakage',
        'label'                => 'Prevent login error leakage',
        'folder'               => 'wordpress',
        'impact'               => 'low',
        'risk'                 => 'high',
        'learning_mode'        => false,
        'option_id'            => 'login_feedback',
        'type'                 => 'checkbox',
        'conditions'           => array(
//			'rsssl_file_editing_allowed',
        ),
        'actions'              => array(
			'fix'       => 'rsssl_remove_wp_version',
//			'ignore'    => 'disable_checkbox',
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
        'conditions'           => array(
//			'rsssl_file_editing_allowed',
        ),
        'actions'              => array(
			'fix'       => 'rsssl_disable_http_methods',
//			'ignore'    => 'disable_checkbox',
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
        'type'                 => 'checkbox',
        'conditions'           => array(
//			'rsssl_file_editing_allowed',
        ),
        'actions'              => array(
			'fix'       => 'rsssl_change_debug_log_location',
//			'ignore'    => 'disable_checkbox',
        ),
    ),

    'disable-indexing' => array(
        'constant_or_function' => 'rsssl_disable_indexing',
        'label'                => 'Disable directory indexing',
        'folder'               => 'server',
        'impact'               => 'low',
        'risk'                 => 'medium',
        'learning_mode'        => false,
        'type'                 => 'checkbox',
        'conditions'           => array(
//			'rsssl_file_editing_allowed',
        ),
        'actions'              => array(
			'fix'       => 'rsssl_disable_indexing',
//			'ignore'    => 'disable_checkbox',
        ),
    ),

	'application-passwords' => array(
		'constant_or_function' => 'rsssl_application_passwords',
		'label'                => 'Disable Application passwords',
		'folder'               => 'wordpress',
		'impact'               => 'low',
		'risk'                 => 'high',
		'learning_mode'        => false,
		'option_id'            => 'application_passwords',
		'type'                 => 'checkbox',
		'conditions'           => array(
			'rsssl_application_passwords_available',
		),
		'actions'              => array(
			'fix'       => 'rsssl_disable_application_passwords',
//			'ignore'    => 'disable_checkbox',
		),
	),

	'rename-db-prefix' => array(
		'constant_or_function' => 'rsssl_maybe_rename_db_prefix',
		'label'                => 'Rename DB prefix',
		'folder'               => 'wordpress',
		'impact'               => 'low',
		'risk'                 => 'high',
		'learning_mode'        => false,
		'option_id'            => 'rename_db_prefix',
		'type'                 => 'checkbox',
		'conditions'           => array(
//			'rsssl_application_passwords_available',
		),
		'actions'              => array(
			'fix'       => 'rsssl_maybe_rename_db_prefix',
//			'ignore'    => 'disable_checkbox',
		),
	),
) );


//require_once( 'fields.php' );

foreach ( $rsssl_integrations_list as $plugin => $details ) {

//    if ( ! isset( $details['early_load'] ) ) {
//		continue;
//	}

	if ( ! file_exists( rsssl_path . 'security/' . $details['folder'] . "/" . $plugin . '.php' )
	) {
		continue;
	} else {
        $file = rsssl_path . 'security/' . $details['folder'] . "/" . $plugin . '.php';
    }

	$skip = false;

	if ( isset( $details['conditions'] ) ) {

		// Include file with output buffering, so we can unset it if the conditions are not met
		ob_start();
		include( $file );
		$output = ob_get_contents();
		ob_end_clean();

		foreach ( $details['conditions'] as $condition ) {
			$condition_met = rsssl_validate_function($condition);
			if ( $condition_met != true ) {
				$skip = true;
			}
		}

		unset( $output );
	}

	if ( isset( $details['option_id'] ) && rsssl_get_option( $details['option_id'] ) !== 1 ) {
		error_log("$plugin skipped, option not enabled");
	} elseif ( file_exists( $file ) && $skip !== true ) {
		require_once( $file );
	} elseif ( $skip !== false ) {
		error_log("$plugin skipped, conditions not met");
	} else {
		error_log( "searched for $plugin integration at $file, but did not find it" );
	}

	$risk = $details['risk'];
	$impact = $details['impact'];

	// Apply fix on high risk, low impact, OR when option has been enabled
    if ( $risk === 'high' && $impact === 'low'
         || ( isset($details['option_id']) && rsssl_get_option($details['option_id'] ) !== false ) ) {
        $fix = $details['actions']['fix'];
        rsssl_validate_function( $fix );
    }

}

/**
 * Check if this plugin's integration is enabled
 *
 * @return bool
 */
function rsssl_is_integration_enabled( $plugin_name ) {
	global $rsssl_integrations_list;
	if ( ! array_key_exists( $plugin_name, $rsssl_integrations_list ) ) {
		return false;
	}
	$fields = get_option( 'rsssl_options_integrations' );
	//default enabled, which means it's enabled when not set.
	if ( isset( $fields[ $plugin_name ] ) && $fields[ $plugin_name ] != 1 ) {
		return false;
	}

	return true;
}

/**
 * Check if a plugin from the integrations list is active
 * @param $plugin
 *
 * @return bool
 */
function rsssl_integration_plugin_is_active( $plugin ){
	global $rsssl_integrations_list;
	if ( !isset($rsssl_integrations_list[ $plugin ]) ) {
		return false;
	}
	//because we need a default, we don't use the get_value from rsssl. The fields array is not loaded yet, so there are no defaults
	$fields = get_option( 'rsssl_options_integrations' );
	$details = $rsssl_integrations_list[ $plugin ];
	$enabled = isset( $fields[ $plugin ] ) ? $fields[ $plugin ] : true;
	$theme = wp_get_theme();
	if (
		( defined($details['constant_or_function'])
		  || function_exists( $details['constant_or_function'] )
		  || class_exists( $details['constant_or_function'] )
		  || ( $theme && ($theme->name === $details['constant_or_function']) )
		)
		&& $enabled
	) {
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
		if ( rsssl_integration_plugin_is_active( $plugin ) ) {
			$actual_integrations_count++;
			$file = apply_filters( 'rsssl_integration_path', rsssl_path . "security/" . $details['folder']. '/' . $plugin.".php", $plugin );
			if ( file_exists( $file ) ) {
				require_once( $file );
			} else {
				error_log( "searched for $plugin integration at $file, but did not find it" );
			}
		}
	}
	update_option('rsssl_active_integrations',  $actual_integrations_count);

	if ( $stored_integrations_count != $actual_integrations_count) {
		update_option('rsssl_integrations_changed', true );
	}

}

add_action( 'plugins_loaded', 'rsssl_integrations', 10 );