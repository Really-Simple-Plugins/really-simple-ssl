<?php
defined('ABSPATH') or die();
/**
 *  Only functions also required on front-end here
 */


/**
 * Get a Really Simple SSL option by name
 *
 * @param string $name
 * @param mixed  $default
 *
 * @return mixed
 */

function rsssl_get_option( string $name, $default=false ) {
	$name = sanitize_title($name);
	if ( is_multisite() && rsssl_is_networkwide_active() ) {
		$options = get_site_option( 'rsssl_options', [] );
	} else {
		$options = get_option( 'rsssl_options', [] );
	}

	$value = isset($options[$name]) ? $options[$name] : false;
	if ( $value===false && $default!==false ) {
		$value = $default;
	}

	return apply_filters("rsssl_option_$name", $value, $name);
}

/**
 * Check if we should treat the plugin as networkwide or not.
 * Note that this function returns false for single sites! Always use icw is_multisite()
 *
 * @return bool
 */
function rsssl_is_networkwide_active(){
	if ( !is_multisite() ) {
		return false;
	}
	if (!function_exists('is_plugin_active_for_network'))
		require_once(ABSPATH . '/wp-admin/includes/plugin.php');

	if ( is_plugin_active_for_network(rsssl_plugin) ) {
		return true;
	} else {
		return false;
	}
}
