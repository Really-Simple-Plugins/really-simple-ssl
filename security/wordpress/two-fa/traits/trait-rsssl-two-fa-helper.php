<?php
/**
 * A helper trait for sanitizing status and method values.
 *
 * @package really-simple-ssl
 */

namespace RSSSL\Security\WordPress\Two_Fa\Traits;

use FG\ASN1\Universal\Boolean;
use RSSSL\Security\WordPress\Two_Fa\Providers\Rsssl_Provider_Loader;
use RSSSL\Security\WordPress\Two_Fa\Providers\Rsssl_Two_Factor_Provider;
use RSSSL\Security\WordPress\Two_Fa\Rsssl_Request_Parameters;
use RSSSL\Security\WordPress\Two_Fa\Rsssl_Two_Fa_Authentication;
use RSSSL\Security\WordPress\Two_Fa\Rsssl_Two_Fa_Status;
use WP_REST_Request;
use WP_REST_Response;

/**
 * A helper trait for sanitizing status and method values.
 */
trait Rsssl_Two_Fa_Helper {
	/**
	 * Sanitize the given status.
	 *
	 * @param  string $status  The status to sanitize.
	 *
	 * @return string The sanitized status.
	 */
	private static function sanitize_status( string $status ): string {
		$statuses_available = Rsssl_Two_Fa_Status::STATUSES;

		if ( empty( $status ) ) {
			return 'open';
		}
		// Check if the $status is in the array of available statuses.
		if ( ! in_array( $status, $statuses_available, true ) ) {
			// if not, set it to 'disabled'.
			$status = 'disabled';
		}

		return sanitize_text_field( $status );
	}

	/**
	 * Sanitize a given method.
	 *
	 * @param  string $method  The method to sanitize.
	 *
	 * @return string  The sanitized method.
	 */
	private static function sanitize_method( string $two_fa_provider ): string {
        $loader = Rsssl_Provider_Loader::get_loader();
		$two_fa_providers_available = $loader::TWO_FA_PROVIDERS;
		// Check if the $method is in the array of available methods.
		if ( ! in_array( $two_fa_provider, $two_fa_providers_available, true ) ) {
			// if not, set it to 'disabled'.
			$two_fa_provider = 'disabled';
		}

		return sanitize_text_field( $two_fa_provider );
	}

    /**
     * Checks if the requested namespace matches our specific namespace and bypasses authentication.
     *
     * @param WP_REST_Request $request The REST request object.
     */
    private function check_custom_validation( WP_REST_Request $request ): bool {
        // first check if the $-REQUEST['rest_route'] is set.
        $params = new Rsssl_Request_Parameters( $request );
        if ( ! isset( $params->login_nonce ) ) {
            return false;
        }
        return Rsssl_Two_Fa_Authentication::verify_login_nonce( $params->user_id, $params->login_nonce );
    }

    /**
     * Verifies a login nonce, gets user by the user id, and returns an error response if any steps fail.
     *
     * @param int    $user_id The user ID.
     * @param string $login_nonce The login nonce.
     *
     * @return bool
     */
    private function verify_hashed_user_id( int $user_id, string $login_nonce ): bool {
	    return Rsssl_Two_Fa_Authentication::verify_login_nonce( $user_id, $login_nonce );
    }

    /**
     * Sets the authentication cookie and returns a success response.
     *
     * @param int    $user_id The user ID.
     * @param string $redirect_to The redirect URL.
     *
     * @return WP_REST_Response
     */
    public function authenticate_and_redirect( int $user_id, string $redirect_to = '' ): WP_REST_Response {
        // Okay checked the provider now authenticate the user.
        wp_set_auth_cookie( $user_id, true );
        // Finally redirect the user to the redirect_to page or to the home page if the redirect_to is not set.
        $redirect_to = $redirect_to ?: home_url();
        return new WP_REST_Response( array( 'redirect_to' => $redirect_to ), 200 );
    }

    /**
     * Sets the active provider for a user.
     *
     * This function loops through all available providers and sets the status of each provider.
     * The provider that matches the allowed method is set to 'active', while all other providers are set to 'disabled'.
     *
     * @param int $user_id The ID of the user.
     * @param string $allowed_method The method that is allowed and should be set to 'active'.
     * @return void
     */
    public static function set_active_provider(int $user_id, string $allowed_method): void
    {
        $user = get_userdata($user_id);
        $providers = Rsssl_Provider_Loader::get_loader()::get_enabled_providers_for_user($user);
        foreach ($providers as $provider) {
            /** @var Rsssl_Two_Factor_Provider $provider */
            if ($provider::METHOD !== $allowed_method) {
                $provider::reset_meta_data($user_id);
                $provider::set_user_status($user_id, 'disabled');
            } else {
                $provider::set_user_status($user_id, 'active');
            }
        }
    }

    /**
     * Sanitizes a token.
     *
     * @param string $token The token to sanitize.
     * @param int $length The expected length of the token. Default is 0.
     *
     * @return string|false The sanitized token, or false if the length is invalid.
     */
    public static function sanitize_token(string $token, int $length = 0 ) {
        $code = wp_unslash( $token );
        $code = preg_replace( '/\s+/', '', $code );

        // Maybe validate the length.
        if ( $length && strlen( $code ) !== $length ) {
            return false;
        }

        return (string) $code;
    }
}
