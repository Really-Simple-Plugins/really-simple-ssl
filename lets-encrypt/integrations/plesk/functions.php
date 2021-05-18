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


function rsssl_plesk_fields($fields){
	$item = array(
		'plesk_host' => array(
			'step'        => 2,
			'section'     => 1,
			'source'      => 'lets-encrypt',
			'type'        => 'text',
			'default'     => '',
			'label'       => __( "Plesk host", 'really-simple-ssl' ),
			'help'       => __( "The URL you use to access your Plesk dashboard. Ends on :8443.", 'really-simple-ssl' ),
			'required'    => true,
			'disabled'    => false,
		),
		'plesk_username' => array(
			'step'        => 2,
			'section'     => 1,
			'source'      => 'lets-encrypt',
			'type'        => 'text',
			'default'     => '',
			'label'       => __( "Plesk username", 'really-simple-ssl' ),
			'required'    => true,
			'disabled'    => false,
//			'callback_condition' => 'rsssl_plesk_api_supported',
		),
		'plesk_password' => array(
			'step'        => 2,
			'section'     => 1,
			'source'      => 'lets-encrypt',
			'type'        => 'password',
			'default'     => '',
			'label'       => __( "Plesk password", 'really-simple-ssl' ),
			'required'    => true,
			'disabled'    => false,
			'callback_condition_condition' => 'rsssl_plesk_api_supported',
		),
	);
	$fields = rsssl_insert_after_key($fields, 'other_host_type', $item );
	return $fields;
}
add_filter( 'rsssl_fields_load_types', 'rsssl_plesk_fields' );
