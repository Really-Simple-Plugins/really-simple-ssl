<?php
defined( 'ABSPATH' ) or die();

/**
 * Front-end safe security helpers.
 *
 * These checks are shared between admin settings and login-page flows, so they
 * must not depend on admin-only bootstrap.
 */

if ( ! function_exists( 'rsssl_rest_api_accessible_for_logged_out_users' ) ) {
	/**
	 * Check if the REST API is accessible for logged-out users.
	 *
	 * Makes an actual HTTP request to the REST API to verify accessibility.
	 * Returns true only if the request succeeds with a 200 status code.
	 * Any other status or network error is considered inaccessible.
	 *
	 * Uses a short option-backed cache to avoid generating a loopback request to
	 * /wp-json/ on every settings request.
	 */
	function rsssl_rest_api_accessible_for_logged_out_users(): bool {
		$is_accessible_for_request = rsssl_rest_api_accessible_runtime_cache();
		if ( $is_accessible_for_request !== null ) {
			return $is_accessible_for_request;
		}

		$cache_key = rsssl_rest_api_accessible_cache_key();
		$cached    = get_option( $cache_key, false );
		if ( is_array( $cached ) && array_key_exists( 'accessible', $cached ) ) {
			$expires = isset( $cached['expires'] ) ? (int) $cached['expires'] : 0;
			if ( $expires > time() ) {
				$is_accessible_for_request = (bool) $cached['accessible'];
				rsssl_rest_api_accessible_runtime_cache( $is_accessible_for_request );
				return $is_accessible_for_request;
			}

			delete_option( $cache_key );
		}

		// sslverify disabled: loopback requests to the site itself often fail SSL
		// verification due to server configuration issues even when browsers work.
		$response = wp_remote_get(
			rest_url(),
			[
				'sslverify' => false,
			]
		);

		$is_accessible_for_request = ! is_wp_error( $response )
			&& wp_remote_retrieve_response_code( $response ) === 200;
		rsssl_rest_api_accessible_runtime_cache( $is_accessible_for_request );

		update_option(
			$cache_key,
			[
				'accessible' => $is_accessible_for_request,
				'expires'    => time() + ( 5 * MINUTE_IN_SECONDS ),
			],
			false
		);

		return $is_accessible_for_request;
	}
}

if ( ! function_exists( 'rsssl_rest_api_accessible_runtime_cache' ) ) {
	/**
	 * Get, set, or clear the in-request REST API accessibility cache.
	 *
	 * @param bool|null $value
	 * @param bool      $clear
	 *
	 * @return bool|null
	 */
	function rsssl_rest_api_accessible_runtime_cache( ?bool $value = null, bool $clear = false ): ?bool {
		static $is_accessible_for_request = null;

		if ( $clear ) {
			$is_accessible_for_request = null;
			return null;
		}

		if ( $value !== null ) {
			$is_accessible_for_request = $value;
		}

		return $is_accessible_for_request;
	}
}

if ( ! function_exists( 'rsssl_rest_api_accessible_cache_key' ) ) {
	/**
	 * Return the option key for the REST API accessibility probe.
	 */
	function rsssl_rest_api_accessible_cache_key(): string {
		return 'rsssl_rest_api_access_' . md5( rest_url() );
	}
}

if ( ! function_exists( 'rsssl_clear_rest_api_accessible_cache' ) ) {
	/**
	 * Clear the cached REST API accessibility probe.
	 */
	function rsssl_clear_rest_api_accessible_cache(): void {
		rsssl_rest_api_accessible_runtime_cache( null, true );
		delete_option( rsssl_rest_api_accessible_cache_key() );
	}
}

if ( ! function_exists( 'rsssl_get_login_protection_enable_block_reason' ) ) {
	/**
	 * Return the blocker message when two-factor authentication cannot be enabled.
	 */
	function rsssl_get_login_protection_enable_block_reason(): string {
		if ( ! rsssl_rest_api_accessible_for_logged_out_users() ) {
			return __( "Two-Factor Authentication requires the REST API to be accessible for logged-out users. Please ensure the REST API is not blocked.", "really-simple-ssl" );
		}

		return '';
	}
}

if ( ! function_exists( 'rsssl_get_passkey_login_enable_block_reason' ) ) {
	/**
	 * Return the blocker message when passkey login cannot be enabled.
	 */
	function rsssl_get_passkey_login_enable_block_reason(): string {
		if ( ! rsssl_get_option( 'ssl_enabled' ) || ! rsssl_get_option( 'site_has_ssl' ) ) {
			return __( "Passkeys require HTTPS to function. Please enable SSL on your site first.", "really-simple-ssl" );
		}

		if ( ! rsssl_rest_api_accessible_for_logged_out_users() ) {
			return __( "Passkey login requires the REST API to be accessible for logged-out users. Please ensure the REST API is not blocked.", "really-simple-ssl" );
		}

		return '';
	}
}
