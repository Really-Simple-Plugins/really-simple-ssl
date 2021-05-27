<?php
defined( 'ABSPATH' ) or die();

/**
 * Based on the FreeSSL.tech Auto CPanel class
 *
 * @author Anindya Sundar Mandal <anindya@SpeedUpWebsite.info>
 * @copyright  Copyright (C) 2018-2019, Anindya Sundar Mandal
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
 * @link       https://SpeedUpWebsite.info
 * @since      Class available since Release 1.0.0
 * 
 *
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <https://www.gnu.org/licenses/>.
 * 
 */
require_once( rsssl_le_path . 'integrations/cpanel/functions.php' );

class rsssl_cPanel
{
    private $cpanel_host;
    private $username;
    private $password;
    public  $ssl_installation_url;

    /**
     * Initiates the cPanel class.
     */
    public function __construct()
    {
	    $username = rsssl_get_value('cpanel_username');
	    $password = RSSSL_LE()->letsencrypt_handler->decode( rsssl_get_value('cpanel_password') );
	    $cpanel_host = rsssl_get_value('cpanel_host');
	    $this->cpanel_host =  str_replace(array('http://', 'https://', ':2083'), '', $cpanel_host);
        $this->username = $username;
        $this->password = $password;
        $this->ssl_installation_url = 'https://'.$this->cpanel_host.":2083/frontend/paper_lantern/ssl/install.html";
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
		    update_option('rsssl_le_certificate_installed_by_rsssl', 'cpanel:default');
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
	    $key_file = get_option('rsssl_private_key_path');
	    $cert_file = get_option('rsssl_certificate_path');
	    $cabundle_file = get_option('rsssl_intermediate_path');
        $request_uri = 'https://'.$this->cpanel_host.':2083/execute/SSL/install_ssl';

        $payload = [
            'domain' => $domain,
            'cert' => file_get_contents($cert_file),
            'key' => file_get_contents($key_file),
            'cabundle' => file_get_contents($cabundle_file),
        ];

        $response = $this->connectUapi($request_uri, $payload);
        //Validate $response
        if (empty($response)) {
            error_log('Not able to login');
	        update_option('rsssl_installation_error', 'cpanel:default');
	        $status = 'warning';
	        $action = 'stop';
	        $message = rsssl_get_manual_instructions_text($this->ssl_installation_url);
        } else if ($response->status) {
	        delete_option('rsssl_installation_error' );

	        error_log('SSL successfully installed on '.$domain.' successfully.');
	        $status = 'success';
	        $action = 'continue';
	        $message = sprintf(__("SSL successfully installed on %s","really-simple-ssl"), $domain);
        } else {
	        update_option('rsssl_installation_error', 'cpanel:default');
	        error_log($response->errors[0]);
	        $status = 'error';
	        $action = 'stop';
	        $message = __("Errors were reported during installation","really-simple-ssl").'<br> '.$response->errors[0];
        }

		return new RSSSL_RESPONSE($status, $action, $message);
    }

	/**
	 * @param $domains
	 *
	 * @return RSSSL_RESPONSE
	 */
    public function enableAutoSSL($domains){
    	$domains = implode(',', $domains);
	    $request_uri = 'https://'.$this->cpanel_host.':2083/execute/SSL/remove_autossl_excluded_domains';
	    $payload = [
		    'domains' => $domains,
	    ];

	    $response = $this->connectUapi($request_uri, $payload);

	    //Validate $response
	    if (empty($response)) {
	    	update_option('rsssl_installation_error', 'cpanel:autossl');
		    error_log('The install_ssl cURL call did not return valid JSON:');
		    $status = 'error';
		    $action = 'skip';
		    $message = rsssl_get_manual_instructions_text($this->ssl_installation_url);
	    } else if ($response->status) {
		    delete_option('rsssl_installation_error');
		    error_log('Congrats! SSL installed on '.$domains.' successfully.');
		    $status = 'success';
		    $action = 'finalize';
		    $message = __("SSL successfully installed on $domains","really-simple-ssl");
	    } else {
		    update_option('rsssl_installation_error', 'cpanel:autossl');
		    error_log('The auto SSL cURL call returned valid JSON, but reported errors:');
		    error_log($response->errors[0]);
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
    	error_log("connect over ".$request_uri);
        // Set up the cURL request object.
        $ch = curl_init($request_uri);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->username.':'.$this->password);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if (null !== $payload) {
            // Set up a POST request with the payload.
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Make the call, and then terminate the cURL caller object.
        $curl_response = curl_exec($ch);
        error_log(print_r($curl_response, true));
        curl_close($ch);

        // Decode and return output.
        return json_decode($curl_response);
    }

	/**
	 * Set DNS TXT record using Json API through cPanel XMLAPI.
	 *
	 * @param string $domain
	 * @param string $txt_name
	 * @param string $txt_value
	 *
	 * @return RSSSL_RESPONSE
	 */
	public function setDnsTxt($domain, $txt_name, $txt_value)
	{
		$xmlapi = new xmlapi($this->cpanel_host, $this->username, $this->password);
		$xmlapi->set_output('json');
		$xmlapi->set_port('2083');
		$xmlapi->set_debug(1);
		$response = $xmlapi->api2_query(
			$this->username,
			'ZoneEdit',
			'add_zone_record',
			[
				'domain' => $domain,
				'name' => $txt_name,
				'type' => 'TXT',
				'txtdata' => $txt_value,
				'ttl' => '600',
				'class' => 'IN',
			]
		);

		$response_array = json_decode($response, true);

		$result = [];
		//Check status
		$event_result = (bool) $response_array['cpanelresult']['event']['result'];
		$preevent_result = isset($response_array['cpanelresult']['preevent']) ? (bool) $response_array['cpanelresult']['preevent']['result'] : true; //Some cPanel doesn't provide this key. In that case, ignore it by setting 'true'.
		$postevent_result = isset($response_array['cpanelresult']['postevent']) ? (bool) $response_array['cpanelresult']['postevent']['result'] : true; //Some cPanel doesn't provide this key. In that case, ignore it by setting 'true'.

		if ($event_result && $preevent_result && $postevent_result) {
			$result['http_code'] = 200;
			$result['body'] = $response_array;
			$status = 'success';
			$action = 'stop';
			$message = __("Successfully added TXT record.","really-simple-ssl");
		} else {
			$result['http_code'] = 404;
			$result['body'] = $response_array;
			$status = 'error';
			$action = 'skip';
			$message = __("Errors were reported during adding of TXT record.","really-simple-ssl");
		}

		return new RSSSL_RESPONSE($status, $action, $message);
	}

}
