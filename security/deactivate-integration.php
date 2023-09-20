<?php
defined('ABSPATH') or die();
/**
 * If a plugin is deactivated, add to deactivated list.
 * @param string $field_id
 * @param mixed $new_value
 * @param mixed $prev_value
 * @param string $type
 *
 * @return void
 */
function rsssl_handle_integration_deactivation($field_id, $new_value, $prev_value, $type){
	if (!rsssl_user_can_manage()) {
		return;
	}
	if ($new_value !== $prev_value && $new_value === 0 ){
		//check if this field id exists in the list of plugins
		global $rsssl_integrations_list;
		foreach ( $rsssl_integrations_list as $plugin => $plugin_data ) {
			if (
				isset($plugin_data['has_deactivation']) &&
				$plugin_data['has_deactivation'] &&
				isset($plugin_data['option_id']) &&
				$plugin_data['option_id'] === $field_id
			) {
				//add to deactivated list
				$current_list = get_option('rsssl_deactivate_list', []);
				if ( !in_array($plugin, $current_list)) {
					$current_list[] = $plugin;
					update_option('rsssl_deactivate_list', $current_list, false);
				}
			}
		}
	}
}
add_action( "rsssl_after_save_field", "rsssl_handle_integration_deactivation", 10, 4 );

/**
 * Remove a plugin from the deactivation list if deactivation procedure was completed
 * @param string $plugin
 *
 * @return void
 */
function rsssl_remove_from_deactivation_list($plugin){
	if (!rsssl_user_can_manage()) {
		return;
	}
	$deactivate_list = get_option('rsssl_deactivate_list', []);
	if ( in_array($plugin, $deactivate_list )) {
		$index = array_search($plugin, $deactivate_list);
		unset($deactivate_list[$index]);
		update_option('rsssl_deactivate_list', $deactivate_list, false );
	}
}