<?php
/**
 * Show notice if certificate needs to be renewed.
 *
 * @param array $notices
 *
 * @return array
 */
function rsssl_le_get_notices_list($notices) {
	if ( rsssl_generated_by_rsssl() ) {
		//expiration date requests are cached.
		$valid    = RSSSL()->certificate->is_valid();
		$certinfo = get_transient( 'rsssl_certinfo' );
		$end_date = isset( $certinfo['validTo_time_t'] ) ? $certinfo['validTo_time_t'] : false;
		//if the certificate expires within the grace period, allow renewal
		//e.g. expiry date 30 may, now = 10 may => grace period 9 june.
		$expiry_date = ! empty( $end_date ) ? date( get_option( 'date_format' ), $end_date ) : false;

		if ( get_option( 'rsssl_create_folders_in_root' ) ) {
			if ( ! get_option( 'rsssl_htaccess_file_set_key' ) || ! get_option( 'rsssl_htaccess_file_set_certs' ) || ! get_option( 'rsssl_htaccess_file_set_ssl' ) ) {
				$notices['root_files_not_protected'] = array(
					'condition' => array( 'rsssl_ssl_enabled' ),
					'callback'  => '_true_',
					'score'     => 10,
					'output'    => array(
						'true' => array(
							'msg'         => __( "Your Key and Certificate directories are not properly protected.", "really-simple-ssl" ),
							'url'         => "https://really-simple-ssl.com/protect-ssl-generation-directories",
							'icon'        => 'warning',
							'plusone'     => true,
							'dismissible' => true,
						),
					),
				);
			}
		}

		if ( strpos(site_url(), 'www.') !== false ) {
			$text = __( "The non-www version of your site does not point to this website. This is recommended, as it will allow you to add it to the certificate as well.", 'really-simple-ssl' );
		} else {
			$text = __( "The www version of your site does not point to this website. This is recommended, as it will allow you to add it to the certificate as well.", 'really-simple-ssl' );
		}
		$notices['alias_domain_notice'] = array(
			'condition' => array( 'NOT rsssl_is_subdomain' ),
			'callback'  => 'RSSSL_LE()->letsencrypt_handler->alias_domain_available',
			'score'     => 10,
			'output'    => array(
				'false'  => array(
					'title' => 	 __( "Domain", 'really-simple-ssl' ),
					'msg'         => $text,
					'icon'        => 'open',
					'plusone'     => true,
					'dismissible' => true,
				),
			),
			'show_with_options' => [
				'domain',
			]
		);

		if ( $expiry_date ) {
			$notices['ssl_detected'] = array(
				'condition' => array( 'rsssl_ssl_enabled' ),
				'callback'  => 'RSSSL()->certificate->about_to_expire',
				'score'     => 10,
				'output'    => array(
					'false' => array(
						'msg'  => sprintf( __( "Your certificate is valid until: %s", "really-simple-ssl" ), $expiry_date ),
						'icon' => 'success'
					),
					'true'  => array(
						'msg'         => sprintf( __( "Your certificate will expire on %s. You can renew it %shere%s.", "really-simple-ssl" ), $expiry_date, '<a href="' . rsssl_letsencrypt_wizard_url() . '">', '</a>' ),
						'icon'        => 'open',
						'plusone'     => true,
						'dismissible' => false,
					),
				),
			);
		}

		$notices['certificate_installation'] = array(
			'condition' => array( 'rsssl_ssl_enabled', 'RSSSL()->certificate->about_to_expire' ),
			'callback'  => 'RSSSL_LE()->letsencrypt_handler->certificate_renewal_status_notice',
			'score'     => 10,
			'output'    => array(
				'automatic-installation-failed' => array(
					'msg'         => sprintf( __( "The automatic installation of your certificate has failed. Please check your credentials, and retry the %sinstallation%s.",
						"really-simple-ssl" ), '<a href="' . rsssl_letsencrypt_wizard_url() . '">', '</a>' ),
					'icon'        => 'open',
					'plusone'     => true,
					'dismissible' => false,
				),
				'manual-installation'           => array(
					'msg'         => sprintf( __( "The SSL certificate has been renewed, and requires manual %sinstallation%s in your hosting dashboard.", "really-simple-ssl" ),
						'<a href="' . rsssl_letsencrypt_wizard_url('le-installation') . '">', '</a>' ),
					'icon'        => 'open',
					'plusone'     => true,
					'dismissible' => false,
				),
				'manual-generation'             => array(
					'msg'         => sprintf( __( "Automatic renewal of your certificate was not possible. The SSL certificate should be %srenewed%s manually.", "really-simple-ssl" ),
						'<a href="' . rsssl_letsencrypt_wizard_url() . '">', '</a>' ),
					'icon'        => 'open',
					'plusone'     => true,
					'dismissible' => false,
				),
				'automatic'                     => array(
					'msg'         => __( "Your certificate will be renewed and installed automatically.", "really-simple-ssl" ),
					'icon'        => 'open',
					'plusone'     => true,
					'dismissible' => false,
				),
			),
		);
	}

	$notices['can_use_shell'] = array(
		'condition' => array('rsssl_can_install_shell_addon' , 'RSSSL()->certificate->about_to_expire'),
		'callback' => '_true_',
		'score'     => 10,
		'output'    => array(
			'true' => array(
				'msg'         => __( "Your server provides shell functionality, which offers additional methods to install SSL. If installing SSL using the default methods is not possible, you can install the shell add on.", "really-simple-ssl" ),
				'icon'        => 'open',
				'url'         => "https://really-simple-ssl.com/installing-ssl-using-shell-functions",
				'plusone'     => true,
				'dismissible' => true,
			),
		),
	);


	//show notice if the shell exec add on is not up to date
	if (function_exists('rsssl_le_load_shell_addon') && defined('rsssl_shell_version') && version_compare(rsssl_shell_version,'1.3','<')){
		$notices['old_shell_exec_plugin'] = array(
			'callback'  => '_true_',
			'score'     => 10,
			'output'    => array(
				'true' => array(
					'msg'         => __( "You are using the Really Simple SSL Shell Exec add on, but of a version not compatible with Really Simple SSL 6.0 and onwards.", "really-simple-ssl" ),
					'icon'        => 'warning',
					'url'         => "https://really-simple-ssl.com/installing-ssl-using-shell-functions",
					'plusone'     => true,
					'dismissible' => false,
				),
			),
		);
	}
	return $notices;
}
add_filter( 'rsssl_notices', 'rsssl_le_get_notices_list', 30, 1 );

/**
 * Replace the go pro or scan button with a renew SSL button when the cert should be renewed.
 */
function rsssl_le_progress_footer_renew_ssl($button){
	if ( rsssl_ssl_enabled() && RSSSL()->certificate->about_to_expire() ){
		$status = RSSSL_LE()->letsencrypt_handler->certificate_renewal_status_notice;
		switch ($status){
			case 'manual-installation':
				$button_text = __("Renew installation", "really-simple-ssl");
				break;
			case 'manual-generation':
				$button_text = __("Renew certificate", "really-simple-ssl");
				break;
			default:
				$button_text = __("Renew certificate", "really-simple-ssl");//false;
		}
		if ($button_text) {
			$url = rsssl_letsencrypt_wizard_url();
			$button = '<a href="'.$url.'" class="button button-default">'.$button_text.'</a>';
		}
	}
	return $button;
}
add_filter("rsssl_progress_footer_right", "rsssl_le_progress_footer_renew_ssl", 30);
