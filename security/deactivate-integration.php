<?php
/**
 * If a plugin is deactivated, add to deactivated list.
 * @param $field_id
 * @param $new_value
 * @param $prev_value
 * @param $type
 *
 * @return void
 */
function rsssl_handle_integration_deactivation($field_id, $new_value, $prev_value, $type){
	if ($new_value !== $prev_value && $new_value === false ){
		//check if this field id exists in the list of plugins
		global $rsssl_integrations_list;
		foreach ( $rsssl_integrations_list as $plugin => $plugin_data ) {
			if ( $plugin_data['option_id'] === $field_id ) {
				//add to deactivated list
				$current_list = get_option('rsssl_deactivate_list', []);
				if ( !in_array($plugin, $current_list)) {
					$current_list[] = $plugin;
					error_log(print_r($current_list,true));
					update_option('rsssl_deactivate_list', $plugin, false);
				}
			}
		}
	}
}
add_action( "rsssl_after_save_field", "rsssl_handle_integration_deactivation", 10, 4 );