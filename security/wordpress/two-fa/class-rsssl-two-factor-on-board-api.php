<?php
/**
 * Handles the API routes for the two-factor authentication onboarding process.
 * This class is responsible for handling the API routes for the two-factor authentication onboarding process.
 * It registers the routes and handles the requests.
 *
 * @package REALLY_SIMPLE_SSL
 * @subpackage Security\WordPress\Two_Fa
 */

namespace RSSSL\Security\WordPress\Two_Fa;

use Exception;
use WP_REST_Request;
use WP_REST_Response;
use WP_User;

/**
 * Registers API routes for the application.
 * This class is responsible for registering the API routes for the two-factor authentication onboarding process.
 * It registers the routes and handles the requests.
 *
 * @package REALLY_SIMPLE_SSL
 * @subpackage Security\WordPress\Two_Fa
 */
class Rsssl_Two_Factor_On_Board_Api
{
	/**
	 * Initializes the object and registers API routes.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_api_routes' ) );
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
	 * @return WP_User|WP_REST_Response
	 */
	private function check_login_and_get_user( int $user_id, string $login_nonce ) {
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

	/**
	 * Sets the authentication cookie and returns a success response.
	 *
	 * @param int    $user_id The user ID.
	 * @param string $redirect_to The redirect URL.
	 *
	 * @return WP_REST_Response
	 */
	private function authenticate_and_redirect( int $user_id, string $redirect_to = '' ): WP_REST_Response {
		// Okay checked the provider now authenticate the user.
		wp_set_auth_cookie( $user_id, true );
		// Finally redirect the user to the redirect_to page or to the home page if the redirect_to is not set.
		$redirect_to = $redirect_to ?: home_url();
		return new WP_REST_Response( array( 'redirect_to' => $redirect_to ), 200 );
	}

    /**
     * Starts the process of email validation for a user.
     *
     * @param int $user_id The ID of the user for whom the email validation process needs to be started.
     * @param string $redirect_to The URL to redirect the user after the email validation process. Default is an empty string.
     *
     * @return WP_REST_Response The REST response object.
     */
    private function start_email_validation(int $user_id, string $redirect_to = '', $profile = false): WP_REST_Response
    {
        $redirect_to = $redirect_to ?: home_url();
        $user = get_user_by('id', $user_id);
        // Sending the email with the code.
        Rsssl_Two_Factor_Email::get_instance()->generate_and_email_token($user, $profile);
        $token = get_user_meta( $user_id, Rsssl_Two_Factor_Email::RSSSL_TOKEN_META_KEY, true );
        if ( $redirect_to === 'profile') {
            return new WP_REST_Response( array( 'token' => $token,  'validation_action' => 'validate_email_setup' ), 200 );
        }
        return new WP_REST_Response( array( 'token' => $token, 'redirect_to' => $redirect_to, 'validation_action' => 'validate_email_setup' ), 200 );
    }

	/**
	 * Sets the user provider as email and redirects the user to the specified page.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_REST_Response The REST response object if user is not logged in or provider is invalid.
	 */
	public function set_as_email( WP_REST_Request $request ): WP_REST_Response {
		$parameters = new Rsssl_Request_Parameters($request);
		try {
			$this->check_login_and_get_user($parameters->user_id, $parameters->login_nonce);
		} catch (Exception $e) {
			return new WP_REST_Response(['error' => $e->getMessage()], 403);
		}
		if ('email' !== $parameters->provider) {
			return new WP_REST_Response(['error' => 'Invalid provider'], 401);
		}

		return $this->start_email_validation($parameters->user_id, $parameters->redirect_to, $parameters->profile);
	}

    /**
     * Sets the profile email for a user.
     *
     * @param WP_REST_Request $request The REST request object.
     *
     * @return WP_REST_Response The REST response object.
     */
    public function set_profile_email(WP_REST_Request $request ): WP_REST_Response {
	    $parameters = new Rsssl_Request_Parameters($request);
	    try {
		    $this->check_login_and_get_user($parameters->user_id, $parameters->login_nonce);
	    } catch (Exception $e) {
		    return new WP_REST_Response(['error' => $e->getMessage()], 403);
	    }
	    if ('email' !== $parameters->provider) {
		    return new WP_REST_Response(['error' => 'Invalid provider'], 401);
	    }

	    return $this->start_email_validation($parameters->user_id, $parameters->redirect_to, $parameters->profile);
    }

    /**
     * Validates the email setup for a user.
     *
     * @param WP_REST_Request $request The REST request object.
     *
     * @return WP_REST_Response The REST response object.
     */
    public function validate_email_setup(WP_REST_Request $request ): WP_REST_Response {
	    $parameters = new Rsssl_Request_Parameters($request);

	    if ('email' !== $parameters->provider) {
		    return new WP_REST_Response(['error' => 'Invalid provider'], 401);
	    }

	    if (!Rsssl_Two_Factor_Email::get_instance()->validate_token($parameters->user_id, self::sanitize_token($parameters->token))) {
		    Rsssl_Two_Factor_Email::set_user_status($parameters->user_id, 'open');
		    Rsssl_Two_Factor_Totp::set_user_status($parameters->user_id, 'open');
		    wp_logout();
		    return new WP_REST_Response(['error' => __('Code was invalid, try "Resend Code"', 'really-simple.ssl-pro')], 401);
	    }

	    Rsssl_Two_Factor_Email::set_user_status($parameters->user_id, 'active');
	    Rsssl_Two_Factor_Totp::set_user_status($parameters->user_id, 'disabled');
	    self::set_other_providers_inactive($parameters->user_id, 'email');

	    return $this->authenticate_and_redirect($parameters->user_id, $parameters->redirect_to);
    }

    /**
     * Resends the email code for a user.
     *
     * @param WP_REST_Request $request The REST request object.
     *
     * @return WP_REST_Response The REST response object.
     */
    public function resend_email_code( WP_REST_Request $request ): WP_REST_Response {
       $parameters = new Rsssl_Request_Parameters( $request );
        Rsssl_Two_Factor_Email::get_instance()->generate_and_email_token($parameters->user, $parameters->profile);
        return new WP_REST_Response( array( 'message' => __('A verification code has been sent to the email address associated with your account to verify functionality.', 'really-simple.ssl-pro') ), 200 );
    }

	/**
	 * Verifies the 2FA code for TOTP.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_REST_Response The REST response object.
	 */
	public function verify_2fa_code_totp( WP_REST_Request $request ): WP_REST_Response {
		$parameters = new Rsssl_Request_Parameters( $request );
		$user       = $this->check_login_and_get_user( $parameters->user_id, $parameters->login_nonce );
		// Check if the provider.
		if ( 'totp' !== $parameters->provider ) {
			$response = new WP_REST_Response( array( 'error' => __('Invalid provider', 'really-simple-ssl') ), 400 );
		}

        //This is an extra check so someone who thinks to use backup codes can't use them.
        $code_backup = Rsssl_Two_Factor_Backup_Codes::sanitize_code_from_request( 'authcode', 8 );
        if ( $code_backup && Rsssl_Two_Factor_Backup_Codes::validate_code( $user, $code_backup, false ) ) {
            $error_message = __('Invalid Two Factor Authentication code.', 'really-simple-ssl');
            return new WP_REST_Response( array( 'error' => $error_message ), 400 );
        }

		if ( Rsssl_Two_Factor_Totp::setup_totp( $user, $parameters->key, $parameters->code ) ) {
			Rsssl_Two_Factor_Totp::set_user_status( $user->ID, 'active' );
			Rsssl_Two_Factor_Email::set_user_status( $user->ID, 'disabled' );
			// Mark all other statuses as inactive.
			self::set_other_providers_inactive( $user->ID, 'totp' );
			// Finally we redirect the user to the redirect_to page.
			return $this->authenticate_and_redirect( $parameters->user_id, $parameters->redirect_to );
		}

        // We get the error message from the setup_totp function.
        $error_message = get_transient( 'rsssl_error_message_' . $user->ID );
        // We delete the transient.
        delete_transient( 'rsssl_error_message_' . $user->ID );
        return  new WP_REST_Response( array( 'error' => $error_message ), 400 );
	}

	/**
	 * Disables two-factor authentication for the user.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_REST_Response The REST response object.
	 */
	public function disable_two_fa_for_user( WP_REST_Request $request ): WP_REST_Response {
		$parameters = new Rsssl_Request_Parameters($request);
		try {
			$user = $this->check_login_and_get_user($parameters->user_id, $parameters->login_nonce);
		} catch (Exception $e) {
			return new WP_REST_Response(['error' => $e->getMessage()], 403);
		}

		$user_available_providers = Rsssl_Provider_Loader::get_providers();
		foreach ($user_available_providers as $provider) {
			$provider::set_user_status($user->ID, 'disabled');
		}

		return $this->authenticate_and_redirect($parameters->user_id, $parameters->redirect_to);
	}

	/**
	 * Skips the onboarding process for the user.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_REST_Response The REST response object.
	 */
	public function skip_onboarding( WP_REST_Request $request ): WP_REST_Response {
		$parameters = new Rsssl_Request_Parameters( $request );
		// As a double we check the user_id with the login nonce.
		try {
			$this->check_login_and_get_user($parameters->user_id, $parameters->login_nonce);
		} catch (Exception $e) {
			return new WP_REST_Response(['error' => $e->getMessage()], 403);
		}
		return $this->authenticate_and_redirect( $parameters->user_id, $parameters->redirect_to );
	}

	/**
	 * Registers API routes for the application.
	 */
	public function register_api_routes(): void {
		register_rest_route(
			Rsssl_Two_Factor::REST_NAMESPACE,
			'/save_default_method_email',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'set_as_email' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return true;  // Allow all requests; handle auth in the callback.
				},
				'args'                => array(
					'provider'    => array(
						'required' => true,
						'type'     => 'string',
					),
					'user_id'     => array(
						'required' => true,
						'type'     => 'integer',
					),
					'login_nonce' => array(
						'required' => true,
						'type'     => 'string',
					),
				),
			)
		);

