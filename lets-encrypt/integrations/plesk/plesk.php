<?php
/**
 * @package Plesk
 * This PHP app issues and installs free SSL certificates in Plesk shared hosting with complete automation.
 *
 * @author rogier lankhorst
 * @copyright  Copyright (C) 2020-2021, Rogier Lankhorst
 */

use PleskX\Api\Client;

require_once rsssl_le_path . 'vendor/autoload.php';
require_once( rsssl_le_path . 'integrations/plesk/functions.php' );

class rsssl_plesk
{
	private $host;
	private $login;
	private $password;
	public $ssl_installation_url;

	/**
	 * Initiates the Plesk class.
	 *
	 * @param string $host
	 * @param string $login
	 * @param string $password
	 */
	public function __construct()
	{
		$password = RSSSL_LE()->letsencrypt_handler->decode( rsssl_get_value('plesk_password') );
		$host = rsssl_get_value('plesk_host');
		$this->host =  str_replace(array('http://', 'https://', ':8443'), '', $host);
		$this->login = rsssl_get_value('plesk_username');
		$this->password = $password;
		error_log(print_r('888:'.$this->password,true));
		$this->ssl_installation_url = 'https://'.$this->host.":8443/smb/ssl-certificate/list/id/21";
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
			error_log(print_r($response,true));
			update_option('rsssl_le_certificate_installed_by_rsssl', 'plesk');
			delete_option('rsssl_installation_error' );
			$status = 'success';
			$action = 'stop';
			$message = __('Successfully installed SSL',"really-simple-ssl");
		} catch(Exception $e) {
			error_log(print_r($e,true));
			update_option('rsssl_installation_error', 'plesk');
			$status = 'warning';
			$action = 'continue';
			$message = $e->getMessage();
			if ($e->getCode() === 1006 ){
				$message .= ' '.__("Possibly rate limited. You can try again later.","really-simple-ssl");
			}
		}
		return new RSSSL_RESPONSE($status, $action, $message);
	}

}

