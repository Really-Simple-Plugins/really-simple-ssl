<?php
defined( 'ABSPATH' ) or die();

function rsssl_install_cpanel_autossl(){
	if (rsssl_is_ready_for('installation')) {
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
	if (rsssl_is_ready_for('installation')) {
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

function rsssl_cpanel_set_txt_record(){
	if ( rsssl_is_ready_for('dns-verification') ) {
		$cpanel = new rsssl_cPanel();
		$tokens = get_option('rsssl_le_dns_tokens');
		if ( !$tokens) {
			$status = 'error';
			$action = 'stop';
			$message = __('Token not generated. Please complete the previous step.',"really-simple-ssl");
			return new RSSSL_RESPONSE($status, $action, $message);
		}

		foreach ($tokens as $domain => $token){
			if (strpos($domain, '*') !== false) continue;
			$response = $cpanel->set_txt_record($domain, $token);
		}

		if ( $response->status === 'success' ) {
			update_option('rsssl_le_dns_configured_by_rsssl', true);
		}
		return $response;
	} else {
		$status = 'error';
		$action = 'stop';
		$message = __("The system is not ready for the DNS verification yet. Please run the wizard again.", "really-simple-ssl");
		return new RSSSL_RESPONSE($status, $action, $message);
	}
}




function rsssl_cpanel_add_condition_actions($steps){
	$cpanel = new rsssl_cPanel();
	if ( $cpanel->credentials_available() ) {
		//this defaults to true, if not known.
		$auto_ssl    = RSSSL_LE()->config->host_api_supported( 'cpanel:autossl' );
		$default_ssl = RSSSL_LE()->config->host_api_supported( 'cpanel:default' );

		$installation_index = array_search( 'installation', array_column( $steps['lets-encrypt'], 'id' ) );
		$dns_index = array_search( 'dns-verification', array_column( $steps['lets-encrypt'], 'id' ) );
		$installation_index ++;
		$dns_index ++;

		//clear existing array
		if ($auto_ssl || $default_ssl ) $steps['lets-encrypt'][ $installation_index ]['actions'] = array();
		if ( $auto_ssl ) {
			$steps['lets-encrypt'][ $installation_index ]['actions'][]
				= array(
				'description' => __( "Attempting to install certificate using AutoSSL...", "really-simple-ssl" ),
				'action'      => 'rsssl_install_cpanel_autossl',
				'attempts'    => 1,
			);
		}

		if ( $default_ssl ) {
			$steps['lets-encrypt'][ $dns_index ]['actions'][]
				= array(
				'description' => __( "Attempting to set DNS txt record...", "really-simple-ssl" ),
				'action'      => 'rsssl_cpanel_set_txt_record',
				'attempts'    => 1,
			);

			$steps['lets-encrypt'][ $installation_index ]['actions'][]
				= array(
				'description' => __( "Attempting to install certificate...", "really-simple-ssl" ),
				'action'      => 'rsssl_install_cpanel_default',
				'attempts'    => 1,
			);
		}
	}

	return $steps;
}

add_filter( 'rsssl_steps', 'rsssl_cpanel_add_condition_actions' );
