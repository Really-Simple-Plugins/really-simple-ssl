<?php
defined('ABSPATH') or die("you do not have access to this page!");

/**
 * Get a Really Simple SSL option by name
 *
 * @param string $name
 * @param mixed $default
 *
 * @return mixed
 */

function rsssl_get_option( $name, $default=false ){
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
	return isset($options[$name]) ? $options[$name]: sanitize_title($default);
}