<?php
add_action( 'rsssl_notice_include_alias', 'rsssl_notice_include_alias', 10, 1 );
function rsssl_notice_include_alias( $args ) {
	if (!rsssl_is_subdomain() && !RSSSL_LE()->letsencrypt_handler->alias_domain_available() ) {
		if (strpos(site_url(), 'www.') !== false ) {
			rsssl_sidebar_notice(  __( "The non-www version of your site does not point to this website. This is recommended, as it will allow you to add it to the certificate as well.", 'complianz-gdpr' ), 'warning' );
		} else {
			rsssl_sidebar_notice(  __( "The www version of your site does not point to this website. This is recommended, as it will allow you to add it to the certificate as well.", 'complianz-gdpr' ), 'warning' );
		}
	}
}

/**
 * Show notice if certificate needs to be renewed.
 *
 * @param array $notices
 *
 * @return array
 */
function rsssl_le_get_notices_list($notices) {
	//expiration date requests are cached.
	$valid    = RSSSL()->rsssl_certificate->is_valid();
	$certinfo = get_transient( 'rsssl_certinfo' );
	$end_date = isset( $certinfo['validTo_time_t'] ) ? $certinfo['validTo_time_t'] : false;
	//if the certificate expires within the grace period, allow renewal
	//e.g. expiry date 30 may, now = 10 may => grace period 9 june.
	$expiry_date = ! empty( $end_date ) ? date( get_option( 'date_format' ), $end_date ) : false;
	$renew_link  = rsssl_letsencrypt_wizard_url();
	$link_open   = '<a href="' . $renew_link . '">';

	$response = RSSSL_LE()->letsencrypt_handler->search_ssl_installation_url();
	$url      = $response->output;

	$ssl_generate_url = add_query_arg( array( "page" => "rlrsssl_really_simple_ssl", "tab" => "letsencrypt" ), admin_url( "options-general.php" ) );

	if ( rsssl_generated_by_rsssl() ) {
		if ( $expiry_date ) {
			$notices['ssl_detected'] = array(
				'condition' => array( 'rsssl_ssl_enabled' ),
				'callback'  => 'RSSSL()->rsssl_certificate->about_to_expire',
				'score'     => 10,
				'output'    => array(
					'false' => array(
						'msg'  => sprintf( __( "Your certificate is valid to: %s", "really-simple-ssl" ), $expiry_date ),
						'icon' => 'success'
					),
					'true'  => array(
						'msg'         => sprintf( __( "Your certificate will expire on %s. You can renew it %shere%s.", "really-simple-ssl" ), $expiry_date, $link_open, '</a>' ),
						'icon'        => 'open',
						'plusone'     => true,
						'dismissible' => false,
					),
				),
			);
		}

		$notices['certificate_installation'] = array(
			'condition' => array( 'rsssl_ssl_enabled', 'RSSSL()->rsssl_certificate->about_to_expire' ),
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
						'<a target="_blank" href="' . $url . '">', '</a>' ),
					'icon'        => 'open',
					'plusone'     => true,
					'dismissible' => false,
				),
				'manual-generation'             => array(
					'msg'         => sprintf( __( "Automatic renewal of your certificate was not possible. The SSL certificate should be %srenewed%s manually.", "really-simple-ssl" ),
						'<a target="_blank" href="' . $ssl_generate_url . '">', '</a>' ),
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
		'condition' => array('rsssl_can_install_shell_addon' , 'RSSSL()->rsssl_certificate->about_to_expire'),
		'callback' => '_true_',
		'score'     => 10,
		'output'    => array(
			'true' => array(
				'msg'         => __( "Your server provides shell functionality, which offers additional methods to install SSL. If installing SSL using the default methods is not possible, you can install the shell add on.", "really-simple-ssl" )
				                 . '&nbsp;'
				                 . '<a href="https://really-simple-ssl.com/installing-ssl-using-shell-functions">'
				                 . __("Read more about this add on.","really-simple-ssl")
				                 . '</a>',
				'icon'        => 'open',
				'plusone'     => true,
				'dismissible' => true,
			),
		),
	);

	if ( get_option( 'rsssl_create_folders_in_root' ) ) {
		if ( ! get_option( 'rsssl_htaccess_file_set_key' ) || ! get_option( 'rsssl_htaccess_file_set_certs' ) || ! get_option( 'rsssl_htaccess_file_set_ssl' ) ) {
			$notices['root_files_not_protected'] = array(
				'condition' => array( 'rsssl_ssl_enabled' ),
				'callback'  => '_true_',
				'score'     => 10,
				'output'    => array(
					'true' => array(
						'msg'         => __( "Your Key and Certificate directories are not properly protected.", "really-simple-ssl" )
						                 . rsssl_read_more( "https://really-simple-ssl.com/protect-ssl-generation-directories" ),
						'icon'        => 'warning',
						'plusone'     => true,
						'dismissible' => false,
					),
				),
			);
		}
	}


	return $notices;
}
add_filter( 'rsssl_notices', 'rsssl_le_get_notices_list', 30, 1 );

/**
 * 	DNS is only necessary for multisite with subdomains, or with domain mapping.
 *  On other setups, directory verification is the easiest.
 *  On  cPanel, there are several subdirectories like mail. etc. which can only get an SSL with a wildcard cert.
 *  For this reason, this option only appears when on cPanel
 *
 * @param $fields
 *
 * @return array
 */

function rsssl_le_custom_field_notices($fields){

	if ( rsssl_is_cpanel() ) {
		if( get_option('rsssl_verification_type') === 'DNS' ) {
			$fields['email_address']['help'] =
				__("You have switched to DNS verification.","really-simple-ssl").'&nbsp;'.
				__("You can switch back to directory verification here.","really-simple-ssl").
				'<br><br><button class="button button-default" name="rsssl-switch-to-directory">'.__("Switch to directory verification", "really-simple-ssl").'</button>';
		} else {
			$fields['email_address']['help'] =
				sprintf(__("If you also want to secure subdomains like mail.domain.com, cpanel.domain.com, you have to use the %sDNS%s challenge.","really-simple-ssl"),'<a target="_blank" href="https://really-simple-ssl.com/lets-encrypt-authorization-with-dns">', '</a>').'&nbsp;'.
				__("Please note that auto-renewal with a DNS challenge might not be possible.","really-simple-ssl").
				'<br><br><button class="button button-default" name="rsssl-switch-to-dns">'.__("Switch to DNS verification", "really-simple-ssl").'</button>';
		}
	}




	return $fields;
}
add_filter( 'rsssl_fields', 'rsssl_le_custom_field_notices', 30, 1 );


