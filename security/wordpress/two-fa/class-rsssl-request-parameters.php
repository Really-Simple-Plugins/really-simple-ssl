<?php
/**
 * Holds the request parameters for a specific action.
 *
 * @package REALLY_SIMPLE_SSL
 */

namespace RSSSL\Security\WordPress\Two_Fa;

use WP_REST_Request;

/**
 * Holds the request parameters for a specific action.
 * This class holds the request parameters for a specific action.
 * It is used to store the parameters and pass them to the functions.
 *
 * @package REALLY_SIMPLE_SSL
 */
class Rsssl_Request_Parameters {
	/**
	 * User ID.
	 *
	 * @var integer
	 */
	public $user_id;

	/**
	 * Login nonce.
	 *
	 * @var string
	 */
	public $login_nonce;

	/**
	 * User.
	 *
	 * @var WP_User
	 */
	public $user;

	/**
	 * Service provider.
	 *
	 * @var object
	 */
	public $provider;

	/**
	 * Redirect to URL.
	 *
	 * @var string
	 */
	public $redirect_to;

	/**
	 * The code.
	 *
	 * @var string
	 */
	public $code;

	/**
	 * The key.
	 *
	 * @var string
	 */
	public $key;

	/**
	 * The nonce.
	 *
	 * @var mixed|null
	 */
	public $nonce;
    /**
     * @var array|string
     */
    public $token;

    /**
     * @var bool
     */
    public $profile;

    /**
	 * Constructor for the class.
	 *
	 * @param WP_REST_Request $request The WordPress REST request object.
	 *
	 * @return void
	 */
	public function __construct( WP_REST_Request $request ) {
		$this->user_id     = $request->get_param( 'user_id' );
		$this->login_nonce = $request->get_param( 'login_nonce' );
		$this->nonce       = $request->get_header( 'X-WP-Nonce' );
		$this->user        = get_user_by( 'id', $this->user_id );
		$this->provider    = $request->get_param( 'provider' );
		$this->redirect_to = $request->get_param( 'redirect_to' )?? admin_url();
		if ( 'totp' === $this->provider ) {
			$this->code = wp_unslash( $request->get_param( 'two-factor-totp-authcode' ) );
			$this->key  = wp_unslash( $request->get_param( 'key' ) );
		}
        if ('email' === $this->provider) {
            $this->token = wp_unslash($request->get_param('token'));
            $this->profile = wp_unslash($request->get_param('profile')?? false);
        }
	}
}
