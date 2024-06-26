<?php
defined( 'ABSPATH' ) or die();
foreach ( glob( rsssl_path . 'settings/config/fields/*.php' ) as $file ) {
	include $file;
}
function rsssl_fields( $load_values = true ) {
	if ( ! rsssl_user_can_manage() ) {
		return [];
	}

	$fields = apply_filters( 'rsssl_fields', [] );

	if ( is_multisite() && rsssl_is_networkwide_active() ) {
		$stored_options = get_site_option( 'rsssl_options', [] );
	} else {
		$stored_options = get_option( 'rsssl_options', [] );
	}

	foreach ( $fields as $key => $field ) {
		$field = wp_parse_args( $field, [ 'default' => '', 'id' => false, 'visible' => true, 'disabled' => false, 'recommended' => false ] );
		//handle server side conditions
		//but not if outside our settings pages
		if ( rsssl_is_logged_in_rest() && isset( $field['server_conditions'] ) ) {
			if ( ! rsssl_conditions_apply( $field['server_conditions'] ) ) {
				unset( $fields[ $key ] );
				continue;
			}
		}
		if ( $load_values ) {
			$value          = rsssl_sanitize_field( rsssl_get_option( $field['id'], $field['default'] ), $field['type'], $field['id'] );
			$field['never_saved'] = !array_key_exists( $field['id'], $stored_options );
			$field['value'] = apply_filters( 'rsssl_field_value_' . $field['id'], $value, $field );
			$fields[ $key ] = apply_filters( 'rsssl_field', $field, $field['id'] );
		}
	}

	$fields = apply_filters( 'rsssl_fields_values', $fields );
	foreach ( $fields as $key => $field ) {
		if (isset($field['help']['url'])) {
			$fields[ $key ]['help']['url'] = rsssl_link( $field['help']['url'], 'instructions', $field['id'] );
		}
	}
	return array_values( $fields );
}