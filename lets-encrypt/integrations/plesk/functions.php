<?php
defined( 'ABSPATH' ) or die();

function rsssl_plesk_install(){
	if (RSSSL_LE()->letsencrypt_handler->is_ready_for('installation')) {
		$cpanel = new rsssl_cPanel();
		$domains = RSSSL_LE()->letsencrypt_handler->get_subjects();
		$response = $cpanel->installSSL($domains);
		if ( $response->status === 'success' ) {
			update_option('rsssl_le_certificate_installed_by_rsssl', 'cpanel:default');
		}
		return $response;
	} else {
		$status = 'error';
		$action = 'stop';
		$message = __("The system is not ready for the installation yet. Please run the wizard again.", "really-simple-ssl");
		return new RSSSL_RESPONSE($status, $action, $message);
	}
}


//function rsssl_plesk_fields($fields){
//	$item = array(
//
//	);
//	$fields = rsssl_insert_after_key($fields, 'other_host_type', $item );
//	return $fields;
//}
//add_filter( 'rsssl_fields_load_types', 'rsssl_plesk_fields' );
