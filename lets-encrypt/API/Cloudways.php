<?php
/**
 *
 */
class rsssl_Cloudways {
	private $email;
	private $api_key;
	public $ssl_installation_url;

	/**
	 * Initiates the cloudways class.
	 *
	 * @param string $email
	 * @param string $api_key
	 */

	public function __construct( ) {
		$this->email             = rsssl_get_value('cloudways_user_email');
		$this->api_key           = rsssl_get_value('cloudways_api_key');
		$this->ssl_installation_url = "";
	}

	/**
	 *
	 * @param string $method      GET|POST|PUT|DELETE
	 * @param string $url         relative URL for the call
	 * @param string $accessToken Access token generated using OAuth Call
	 * @param array   $post        Optional post data for the call
	 *
	 * @return RSSSL_RESPONSE
	 */
	private function callCloudwaysAPI( $method, $url, $accessToken, $post = [] ) {
		$baseURL = 'https://api.cloudways.com/api/v1/';
		try {
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $method );
			curl_setopt( $ch, CURLOPT_URL, $baseURL . $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			//curl_setopt($ch, CURLOPT_HEADER, 1);
			//Set Authorization Header
			if ( $accessToken ) {
				curl_setopt( $ch, CURLOPT_HTTPHEADER, [ 'Authorization: Bearer ' . $accessToken ] );
			}

			//Set Post Parameters
			$encoded = '';
			if ( count( $post ) ) {
				foreach ( $post as $name => $value ) {
					$encoded .= urlencode( $name ) . '=' . urlencode( $value ) . '&';
				}
				$encoded = substr( $encoded, 0, strlen( $encoded ) - 1 );

				curl_setopt( $ch, CURLOPT_POSTFIELDS, $encoded );
				curl_setopt( $ch, CURLOPT_POST, 1 );
			}

			$output = curl_exec( $ch );
			error_log("API Response ###");
			error_log(print_r($output, true));

			$httpcode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
			if ($output && isset($output->error_description)) {
				return new RSSSL_RESPONSE( 'error', 'retry', $output->error_description );
			} else if ( $httpcode != '200' ) {
				$message = 'An error occurred code: ' . $httpcode . ' output: ' . substr( $output, 0, 10000 );
				error_log(print_r($message, true));
				return new RSSSL_RESPONSE( 'error', 'retry', $message );
			}
			curl_close( $ch );
			return new RSSSL_RESPONSE( 'success', 'continue', '', json_decode( $output ) );
		} catch(Exception $e) {
			error_log(print_r($e,true));
			return new RSSSL_RESPONSE( 'error', 'retry', $e->getMessage() );
		}
	}


	/**
	 * Get an access token
	 * @return RSSSL_RESPONSE
	 */

	private function getAccessToken() {
		error_log("try retrieving access token");
		$accessToken = get_transient('rsssl_cw_t');
		if (!$accessToken) {
			error_log("not found, get new");

			$response = $this->callCloudwaysAPI( 'POST', '/oauth/access_token', null, [ 'email' => $this->email, 'api_key' => $this->api_key ] );
			error_log(print_r($response, true));
			if ($response->status === 'success' ) {
				$accessToken   = $response->output->access_token;
				set_transient('rsssl_cw_t', $accessToken, 1800);
			} else {
				return new RSSSL_RESPONSE( 'error', 'retry', $response->message );
			}
		}
		return new RSSSL_RESPONSE( 'success', 'continue', '',$accessToken );
	}

