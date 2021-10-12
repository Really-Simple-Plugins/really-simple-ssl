<?php
defined( 'ABSPATH' ) or die();

/**
 * @package    DirectAdmin
 * @author     Rogier Lankhorst
 * @copyright  Copyright (C) 2021, Rogier Lankhorst
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
 * @link       https://really-simple-ssl.com
 * @since      Class available since Release 5.0.0
 *
 */

require_once( rsssl_le_path . 'integrations/directadmin/httpsocket.php' );
require_once( rsssl_le_path . 'integrations/directadmin/functions.php' );

class rsssl_directadmin {
	public $host;
	private $login;
	private $password;
	public $ssl_installation_url;

	/**
	 * Initiates the directadmin class.
	 *
	 */
	public function __construct() {
		$password                   = RSSSL_LE()->letsencrypt_handler->decode( rsssl_get_value( 'directadmin_password' ) );
		$host                       = rsssl_get_value( 'directadmin_host' );
		$this->host                 = str_replace( array( 'http://', 'https://', ':2222' ), '', $host );
		$this->login                = rsssl_get_value( 'directadmin_username' );
		$this->password             = $password;
		$this->ssl_installation_url = 'https://' . $this->host . "";
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

	public function installSSL( $domains ) {
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

		return $response;
	}

	/**
	 * Install certificate
	 *
	 * @param string $domain
	 *
	 * @return RSSSL_RESPONSE
	 */
	public function installSSLPerDomain( $domain ) {
		$key_file      = get_option( 'rsssl_private_key_path' );
		$cert_file     = get_option( 'rsssl_certificate_path' );
		$cabundle_file = get_option( 'rsssl_intermediate_path' );

		try {
			$server_ssl=true;
			$server_port=2222;
			$sock = new HTTPSocket;
			if ($server_ssl){
				$sock->connect("ssl://".$this->host, $server_port);
			} else {
				$sock->connect($this->host, $server_port);
			}
			$sock->set_login($this->login, $this->password);
			$sock->method = "POST";
			$sock->query('/CMD_API_SSL',
				array(
					'domain' => $domain,
					'action' => 'save',
					'type' => 'paste',
					'certificate' => file_get_contents( $key_file ) . file_get_contents( $cert_file )
				));
			$response = $sock->fetch_parsed_body();
			error_log( print_r( $response, true ) );

			//set a default error response
			$status = 'warning';
			$action = 'continue';
			$message = rsssl_get_manual_instructions_text($this->ssl_installation_url);


			//if successful, proceed to next step
			if ( empty($response['details']) && stripos($response[0], 'Error' ) ) {
				$sock->query('/CMD_SSL',
					array(
						'domain' => $domain,
						'action' => 'save',
						'type' => 'cacert',
						'active' => 'yes',
						'cacert' => file_get_contents( $cabundle_file )
					));
				$response = $sock->fetch_parsed_body();
				error_log( print_r( $response, true ) );
				if ( empty($response['details']) && stripos($response[0], 'Error' ) ) {
					$status = 'success';
					$action = 'finalize';
					$message = sprintf(__("SSL successfully installed on %s","really-simple-ssl"), $domain);
					update_option( 'rsssl_le_certificate_installed_by_rsssl', 'directadmin' );
					delete_option( 'rsssl_installation_error' );
				}
			}


		} catch ( Exception $e ) {
			error_log( print_r( $e, true ) );
			update_option( 'rsssl_installation_error', 'directadmin' );
			$status  = 'warning';
			$action  = 'continue';
			$message = $e->getMessage();
		}

		return new RSSSL_RESPONSE( $status, $action, $message );
	}

}





