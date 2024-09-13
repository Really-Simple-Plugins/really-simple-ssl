<?php
/**
 * Holds the request parameters for a specific action.
 *
 * @package REALLY_SIMPLE_SSL
 */

namespace RSSSL\Security\WordPress\Two_Fa;

use WP_User;

/**
 * Check if a user is forced.
 *
 * @param WP_User $user The user to check.
 *
 * @return bool True if the user is forced, false otherwise.
 */
interface Rsssl_Two_Factor_Provider_Interface {
	/**
	 * Check if a user is forced.
	 *
	 * @param  WP_User $user  The user to check.
	 *
	 * @return bool True if the user is forced, false otherwise.
	 */
	public static function is_forced( WP_User $user ): bool;

	/**
	 * Check if a method is enabled within the roles of the user.
	 *
	 * @param  WP_User $user  The user to check.
	 *
	 * @return bool True if the user is enabled, false otherwise.
	 */
	public static function is_enabled( WP_User $user ): bool;

    public static function is_optional( WP_User $user ): bool;

    public static function is_configured( WP_User $user ): bool;
}
