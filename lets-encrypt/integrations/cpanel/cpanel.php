<?php
defined( 'ABSPATH' ) or die();

require_once( rsssl_le_path . 'integrations/cpanel/functions.php' );
/**
 * Completely rebuilt and improved on the FreeSSL.tech Auto CPanel class by Anindya Sundar Mandal
 */
class rsssl_cPanel
{
    public $host;
    private $username;
    private $password;
    public  $ssl_installation_url;

    /**
     * Initiates the cPanel class.
     */
    public function __construct()
    {
	    $username = rsssl_get_option('cpanel_username');
	    $password = RSSSL_LE()->letsencrypt_handler->decode( rsssl_get_option('cpanel_password') );
	    $host = rsssl_get_option('cpanel_host');
	    $this->host =  str_replace( array('http://', 'https://', ':2083',':'), '', $host );
        $this->username = $username;
        $this->password = $password;
        $this->ssl_installation_url = 'https://'.$this->host.":2083/frontend/jupiter/ssl/install.html";
    }
	/**
	 * Check if all creds are available
	 * @return bool
	 */
	public function credentials_available(){
		if (!empty($this->host) && !empty($this->password) && !empty($this->username)) {
			return true;
		}
		return false;
	}

	/**
	 * Install SSL for all passed domains
	 * @param array $domains
	 *
	 * @return RSSSL_RESPONSE
	 */
    public function installSSL($domains) {
	    $response = false;

	    if ( is_array($domains) && count($domains)>0 ) {
		    foreach( $domains as $domain ) {
			    $response_item = $this->installSSLPerDomain($domain);
			    //set on first iteration
			    if ( !$response ) {
				    $response = $response_item;
			    }

			    //override if not successfull, to always get the error.
			    if ( $response->status !== 'success' ) {
				    $response = $response_item;
			    }
		    }
	    }

	    if ( !$response ) {
	    	$response = new RSSSL_RESPONSE('error', 'stop', __("No valid list of domains.", "really-simple-ssl"));
	    }

	    if ( $response->status === 'success' ) {
		    update_option('rsssl_le_certificate_installed_by_rsssl', 'cpanel:default', false);
	    }

	    return $response;
    }

    /**
     * Install an SSL certificate on the domain provided - using cPanel UAPI.
     *
     * @param string $domain
     *
     * @return RSSSL_RESPONSE
     */
    public function installSSLPerDomain($domain)
    {
	    $shell_addon_active = defined('rsssl_shell_path');

	    $key_file = get_option('rsssl_private_key_path');
	    $cert_file = get_option('rsssl_certificate_path');
	    $cabundle_file = get_option('rsssl_intermediate_path');
        $request_uri = 'https://'.$this->host.':2083/execute/SSL/install_ssl';

        $payload = [
            'domain' => $domain,
            'cert' => file_get_contents($cert_file),
            'key' => file_get_contents($key_file),
            'cabundle' => file_get_contents($cabundle_file),
        ];

	    $response_raw = $this->connectUapi($request_uri, $payload);
	    $isIpBlock = $this->isIpBlock($response_raw);
	    $isLoginError = !$isIpBlock && $this->isLoginError($response_raw);
	    $response = json_decode($response_raw);
        //Validate $response
	    if ($isIpBlock) {
		    update_option( 'rsssl_installation_error', 'cpanel:autossl', false );
		    $status  = 'error';
		    $action  = 'stop';
		    $message = __( "Your website's ip address is blocked. Please add your domain's ip address to the security policy in CPanel", "really-simple-ssl" );
	    } else if ($isLoginError) {
		    update_option('rsssl_installation_error', 'cpanel:autossl', false);
		    $status = 'error';
		    $action = 'stop';
		    $message = __("Login credentials incorrect. Please check your login credentials for cPanel.","really-simple-ssl");
	    } else if ( empty($response) ) {
	        update_option('rsssl_installation_error', 'cpanel:default', false);
	        $status = 'warning';
	        $action = $shell_addon_active ? 'skip' : 'continue';
	        $message = rsssl_get_manual_instructions_text($this->ssl_installation_url);
        } else if ($response->status) {
	        delete_option('rsssl_installation_error' );
	        $status = 'success';
	        $action = 'continue';
	        $message = sprintf(__("SSL successfully installed on %s","really-simple-ssl"), $domain);
        } else {
	        update_option('rsssl_installation_error', 'cpanel:default', false);
	        $status = 'error';
	        $action = $shell_addon_active ? 'skip' : 'continue';
	        $message = __("Errors were reported during installation","really-simple-ssl").'<br> '.$response->errors[0];
        }

		return new RSSSL_RESPONSE($status, $action, $message);
    }

