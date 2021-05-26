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

	if ( RSSSL_LE()->letsencrypt_handler->generated_by_rsssl() ) {
		$valid = RSSSL()->rsssl_certificate->is_valid();
		$certinfo = get_transient('rsssl_certinfo');
		$end_date = isset($certinfo['validTo_time_t']) ? $certinfo['validTo_time_t'] : false;
		//if the certificate expires within the grace period, allow renewal
		//e.g. expiry date 30 may, now = 10 may => grace period 9 june.
		$expiry_date = !empty($end_date) ? date( get_option('date_format'), $end_date ) : __("(unknown)","really-simple-ssl");
		$renew_link = rsssl_letsencrypt_wizard_url();
		$link_open = '<a href="'.$renew_link.'">';

		$notices['certificate_renewal'] = array(
			'condition' => array( 'rsssl_ssl_enabled', 'RSSSL_LE()->letsencrypt_handler->generated_by_rsssl' ),
			'callback'  => 'RSSSL_LE()->letsencrypt_handler->certificate_about_to_expire',
			'score'     => 10,
			'output'    => array(
				'false' => array(
					'msg'  => sprintf( __( "Your certificate is valid to: %s", "really-simple-ssl-pro" ), $expiry_date ),
					'icon' => 'success'
				),
				'true'  => array(
					'msg'     => sprintf( __( "Your certificate will expire on %s. You can renew it %shere%s.", "really-simple-ssl-pro" ), $expiry_date, $link_open, '</a>' ),
					'icon'    => 'open',
					'plusone' => true,
					'dismissible' => false,
				),
			),
		);

		if ( RSSSL_LE()->letsencrypt_handler->certificate_install_required() ) {
			if ( RSSSL_LE()->letsencrypt_handler->certificate_automatic_install_possible() ) {
				$notices['certificate_installation'] = array(
					'condition' => array( 'rsssl_ssl_enabled' ),
					'callback'  => 'RSSSL_LE()->letsencrypt_handler->installation_failed',
					'score'     => 10,
					'output'    => array(
						'true' => array(
							'msg'  => sprintf( __( "The automatic installation of your certificate has failed. Please check your credentials, and retry the %sinstallation%s.", "really-simple-ssl-pro" ), '<a href="'.rsssl_letsencrypt_wizard_url().'">', '</a>' ),
							'icon' => 'open',
							'plusone' => true,
							'dismissible' => false,

						),
					),
				);
			} else {
				$response = RSSSL_LE()->letsencrypt_handler->search_ssl_installation_url();
				$url = $response->output;

				$notices['certificate_installation'] = array(
				'condition' => array( 'rsssl_ssl_enabled' ),
				'callback'  => 'RSSSL_LE()->letsencrypt_handler->should_start_manual_installation_renewal',
				'score'     => 10,
				'output'    => array(
					'true' => array(
						'msg'  => sprintf( __( "The SSL certificate has been renewed, and requires manual %sinstallation%s in your hosting dashboard.", "really-simple-ssl-pro" ), '<a target="_blank" href="'.$url.'">', '</a>' ),
						'icon' => 'open',
						'plusone' => true,
						'dismissible' => false,
					),
				),
			);
			}
		}

	}

	return $notices;
}
add_filter( 'rsssl_notices', 'rsssl_le_get_notices_list', 30, 1 );