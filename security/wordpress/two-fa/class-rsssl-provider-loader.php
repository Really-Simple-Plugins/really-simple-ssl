<?php
/**
 * This file contains the Rsssl_Provider_Loader class.
 * This class is responsible for loading and managing Two-Factor authentication providers.
 *
 * @package RSSSL\Pro\Security\WordPress\Two_Fa
 * @subpackage Providers
 */

namespace RSSSL\Security\WordPress\Two_Fa;

use Exception;
use WP_User;
use RSSSL\Security\WordPress\Two_Fa\Rsssl_Two_Factor_Totp;
use RSSSL\Security\WordPress\Two_Fa\Rsssl_Two_Factor_Email;
use RSSSL\Security\WordPress\Two_Fa\Rsssl_Two_Fa_Status;

/**
 * Class Rsssl_Provider_Loader
 *
 * This class is responsible for loading and managing Two-Factor authentication providers.
 *
 * @package RSSSL\Pro\Security\WordPress\Two_Fa
 * @subpackage Providers
 */
class Rsssl_Provider_Loader {

	public const METHODS = array( 'totp', 'email' ); // This is a list of all available methods.

	/**
	 * For each provider, include it and then instantiate it.
	 *
	 * @return array
	 * @since 0.1-dev
	 */
	public static function get_providers(): array {
		$providers = array(
			Rsssl_Two_Factor_Email::class => __DIR__ . '/class-rsssl-two-factor-email.php',
			Rsssl_Two_Factor_Totp::class  => __DIR__ . '/class-rsssl-two-factor-totp.php',
		);

		/**
		 * Filter the supplied providers.
		 *
		 * This lets third-parties either remove providers (such as Email), or
		 * add their own providers (such as text message or Clef).
		 *
		 * @param  array  $providers  A key-value array where the key is the class name, and
		 *                         the value is the path to the file containing the class.
		 */
		$providers = apply_filters( 'rsssl_two_factor_providers', $providers );

		/**
		 * For each filtered provider,
		 */
		foreach ( $providers as $class => $path ) {
			include_once $path;

			/**
			 * Confirm that it's been successfully included before instantiating.
			 */
			if ( class_exists( $class ) ) {
				try {
					$providers[ $class ] = call_user_func( array( $class, 'get_instance' ) );
				} catch ( Exception $e ) {
					unset( $providers[ $class ] );
				}
			}
		}

		return $providers;
	}

	/**
	 * Get all Two-Factor Auth providers that are enabled for the specified|current user.
	 *
	 * @param  WP_User $user  Optional. User ID, or WP_User object of the user. Defaults to current user.
	 *
	 * @return array
	 */
	public static function get_enabled_providers_for_user( WP_User $user ): array {

		$enabled_providers = self::get_user_enabled_providers( $user );
		$statuses          = Rsssl_Two_Fa_Status::get_user_two_fa_status( $user );

		$forced_providers  = array();

		foreach ( $statuses as $method => $status ) {
			/**
			 * Check if the provider is forced for the user.
			 *
			 * @var Rsssl_Two_Factor_Provider_Interface $provider_class
			 */
			$provider_class = 'RSSSL\Security\WordPress\Two_Fa\Rsssl_Two_Factor_' . ucfirst( $method );


			if ( in_array( $status, array( 'active', 'open', 'disabled' ), true ) && $provider_class::is_enabled( $user ) ) {
				$forced_providers[] = $provider_class;
			}
		}

		if ( ! empty( $forced_providers ) ) {
			$enabled_providers = $forced_providers;
		} else {
			foreach ( $enabled_providers as $key => $enabled_provider ) {
				/**
				 * Check if the provider is optional for the user.
				 *
				 * @var Rsssl_Two_Factor_Provider_Interface $enabled_provider
				 */
				if ( ! $enabled_provider::is_optional( $user ) ) {
					unset( $enabled_providers[ $key ] );
				}
			}
		}
		return $enabled_providers;
	}

	/**
	 * This isn't currently set anywhere, but allows to add more providers in the future.
	 *
	 * @param WP_User $user The user to check.
	 *
	 * @return array|string[]
	 */
	public static function get_user_enabled_providers( WP_User $user ): array {
		$enabled_providers = array();
		if ( true === Rsssl_Two_Factor_Totp::is_enabled( $user ) ) {
			$enabled_providers[] = Rsssl_Two_Factor_Totp::class;
		}
		if ( true === Rsssl_Two_Factor_Email::is_enabled( $user ) ) {
			$enabled_providers[] = Rsssl_Two_Factor_Email::class;
		}

		return $enabled_providers;
	}

	/**
	 * Get the enabled providers for the user's roles.
	 *
	 * @param WP_User $user The user object.
	 *
	 * @return array The enabled providers.
	 */
	public static function get_enabled_providers_for_roles( WP_User $user ): array {
		// First get all the providers that are enabled for the user's role.
		$totp = Rsssl_Two_Factor_Totp::is_enabled( $user );
		$email = Rsssl_Two_Factor_Email::is_enabled( $user );

		// Put the enabled providers in an array.
		$enabled_providers = array();
		if ( $totp ) {
			$enabled_providers[] = 'totp';
		}
		if ( $email ) {
			$enabled_providers[] = 'email';
		}

		// Return the enabled providers.
		return $enabled_providers;
	}
}
