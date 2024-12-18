<?php
namespace RSSSL\lib\admin;

/**
 * Trait admin helper
 *
 *
 * @package RSSSL\helper
 * @since   8.2
 *
 * @author  Really Simple Security
 * @see     https://really-simple-ssl.com
 */
trait Helper {
	/**
	 * Get the wp-config path
	 *
	 * @return string
	 */
	public function wpconfig_path(): string {
		// Allow the wp-config.php path to be overridden via a filter.
		$filtered_path = apply_filters( 'rsssl_wpconfig_path', '' );

		// If a filtered path is provided, validate it.
		if ( ! empty( $filtered_path ) ) {
			$directory = dirname( $filtered_path );

			// Ensure the directory exists before checking for the file.
			if ( is_dir( $directory ) && file_exists( $filtered_path ) ) {
				return $filtered_path;
			}
		}

		// Default behavior to locate wp-config.php
		$location_of_wp_config = ABSPATH;
		if ( ! file_exists( ABSPATH . 'wp-config.php' ) && file_exists( dirname( ABSPATH ) . '/wp-config.php' ) ) {
			$location_of_wp_config = dirname( ABSPATH );
		}

		$location_of_wp_config = trailingslashit( $location_of_wp_config );
		$wpconfig_path         = $location_of_wp_config . 'wp-config.php';

		// Check if the file exists and return the path if valid.
		if ( file_exists( $wpconfig_path ) ) {
			return $wpconfig_path;
		}

		// Return an empty string if no valid wp-config.php path is found.
		return '';
	}


	/**
	 * Log a message if WP_DEBUG is enabled
	 *
	 * @param string $message
	 *
	 * @return void
	 */
	public function log( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "Really Simple Security: ".$message );
		}
	}

}
