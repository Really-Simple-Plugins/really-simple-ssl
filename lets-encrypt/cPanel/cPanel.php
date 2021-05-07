<?php
/**
 *
 * @package FreeSSL.tech Auto
 * This PHP app issues and installs free SSL certificates in cPanel shared hosting with complete automation.
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

class rsssl_cPanel
{
    private $cpanel_host;
    private $username;
    private $password;

    /**
     * Initiates the cPanel class.
     *
     * @param string $cpanel_host
     * @param string $username
     * @param string $password
     */
    public function __construct($cpanel_host, $username, $password)
    {
	    $this->cpanel_host =  str_replace(array('http://', 'https://', ':2083'), '', $cpanel_host);;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Install an SSL certificate on the domain provided - using cPanel UAPI.
     *
     * @param string $domain
     *
     * @return bool
     */
    public function installSSL($domain)
    {
        error_log('Let\'s install the SSL now!!');
	    $key_file = get_option('rsssl_private_key_path');
	    error_log($key_file);

	    $cert_file = get_option('rsssl_certificate_path');
	    error_log($cert_file);

	    $cabundle_file = get_option('rsssl_intermediate_path');
        $request_uri = 'https://'.$this->cpanel_host.':2083/execute/SSL/install_ssl';

        // Set up the payload to send to the server.
        $payload = [
            'domain' => $domain,
            'cert' => file_get_contents($cert_file),
            'key' => file_get_contents($key_file),
            'cabundle' => file_get_contents($cabundle_file),
        ];

        $response = $this->connectUapi($request_uri, $payload);
		error_log(print_r($response, true));
        //Validate $response
        if (empty($response)) {
            error_log('The install_ssl cURL call did not return valid JSON:');
            error_log('Sorry, there was a problem installing the SSL on '.$domain);
            return 'no-response';
        }

        if ($response->status) {
            error_log('Congrats! SSL installed on '.$domain.' successfully.');
            return 'success';
        } else {
        	error_log('The install_ssl cURL call returned valid JSON, but reported errors:');
	        echo $response->errors[0].'<br />';
	        error_log('Sorry, there was a problem installing the SSL on '.$domain);
	        return 'error';
        }
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

}
