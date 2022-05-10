<?php
defined('ABSPATH') or die("you do not have access to this page!");

/**
 * Get a Really Simple SSL option by name
 *
 * @param string $name
 * @param mixed $default
 *
 * @return bool
 */

function rsssl_get_option( $name, $default=false ) {
	$name = sanitize_title($name);
	$options = get_option( 'rsssl_options', array() );
	return isset($options[$name]) ? $options[$name]: sanitize_title($default);
}

/**
 * Get a Really Simple SSL network option by name
 * @param string $name
 * @param mixed $default
 *
 * @return mixed
 */

function rsssl_get_network_option( $name, $default=false ){
	$name = sanitize_title($name);
	$options = get_site_option( 'rsssl_options', array() );

	if ( isset($options[$name] ) && $options[$name] === 1 ) {
		return true;
	}

	return false;
}

/**
 * @param $name
 * @param $value
 * @return void
 *
 * Update an RSSSL option. Used to sync with WordPress options
 */
function rsssl_update_option( $name, $value ) {
    $name = sanitize_title($name);
    $value = sanitize_title($value);
    $options = get_site_option( 'rsssl_options', array() );

    $options[$name] = $value;
    update_site_option('rsssl_options', $options);
}