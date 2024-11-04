<?php
/**
 * Two-Factor Authentication.
 * Status class.
 *
 * @package REALLY_SIMPLE_SSL
 */

namespace RSSSL\Security\WordPress\Two_Fa;

use RSSSL\Security\WordPress\Two_Fa\Traits\Rsssl_Two_Fa_Helper;
use WP_User;


/**
 * Class Rsssl_Two_Fa_Status
 *
 * Represents the two-factor authentication status.
 *
 * @package REALLY_SIMPLE_SSL
 */
class Rsssl_Two_Fa_Status {

	use Rsssl_Two_Fa_Helper;

	public const STATUSES = array( 'disabled', 'open', 'active' ); // This is a list of all available statuses.

	/**
	 * Get the status of two-factor authentication for a user.
	 *
	 * @param  WP_User|null $user  (optional) The user for which to retrieve the status. Defaults to current user.
	 *
	 * @return array  An associative array where the method names are the keys and the status values are the values.
	 *               The status can be one of the following: 'disabled' if the method is disabled for the user,
	 *               'enabled' if the method is enabled for the user, or 'unknown' if the status could not be determined.
	 */
	public static function get_user_two_fa_status( $user = null ): array {
		$methods  = Rsssl_Provider_Loader::METHODS; // Assume this function returns all available methods.
		$statuses = array();

		foreach ( $methods as $method ) {
			$status              = self::get_user_status( $method, $user->ID );
			$statuses[ $method ] = $status ? $status : 'disabled';
		}

		return $statuses;
	}

	/**
	 * Get the user's two-factor authentication status.
	 *
	 * @param  string $method  The authentication method used by the user.
	 * @param  int    $user_id  The ID of the user.
	 *
	 * @return string The user's two-factor authentication status (enabled or disabled).
	 */
	public static function get_user_status( string $method, int $user_id ): string {
		$activated = $method === 'email' ? '_email' : '_' . self::sanitize_method( $method );

		// Check the roles per method if they are enabled.
		$enabled_roles = rsssl_get_option( 'two_fa_enabled_roles'.$activated, array());

		if ( empty( $enabled_roles ) && self::is_user_role_enabled( $user_id, $enabled_roles )) {
			return 'disabled';
		}

		$status = get_user_meta( $user_id, "rsssl_two_fa_status_$method", true );

		return self::sanitize_status( $status );
	}

	/**
	 * Delete two-factor authentication metadata for a user.
	 *
	 * @param  WP_User $user  The user object for whom to delete the metadata.
	 *
	 * @return void
	 */

	public static function delete_two_fa_meta( $user ): void {
        if( is_object($user) ){
            $user = $user->ID;
        }
		delete_user_meta( $user, '_rsssl_two_factor_totp_last_successful_login' );
		delete_user_meta( $user, '_rsssl_two_factor_nonce' );
        delete_user_meta( $user, 'rsssl_two_fa_status' );
        delete_user_meta( $user, 'rsssl_two_fa_status_email' );
		delete_user_meta( $user, 'rsssl_two_fa_status_totp' );
		delete_user_meta( $user, '_rsssl_two_factor_totp_key' );
		delete_user_meta( $user, '_rsssl_two_factor_backup_codes' );
		delete_user_meta( $user, 'rsssl_activation_date' );
		delete_user_meta( $user, 'rsssl_two_fa_last_login' );
		delete_user_meta( $user, 'rsssl_two_fa_skip_token' );
		delete_user_meta( $user, '_rsssl_factor_email_token_timestamp' );
		delete_user_meta( $user, '_rsssl_factor_email_token' );
        delete_user_meta( $user, 'rsssl_two_fa_reminder_sent' );
	}

	/**
	 * Checks if a user has any of the enabled roles.
	 *
	 * @param int $user_id The user ID.
	 * @param array $enabled_roles The enabled roles to check against.
	 *
	 * @return bool Returns true if the user has any of the enabled roles, false otherwise.
	 */
	private static function is_user_role_enabled( int $user_id, array $enabled_roles ):bool {
		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return false;
		}

		$user_roles = $user->roles;

		foreach ( $user_roles as $role ) {
			if ( in_array( $role, $enabled_roles, true ) ) {
				return true;
			}
		}

		return false;
	}
}
