<?php
/**
 * @package PLESK
 * @author Rogier Lankhorst
 * @copyright  Copyright (C) 2021, Rogier Lankhorst
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
 * @link       https://really-simple-ssl.com
 * @since      Class available since Release 5.0.0
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

use PleskX\Api\Client;

require_once rsssl_le_path . 'vendor/autoload.php';
require_once( rsssl_le_path . 'integrations/plesk/functions.php' );

class rsssl_plesk
{
	public $host;
	private $login;
	private $password;
	public $ssl_installation_url;

	/**
	 * Initiates the Plesk class.
	 *
	 */
	public function __construct()
	{
		$password = RSSSL_LE()->letsencrypt_handler->decode( rsssl_get_option('plesk_password') );
		$host = rsssl_get_option('plesk_host');
		$this->host =  str_replace(array('http://', 'https://', ':8443'), '', $host);
		$this->login = rsssl_get_option('plesk_username');
		$this->password = $password;
		$this->ssl_installation_url = 'https://'.$this->host.":8443/smb/ssl-certificate/list/id/21";
	}

	/**
	 * Check if all creds are available
	 * @return bool
	 */
	public function credentials_available(){
		if (!empty($this->host) && !empty($this->password) && !empty($this->login)) {
			return true;
		}
		return false;
	}

	/**
	 * Install certificate
	 * @param $domains
	 *
	 * @return RSSSL_RESPONSE
	 */
	public function installSSL($domains){
		$key_file = get_option('rsssl_private_key_path');
		$cert_file = get_option('rsssl_certificate_path');
		$cabundle_file = get_option('rsssl_intermediate_path');

		try {
			$client = new Client($this->host);
			$client->setCredentials($this->login, $this->password);
			$response = $client->certificate()->install($domains, [
				'csr' => '',
				'pvt' => file_get_contents($key_file),
				'cert' => file_get_contents($cert_file),
				'ca' => file_get_contents($cabundle_file),
			]);
			update_option('rsssl_le_certificate_installed_by_rsssl', 'plesk', false);
			delete_option('rsssl_installation_error' );
			$status = 'success';
			$action = 'continue';
			$message = __('Successfully installed SSL',"really-simple-ssl");
		} catch(Exception $e) {
			update_option('rsssl_installation_error', 'plesk', false);
			$status = 'warning';
			$action = 'continue';
			$message = $e->getMessage();
		}
		return new RSSSL_RESPONSE($status, $action, $message);
	}

}

