<?php
/**
 * A helper trait for sanitizing status and method values.
 *
 * @package really-simple-ssl
 */

namespace RSSSL\Security\WordPress\Two_Fa\Traits;

use RSSSL\Security\WordPress\Two_Fa\Rsssl_Provider_Loader;
use RSSSL\Security\WordPress\Two_Fa\Rsssl_Two_Fa_Status;

/**
 * A helper trait for sanitizing status and method values.
 */
trait Rsssl_Two_Fa_Helper {
	/**
	 * Sanitize the given status.
	 *
	 * @param  string $status  The status to sanitize.
	 *
	 * @return string The sanitized status.
	 */
	private static function sanitize_status( string $status ): string {
		$statuses_available = Rsssl_Two_Fa_Status::STATUSES;

		if ( empty( $status ) ) {
			return 'open';
		}
		// Check if the $status is in the array of available statuses.
		if ( ! in_array( $status, $statuses_available, true ) ) {
			// if not, set it to 'disabled'.
			$status = 'disabled';
		}

		return sanitize_text_field( $status );
	}

	/**
	 * Sanitize a given method.
	 *
	 * @param  string $method  The method to sanitize.
	 *
	 * @return string  The sanitized method.
	 */
	private static function sanitize_method( string $method ): string {
		$methods_available = Rsssl_Provider_Loader::METHODS;
		// Check if the $method is in the array of available methods.
		if ( ! in_array( $method, $methods_available, true ) ) {
			// if not, set it to 'disabled'.
			$method = 'disabled';
		}

		return sanitize_text_field( $method );
	}
}
