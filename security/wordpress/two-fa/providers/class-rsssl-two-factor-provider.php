<?php
/**
 * Abstract class for creating two factor authentication providers.
 *
 * @package Two_Factor
 */

namespace RSSSL\Security\WordPress\Two_Fa\Providers;

use WP_User;

/**
 * Abstract class for creating two-factor authentication providers.
 *
 * @since 7.0.6
 *
 * @package Two_Factor
 */
abstract class Rsssl_Two_Factor_Provider {

	/**
	 *  The instance of the provider.
	 *
	 * @var Rsssl_Two_Factor_Provider
	 */
	public $instance;

	/**
	 * Class constructor.
	 *
	 * @since 0.1-dev
	 */
	protected function __construct() {
		$this->instance = $this;
	}

	/**
	 * Returns the name of the provider.
	 *
	 * @since 0.1-dev
	 *
	 * @return string
	 */
	abstract public function get_label();

	/**
	 * Prints the name of the provider.
	 *
	 * @since 0.1-dev
	 */
	public function print_label() {
		echo esc_html( $this->get_label() );
	}

	/**
	 * Prints the form that prompts the user to authenticate.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 *
	 * @since 0.1-dev
	 */
	abstract public function authentication_page( WP_User $user );

	/**
	 * Allow providers to do extra processing before the authentication.
	 * Return `true` to prevent the authentication and render the
	 * authentication page.
	 *
	 * @param  WP_User $user WP_User object of the logged-in user.
	 * @return boolean
	 */
	public function pre_process_authentication( $user ) {
		return false;
	}

	/**
	 * Validates the users input token.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 * @return boolean
	 *@since 0.1-dev
	 *
	 */
	abstract public function validate_authentication( WP_User $user ): bool;

	/**
	 * Whether this Two Factor provider is configured and available for the user specified.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 * @return boolean
	 */
	abstract public function is_available_for_user(WP_User $user ): bool;

    /**
     * Start the controller needed for onboarding and profile management.
     * @return mixed
     */
    abstract protected static function start_controller( string $namespace, string $version, string $featureVersion ):void;

	/**
	 * Generate a random eight-digit string to send out as an auth code.
	 *
	 * @param int          $length The code length.
	 * @param string|array $chars Valid auth code characters.
	 * @return string
	 *@since 0.1-dev
	 *
	 */
	public static function get_code(int $length = 8, $chars = '1234567890' ): string {
		$code = '';
		if ( is_array( $chars ) ) {
			$chars = implode( '', $chars );
		}
		for ( $i = 0; $i < $length; $i++ ) {
			$code .= substr( $chars, wp_rand( 0, strlen( $chars ) - 1 ), 1 );
		}
		return $code;
	}

	/**
	 * Sanitizes a numeric code to be used as an auth code.
	 *
	 * @param string $field  The _REQUEST field to check for the code.
	 * @param int    $length The valid expected length of the field.
	 *
	 * @return false|string Auth code on success, false if the field is not set or not expected length.
	 */
	public static function sanitize_code_from_request( string $field, int $length = 0 ) {
		if ( empty( $_REQUEST[ $field ] ) ) {
			return false;
		}

		$code = wp_unslash( $_REQUEST[ $field ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, handled by the core method already.
		$code = preg_replace( '/\s+/', '', $code );

		// Maybe validate the length.
		if ( $length && strlen( $code ) !== $length ) {
			return false;
		}

		return (string) $code;
	}

	/**
	 * Set user status.
	 *
	 * This function updates the 'rsssl_two_fa_status' user meta key with the provided status.
	 *
	 * @param int    $user_id The user ID.
	 * @param string $status The user status.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public static function set_user_status( int $user_id, string $status ): void {
		update_user_meta( $user_id, 'rsssl_two_fa_status', $status );
	}

    abstract public static function reset_meta_data (int $user_id): void;
}