	/**
	 * @param array $domains
	 *
	 * @return RSSSL_RESPONSE
	 */
	public function installSSL($domains){
		$response = $this->getAccessToken();
		if ( $response->status !== 'success' ) {
			return new RSSSL_RESPONSE('error','retry',$response->message);
		}
		$accessToken = $response->output;

		$response = $this->getServerInfo();
		if ($response->status === 'success' ) {
			$server_id = get_transient('rsssl_cw_app_id');
			$app_id = get_transient('rsssl_cw_server_id' );
			$response = $this->callCloudWaysAPI( 'POST', 'security/lets_encrypt_install', $accessToken,
				[
					'server_id' => $server_id,
					'app_id' => $app_id,
					'ssl_email' => $this->email,
					'wild_card' => false,
					'ssl_domains' => $domains,
				]
			);
		}

		if ( $response->status === 'success' ) {
			if (isset($response->output->operation_id)) {
				$status = 'success';
				$action = 'continue';
				$message = __("Successfully installed Lets Encrypt","really-simple-ssl");
			} else {
				$status = 'error';
				$action = 'retry';
				$message = $response->message;
			}

		} else {
			$status = 'error';
			$action = 'retry';
			$message = __("Error installing Lets Encrypt","really-simple-ssl");
		}

		return new RSSSL_RESPONSE( $status, $action, $message );
	}

	/**
	 *
	 * @return RSSSL_RESPONSE
	 */
	public function enableAutoRenew(){
		$response = $this->getAccessToken();
		if ( $response->status !== 'success' ) {
			return new RSSSL_RESPONSE('error','retry', __("Failed retrieving access token","really-simple-ssl"));
		}
		$accessToken = $response->output;

		$response = $this->getServerInfo();
		if ($response->status === 'success' ) {
			$server_id = get_transient('rsssl_cw_app_id');
			$app_id = get_transient('rsssl_cw_server_id' );
			$response = $this->callCloudWaysAPI( 'POST', 'security/lets_encrypt_auto', $accessToken,
				[
					'server_id' => $server_id,
					'app_id' => $app_id,
					'auto' => true,
				]
			);
		}

		if ( $response->status === 'success' ) {
			$status = 'success';
			$action = 'continue';
			$message = __("Successfully installed Lets Encrypt","really-simple-ssl");
		} elseif ($response->status === 'error') {
			$status = 'error';
			$action = 'retry';
			$message = $response->message;
		} else {
			$status = 'error';
			$action = 'retry';
			$message = __("Error enabling auto renew for Lets Encrypt","really-simple-ssl");
		}

		return new RSSSL_RESPONSE( $status, $action, $message );
	}


	/**
	 * Get the server id and app id
	 *
	 * @return RSSSL_RESPONSE
	 */
	public function getServerInfo(){
		if ( get_transient('rsssl_cw_app_id') && get_transient('rsssl_cw_server_id' ) ) {
			$status = 'success';
			$action = 'continue';
			$message = __("Successfully retrieved server id and app id","really-simple-ssl");
			return new RSSSL_RESPONSE( $status, $action, $message );
		}

		$response = $this->getAccessToken();
		if ( $response->status !== 'success' ) {
			return new RSSSL_RESPONSE('error','retry', $response->message);
		}
		$accessToken = $response->output;

		$response = $this->callCloudwaysAPI('GET', '/server', $accessToken );
		$success = false;
		if ($response->status === 'success') {
			$serverList = $response->output;
			$servers = $serverList->servers;
			error_log(print_r($servers, true));
			foreach ($servers as $server ){
				$apps = $server->apps;
				foreach ($apps as $app ){
					$app_domain = $app->app_fqdn;
					error_log("app domain ".$app_domain);
					$this_site_domain = str_replace(array('https://', 'http://', 'www.'), '',site_url());
					if (strpos($app_domain, $this_site_domain) !== false ) {
						$success = true;
						set_transient('rsssl_cw_app_id', $app->id, WEEK_IN_SECONDS);
						set_transient('rsssl_cw_server_id', $server->id, WEEK_IN_SECONDS);
						break 2;
					}
				}
			}
		}

		if ( $success ) {
			$status = 'success';
			$action = 'continue';
			$message = __("Successfully retrieved server id and app id","really-simple-ssl");
		} else {
			$status = 'error';
			$action = 'retry';
			if ( isset($serverList->error_description) ) {
				$message = $serverList->error_description;
			} else {
				$message = __("Could not retrieve server list","really-simple-ssl");
			}
		}

		return new RSSSL_RESPONSE( $status, $action, $message );
	}

}

