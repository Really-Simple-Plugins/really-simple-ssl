<?php
defined('ABSPATH') or die();

/**
 * Conditionally we can decide to disable fields, add comments, and manipulate the value here
 * @param array $field
 * @param string $field_id
 *
 * @return array
 */
function rsssl_disable_fields($field, $field_id){

	if ( $field_id === 'wp_redirect' ) {
		if (is_multisite() && rsssl_get_network_option('wp_redirect') ) {
			$field['disabled'] = true;
			$field['value'] = true;
			$field['comment'] = __("This option is enabled on the network menu.", "really-simple-ssl");
		}
	}

	if ( $field_id === 'htaccess_redirect' ) {
		if ( !rsssl_get_option('htaccess_redirect') ){
			$field['comment'] = sprintf(
				__("Before you enable the htaccess redirect, make sure you know how to %sregain access%s to your site in case of a redirect loop.", "really-simple-ssl"),
				'<a href="https://really-simple-ssl.com/knowledge-base/remove-htaccess-redirect-site-lockout/" target="_blank">',
				'</a>'
			);
		}
		//networkwide is not shown, so this only applies to per site activated sites.
		if ( is_multisite() && rsssl_get_network_option('htaccess_redirect') ) {
			$field['disabled'] = true;
			$field['comment'] = __("This option is enabled on the network menu.", "really-simple-ssl");
		} else if ( rsssl_get_option('do_not_edit_htaccess') ) {
			//on multisite, the .htaccess do not edit option is not available
			$field['comment'] = __("If the setting 'stop editing the .htaccess file' is enabled, you can't change this setting.", "really-simple-ssl");
			$field['disabled'] = true;
		}
	}

	if ( $field_id === 'do_not_edit_htaccess' ) {
		if ( !rsssl_get_option('do_not_edit_htaccess') && !is_writable(RSSSL()->really_simple_ssl->htaccess_file() ) )  {
			$field['comment'] = sprintf(__(".htaccess is currently not %swritable%s.", "really-simple-ssl"), '<a target="_blank" href="https://really-simple-ssl.com/knowledge-base/htaccess-wp-config-files-not-writable/">', '</a>');
		}
	}

	if ( $field_id === 'do_not_edit_htaccess' ) {
		if ( is_multisite() && rsssl_get_network_option('dismiss_all_notices') ) {
			$field['disabled'] = true;
			$field['comment'] = __("This option is enabled on the network menu.", "really-simple-ssl");
		}
	}

	if ( $field_id === 'mixed_content_fixer' ) {
		if (is_multisite() && rsssl_get_network_option('mixed_content_fixer') ) {
			$field['disabled'] = true;
			$field['value'] = true;
			$field['comment'] = __("This option is enabled on the network menu.", "really-simple-ssl");
		}
	}


	return $field;
}
add_filter('rsssl_field', 'rsssl_disable_fields');

