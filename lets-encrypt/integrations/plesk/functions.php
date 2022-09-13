<?php
defined( 'ABSPATH' ) or die();

function rsssl_plesk_install(){
	if (rsssl_is_ready_for('installation')) {
		$cpanel = new rsssl_plesk();
		$domains = RSSSL_LE()->letsencrypt_handler->get_subjects();
		$response = $cpanel->installSSL($domains);
		if ( $response->status === 'success' ) {
			update_option('rsssl_le_certificate_installed_by_rsssl', 'cpanel:default', false);
		}
		return $response;
	} else {
		$status = 'error';
		$action = 'stop';
		$message = __("The system is not ready for the installation yet. Please run the wizard again.", "really-simple-ssl");
		return new RSSSL_RESPONSE($status, $action, $message);
	}
}

/**
 * Add the step to install SSL using Plesk
 * @param array $fields
 *
 * @return array
 */
function rsssl_plesk_add_installation_step($fields){
	$plesk = new rsssl_plesk();
	if ( $plesk->credentials_available() ) {
		$index = array_search( 'installation', array_column( $fields, 'id' ) );
		$fields[ $index ]['actions'] = array_merge(array(
			array(
				'description' => __("Installing SSL certificate using PLESK API...", "really-simple-ssl"),
				'action'=> 'rsssl_plesk_install',
				'attempts' => 1,
				'status'      => 'inactive',
			)
		) , $fields[ $index ]['actions'] );
	}

	return $fields;
}
add_filter( 'rsssl_fields', 'rsssl_plesk_add_installation_step' );
