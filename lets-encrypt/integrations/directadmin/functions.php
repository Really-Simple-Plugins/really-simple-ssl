<?php
defined( 'ABSPATH' ) or die();

function rsssl_install_directadmin(){
	if (rsssl_is_ready_for('installation')) {
		$directadmin = new rsssl_directadmin();
		$domains = RSSSL_LE()->letsencrypt_handler->get_subjects();
		$response = $directadmin->installSSL($domains);
		if ( $response->status === 'success' ) {
			update_option('rsssl_le_certificate_installed_by_rsssl', 'directadmin', false );
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
 * Add actions for direct admin
 * @param array $fields
 *
 * @return array
 */
function rsssl_directadmin_add_condition_actions($fields){
	$directadmin = new rsssl_directadmin();
	if ( $directadmin->credentials_available() ) {
		$index = array_search( 'installation', array_column( $fields, 'id' ) );
		//clear existing array
		$fields[ $index ]['actions'] = [];
		$fields[ $index ]['actions'][]
			= array(
			'description' => __( "Attempting to install certificate...", "really-simple-ssl" ),
			'action'      => 'rsssl_install_directadmin',
			'attempts'    => 1,
			'status'      => 'inactive',
		);
	}

	return $fields;
}
add_filter( 'rsssl_fields', 'rsssl_directadmin_add_condition_actions' );


