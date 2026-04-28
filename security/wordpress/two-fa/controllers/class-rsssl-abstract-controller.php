<?php

namespace RSSSL\Security\WordPress\Two_Fa\Controllers;

use Exception;
use ReflectionClass;
use RSSSL\Security\WordPress\Two_Fa\Models\Rsssl_Request_Parameters;
use RSSSL\Security\WordPress\Two_Fa\Providers\Rsssl_Provider_Loader;
use RSSSL\Security\WordPress\Two_Fa\Rsssl_Two_Fa_Authentication;
use RSSSL\Security\WordPress\Two_Fa\Traits\Rsssl_Args_Builder;
use RSSSL\Security\WordPress\Two_Fa\Traits\Rsssl_Two_Fa_Helper;
use WP_REST_Request;
use WP_User;

abstract class Rsssl_Abstract_Controller
{
    use Rsssl_Args_Builder;
	use Rsssl_Two_Fa_Helper;

    /**
     * The default HTTP method for the routes.
     * Child classes can override this constant if necessary.
     */
    protected const METHOD = 'POST';

    /**
     * The base route for all API endpoints.
     * Child classes should specify the route for their own context.
     */
    protected const FEATURE_ROUTE = '/two-fa';

    /**
     * The namespace for the API routes.
     * This will be dynamically set in the constructor.
     */
    protected string $namespace;


    /**
     * Constructor to set the namespace and initialize the API routes.
     * Child classes should pass their own namespace and version.
     *
     * @param string $namespace The base namespace for the API.
     * @param string $version The version of the API. E.g., 'v1'.
     */
    public function __construct(string $namespace, string $version, string $featureVersion)
    {
        $this->namespace = strtolower($namespace) . '/' .strtolower( $version ) . self::FEATURE_ROUTE . '/' . strtolower( $featureVersion );
	    $reflect = new ReflectionClass($this);

	    if (!$reflect->isFinal()) {
		    wp_die('Subclasses of Rsssl_Abstract_Controller must be declared as final.');
	    }
    }

    /**
     * Abstract method to register API routes.
     * Must be implemented by subclasses.
     *
     * @return void
     */
    abstract public function register_api_routes(): void;

	/**
	 * Registers a REST API route.
	 *
	 * @param string $namespace The namespace for the route.
	 * @param string $method The HTTP method for the route (e.g., 'POST', 'GET').
	 * @param string $route The route endpoint.
	 * @param callable $callback The callback function to handle the request.
	 * @param callable|null $permission_callback The permission callback function or true to allow all requests.
	 * @param array $args Optional. The arguments for the route.
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function route(
		string $namespace,
		string $method,
		string $route,
		callable $callback,
		?callable $permission_callback = null,
		array $args = array()
	): void {
		if ($permission_callback === null) {
			$permission_callback = array($this, 'permission_check');
		}

		register_rest_route($namespace, $route, array(
			'methods' => $method,
			'permission_callback' => $permission_callback,
			'callback' => $callback,
			'args' => $args,
		));
	}

	/**
     * Checks if the user is logged in and has the correct nonce.
     *
     *
     * @return bool
     */
    public function permission_check(WP_REST_Request $request):bool
	{
		$parameters = new Rsssl_Request_Parameters( $request );
		return $this->verify_hashed_user_id( $parameters->user_id, $parameters->login_nonce );
	}

    /**
     * Verifies a login nonce, gets user by the user id, and returns an error response if any steps fail.
     *
     * @throws Exception
     */
    public function check_login_and_get_user( int $user_id, string $login_nonce ): WP_User {
        if ( ! Rsssl_Two_Fa_Authentication::verify_login_nonce( $user_id, $login_nonce ) ) {
            throw new Exception( __( 'Invalid authentication request.', 'really-simple-ssl' ) );
        }
        /**
         * Get the user by the user ID.
         *
         * @var WP_User $user
         */
        $user = get_user_by('id', $user_id);
        if (!$user) {
            throw new Exception('User not found');
        }

        return $user;
    }

	/**
	 * Check if the user already has a configured two-factor provider.
	 *
	 * Users with an active provider must complete that provider's challenge before
	 * the login can be completed or two-factor settings can be changed.
	 * A login nonce only proves the password step was passed.
	 *
	 * @param WP_User $user The user object.
	 *
	 * @return bool
	 */
	protected function has_configured_provider( WP_User $user ): bool {
		$loader = Rsssl_Provider_Loader::get_loader();
		$login_protection_enabled = (bool) rsssl_get_option( 'login_protection_enabled' );

		foreach ( $loader::available_providers() as $method => $provider ) {
			if ( ! $this->is_provider_available_for_current_login_mode( $method, $login_protection_enabled ) ) {
				continue;
			}

			if ( ! $provider::is_enabled( $user ) ) {
				continue;
			}

			if ( $provider::is_configured( $user ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if the user has a configured provider other than the allowed method.
	 *
	 * Used when the allowed method still has its own challenge to verify. For
	 * example, email may finish with an email token, but it may not replace TOTP.
	 *
	 * @param WP_User $user The user object.
	 * @param string  $allowed_method The method that may complete the current challenge.
	 *
	 * @return bool
	 */
	protected function has_configured_provider_other_than( WP_User $user, string $allowed_method ): bool {
		$loader = Rsssl_Provider_Loader::get_loader();
		$login_protection_enabled = (bool) rsssl_get_option( 'login_protection_enabled' );

		foreach ( $loader::available_providers() as $method => $provider ) {
			if ( $allowed_method === $method ) {
				continue;
			}

			if ( ! $this->is_provider_available_for_current_login_mode( $method, $login_protection_enabled ) ) {
				continue;
			}

			if ( ! $provider::is_enabled( $user ) ) {
				continue;
			}

			if ( $provider::is_configured( $user ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if the provider belongs to the active login protection mode.
	 *
	 * @param string $method The provider method.
	 * @param bool   $login_protection_enabled Whether full login protection is enabled.
	 *
	 * @return bool
	 */
	protected function is_provider_available_for_current_login_mode( string $method, bool $login_protection_enabled ): bool {
		return $login_protection_enabled || 'passkey' === $method;
	}

}
