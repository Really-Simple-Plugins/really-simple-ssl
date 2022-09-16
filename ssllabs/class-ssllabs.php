<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );
class rsssl_ssllabs {
	function __construct() {

	}

	public function get(){
//		x_log("retrieve ssl labs data");
//		x_log(get_option('rsssl_ssl_labs_data'));
//		delete_option('rsssl_ssl_labs_data');

		return get_option('rsssl_ssl_labs_data');
	}

	public function update($data) {
//		x_log($data);
//		delete_option('rsssl_ssl_labs_data');

		update_option('rsssl_ssl_labs_data', $data, false);
//		delete_option('rsssl_ssl_labs_data');

	}
}



