<?php defined( 'ABSPATH' ) or die();
define('rsssl_rest_api_optimizer', true);
if ( ! function_exists( 'rsssl_exclude_plugins_for_rest_api' ) ) {
	/**
	 * Exclude all other plugins from the active plugins list if this is a Really Simple SSL rest request
	 *
	 * @param array $plugins The active plugins.
	 *
	 * @return array The filtered active plugins.
	 */
	function rsssl_exclude_plugins_for_rest_api( $plugins ) {
		// if not an rsp request return all plugins
		if ( isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'wp-json/reallysimplessl/v') === false ) {
			return $plugins;
		//we need to be able to detect active and not active status for these requests, for other plugins installation purposes, like burst, complianz.
		} else if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'otherpluginsdata') !==false) {
			return $plugins;
		} else if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'plugin_actions') !==false) {
			return $plugins;
		} else if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'onboarding') !==false) {
			return $plugins;
		}

		//Only leave RSSSL and premium add ons active for this request
		foreach ( $plugins as $key => $plugin ) {
			if ( strpos($plugin, 'really-simple-ssl') !== false ){
				continue;
			}
			unset( $plugins[ $key ] );
		}
		return $plugins;
	}
	add_filter( 'option_active_plugins', 'rsssl_exclude_plugins_for_rest_api' );
}