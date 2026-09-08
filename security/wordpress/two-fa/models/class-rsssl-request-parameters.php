<?php
/**
 * Holds the request parameters for a specific action.
 *
 * @package REALLY_SIMPLE_SSL
 */

namespace RSSSL\Security\WordPress\Two_Fa\Models;

use RSSSL\Pro\Security\WordPress\Two_Fa\Providers\Rsssl_Two_Factor_Passkey;
use WP_REST_Request;
use WP_User;

/**
 * Class Rsssl_Request_Parameters
 *
 * This class holds the request parameters for a specific action.
 * It is used to store the parameters and pass them to the functions.
 *
 * @package REALLY_SIMPLE_SSL
 */
class Rsssl_Request_Parameters {
	/**
	 * User ID.
	 *
	 * @var int
	 */
	public int $user_id = 0;

	/**
	 * Login nonce.
	 *
	 * @var string
	 */
	public string $login_nonce = '';

	/**
	 * User object.
	 *
	 * @var WP_User|null
	 */
	public ?WP_User $user = null;

	/**
	 * Service provider.
	 *
	 * @var string|object
	 */
	public string $provider = '';

	/**
	 * Redirect URL.
	 *
	 * @var string
	 */
	public string $redirect_to = '';

	/**
	 * Authentication code.
	 *
	 * @var string
	 */
	public string $code = '';

	/**
	 * Authentication key.
	 *
	 * @var string
	 */
	public string $key = '';

	/**
	 * Nonce value.
	 *
	 * @var mixed|null
	 */
	public string $nonce = '';

	/**
	 * Authentication token.
	 *
	 * @var string
	 */
	public string $token = '';

	/**
	 * Passkey ID.
	 *
	 * @var string
	 */
	public string $id = '';

	/**
	 * Raw ID for passkey.
	 *
	 * @var string
	 */
	public string $rawId = '';

	/**
	 * Response data.
	 *
	 * @var array
	 */
	public array $response = [];

	/**
	 * Request type.
	 *
	 * @var string
	 */
	public string $type = '';

	/**
	 * Unique browser identifier.
	 *
	 * @var string
	 */
	public string $unique_browser_identifier = '';

	/**
	 * User login.
	 *
	 * @var string
	 */
	public string $user_login = '';

	/**
	 * User handle.
	 *
	 * @var mixed|null
	 */
	public string $user_handle = '';

	/**
	 * Onboarding flag.
	 *
	 * @var bool
	 */
	public bool $onboarding = false;

	/**
	 * Auth device ID.
	 *
	 * @var string
	 */
	public string $auth_device_id = '';

	public int $entry_id = 0;

	public bool $profile = false;

	public array $forced_roles = [];

	public int $days_threshold = 0;

	/**
	 * Constructor for the class.
	 *
	 * @param WP_REST_Request $request The WordPress REST request object.
	 */
	public function __construct( WP_REST_Request $request ) {
		$this->initialize_parameters( $request );
	}

