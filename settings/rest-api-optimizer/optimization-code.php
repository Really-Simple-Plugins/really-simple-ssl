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
		// but for some requests, we need to load other plugins, to ensure we can detect them.
		if ( isset($_SERVER['REQUEST_URI']) && (
				strpos($_SERVER['REQUEST_URI'], 'wp-json/reallysimplessl/v') === false ||
				strpos($_SERVER['REQUEST_URI'], 'otherpluginsdata') !==false  ||
				strpos($_SERVER['REQUEST_URI'], 'plugin_actions') !==false  ||
				strpos($_SERVER['REQUEST_URI'], 'onboarding') !==false ||
				strpos($_SERVER['REQUEST_URI'], 'do_action/activate') !==false
			)
		) {
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