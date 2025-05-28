<?php
defined('ABSPATH') or die();
/**
 * Prevent User Enumeration
 * @return void
 */
function rsssl_check_user_enumeration() {
	if ( ! is_user_logged_in() && isset( $_REQUEST['author'] ) ) {
		if ( preg_match( '/\\d/', $_REQUEST['author'] ) > 0 ) {
			wp_die( sprintf(__( 'forbidden - number in author name not allowed = %s', 'really-simple-ssl' ), esc_html( $_REQUEST['author'] ) ) );
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

/**
 * Prevent WP JSON API User Enumeration
 * Return 401 Unauthorized
 */
if ( !is_user_logged_in() || !current_user_can('edit_posts') ) {
	add_filter( 'rest_endpoints', function ( $endpoints ) {
		if ( isset( $endpoints['/wp/v2/users'] ) ) {
			// Save the original endpoint
			$original_endpoint = $endpoints['/wp/v2/users'];

			// Override the GET callback
			$endpoints['/wp/v2/users'][0]['callback'] = function() {
				return new WP_Error(
					'rest_user_cannot_view',
					__( 'Sorry, you are not allowed to access users without authentication.', 'really-simple-ssl' ),
					array( 'status' => 401 )
				);
			};

			// Preserve the original args and permission callback
			$endpoints['/wp/v2/users'][0]['args'] = $original_endpoint[0]['args'];
			$endpoints['/wp/v2/users'][0]['permission_callback'] = '__return_true';
		}

		if ( isset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] ) ) {
			// Save the original endpoint
			$original_endpoint = $endpoints['/wp/v2/users/(?P<id>[\d]+)'];

			// Override the GET callback
			$endpoints['/wp/v2/users/(?P<id>[\d]+)'][0]['callback'] = function() {
				return new WP_Error(
					'rest_user_cannot_view',
					__( 'Sorry, you are not allowed to access user data without authentication.', 'really-simple-ssl' ),
					array( 'status' => 401 )
				);
			};

			// Preserve the original args and permission callback
			$endpoints['/wp/v2/users/(?P<id>[\d]+)'][0]['args'] = $original_endpoint[0]['args'];
			$endpoints['/wp/v2/users/(?P<id>[\d]+)'][0]['permission_callback'] = '__return_true';
		}

		return $endpoints;
	} );
}

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