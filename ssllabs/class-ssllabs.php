<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );
class rsssl_ssllabs {
	function __construct() {

	}

	public function new_test(){
		return $this->get(true);
	}

	public function get( $clear_cache = false ){
		error_log("#######");
		if ($clear_cache){
			update_option( 'rsssl_ssltest_endpoint_ip', false );
			update_option( 'rsssl_ssltest_base_request', false );
			update_option( 'rsssl_ssltest_endpoint', false);
		}
		$ip = get_option( 'rsssl_ssltest_endpoint_ip' );
		if ( ! $ip ) {
			$url      = "https://api.ssllabs.com/api/v3/analyze?host=ziprecipes.net";
			if ($clear_cache) $url.= '&startNew=on';

			$response = wp_remote_get( $url );
			$status   = wp_remote_retrieve_response_code( $response );
			$body     = wp_remote_retrieve_body( $response );
			error_log( print_r( $body, true ) );
			if ( $status == 200 ) {
				$body = json_decode( $body );
				if ( $body->status === 'READY' && isset( $body->endpoints ) && is_array( $body->endpoints ) ) {
					update_option( 'rsssl_ssltest_endpoint_ip', $body->endpoints[0]->ipAddress );
					update_option( 'rsssl_ssltest_base_request', $body );
				}
			}
		} elseif ( !get_option( 'rsssl_ssltest_endpoint') ) {
			$body = get_option( 'rsssl_ssltest_base_request');
			$url      = "https://api.ssllabs.com/api/v3/getEndpointData?host=ziprecipes.net&s=" . $ip;
			$response = wp_remote_get( $url );
			$status   = wp_remote_retrieve_response_code( $response );
			$endpoint_body     = wp_remote_retrieve_body( $response );
			if ( $status == 200 ) {
				$endpoint_body = json_decode( $endpoint_body );
				update_option( 'rsssl_ssltest_endpoint', $endpoint_body );
			}
		}

		$total_progress = $this->get_progress();
		$body = get_option( 'rsssl_ssltest_base_request');
		$endpoint_body = get_option( 'rsssl_ssltest_endpoint');
		if (!$body || !$endpoint_body){
			$output = [
				'progress' => $total_progress,
			];
		} else {
			$output = [
				'progress' => $total_progress,
				'host'     => $body->host,
			];
			if ( $total_progress >= 100 ) {
				$output['grade']      = $body->endpoints[0]->grade;
				$output['hsts']       = $endpoint_body->details->hstsPolicy->status === 'present';
				$output['warnings']   = $body->endpoints[0]->hasWarnings;
				$output['serverName'] = $body->endpoints[0]->serverName;
			}
		}

		return $output;
	}

	/**
	 * Get progress of test
	 *
	 * @return int
	 */
	private function get_progress(){
		//calculate progress
		$total_progress = 0;
		$body = get_option('rsssl_ssltest_base_request');
		if ($body && $body->status==='READY' && isset($body->endpoints) && is_array($body->endpoints) ) {
			$endpoints = $body->endpoints;
			$total = count($endpoints);
			$progress = 0;
			foreach ($endpoints as $endpoint){
				$progress += $endpoint->progress;
			}
			$total_progress = $progress/$total;
		}
		return $total_progress;
	}
}



