<?php
defined('ABSPATH') or die();
/**
 * Prevent User Enumeration
 * @return void
 */
function rsssl_check_user_enumeration() {
	if ( ! is_user_logged_in() && isset( $_REQUEST['author'] ) ) {
		if ( preg_match( '/\\d/', $_REQUEST['author'] ) > 0 ) {
			wp_die( esc_html__( 'forbidden - number in author name not allowed = ', 'really-simple-ssl' ) . esc_html( $_REQUEST['author'] ) );
		}
	}
}
add_action('init', 'rsssl_check_user_enumeration');

/**
 * @return bool
 * Remove author from Yoast sitemap
 */
function rsssl_remove_author_from_yoast_sitemap( $users ) {
	return false;
}
add_filter('wpseo_sitemap_exclude_author', 'rsssl_remove_author_from_yoast_sitemap', 10, 1 );

//PREVENT WP JSON API User Enumeration
add_filter( 'rest_endpoints', function( $endpoints ) {
	if ( isset( $endpoints['/wp/v2/users'] ) ) {
		unset( $endpoints['/wp/v2/users'] );
	}
	if ( isset( $endpoints['/wp/v2/users/(?P[\d]+)'] ) ) {
		unset( $endpoints['/wp/v2/users/(?P[\d]+)'] );
	}
	return $endpoints;
});

//prevent xml site map user enumeration
add_filter(
	'wp_sitemaps_add_provider',
	function( $provider, $name ) {
		if ( 'users' === $name ) {
			return false;
		}

		return $provider;
	},
	10,
	2
);