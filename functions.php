<?php
defined('ABSPATH') or die("you do not have access to this page!");
/**
 *  Only functions also required on front-end here
 */

/**
 * Check if the server side conditions apply
 *
 * @param array $conditions
 *
 * @return bool
 */

function rsssl_conditions_apply( $conditions ){
	$defaults = ['relation' => 'AND'];
	$conditions = wp_parse_args($conditions, $defaults);
	$relation = $conditions['relation'] === 'AND' ? 'AND' : 'OR';
	unset($conditions['relation']);
	$condition_applies = true;
	foreach ( $conditions as $condition => $condition_value ) {
		$invert = substr($condition, 1)==='!';
		$condition = ltrim($condition, '!');

		if ( is_array($condition_value)) {
			$this_condition_applies = rsssl_conditions_apply($condition_value);
		} else {
			//check if it's a function
			if (substr($condition, -2) === '()'){
				$func = $condition;
				if ( preg_match( '/(.*)\(\)\-\>(.*)->(.*)/i', $func, $matches)) {
					$base = $matches[1];
					$class = $matches[2];
					$func = $matches[3];
					$func = str_replace('()', '', $func);
					$this_condition_applies = call_user_func( array( $base()->{$class}, $func ) ) === $condition_value ;
				} else {
					$func = str_replace('()', '', $func);
					$this_condition_applies = $func() === $condition_value;
				}
			} else {
				$var = $condition;
				if ( preg_match( '/(.*)\(\)\-\>(.*)->(.*)/i', $var, $matches)) {
					$base = $matches[1];
					$class = $matches[2];
					$var = $matches[3];
					$this_condition_applies = $base()->{$class}->_get($var) === $condition_value ;
				} else {
					$this_condition_applies = $var === $condition_value;
				}
			}

			if ( $invert ){
				$this_condition_applies = !$this_condition_applies;
			}

		}

		if ($relation === 'AND') {
			$condition_applies = $condition_applies && $this_condition_applies;
		} else {
			$condition_applies = $condition_applies || $this_condition_applies;
		}
	}

	return $condition_applies;
}

/**
 * Get a Really Simple SSL option by name
 *
 * @param string $name
 * @param mixed $default
 *
 * @return mixed
 */

function rsssl_get_option( $name, $default=false ) {
	$name = sanitize_title($name);
	if ( rsssl_is_networkwide_active() ) {
		$options = get_site_option( 'rsssl_options', array() );
	} else {
		$options = get_option( 'rsssl_options', array() );
	}
//	if ( $name === 'permissions_policy') {
//		$options[$name] = false;
//	}

	if ( !isset($options[$name]) ) {
		$value = false;
	} else {
		$value = $options[$name];
	}

	if ( $value===false && $default!==false ) {
		$value = $default;
	}

	return apply_filters("rsssl_option_$name", $value, $name);
}

/**
 * Check if we should treat the plugin as networkwide or not.
 *
 * @return bool
 */
function rsssl_is_networkwide_active(){

	if (!function_exists('is_plugin_active_for_network'))
		require_once(ABSPATH . '/wp-admin/includes/plugin.php');

	if (is_plugin_active_for_network(rsssl_plugin)) {
		return true;
	} else {
		return false;
	}
}