	/**
	 * Based on the known output of an ip block html page, check if the user should whitelist their own website ip.
	 * @param $raw
	 *
	 * @return bool
	 */
	public function isIpBlock($raw){
		$triggers = [
			'security_policy',
			'You appear to be logging in from an unknown location',
			'unrecognized IP address',
		];
		foreach($triggers as $key => $trigger ) {
			if (strpos($raw,$trigger)!==false) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Based on the known output of an ip block html page, check if the user has entered incorrect login creds
	 * @param $raw
	 *
	 * @return bool
	 */
	public function isLoginError($raw){
		$triggers = [
			'input-field-login icon password',
			'name="pass" id="pass"',
		];
		foreach($triggers as $key => $trigger ) {
			if (strpos($raw,$trigger)!==false) {
				return true;
			}
		}
		return false;
	}
	/**
	 * @param $domains
	 *
	 * @return RSSSL_RESPONSE
	 */
    public function enableAutoSSL($domains){
    	$domains = implode(',', $domains);
	    $request_uri = 'https://'.$this->host.':2083/execute/SSL/remove_autossl_excluded_domains';
	    $payload = [
		    'domains' => $domains,
	    ];

	    $response_raw = $this->connectUapi($request_uri, $payload);
		$isIpBlock = $this->isIpBlock($response_raw);
		$response = json_decode($response_raw);
	    //Validate $response
	    if ($isIpBlock) {
		    update_option('rsssl_installation_error', 'cpanel:autossl', false);
		    $status = 'error';
		    $action = 'stop';
		    $message = __("Your website's ip address is blocked. Please add your domain's ip address to the security policy in CPanel","really-simple-ssl");
	    } else if (empty($response)) {
	    	update_option('rsssl_installation_error', 'cpanel:autossl', false);
		    $status = 'error';
		    $action = 'skip';
		    $message = rsssl_get_manual_instructions_text($this->ssl_installation_url);
	    } else if ($response->status) {
		    delete_option('rsssl_installation_error');
		    $status = 'success';
		    $action = 'finalize';
		    $message = __("SSL successfully installed on $domains","really-simple-ssl");
	    } else {
		    update_option('rsssl_installation_error', 'cpanel:autossl', false);
		    $status = 'error';
		    $action = 'skip';//we try the default next
		    $message = __("Errors were reported during installation.","really-simple-ssl").'<br> '.$response->errors[0];
	    }
	    return new RSSSL_RESPONSE($status, $action, $message);
    }

    /**
     * Connect to the cPanel using UAPI.
     *
     * @param string     $request_uri
     * @param null|array $payload
     *
     * @return mixed
     */
    public function connectUapi($request_uri, $payload = null)
    {
    	// Set up the cURL request object.
        $ch = curl_init($request_uri);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->username.':'.$this->password);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	    curl_setopt($ch, CURLOPT_BUFFERSIZE, 131072);

        if (null !== $payload) {
            // Set up a POST request with the payload.
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Make the call, and then terminate the cURL caller object.
        $curl_response = curl_exec($ch);
	    if (curl_errno($ch)) {
		    $error_msg = curl_error($ch);
	    }
        curl_close($ch);

        //return output.
        return $curl_response;
    }

	/**
	 * Set DNS TXT record using Json API through cPanel XMLAPI.
	 *
	 * @param string $domain
	 * @param string $value
	 *
	 * @return RSSSL_RESPONSE
	 */
	public function set_txt_record($domain, $value)
	{
		$args = [
			'domain' => $domain,
			'name' => '_acme-challenge',
			'type' => 'TXT',
			'txtdata' => $value,
			'ttl' => '600',
			'class' => 'IN',
			'cpanel_jsonapi_user' => $this->username,
			'cpanel_jsonapi_module' => 'ZoneEdit',
			'cpanel_jsonapi_func' => 'add_zone_record',
			'cpanel_jsonapi_apiversion' => '2',
		];

		$args = http_build_query($args, '', '&');
		$url = 'https://'.$this->host.':2083/json-api/cpanel';
		$authstr = 'Authorization: Basic '.base64_encode($this->username.':'.$this->password)."\r\n";

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_BUFFERSIZE, 131072);

		$header[0] = $authstr.
		             "Content-Type: application/x-www-form-urlencoded\r\n".
		             'Content-Length: '.\strlen($args)."\r\n"."\r\n".$args;

		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl, CURLOPT_POST, 1);

		$response = curl_exec($curl);
		curl_close($curl);

		if (false === $response) {
			return new RSSSL_RESPONSE('error', 'stop', __("Unable to connect to cPanel", "really-simple-ssl").' '.curl_error($curl));
		}

		if (true === stristr($response, '<html>')) {
			return new RSSSL_RESPONSE('error', 'stop', __("Login credentials incorrect", "really-simple-ssl"));
		}
		$response_array = json_decode($response, true);

		if ( isset($response_array['cpanelresult']['data'][0]['result']['status']) ) {
			if ($response_array['cpanelresult']['data'][0]['result']['status']) {
				$status = 'success';
				$action = 'continue';
				$message = __("Successfully added TXT record.","really-simple-ssl");
			} else {
				$status = 'warning';
				$action = 'continue';
				$message = __("Could not automatically add TXT record. Please proceed manually, following the steps below.","really-simple-ssl");
				if (isset($response_array['cpanelresult']['data'][0]['result']['statusmsg'])) {
					$message .= '<br>'.$response_array['cpanelresult']['data'][0]['result']['statusmsg'];
				}
			}
			return new RSSSL_RESPONSE($status, $action, $message);
		}

		$event_result = (bool) $response_array['cpanelresult']['event']['result'];
		$preevent_result = isset($response_array['cpanelresult']['preevent']) ? (bool) $response_array['cpanelresult']['preevent']['result'] : true; //Some cPanel doesn't provide this key. In that case, ignore it by setting 'true'.
		$postevent_result = isset($response_array['cpanelresult']['postevent']) ? (bool) $response_array['cpanelresult']['postevent']['result'] : true; //Some cPanel doesn't provide this key. In that case, ignore it by setting 'true'.

		if ($event_result && $preevent_result && $postevent_result) {
			$status = 'success';
			$action = 'continue';
			$message = __("Successfully added TXT record.","really-simple-ssl");
		} else {
			$status = 'warning';
			$action = 'continue';
			$message = __("Could not automatically add TXT record. Please proceed manually, following the steps below.","really-simple-ssl");
		}

		return new RSSSL_RESPONSE($status, $action, $message);
	}


}
