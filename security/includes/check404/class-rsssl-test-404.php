<?php
namespace RSSSL\Security\Includes\Check404;

class Rsssl_Test_404 {
	// Static instance property
	public static $instance = null;

	// Private constructor to prevent direct instantiation
	private function __construct() {
		// Immediately check if there are resources to process and handle them
		$resources = get_option( 'rsssl_404_resources_to_check' );
		$found_404_option_value = get_option( 'rsssl_homepage_contains_404_resources', false );
		$found_404s = $found_404_option_value === true || $found_404_option_value === "true";

		if ( ! empty( $resources ) && ! $found_404s ) {
			// Trigger chunk processing if resources are pending
			$this->process_404_resources_chunk();
		}

		$this->fetch_and_check_homepage_resources();

	}

	// Static method to get the single instance of the class
	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	// Process resources in chunks
	public function process_404_resources_chunk() {
		$resources = get_option( 'rsssl_404_resources_to_check' );
		if ( empty( $resources ) ) {
			update_option( 'rsssl_homepage_contains_404_resources', 'false' );
			return false;
		}

		// Process a chunk of the resources (e.g., 2 at a time)
		$chunk_size      = 2;
		$resources_chunk = array_splice( $resources, 0, $chunk_size );

		$result = $this->process_404_resources( $resources_chunk );

		// Update the remaining resources back to the option
		if ( ! empty( $resources ) ) {
			update_option( 'rsssl_404_resources_to_check', $resources );
			return 'processing';
		} else {
			// All resources have been processed
			return $result;
		}
	}

	// Function to check homepage and handle 404s
	public static function homepage_contains_404_resources() {
		$found_404_option_value = get_option( 'rsssl_homepage_contains_404_resources', false );
		if ( $found_404_option_value === true || $found_404_option_value === "true" ) {
			return true;
		}

		$resources = get_option( 'rsssl_404_resources_to_check' );
		if ( ! empty( $resources ) ) {
			// If resources are available to check, process them immediately
			return self::get_instance()->process_404_resources_chunk();
		}

	}

	// Function to fetch homepage resources and check for 404 errors
	public function fetch_and_check_homepage_resources() {

		if ( get_option('rsssl_homepage_contains_404_resources') ) {
			return;
		}

		$site_url = trailingslashit( site_url() );

		$response = wp_remote_get( $site_url );
		if ( is_wp_error( $response ) ) {
			update_option( 'rsssl_homepage_contains_404_resources', false );
			return false;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code == 404 ) {
			update_option( 'rsssl_homepage_contains_404_resources', true );
			return true;
		}

		// Patterns to match img, script, link tags
		$body     = wp_remote_retrieve_body( $response );
		$patterns = array(
			'/<img[^>]+src=([\'"])?((.*?)\1)/i',
			'/<script[^>]+src=([\'"])?((.*?)\1)/i',
			'/<link[^>]+href=([\'"])?((.*?)\1)/i'
		);

		$resources = array();
		foreach ( $patterns as $pattern ) {
			if ( preg_match_all( $pattern, $body, $matches ) ) {
				foreach ( $matches[2] as $resource_url ) {
					$resource_url = esc_url_raw( $resource_url );
					if ( strpos( $resource_url, $site_url ) !== false ) {
						$resources[] = $resource_url;
					}
				}
			}
		}

		if ( count( $resources ) > 2 ) {
			update_option( 'rsssl_404_resources_to_check', $resources );
			return $this->process_404_resources_chunk();
		} else {
			if ( empty( $resources ) ) {
				update_option( 'rsssl_homepage_contains_404_resources', 'false' );
				return false;
			}

			update_option( 'rsssl_404_resources_to_check', $resources );
			// Process all resources if fewer than 5
			return $this->process_404_resources( $resources );
		}
	}

	// Function to process a list of resources and check for 404 errors
	private function process_404_resources( $resources ) {
		$not_found_resources = array();

		foreach ( $resources as $resource_url ) {
			$resource_response = wp_remote_head( $resource_url );
			if ( is_wp_error( $resource_response ) ) {
				$not_found_resources[] = $resource_url . ' (Error: ' . $resource_response->get_error_message() . ')';
			} else {
				$resource_status = wp_remote_retrieve_response_code( $resource_response );
				if ( $resource_status == 404 ) {
					$not_found_resources[] = $resource_url;
				}
			}
		}

		if ( empty( $not_found_resources ) ) {
			update_option( 'rsssl_homepage_contains_404_resources', 'false' );
			return false;
		} else {
			update_option( 'rsssl_homepage_contains_404_resources', 'true' );
			return true;
		}
	}
}