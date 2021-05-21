<?php
defined( 'ABSPATH' ) or die();

function rsssl_install_cpanel_autossl(){
	if (RSSSL_LE()->letsencrypt_handler->is_ready_for('installation')) {
		$cpanel = new rsssl_cPanel();
		$domains = RSSSL_LE()->letsencrypt_handler->get_subjects();
		$response = $cpanel->enableAutoSSL($domains);
		if ( $response->status === 'success' ) {
			update_option('rsssl_le_certificate_installed_by_rsssl', 'cpanel:autossl');
		}
		return $response;
	} else {
		$status = 'error';
		$action = 'stop';
		$message = __("The system is not ready for the installation yet. Please run the wizard again.", "really-simple-ssl");
		return new RSSSL_RESPONSE($status, $action, $message);
	}
}

function rsssl_install_cpanel_default(){
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


function rsssl_cpanel_add_condition_actions($steps){
	error_log("run cpanel filter");

	$auto_ssl = RSSSL_LE()->config->current_host_can('cpanel:autossl');
	$default_ssl = RSSSL_LE()->config->current_host_can('cpanel:default');

	$index = array_search( 'installation', array_column( $steps['lets-encrypt'], 'id' ) );
	$index ++;
	//clear existing array
	$steps['lets-encrypt'][ $index ]['actions'] = array();

	if ($auto_ssl) {
		$steps['lets-encrypt'][ $index ]['actions'][]
			= array(
			'description' => __( "Attempting to install certificate using AutoSSL...", "really-simple-ssl" ),
			'action'      => 'rsssl_install_cpanel_autossl',
			'attempts'    => 1,
			'speed' => 'normal',
		);
	}

	if ($default_ssl) {
		$steps['lets-encrypt'][ $index ]['actions'][]
			= array(
			'description' => __( "Attempting to install certificate...", "really-simple-ssl" ),
			'action'      => 'rsssl_install_cpanel_default',
			'attempts'    => 1,
			'speed' => 'normal',
		);
	}

	return $steps;
}

add_filter( 'rsssl_steps', 'rsssl_cpanel_add_condition_actions' );
