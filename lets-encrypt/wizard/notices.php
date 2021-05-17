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