        register_rest_route(
            Rsssl_Two_Factor::REST_NAMESPACE,
            '/save_default_method_email_profile',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'set_profile_email' ),
                'permission_callback' => function ( WP_REST_Request $request ) {
                    return true;  // Allow all requests; handle auth in the callback.
                },
                'args'                => array(
                    'provider'    => array(
                        'required' => true,
                        'type'     => 'string',
                    ),
                    'user_id'     => array(
                        'required' => true,
                        'type'     => 'integer',
                    ),
                    'login_nonce' => array(
                        'required' => true,
                        'type'     => 'string',
                    ),
                ),
            )
        );

        register_rest_route(
            Rsssl_Two_Factor::REST_NAMESPACE,
            '/validate_email_setup',
            array(
                'methods' => 'POST',
                'callback' => array( $this, 'validate_email_setup' ),
                'permission_callback' => function ( WP_REST_Request $request ) {
                    return true;  // Allow all requests; handle auth in the callback.
                },
                'args' => array(
                    'provider' => array(
                        'required' => true,
                        'type' => 'string',
                    ),
                    'user_id' => array(
                        'required' => true,
                        'type' => 'integer',
                    ),
                    'login_nonce' => array(
                        'required' => true,
                        'type' => 'string',
                    ),
                    'redirect_to' => array(
                        'required' => false,
                        'type' => 'string',
                    ),
                    'token' => array(
                        'required' => true,
                        'type' => 'string',
                    ),
                ),
            )
        );

        register_rest_route(
            Rsssl_Two_Factor::REST_NAMESPACE,
            '/resend_email_code',
            array(
                'methods' => 'POST',
                'callback' => array( $this, 'resend_email_code' ),
                'permission_callback' => function ( WP_REST_Request $request ) {
                    return true;  // Allow all requests; handle auth in the callback.
                },
                'args' => array(
                    'provider' => array(
                        'required' => true,
                        'type' => 'string',
                    ),
                    'user_id' => array(
                        'required' => true,
                        'type' => 'integer',
                    ),
                    'login_nonce' => array(
                        'required' => true,
                        'type' => 'string',
                    ),
                ),
            )
        );

		register_rest_route(
			Rsssl_Two_Factor::REST_NAMESPACE,
			'/save_default_method_totp',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'verify_2fa_code_totp' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return true;  // Allow all requests; handle auth in the callback.
				},
				'args'                => array(
					'two-factor-totp-authcode' => array(
						'required' => true,
						'type'     => 'string',
					),
					'provider'                 => array(
						'required' => true,
						'type'     => 'string',
					),
					'key'                      => array(
						'required' => true,
						'type'     => 'string',
					),
					'redirect_to'              => array(
						'required' => false,
						'type'     => 'string',
					),
				),
			)
		);

		register_rest_route(
			Rsssl_Two_Factor::REST_NAMESPACE,
			'do_not_ask_again',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'disable_two_fa_for_user' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return true;  // Allow all requests; handle auth in the callback.
				},
				'args'                => array(
					'redirect_to' => array(
						'required' => false,
						'type'     => 'string',
					),
					'user_id'     => array(
						'required' => true,
						'type'     => 'integer',
					),
					'login_nonce' => array(
						'required' => true,
						'type'     => 'string',
					),
				),
			)
		);

		register_rest_route(
			Rsssl_Two_Factor::REST_NAMESPACE,
			'skip_onboarding',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'skip_onboarding' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'redirect_to' => array(
						'required' => false,
						'type'     => 'string',
					),
					'user_id'     => array(
						'required' => true,
						'type'     => 'integer',
					),
					'login_nonce' => array(
						'required' => true,
						'type'     => 'string',
					),
				),
			)
		);
	}

	/**
	 * Sets all other providers to inactive.
	 *
	 * @param  int    $id  The user ID.
	 * @param  string $allowed_method  The allowed method.
	 *
	 * @return void
	 */
	public static function set_other_providers_inactive( int $id, string $allowed_method ): void {
		// First we get all the available providers for the user.
		// We get the user from the id.
		$user_available_providers = Rsssl_Provider_Loader::get_enabled_providers_for_user( get_user_by( 'id', $id ) );
		foreach ( $user_available_providers as $provider ) {
			$namespace_parts = explode( '\\', $provider );
			$last_key        = end( $namespace_parts );
			// we explode the last key to get the provider name.
			$provider_name = explode( '_', $last_key );
			$provider_name = end( $provider_name );
			if ( ucfirst( $allowed_method ) !== $provider_name ) {
				$provider::set_user_status( $id, 'disabled' );
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