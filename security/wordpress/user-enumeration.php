<?php

/**
 * @return void
 * Update option to disable user enumeration
 */
function rsssl_disable_user_enumeration() {
    update_option('disable_user_enumeration', true );
}

/**
 * @return void
 * Update option to enable user enumeration
 */
function rsssl_enable_user_enumeration() {
    update_option('disable_user_enumeration', false );
}

// User Enumeration
function check_user_enumeration() {
	if ( ! is_user_logged_in() && isset( $_REQUEST['author'] ) ) {
		if ( rsssl_contains_numbers( $_REQUEST['author'] ) ) {
			wp_die( esc_html__( 'forbidden - number in author name not allowed = ', 'really-simple-ssl' ) . esc_html( $_REQUEST['author'] ) );
		}
	}
}

add_filter('wpseo_sitemap_exclude_author', 'remove_author_from_yoast_sitemap', 10, 1 );

/**
 * @return bool
 * Remove author from Yoast sitemap
 */
function remove_author_from_yoast_sitemap( $users ) {
	return false;
}

// Rss actions
if ( rsssl_get_option('disable_rss_feeds' ) ) {
	add_action( 'do_feed', 'wpb_disable_feed', 1 );
	add_action( 'do_feed_rdf', 'wpb_disable_feed', 1 );
	add_action( 'do_feed_rss', 'wpb_disable_feed', 1 );
	add_action( 'do_feed_rss2', 'wpb_disable_feed', 1 );
	add_action( 'do_feed_atom', 'wpb_disable_feed', 1 );
	add_action( 'do_feed_rss2_comments', 'wpb_disable_feed', 1 );
	add_action( 'do_feed_atom_comments', 'wpb_disable_feed', 1 );
}

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

add_action('template_redirect', 'check_user_enumeration');