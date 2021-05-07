<?php
//--------------------------------------------------------------------------------------
// Instructions:
//--------------------------------------------------------------------------------------
// 1) cd /usr/local/cpanel/base/frontend/paper_lantern
// 2) mkdir api_examples
// 3) cd api_examples
// 4) create a file SSL_install_ssl.live.php and put this code into that file.
// 5) In your browser login to a cPanel account.
// 6) Manually change the url from: .../frontend/paper_lantern/
//    to .../frontend/paper_lantern/api_examples/SSL_install_ssl.live.php
//--------------------------------------------------------------------------------------


class rsssl_installation {

	private static $_this;

	function __construct() {

		if ( isset( self::$_this ) ) {
			wp_die( sprintf( __( '%s is a singleton class and you cannot create a second instance.', 'really-simple-ssl' ), get_class( $this ) ) );
		}
	}


	public function install(){
		if ($this->is_cpanel()){
			$this->cpanel_install();
		}
	}
	public function is_cpanel(){
		return file_exists("/usr/local/cpanel");
	}

	public function cpanel_install(){
		// Instantiate the CPANEL object.
		require_once "/usr/local/cpanel/php/cpanel.php";
		// Connect to cPanel - only do this once.
		$cpanel = new CPANEL();

		// Call the API
		$private_key = get_option('rsssl_private_key_path');
		$certificate = get_option('rsssl_certificate_path');
		$intermediate = get_option('rsssl_intermediate_path');

		$response = $cpanel->uapi(
			'SSL',
			'install_ssl',
			array (
				'domain' => 'example.com',
				'cert' => file_get_contents($certificate),
				'key' => file_get_contents($private_key),
			)
		);

		// Handle the response
		if ($response['cpanelresult']['result']['status']) {
			$data = $response['cpanelresult']['result']['data'];
			// Do something with the $data
			// So you can see the data shape we print it here.
			print to_json($data);
		}
		else {
			// Report errors:
			print to_json($response['cpanelresult']['result']['errors']);
		}

		// Disconnect from cPanel - only do this once.
		$cpanel->end();
	}


}


//--------------------------------------------------------------------------------------
// Helper function to convert a PHP value to html printable json
//--------------------------------------------------------------------------------------
function to_json($data) {
	return json_encode($data, JSON_PRETTY_PRINT);
}