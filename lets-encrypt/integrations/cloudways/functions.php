<?php defined( 'ABSPATH' ) or die();

function rsssl_cloudways_server_data(){
	require_once( rsssl_le_path . 'integrations/cloudways/cloudways.php' );
	$cloudways = new rsssl_Cloudways();
	return $cloudways->getServerInfo();
}

function rsssl_cloudways_install_ssl(){
	if (rsssl_is_ready_for('installation')) {
		require_once( rsssl_le_path . 'integrations/cloudways/cloudways.php' );
		$domains = RSSSL_LE()->letsencrypt_handler->get_subjects();
		$cloudways = new rsssl_Cloudways();
		$response =  $cloudways->installSSL($domains);
		if ($response->status === 'success') {
			update_option('rsssl_le_certificate_installed_by_rsssl', 'cloudways', false);
		}
		return $response;
	} else {
		$status = 'error';
		$action = 'stop';
		$message = __("The system is not ready for the installation yet. Please run the wizard again.", "really-simple-ssl");
		return new RSSSL_RESPONSE($status, $action, $message);
	}
}

function rsssl_cloudways_auto_renew(){
	require_once( rsssl_le_path . 'integrations/cloudways/cloudways.php' );
	$cloudways = new rsssl_Cloudways();
	return $cloudways->enableAutoRenew();
}

function rsssl_cloudways_add_condition_actions($fields){
	$index = array_search('installation',array_column($fields,'id'));
	$fields[$index]['actions'] = array(
		array(
			'description' => __("Retrieving Cloudways server data...", "really-simple-ssl"),
			'action'=> 'rsssl_cloudways_server_data',
			'attempts' => 5,
			'speed' => 'normal',
		),
		array(
			'description' => __("Installing SSL certificate...", "really-simple-ssl"),
			'action'=> 'rsssl_cloudways_install_ssl',
			'attempts' => 5,
			'speed' => 'normal',
		),
		array(
			'description' => __("Enabling auto renew...", "really-simple-ssl"),
			'action'=> 'rsssl_cloudways_auto_renew',
			'attempts' => 5,
			'speed' => 'normal',
		),
	);

	//drop store credentials field
	$creds_index = array_search('store_credentials',array_column($fields,'id'));
	unset($fields[$creds_index]);
	return $fields;
}
add_filter( 'rsssl_fields', 'rsssl_cloudways_add_condition_actions' );

