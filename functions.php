<?php
defined('ABSPATH') or die("you do not have access to this page!");

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
 * @return bool
 */

function rsssl_get_option( $name, $default=false ) {
	$name = sanitize_title($name);
	$options = get_option( 'rsssl_options', array() );
	$value = isset($options[$name]) ? $options[$name]: sanitize_title($default);
	return apply_filters("rsssl_option_$name", $value, $name);
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