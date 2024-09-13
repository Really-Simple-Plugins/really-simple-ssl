<?php
/**
 * Two-Factor Authentication.
 *
 * @package REALLY_SIMPLE_SSL
 *
 * @since 0.1-dev
 */

namespace RSSSL\Security\WordPress\Two_Fa;

use Exception;

/**
 * Class Rsssl_Two_Fa_Authentication
 *
 * Represents the two-factor authentication functionality.
 */
class Rsssl_Two_Fa_Authentication {

	/**
	 * The user meta nonce key.
	 *
	 * @type string
	 */
	public const RSSSL_USER_META_NONCE_KEY = '_rsssl_two_factor_nonce';

	/**
	 * Verify a login nonce for a user.
	 *
	 * @param int    $user_id The ID of the user.
	 * @param string $nonce The login nonce to verify.
	 *
	 * @return bool True if the nonce is valid and has not expired, false otherwise.
	 */
	public static function verify_login_nonce( int $user_id, string $nonce ): bool {
		$login_nonce = get_user_meta( $user_id, self::RSSSL_USER_META_NONCE_KEY, true );

		if ( ! $login_nonce || empty( $login_nonce['rsssl_key'] ) || empty( $login_nonce['rsssl_expiration'] ) ) {
			return false;
		}

		$unverified_nonce = array(
			'rsssl_user_id'    => $user_id,
			'rsssl_expiration' => $login_nonce['rsssl_expiration'],
			'rsssl_key'        => $nonce,
		);

		$unverified_hash = self::hash_login_nonce( $unverified_nonce );
		$hashes_match    = $unverified_hash && hash_equals( $login_nonce['rsssl_key'], $unverified_hash );

		if ( $hashes_match && time() < $login_nonce['rsssl_expiration'] ) {
			return true;
		}

		// Require a fresh nonce if verification fails.
		self::delete_login_nonce( $user_id );

		return false;
	}

	/**
	 * Create a login nonce for a user.
	 *
	 * @param int $user_id The ID of the user.
	 *
	 * @return array|false The login nonce array if successfully created and stored, false otherwise.
	 */
	public static function create_login_nonce( int $user_id ) {
		$login_nonce = array(
			'rsssl_user_id'    => $user_id,
			'rsssl_expiration' => time() + ( 15 * MINUTE_IN_SECONDS ),
		);

		try {
			$login_nonce['rsssl_key'] = bin2hex( random_bytes( 32 ) );
		} catch ( Exception $ex ) {
			$login_nonce['rsssl_key'] = wp_hash( $user_id . wp_rand() . microtime(), 'nonce' );
		}

		// Store the nonce hashed to avoid leaking it via database access.
		$hashed_key = self::hash_login_nonce( $login_nonce );

		if ( $hashed_key ) {
			$login_nonce_stored = array(
				'rsssl_expiration' => $login_nonce['rsssl_expiration'],
				'rsssl_key'        => $hashed_key,
			);

			if ( update_user_meta( $user_id, self::RSSSL_USER_META_NONCE_KEY, $login_nonce_stored ) ) {
				return $login_nonce;
			}
		}

		return false;
	}

	/**
	 * Delete the login nonce.
	 *
	 * @param  int $user_id  User ID.
	 *
	 * @return bool
	 * @since 0.1-dev
	 */
	public static function delete_login_nonce( int $user_id ): bool {
		return delete_user_meta( $user_id, self::RSSSL_USER_META_NONCE_KEY );
	}

	/**
	 * Get the hash of a nonce for storage and comparison.
	 *
	 * @param  array $nonce  Nonce array to be hashed. ⚠️ This must contain user ID and expiration,
	 *                    to guarantee the nonce only works for the intended user during the
	 *                    intended time window.
	 *
	 * @return string|false
	 */
	protected static function hash_login_nonce( array $nonce ) {
		$message = wp_json_encode( $nonce );

		if ( ! $message ) {
			return false;
		}

		return wp_hash( $message, 'nonce' );
	}
}
