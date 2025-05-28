<?php

namespace RSSSL\Security\WordPress\Two_Fa\Controllers;

use Exception;
use ReflectionClass;
use RSSSL\Security\WordPress\Two_Fa\Models\Rsssl_Request_Parameters;
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
            // We throw an error
            wp_die();
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

}