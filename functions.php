<?php
defined( 'ABSPATH' ) or die();
/**
 *  Only functions also required on front-end here
 */

/**
 * Get a Really Simple SSL option by name
 *
 * @param string $name
 * @param mixed  $default_value
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
		'two_fa_enabled'       => rsssl_get_option( 'two_fa_enabled' ) == 1,//phpcs:ignore
	);

	// Check if the current feature is in the essential features array
	foreach ( $essential_features as $feature => $is_essential ) {
		if ( $is_essential ) {
			return true;
		}
	}

	return false;
}