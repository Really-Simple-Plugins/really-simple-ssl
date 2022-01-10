<?php
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );
add_filter( 'rsssl_fields_load_types', 'rsssl_filter_integration_fields', 10, 1 );
function rsssl_filter_integration_fields( $fields ) {
	global $rsssl_integrations_list;
	$plugin_fields          = array();
	$disabled_plugin_fields = array();
	$enabled_plugin_fields  = array();
	foreach ( $rsssl_integrations_list as $plugin => $details ) {
		$file = apply_filters( 'rsssl_integration_path',
			rsssl_path . "integrations/plugins/$plugin.php", $plugin );
		if ( file_exists( $file ) ) {
			$plugin_fields[ $plugin ] = array(
				'source'                  => 'integrations',
				'type'                    => 'checkbox',
				'default'                 => false,
				'label'                   => $details['label'],
				'table'                   => true,
				'disabled'                => true,
			);

			if ( isset( $details['callback_condition'] ) ) {
				$plugin_fields[ $plugin ]['callback_condition']
					= $details['callback_condition'];
			}
			$theme = wp_get_theme();
			if ( defined( $details['constant_or_function'] )
			     || function_exists( $details['constant_or_function'] )
			     || class_exists( $details['constant_or_function'] )
			     || ($theme && ($theme->name === $details['constant_or_function']) )
			) {
				$plugin_fields[ $plugin ]['disabled'] = false;
				$plugin_fields[ $plugin ]['default']  = true;
				$enabled_plugin_fields[ $plugin ]
				                                      = $plugin_fields[ $plugin ];
			} else {
				$disabled_plugin_fields[ $plugin ] = $plugin_fields[ $plugin ];
			}
		}
	}

	//now make sure enabled ones are first
	$fields = $fields + $enabled_plugin_fields;
	return $fields;

}