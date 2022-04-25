<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );
class rsssl_ssllabs {
	function __construct() {

	}

	public function get( $state ){
		$ip = get_option( 'rsssl_ssltest_endpoint_ip' );
		$message = '';
		$footer_html = '';
		$disabled = false;
		$domain = 'really-simple-ssl.com';//$this->get_host();
		if (strpos($domain, 'localhost')!==false){
			return ['footerHtml'=>$footer_html,'disabled'=>true, 'html' => '<div class="rsssl-ssltest"><div class="rsssl-ssltest-element">'.__("SSL Test is not possible on localhost","really-simple-ssl").'</div></div>', 'progress' => 100];
		}

		$last_test = false;//get_option('rsssl_last_ssltest');
		$one_day_ago = strtotime('-1 day');
		if ($last_test && $last_test>$one_day_ago){
			$disabled = true;
			$footer_html = sprintf(__("Available in %s hours", "really-simple-ssl"), gmdate("H:i", $last_test-$one_day_ago));
		}

		if ( $state==='initial' && !$ip   ) {
			return ['footerHtml'=>$footer_html,'disabled'=>$disabled, 'html' => '<div class="rsssl-ssltest"><div class="rsssl-ssltest-element">'.__("Start a test to see your SSL rating","really-simple-ssl").'</div></div>', 'progress' => 100];
		} else if ( $state === 'clearcache' ) {
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
				if ( $endpoint_body && isset($endpoint_body->errors[0]->message ) && $endpoint_body->errors[0]->message==='Could not find assessment results for the host') {
					$endpoint_body = false;
					$message = __('Encountered error, restarting...','really-simple-ssl');
				}
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
			$disabled = true;
		} else {
			$test_time = substr($body->testTime, 0, 10);
			update_option('rsssl_last_ssltest', $test_time);
			$date = date(get_option('date_format'),$test_time);
			$time = date(get_option('time_format'), $test_time);
			$html_arr[] = __('Last test:','really-simple-ssl').' '.$date.' - '.$time;
			$html_arr[] = $this->supports_only_secure_tls() ? __('Secure TLS','really-simple-ssl') : __('Supports insecure TLS version','really-simple-ssl');
			$html_arr[] = $this->has_hsts() ? __('HSTS enabled','really-simple-ssl') : __('HSTS not enabled','really-simple-ssl');
			$html_arr[] = $this->has_warnings() ? __('Warnings detected, see the full report for details.','really-simple-ssl') : __("No warnings", 'really-simple-ssl');
		}
		$grade = isset($body->endpoints[0]->grade) ? $body->endpoints[0]->grade : '';
		$html = '<div class="rsssl-gridblock-progress-container"><div class="rsssl-gridblock-progress" style="width:'.$total_progress.'%"></div></div><div class="rsssl-ssltest"><div><div>'.implode('</div><div>', $html_arr ).'</div></div><div class="rsssl-grade"><span>'.$grade.'</span></div></div>';
		$url = 'https://www.ssllabs.com/analyze.html?d='.urlencode($domain);
		$class = "rsssl-complete";
		if ( $total_progress<100 ) {
			$class = "rsssl-incomplete";
			$url = '#';
		}
		$html .= '<div class="rsssl-detailed-report '.$class.'"><a href="'.$url.'" target="_blank">'.__("View details report on Qualys SSL Labs", "really-simple-ssl").'</a></div>';
		return ['footerHtml'=>$footer_html,'disabled'=>$disabled, 'html' => $html, 'progress' => $total_progress ];
	}

	/**
	 * Check if HSTS is enabled
	 * @return bool
	 */
	private function has_hsts(){
		$endpoint_body = get_option( 'rsssl_ssltest_endpoint');
		return $endpoint_body && $endpoint_body->details->hstsPolicy->status === 'present';
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
				$message = isset($endpoint->statusDetailsMessage) ? $endpoint->statusDetailsMessage : 'no message';
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
				return isset($endpoint->serverName) ? $endpoint->serverName : __('searching...', 'really-simple-ssl');
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
				$new_progress = isset($endpoint->progress) ? $endpoint->progress :  0;
				$progress += $new_progress;
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

	/**
	 * Get host of this site
	 * @return false|string
	 */
	private function get_host(){
		$parse = parse_url(site_url());
		return isset($parse['host']) ? $parse['host'] : false;
	}
}



