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

	public function __construct( $email, $api_key ) {
		$this->email             = $email;
		$this->api_key           = $api_key;
		$this->ssl_installation_url = "";
	}

	/**
	 *
	 * @param string $method      GET|POST|PUT|DELETE
	 * @param string $url         relative URL for the call
	 * @param string $accessToken Access token generated using OAuth Call
	 * @param array   $post        Optional post data for the call
	 *
	 * @return object Output from CW API
	 */
	private function callCloudwaysAPI( $method, $url, $accessToken, $post = [] ) {
		$baseURL = 'https://api.cloudways.com/api/v1/';

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

		$httpcode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		if ( $httpcode != '200' ) {
			die( 'An error occurred code: ' . $httpcode . ' output: ' . substr( $output, 0, 10000 ) );
		}
		curl_close( $ch );

		return json_decode( $output );
	}


	/**
	 * Get an access token
	 * @return string
	 */
	private function getAccessToken() {

		//Fetch Access Token
		$accessToken = get_transient('rsssl_cw_t');
		if (!$accessToken) {
			$tokenResponse = $this->callCloudwaysAPI( 'POST', '/oauth/access_token', null, [ 'email' => $this->email, 'api_key' => $this->api_key ] );
			$accessToken   = $tokenResponse->access_token;
			set_transient('rsssl_cw_t', $accessToken, 1800);
		}
		return $accessToken;
	}

	public function installSSL(){
		$url   = 'security/lets_encrypt_install';
		$accessToken = $this->getAccessToken();

		//server_id	Integer	Form	True Numeric id of the server
		//app_id	Integer	Form	True Numeric id of the application
		//ssl_email	String	Form	True Attached email for the certificate
		//wild_card	Boolean	Form	True Certificate with wildcard
		//ssl_domains	List(String)	Form	True Domain name(s) to be protected with a single certificate

		$addSeverResponse = $this->callCloudWaysAPI( 'POST', $url, $accessToken,
			['cloud' => 'do',
	          'region' => 'nyc3',
	          'instance_type' => '512MB',
	          'application' => 'wordpress',
	          'app_version' => '4.6.1',
	          'server_label' => 'API Test',
	          'app_label' => 'API Test',]
		);

		$operation = $addSeverResponse->server->operations[0];

		//Wait for operation to be completed
		while ( $operation->is_completed == 0 ) {
			$operation = $this->callCloudWaysAPI( 'GET', '/operation/' . $operation->id, $accessToken )->operation;
			sleep( 30 );
		}

		return new RSSSL_RESPONSE( $status, $action, $message );
	}

}

