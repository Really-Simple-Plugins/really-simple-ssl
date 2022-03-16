<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );
class rsssl_ssllabs {
	function __construct() {

	}

	public function get( $state, $clear_cache = false ){
		$ip = get_option( 'rsssl_ssltest_endpoint_ip' );
		$domain = 'ziprecipes.net';
		$message = ' empty message';

		if ( $state==='initial' && !$ip ) {
			return ['html' => '<div class="rsssl-ssltest"><div class="rsssl-ssltest-element">'.__("Start a test to see your SSL rating","really-simple-ssl").'</div></div>', 'progress' => 0];
		} else if ( $state==='clearcache' ) {
			update_option( 'rsssl_ssltest_endpoint_ip', false );
			update_option( 'rsssl_ssltest_base_request', false );
			update_option( 'rsssl_ssltest_endpoint', false);
			$ip = false;
		}

		if ( ! $ip ) {
			$url      = "https://api.ssllabs.com/api/v3/analyze?host=$domain";
			if ( $state==='clearcache') $url.= '&startNew=on';
			$response = wp_remote_get( $url );
			$status   = wp_remote_retrieve_response_code( $response );
			$body     = wp_remote_retrieve_body( $response );
			if ( $status == 200 ) {
				$body = json_decode( $body );
				//get active test
				update_option( 'rsssl_ssltest_base_request', $body );
				$message = $this->get_message();
				if ( $body->status === 'READY' && isset( $body->endpoints ) && is_array( $body->endpoints ) ) {
					$ip = $body->endpoints[0]->ipAddress;
					update_option( 'rsssl_ssltest_endpoint_ip', $body->endpoints[0]->ipAddress );
				}
			}

		} elseif ( !get_option( 'rsssl_ssltest_endpoint') ) {
			$url      = "https://api.ssllabs.com/api/v3/getEndpointData?host=$domain&s=" . $ip;
			$response = wp_remote_get( $url );
			$status   = wp_remote_retrieve_response_code( $response );
			$endpoint_body     = wp_remote_retrieve_body( $response );


			if ( $status == 200 ) {
				$endpoint_body = json_decode( $endpoint_body );
				$message = __('Finalizing results','really-simple-ssl');
				update_option( 'rsssl_ssltest_endpoint', $endpoint_body );
			}
		}

		$total_progress = $this->get_progress();
		$body = get_option( 'rsssl_ssltest_base_request');
        $html_arr[] = __('Progress:','really-simple-ssl').' '.$total_progress.'%';
		$html_arr[] = __('Host:','really-simple-ssl').' '.$domain;
		$html_arr[] = $ip;
		$html_arr[] = __('Servername:','really-simple-ssl').' '.$this->get_server();
		if ( $total_progress<100 ){
			$html_arr[] = $message;
		} else {
			$date = date(get_option('date_format'), substr($body->testTime, 0, 10));
			$time = date(get_option('time_format'), substr($body->testTime, 0, 10));
			$html_arr[] = __('Last test:','really-simple-ssl').' '.$date.' - '.$time;
			$html_arr[] = $this->supports_only_secure_tls() ? __('Secure TLS','really-simple-ssl') : __('Supports insecure TLS version','really-simple-ssl');
			$html_arr[] = $this->has_hsts() ? __('HSTS enabled','really-simple-ssl') : __('HSTS not enabled','really-simple-ssl');
			$html_arr[] = $this->has_warnings() ? __('Warnings detected, see the full report for details.','really-simple-ssl') : __("No warnings", 'really-simple-ssl');
		}
		$html = '<div class="rsssl-ssltest"><div><div>'.implode('</div><div>', $html_arr ).'</div></div><div class="rsssl-grade"><span>'.$body->endpoints[0]->grade.'</span></div></div>';
		$html .= '<div class="rsssl-detailed-report"><a href="https://www.ssllabs.com/analyze.html?d='.urlencode($domain).'" target="_blank">'.__("View details report on Qualys SSL Labs", "really-simple-ssl").'</a></div>';
		return ['html' => $html, 'progress' => $total_progress ];
	}

	/**
	 * Check if HSTS is enabled
	 * @return bool
	 */
	private function has_hsts(){
		$endpoint_body = get_option( 'rsssl_ssltest_endpoint');
		return $endpoint_body->details->hstsPolicy->status === 'present';
	}

	/**
	 * Check if insecure versions are supported
	 * @return bool
	 */
	private function supports_only_secure_tls(){
		$endpoint_body = get_option( 'rsssl_ssltest_endpoint');
		if ( !isset($endpoint_body->details->protocols) ) {
			return true;
		}
		$protocols = $endpoint_body->details->protocols;
		foreach ( $protocols as $protocol ) {
			if ($protocol->name==='TLS' && version_compare($protocol->version,'1.2','<')) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Get activate test
	 * @return string
	 */

	private function get_message(){
		$body = get_option('rsssl_ssltest_base_request');
		$message = '';
		if (isset($body->endpoints) && is_array($body->endpoints) ) {
			$endpoints = $body->endpoints;
			foreach ($endpoints as $endpoint){
				$message = $endpoint->statusDetailsMessage;
				if ( $endpoint->statusMessage==='In progress'){
					return $message;
				}
			}
		}
		//if none are in progress, get the 'ready' message
		return $message;
	}

	/**
	 * Get server
	 * @return string
	 */
	private function get_server(){
		$body = get_option('rsssl_ssltest_base_request');
		if ($body && isset($body->endpoints) && is_array($body->endpoints) ) {
			$endpoints = $body->endpoints;
			$endpoints = array_reverse($endpoints);
			foreach ($endpoints as $endpoint){
				return $endpoint->serverName;
			}
		}
		return __('Searching...','really-simple-ssl');

	}

	/**
	 * Check if there are any warnings
	 * @return bool
	 */
	private function has_warnings(){
		$body = get_option('rsssl_ssltest_base_request');
		if ($body && isset($body->endpoints) && is_array($body->endpoints) ) {
			$endpoints = $body->endpoints;
			foreach ($endpoints as $endpoint){
				if ( $endpoint->hasWarnings){
					return true;
				}
			}
		}
		return false;
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
		$endpoint_body = get_option( 'rsssl_ssltest_endpoint');

		if ($body && isset($body->endpoints) && is_array($body->endpoints) ) {
			$endpoints = $body->endpoints;
			$total = count($endpoints);
			$progress = 0;
			foreach ($endpoints as $endpoint){
				$progress += $endpoint->progress;
			}
			$total_progress = $progress/$total;
		}

		//make sure the last endpoint gets retrieved. We add 5% for that step
		if ( $total_progress>5 ){
			$total_progress -= 5;
			if ( $endpoint_body ) {
				$total_progress += 5;
			}
		}

		$total_progress =  $total_progress==0 ? 1 : $total_progress;
		return ROUND($total_progress,0);
	}
}



