<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );
class rsssl_ssllabs {
	function __construct() {

	}

	public function get( $state, $clear_cache = false ){
		$ip = get_option( 'rsssl_ssltest_endpoint_ip' );
		$domain = 'ziprecipes.net';
		if ( $state==='initial' && !$ip ) {
			error_log("initial state");
			return ['html' => 'initial state html', 'progress' => 0];
		} else if ( $state==='clearcache' ) {
			error_log("clear cache");
			update_option( 'rsssl_ssltest_endpoint_ip', false );
			update_option( 'rsssl_ssltest_base_request', false );
			update_option( 'rsssl_ssltest_endpoint', false);
			$ip = false;
		}

		if ( ! $ip ) {
			error_log("not ip ");
			$url      = "https://api.ssllabs.com/api/v3/analyze?host=$domain";
			error_log( "not ip, state: ".$state );
			if ( $state==='clearcache') $url.= '&startNew=on';
			error_log($url);
			$response = wp_remote_get( $url );
			$status   = wp_remote_retrieve_response_code( $response );
			$body     = wp_remote_retrieve_body( $response );
			error_log(print_r($body,true));
			if ( $status == 200 ) {
				$body = json_decode( $body );
				update_option( 'rsssl_ssltest_base_request', $body );
				if ( $body->status === 'READY' && isset( $body->endpoints ) && is_array( $body->endpoints ) ) {
					update_option( 'rsssl_ssltest_endpoint_ip', $body->endpoints[0]->ipAddress );
				}
			}
		} elseif ( !get_option( 'rsssl_ssltest_endpoint') ) {
			error_log("we have ip, but not endpoint ");
			$url      = "https://api.ssllabs.com/api/v3/getEndpointData?host=$domain&s=" . $ip;
			$response = wp_remote_get( $url );
			$status   = wp_remote_retrieve_response_code( $response );
			$endpoint_body     = wp_remote_retrieve_body( $response );
			error_log(print_r($endpoint_body,true));
			if ( $status == 200 ) {
				$endpoint_body = json_decode( $endpoint_body );

				update_option( 'rsssl_ssltest_endpoint', $endpoint_body );
			}
		}

		$total_progress = $this->get_progress();
		$body = get_option( 'rsssl_ssltest_base_request');
		error_log( print_r( $body, true ) );

		$endpoint_body = get_option( 'rsssl_ssltest_endpoint');
//		error_log( print_r( $endpoint_body, true ) );

		if (!$body || !$endpoint_body){
			if (!$body) {error_log("not body");}
			if (!$endpoint_body) {error_log("not endpoint body");}
			error_log("not body or not endpoint body");
			$output = [
				'progress' => $total_progress,
			];
		} else {
			$output = [
				'progress' => $total_progress,
			];
			if ( $total_progress >= 100 ) {
				$output['grade']      = $body->endpoints[0]->grade;
				$output['hsts']       = $endpoint_body->details->hstsPolicy->status === 'present';
				$output['warnings']   = $body->endpoints[0]->hasWarnings;
//				$output['serverName'] = $body->endpoints[0]->serverName;
			}
		}

        $html = 'progress: '.$output['progress'].'<br><br>';
		$html .= 'Host: '.$domain.'<br><br>';
		if ( $total_progress >= 100 ) {
            $html .= 'grade: '.$output['grade'].'<br><br>';
            $html .= 'Has HSTS: '.$output['hsts'].'<br><br>';
            $html .= 'Has warnings: '.$output['warnings'].'<br><br>';
//            $html .= 'Servername: '.$output['serverName'].'<br><br>';
        }
		error_log($html);
		return ['html' => $html, 'progress' => $total_progress];
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
			$total +=1;//+10 for endpoints step
			error_log("total $total");
			$progress = 0;
			foreach ($endpoints as $endpoint){
				$progress += $endpoint->progress;
			}
			if ($endpoint_body) {
				$progress += 10;
			}
			error_log("progress $progress");
			$total_progress = $progress/$total;
			$total_progress=100;

		}
		return ROUND($total_progress,0);
	}
}