	/**
	 * Initialize the class properties based on the request parameters.
	 *
	 * @param WP_REST_Request $request The WordPress REST request object.
	 */
	private function initialize_parameters( WP_REST_Request $request ): void {
		$allowed_providers = array( 'passkey', 'email', 'totp', 'passkey_register' );
		$redirect_to       = $request->get_param( 'redirect_to' );
		$provider          = $this->sanitize_string_value( $request->get_param( 'provider' ) );

		$this->nonce       = $this->sanitize_string_value( $request->get_header( 'X-WP-Nonce' ) );
		$this->redirect_to = is_string( $redirect_to ) && $redirect_to !== ''
			? wp_validate_redirect( $redirect_to, admin_url() )
			: admin_url();
		$this->login_nonce    = $this->sanitize_string_value( $request->get_param( 'login_nonce' ) );
		$this->forced_roles   = rsssl_get_option( 'two_fa_forced_role', [] );
		$this->days_threshold = rsssl_get_option( 'two_fa_days_threshold', 0 );

		if ( ! in_array( $provider, $allowed_providers, true ) ) {
			$provider = null;
		}

		if ( $request->has_param( 'credential' ) || $request->has_param( 'credentials' ) ) {
			$this->initialize_passkey_parameters( $request );
		} else {
			$this->user_id  = $this->sanitize_integer_value( $request->get_param( 'user_id' ) );
			$this->provider = $provider ?? 'none';
			$user           = get_user_by( 'id', $this->user_id );
			if ( $user ) {
				$this->user = $user;
			}
			if ( $request->has_param( 'entry_id' ) ) {
				$this->entry_id = $this->sanitize_integer_value( $request->get_param( 'entry_id' ) );
			}
		}

		if ( $provider === 'totp' ) {
			$this->code = $this->sanitize_string_value( $request->get_param( 'two-factor-totp-authcode' ) );
			$this->key  = $this->sanitize_string_value( $request->get_param( 'key' ) );
		}

		if ( $provider === 'email' ) {
			$profile       = $request->get_param( 'profile' );
			$this->token   = $this->sanitize_string_value( $request->get_param( 'token' ) );
			$this->profile = is_scalar( $profile ) ? rest_sanitize_boolean( $profile ) : false;
		}

		$this->unique_browser_identifier = $this->sanitize_string_value( $request->get_param( 'unique_browser_identifier' ) );
		$this->user_login                = sanitize_user( $this->sanitize_string_value( $request->get_param( 'user_login' ) ) );

		$onboarding = $request->get_param( 'onboarding' );
		$this->user_handle    = $this->sanitize_string_value( $request->get_param( 'userHandle' ) );
		$this->onboarding     = is_scalar( $onboarding ) ? rest_sanitize_boolean( $onboarding ) : false;
		$this->auth_device_id = $this->sanitize_string_value( $request->get_param( 'device_name' ), 'unknown' );

		// If user_id is set, we try to get the user object.
		if ( $this->user_id ) {
			$user = get_user_by( 'id', $this->user_id );
			if ( $user ) {
				$this->user = $user;
			}
			return;
		}

		// If user_login is set, we try to get the user object by login. Since we probably are in the login flow,
		// we want to get the user by login.
		if ( $this->user_login ) {
			$user = get_user_by( 'login', $this->user_login );
			if ( $user ) {
				$this->user_id = $user->ID;
				$this->user    = $user;
			}
		}
	}

	/**
	 * Initialize passkey-specific parameters.
	 *
	 * @param WP_REST_Request $request The WordPress REST request object.
	 */
	private function initialize_passkey_parameters( WP_REST_Request $request ): void {
		$user_id = $request->get_param( 'user_id' );

		// Only fall back to the current user when user_id is absent. Cast a supplied
		// value to a non-negative integer to prevent TypeErrors in permission callbacks.
		if ( ! $request->has_param( 'user_id' ) ) {
			$this->user_id = get_current_user_id();
		} else {
			$this->user_id = $this->sanitize_integer_value( $user_id );
		}

		$this->provider = Rsssl_Two_Factor_Passkey::class;
		$this->id       = $this->sanitize_string_value( $request->get_param( 'id' ) );
		$this->rawId    = $this->sanitize_string_value( $request->get_param( 'rawId' ) );
		if ( ! $request->has_param( 'credentials' ) ) {
			$response       = $request->get_param( 'credential' );
			$this->response = is_array( $response ) ? $response : [];
		}
		$this->type     = $this->sanitize_string_value( $request->get_param( 'type' ) );
		$this->entry_id = $this->sanitize_integer_value( $request->get_param( 'entry_id' ) );
	}

	/**
	 * Sanitize a request value without passing compound values to scalar-only
	 * WordPress sanitizers.
	 *
	 * @param mixed  $value   Request value.
	 * @param string $fallback Default value for invalid input.
	 *
	 * @return string
	 */
	private function sanitize_string_value( $value, string $fallback = '' ): string {
		if ( ! is_scalar( $value ) ) {
			return $fallback;
		}

		return sanitize_text_field( wp_unslash( (string) $value ) );
	}

	/**
	 * Convert a scalar request value to a non-negative integer.
	 *
	 * @param mixed $value Request value.
	 *
	 * @return int
	 */
	private function sanitize_integer_value( $value ): int {
		if ( ! is_scalar( $value ) ) {
			return 0;
		}

		return absint( $value );
	}
}
