<?php
defined( 'ABSPATH' ) or die();

function rsssl_plesk_install(){
	if (rsssl_is_ready_for('installation')) {
		$cpanel = new rsssl_plesk();
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

/**
 * Add the step to install SSL using Plesk
 * @param $steps
 *
 * @return mixed
 */
function rsssl_plesk_add_installation_step($steps){
	$plesk = new rsssl_plesk();
	if ( $plesk->credentials_available() ) {
		$index = array_search( 'installation', array_column( $steps['lets-encrypt'], 'id' ) );
		$index ++;
		$steps['lets-encrypt'][ $index ]['actions'] = array_merge(array(
			array(
				'description' => __("Installing SSL certificate using PLESK API...", "really-simple-ssl"),
				'action'=> 'rsssl_plesk_install',
				'attempts' => 1,
				'speed' => 'normal',
			)
		) , $steps['lets-encrypt'][ $index ]['actions'] );
	}

	return $steps;
}
add_filter( 'rsssl_steps', 'rsssl_plesk_add_installation_step' );
