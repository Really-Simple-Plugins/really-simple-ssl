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
	public function __construct($host, $login='', $password='')
	{
		$this->host =  str_replace(array('http://', 'https://', ':2083'), '',$host);;
		$this->host = $host;
		$this->login = $login;
		$this->password = $password;
		$this->ssl_installation_url = $this->host.":2083/frontend/paper_lantern/ssl/index.html";
	}

	/**
	 * Install certificate
	 * @param $domains
	 *
	 * @return RSSSL_RESPONSE
	 */
	public function install($domains){
		$key_file = get_option('rsssl_private_key_path');
		$cert_file = get_option('rsssl_certificate_path');
		$cabundle_file = get_option('rsssl_intermediate_path');

		try {
			$client = new Client($this->host);
			$client->setCredentials($this->login, $this->password);
			$client->certificate()->install($domains, [
				'csr' => '',
				'pvt' => file_get_contents($key_file),
				'cert' => file_get_contents($cert_file),
				'ca' => file_get_contents($cabundle_file),
			]);
			update_option('rsssl_le_certificate_installed_by_rsssl', 'plesk');

			$status = 'success';
			$action = 'stop';
			$message = __('Successfully installed SSL',"really-simple-ssl");
		} catch(Exception $e) {
			$status = 'error';
			$action = 'stop';
			$message = $e->getMessage();
		}
		return new RSSSL_RESPONSE($status, $action, $message);
	}

}

