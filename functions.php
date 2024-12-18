<?php
defined( 'ABSPATH' ) or die();
/**
 *  Only functions also required on front-end here
 */

/**
 * Get a Really Simple Security option by name
 *
 * @param string $name The name of the option to retrieve.
 * @param mixed  $default_value The default value to return if the option does not exist.
 *
 * @return mixed
 */

function rsssl_get_option( string $name, $default_value = false ) {
	$name = sanitize_title( $name );
	if ( is_multisite() && rsssl_is_networkwide_active() ) {
		$options = get_site_option( 'rsssl_options', [] );
	} else {
		$options = get_option( 'rsssl_options', [] );
	}

	//fallback, will be removed after 6.2
	//because we only check if the option is not saved in the new style, this if should normally never get executed.
	if (
		! isset( $options[ $name ] ) &&
		( 'ssl_enabled' === $name || 'redirect' === $name || 'mixed_content_fixer' === $name || 'dismiss_all_notices' === $name )
	) {
		$options = rsssl_get_legacy_option( $options, $name );
	}

	$value = $options[ $name ] ?? false;
	if ( false === $value && false !== $default_value ) {
		$value = $default_value;
	}

	if ( 1 === $value ) {
		$value = true;
	}

	return apply_filters( "rsssl_option_$name", $value, $name );
}

/**
 * Check if we should treat the plugin as networkwide or not.
 * Note that this function returns false for single sites! Always use icw is_multisite()
 *
 * @return bool
 */

