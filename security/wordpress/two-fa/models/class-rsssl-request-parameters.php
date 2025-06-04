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
	public int $user_id;

	/**
	 * Login nonce.
	 *
	 * @var string
	 */
	public string $login_nonce;

	/**
	 * User object.
	 *
	 * @var WP_User
	 */
	public WP_User $user;

	/**
	 * Service provider.
	 *
	 * @var string|object
	 */
	public string $provider;

	/**
	 * Redirect URL.
	 *
	 * @var string
	 */
	public string $redirect_to;

	/**
	 * Authentication code.
	 *
	 * @var string
	 */
	public string $code;

	/**
	 * Authentication key.
	 *
	 * @var string
	 */
	public string $key;

	/**
	 * Nonce value.
	 *
	 * @var mixed|null
	 */
	public string $nonce;

	/**
	 * Authentication token.
	 *
	 * @var string
	 */
	public string $token;

	/**
	 * Passkey ID.
	 *
	 * @var string
	 */
	public string $id;

	/**
	 * Raw ID for passkey.
	 *
	 * @var string
	 */
	public string $rawId;

	/**
	 * Response data.
	 *
	 * @var array
	 */
	public array $response;

	/**
	 * Request type.
	 *
	 * @var string
	 */
	public string $type;

	/**
	 * Unique browser identifier.
	 *
	 * @var string
	 */
	public string $unique_browser_identifier;

	/**
	 * User login.
	 *
	 * @var string
	 */
	public string $user_login;

	/**
	 * User handle.
	 *
	 * @var mixed|null
	 */
	public string $user_handle;

	/**
	 * Onboarding flag.
	 *
	 * @var bool
	 */
	public bool $onboarding;

	/**
	 * Auth device ID.
	 *
	 * @var string
	 */
	public string $auth_device_id;

	public int $entry_id;

	public bool $profile;

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
		$this->nonce       = sanitize_text_field( $request->get_header( 'X-WP-Nonce' ) );
		$this->redirect_to = $request->get_param( 'redirect_to' ) ? esc_url_raw( $request->get_param( 'redirect_to' ) ) : admin_url();
		$this->login_nonce = sanitize_text_field( $request->get_param( 'login_nonce' ) );
		$provider          = $request->get_param( 'provider' );
		$this->forced_roles = rsssl_get_option( 'two_fa_forced_role' , [] );
		$this->days_threshold = rsssl_get_option( 'two_fa_days_threshold', 0 );

		if ( ! in_array( $provider, $allowed_providers, true ) ) {
			$provider = null;
		}

		if ( $request->has_param( 'credential' ) || $request->has_param( 'credentials' ) ) {
			$this->initialize_passkey_parameters( $request );
		} else {
			$this->user_id  = $request->get_param( 'user_id' )?? 0;
			$this->provider = $provider?? 'none';
			$this->user = get_user_by( 'id', $this->user_id );
			if ($request->has_param('entry_id')) {
				$this->entry_id = (int) $request->get_param('entry_id');
			}
		}

		if ( $provider === 'totp' ) {
			$this->code = sanitize_text_field( wp_unslash( $request->get_param( 'two-factor-totp-authcode' ) ) );
			$this->key  = sanitize_text_field( wp_unslash( $request->get_param( 'key' ) ) );
		}

		if ( $provider === 'email' ) {
			$this->token   = sanitize_text_field( wp_unslash( $request->get_param( 'token' ) ) );
			$this->profile = wp_unslash( $request->get_param( 'profile' ) ?? false );
		}

		$this->unique_browser_identifier = sanitize_text_field( $request->get_param( 'unique_browser_identifier' ) );
		$this->user_login                = sanitize_user( wp_unslash( $request->get_param( 'user_login' ) ) );

		$this->user_handle    = sanitize_text_field( $request->get_param( 'userHandle' ) );
		$this->onboarding     = (bool) $request->get_param( 'onboarding' );
		$this->auth_device_id = sanitize_text_field( $request->get_param( 'device_name' ) ?? 'unknown' );
	}

	/**
	 * Initialize passkey-specific parameters.
	 *
	 * @param WP_REST_Request $request The WordPress REST request object.
	 */
	private function initialize_passkey_parameters( WP_REST_Request $request ): void {
		$this->user_id  = $request->get_param( 'user_id' ) ? absint( $request->get_param( 'user_id' ) ) : get_current_user_id();
		$this->provider = Rsssl_Two_Factor_Passkey::class;
		$this->id       = sanitize_text_field( $request->get_param( 'id' ) );
		$this->rawId    = sanitize_text_field( $request->get_param( 'rawId' ) );
		if( !$request->has_param( 'credentials' ) ) {
			//To do regex sanitation
			$this->response = $request->get_param( 'credential' );
		}
		$this->type     = sanitize_text_field( $request->get_param( 'type' ) );
		$this->entry_id = (int) $request->get_param( 'entry_id' );
	}
}