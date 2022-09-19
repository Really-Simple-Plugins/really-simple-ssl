<?php
defined('ABSPATH') or die();
/**
 * Action to disable user registration
 * @param bool $value
 * @param string $option
 *
 * @return bool
 */

function rsssl_disable_user_enumeration() {
	return true;
}
add_filter( "option_users_disable_user_enumeration", 'rsssl_disable_user_enumeration', 999 );

/**
 * Prevent User Enumeration
 * @return void
 */
function check_user_enumeration() {
	if ( ! is_user_logged_in() && isset( $_REQUEST['author'] ) ) {
		if ( preg_match( '/\\d/', $_REQUEST['author'] ) > 0 ) {
			wp_die( esc_html__( 'forbidden - number in author name not allowed = ', 'really-simple-ssl' ) . esc_html( $_REQUEST['author'] ) );
		}
	}
}
add_action('init', 'check_user_enumeration');

/**
 * @return bool
 * Remove author from Yoast sitemap
 */
function remove_author_from_yoast_sitemap( $users ) {
	return false;
}
add_filter('wpseo_sitemap_exclude_author', 'remove_author_from_yoast_sitemap', 10, 1 );

// Rss actions
//if ( rsssl_get_option('disable_rss_feeds' ) ) {
//	add_action( 'do_feed', 'rsssl_disable_rss', 1 );
//	add_action( 'do_feed_rdf', 'rsssl_disable_rss', 1 );
//	add_action( 'do_feed_rss', 'rsssl_disable_rss', 1 );
//	add_action( 'do_feed_rss2', 'rsssl_disable_rss', 1 );
//	add_action( 'do_feed_atom', 'rsssl_disable_rss', 1 );
//	add_action( 'do_feed_rss2_comments', 'rsssl_disable_rss', 1 );
//	add_action( 'do_feed_atom_comments', 'rsssl_disable_rss', 1 );
//
//	/**
//	 * Disable RSS
//	 * @return void
//	 */
//	function rsssl_disable_rss() {
//		wp_die( __('RSS Feeds disabled by user', 'really-simple-ssl') );
//	}
//}

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