function rsssl_is_networkwide_active() {
	if ( ! is_multisite() ) {
		return false;
	}
	if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
		require_once ABSPATH . '/wp-admin/includes/plugin.php';
	}

	if ( is_plugin_active_for_network( rsssl_plugin ) ) {
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
function rsssl_get_legacy_option( $options, string $name ): array {
	$old_options = is_multisite() ? get_site_option( 'rlrsssl_network_options' ) : get_option( 'rlrsssl_options' );
	$options     = [];

	if ( $old_options ) {
		if ( 'ssl_enabled' === $name && isset( $old_options['ssl_enabled'] ) ) {
			$options['ssl_enabled'] = $old_options['ssl_enabled'];
		} elseif ( 'dismiss_all_notices' === $name && isset( $old_options['dismiss_all_notices'] ) ) {
			$options['dismiss_all_notices'] = $old_options['dismiss_all_notices'];
		} elseif ( 'dismiss_all_notices' === $name && isset( $old_options['dismiss_all_notices'] ) ) {
			$options['dismiss_all_notices'] = $old_options['dismiss_all_notices'];
		} elseif ( 'mixed_content_fixer' === $name && isset( $old_options['autoreplace_insecure_links'] ) ) {
			$options['mixed_content_fixer'] = $old_options['autoreplace_insecure_links'];
		} elseif ( 'redirect' === $name ) {
			if ( isset( $old_options['htaccess_redirect'] ) && $old_options['htaccess_redirect'] ) {
				$options['redirect'] = 'htaccess';
			} elseif ( isset( $old_options['wp_redirect'] ) && $old_options['wp_redirect'] ) {
				$options['redirect'] = 'wp_redirect';
			}
		}
	}
	return $options;
}

function rsssl_check_if_email_essential_feature() {
	$essential_features = array(
		'limit_login_attempts' => rsssl_get_option( 'enable_limited_login_attempts' ) == 1,//phpcs:ignore
		'login_protection_enabled'       => rsssl_get_option( 'login_protection_enabled' ) == 1,//phpcs:ignore
	);

	// Check if the current feature is in the essential features array
	foreach ( $essential_features as $feature => $is_essential ) {
		if ( $is_essential ) {
			return true;
		}
	}

	return false;
}

/**
 * Retrieves the path to a template file.
 *
 * @param string $template The name of the template to retrieve.
 * @param string $path (Optional) The path to look for the template file. If not specified, the default path will be used.
 *
 * @return string The full path to the template file.
 * @throws \RuntimeException Throws a runtime exception if the template file cannot be found.
 */
function rsssl_get_template( string $template, string $path = '' ): string {
	// Define the path in the theme where templates can be overridden.
	$theme_template_path = get_stylesheet_directory() . '/really-simple-ssl-templates/' . $template;

	// Check if the theme has an override for the template.
	if ( file_exists( $theme_template_path ) ) {
		return $theme_template_path;
	}
	// If $path is not set, use the default path
	if ( $path === '' ) {
		$path = rsssl_path . 'templates/'; // Remember this only works in free version, for pro we need to add the $path parameter/argument
	} else {
		// Ensure the path ends with a slash
		$path = trailingslashit( $path );
	}

	// Full path to the template file
	$full_path = $path . $template;

	// Check if the template exists in the specified path.
	if ( ! file_exists( $full_path ) ) {
		throw new \RuntimeException( 'Template not found: ' . $full_path );
	}

	return $full_path;
}

/**
 * Loads a template file and includes it.
 *
 * @param string $template The name of the template to load.
 * @param array  $vars (Optional) An associative array of variables to make available in the template scope.
 * @param string $path (Optional) The path to look for the template file. If not specified, the default path will be used.
 *
 * @return void
 * @throws Exception Throws an exception if the template file cannot be found.
 */
function rsssl_load_template( string $template, array $vars = array(), string $path = '' ) {
	// Extract variables to be available in the template scope.
	if ( is_array( $vars ) ) {
		extract( $vars );
	}

	// Get the template file, checking for theme overrides.
	$template_file = rsssl_get_template( $template, $path );

	// Include the template file.
	include $template_file;
}

/**
 * Determines the path to WordPress configuration file (wp-config.php)
 *
 * This function attempts to locate the wp-config.php file in the following order:
 * 1. Checks for a filtered path via 'rsssl_wpconfig_path' filter
 * 2. Looks in the WordPress installation root directory (ABSPATH)
 * 3. Looks in the parent directory of the WordPress installation
 *
 * @return string The full path to wp-config.php if found, empty string otherwise
 *
 * @filter rsssl_wpconfig_path Allows modification of the wp-config.php path
 *
 * @example
 * // Get wp-config.php path
 * $config_path = rsssl_wpconfig_path();
 *
 * // Filter example
 * add_filter('rsssl_wpconfig_path', function($path) {
 *     return '/custom/path/to/wp-config.php';
 * });
 */
if ( ! function_exists( 'rsssl_wpconfig_path' ) ) {
	function rsssl_wpconfig_path(): string {
		// Allow the wp-config.php path to be overridden via a filter.
		$filtered_path = apply_filters( 'rsssl_wpconfig_path', '' );

		// If a filtered path is provided and valid, use it.
		if ( ! empty( $filtered_path ) && file_exists( $filtered_path ) ) {
			return $filtered_path;
		}

		// Default behavior to locate wp-config.php
		$location_of_wp_config = ABSPATH;
		if ( ! file_exists( ABSPATH . 'wp-config.php' ) && file_exists( dirname( ABSPATH ) . '/wp-config.php' ) ) {
			$location_of_wp_config = dirname( ABSPATH );
		}

		$location_of_wp_config = trailingslashit( $location_of_wp_config );
		$wpconfig_path         = $location_of_wp_config . 'wp-config.php';

		// Check if the file exists and return the path if valid.
		if ( file_exists( $wpconfig_path ) ) {
			return $wpconfig_path;
		}

		// Return an empty string if no valid wp-config.php path is found.
		return '';
	}
}
/**
 * @return void
 *
 * Set encryption keys
 */
if ( ! function_exists('rsssl_set_encryption_key')) {
	function rsssl_set_encryption_key(): void {

		// Return if key has been set
		if ( get_site_option( 'rsssl_encryption_keys_set' ) ) {
			return;
		}

		$wp_config_path = rsssl_wpconfig_path();

		// Check if we already have a key defined
		if ( defined( 'RSSSL_KEY' ) ) {
			return;
		}

		$key           = get_site_option( 'rsssl_main_key' );
		$new_generated = false;

		// If we don't have a key, generate one
		if ( ! $key ) {
			$new_generated = true;
			$key           = wp_generate_password( 64, false );
		}

		if ( is_writable( $wp_config_path ) ) {
			// Add the key to the wp-config file
			$rule         = "//Begin Really Simple Security key\n";
			$rule         .= "define('RSSSL_KEY', '" . $key . "');\n";
			$rule         .= "//END Really Simple Security key\n";
			$insert_after = '<?php';

			$contents = file_get_contents( $wp_config_path );
			$pos      = strpos( $contents, $insert_after );
			if ( false !== $pos && strpos( $contents, 'RSSSL_KEY' ) === false ) {
				$contents = substr_replace( $contents, $rule, $pos + 1 + strlen( $insert_after ), 0 );
				file_put_contents( $wp_config_path, $contents );
			}

			// If the wp-config was just set to writable, we can delete the key from the database now.
			delete_site_option( 'rsssl_main_key' );
		} elseif ( $new_generated ) {
			// If we can't write to the wp-config file, store the key in the database
			// When wp-config is set to writable, auto upgrade to constant
			update_site_option( 'rsssl_main_key', $key, false );
		}

		update_site_option( 'rsssl_encryption_keys_set', true );
	}
	rsssl_set_encryption_key();
}