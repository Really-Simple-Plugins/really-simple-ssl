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
		$location_of_wp_config = ABSPATH;
		if ( ! file_exists( ABSPATH . 'wp-config.php' ) && file_exists( dirname( ABSPATH ) . '/wp-config.php' ) ) {
			$location_of_wp_config = dirname( ABSPATH );
		}
		$location_of_wp_config = trailingslashit( $location_of_wp_config );
		$wpconfig_path         = $location_of_wp_config . 'wp-config.php';
		if ( file_exists( $wpconfig_path ) ) {
			return $wpconfig_path;
		}
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
