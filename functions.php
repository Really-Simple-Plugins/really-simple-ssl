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

	//fallback, will be removed after 6.2
	//because we only check if the option is not saved in the new style, this if should normally never get executed.
	if (
		!isset($options[$name]) &&
		($name === 'ssl_enabled' || $name === 'redirect' || $name === "mixed_content_fixer" || $name === 'dismiss_all_notices' )
	) {
		$options = rsssl_get_legacy_option($options, $name);
	}

	$value = $options[ $name ] ?? false;
	if ( $value===false && $default!==false ) {
		$value = $default;
	}

	if ($value===1) {
		$value = true;
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
	if ( !function_exists('is_plugin_active_for_network') ) {
		require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
	}

	if ( is_plugin_active_for_network(rsssl_plugin) ) {
		return true;
	}

	return false;
}

/**
 * if the option is does not exist in our new array, check if it's available in the old option. If so, use that one
 * @deprecated to be used until 6.2, as fallback for failed upgrades in some specific edge case situations
 * @param array|bool $options
 * @param string $name
 *
 * @return array
 */
function rsssl_get_legacy_option( $options, string $name): array {
	$old_options = is_multisite() ? get_site_option('rlrsssl_network_options') : get_option( 'rlrsssl_options' );
	if ( $old_options ) {
		if ( $name === 'ssl_enabled' && isset( $old_options['ssl_enabled']) ) {
			$options['ssl_enabled'] = $old_options['ssl_enabled'];
		} else if ( $name === 'dismiss_all_notices' && isset( $old_options['dismiss_all_notices']) ) {
			$options['dismiss_all_notices'] = $old_options['dismiss_all_notices'];
		} else if ( $name === 'dismiss_all_notices' && isset( $old_options['dismiss_all_notices']) ) {
			$options['dismiss_all_notices'] = $old_options['dismiss_all_notices'];
		} else if ( $name === 'mixed_content_fixer' && isset($old_options['autoreplace_insecure_links']) ) {
			$options['mixed_content_fixer'] = $old_options['autoreplace_insecure_links'];
		} else if ( $name === 'redirect' ){
			if ( isset($old_options['htaccess_redirect']) && $old_options['htaccess_redirect'] ) {
				$options['redirect'] = 'htaccess';
			} else if (isset($old_options['wp_redirect']) && $old_options['wp_redirect']) {
				$options['redirect'] = 'wp_redirect';
			}
		}
	}
	return $options;
}

function check_if_email_essential_feature() {
	$essential_features = array(
		'limit_login_attempts' => rsssl_get_option('enable_limited_login_attempts') == 1,
		'two_fa_enabled' => rsssl_get_option('two_fa_enabled') == 1,
		'two_fa_enabled_totp' => rsssl_get_option('two_fa_enabled_totp') == 1
	);

	// Check if the current feature is in the essential features array
	foreach( $essential_features as $feature => $is_essential ) {
		if ( $is_essential ) {
			return true;
		}
	}

	return false;
}

/**
 * @param $response
 * @param $user
 * @param $request
 *
 * @return mixed
 *
 * Add user roles to /users endpoint
 */
function add_user_role_to_api_response( $response, $user, $request ) {
	$headers = $request->get_headers();

	if (isset($headers['referer']) && strpos($headers['referer'][0], 'really-simple-security') !== false) {
		$data = $response->get_data();
		$data['roles'] = $user->roles;
		$response->set_data($data);
	}

	return $response;
}
add_filter('rest_prepare_user', 'add_user_role_to_api_response', 10, 3);

/**
 * Adds a template.
 *
 * @param  string  $template
 * @param  string  $path
 *
 * @return string
 * @throws Exception
 */
function rsssl_get_template( string $template, string $path = '' ): string {
	// Define the path in the theme where templates can be overridden.
	$theme_template_path = get_stylesheet_directory() . '/really-simple-ssl-templates/' . $template;

	// Check if the theme has an override for the template.
	if (file_exists($theme_template_path)) {
		return $theme_template_path;
	}

	// If $path is not set, use the default path
	if ($path === '') {
		$path = rsssl_path . 'templates/'; //Remember this only works in free version, for pro we need to add the $path parameter/argument
	} else {
		// Ensure the path ends with a slash
		$path = trailingslashit($path);
	}

	// Full path to the template file
	$full_path = $path . $template;

	// Check if the template exists in the specified path.
	if (!file_exists($full_path)) {
		throw new \RuntimeException('Template not found: ' . $full_path);
	}

	return $full_path;

}

/**
 * Load a template file.
 *
 * @param  string  $template
 * @param  array  $vars
 * @param  string  $path
 *
 * @throws Exception
 */
function rsssl_load_template( string $template, array $vars = [], string $path = '' ) {
	// Extract variables to be available in the template scope
	if (is_array($vars)) {
		extract($vars);
	}

	// Get the template file, checking for theme overrides
	$template_file = rsssl_get_template($template, $path);

	// Include the template file
	include $template_file;
}

/**
 * Add rsssl_two_fa_status usermeta field
 *
 * @return void
 */
function rsssl_register_user_meta() {
	register_meta('user', 'rsssl_two_fa_status', [
		'show_in_rest' => true,
		'single' => true,
		'type' => 'string',
		'description' => 'The method of two-factor authentication for the user.',
		'default' => 'false',
		'auth_callback' => function() {
			return rsssl_user_can_manage();
		},
	]);
}

add_action( 'init' , 'rsssl_register_user_meta' );

/**
 * Minifies the given JS string or file to be added inline as a script tag
 *
 * @param $input
 *
 * @return string
 */
function rsssl_minify_js($input): string {
	if(trim($input) === "") return $input;
	// Remove comments, whitespace, newlines, etc.
	$input = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $input);

	return str_replace( array( ': ', "\r\n", "\r", "\n", "\t", '  ', '    ', '     ' ),
		array( ':', '', '', '', '', '', '', '' ), $input );
}