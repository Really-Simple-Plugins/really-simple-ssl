<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

// Add notice in backend
if ( is_admin() ) {
	add_filter('rsssl_notices', 'hide_wp_version_notice', 50, 3);
}

function hide_wp_version_notice( $notices ) {
	$notices['hide-wp-version'] = array(
		'callback' => 'rsssl_hide_wp_version_notice',
		'score' => 5,
		'output' => array(
			'visible' => array(
				'msg' => __("Your WordPress version is visible.", "really-simple-ssl"),
				'icon' => 'open',
				'dismissible' => true,
			),
		),
	);

	return $notices;
}

/**
 * @return string
 * Add a notice for this integration
 */
function rsssl_hide_wp_version_notice()
{
	if ( rsssl_src_contains_wp_version() ) {
		return 'visible';
	}

	return false;
}

/**
 * @return bool
 * Check if page source contains WordPress version information
 */

function rsssl_src_contains_wp_version() {

	// wp get version
	$wp_version = get_bloginfo( 'version' );

	if ( ! get_transient('rsssl_wp_version_detected' ) ) {

		$web_source = "";
		//check if the mixed content fixer is active
		$response = wp_remote_get( home_url() );

		if ( ! is_wp_error( $response ) ) {
			if ( is_array( $response ) ) {
				$status     = wp_remote_retrieve_response_code( $response );
				$web_source = wp_remote_retrieve_body( $response );
			}

			if ( $status != 200 ) {
				set_transient( 'rsssl_wp_version_detected', false, DAY_IN_SECONDS );

				return false;
				//no-response
			} elseif ( strpos( $web_source, $wp_version ) === false ) {
				set_transient( 'rsssl_wp_version_detected', false, DAY_IN_SECONDS );

				return false;
				// not-found
			} else {
				set_transient( 'rsssl_wp_version_detected', true, DAY_IN_SECONDS );

				return true;
			}
		}

	}

	return false;
}

/**
 * @return void
 * Remove WordPress version info from page source
 */
function rsssl_remove_wp_version() {

	// remove <meta name="generator" content="WordPress VERSION" />
	add_filter( 'the_generator', 'rsssl_remove_wp_version_head' );
	// remove WP ?ver=5.X.X from css/js
	add_filter( 'style_loader_src', 'rsssl_remove_css_js_version', 9999 );
	add_filter( 'script_loader_src', 'rsssl_remove_css_js_version', 9999 );

	remove_action('wp_head', 'wp_generator'); // remove wordpress version

	remove_action('wp_head', 'index_rel_link'); // remove link to index page
	remove_action('wp_head', 'wlwmanifest_link'); // remove wlwmanifest.xml (needed to support windows live writer)

	remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0); // Remove shortlink
}

/**
 * @return string
 * Remove WordPress version from head
 */
function rsssl_remove_wp_version_head() {
	return '';
}

/**
 * @param $src
 * @return mixed|string
 * Remove WordPress version from css and js strings
 */
function rsssl_remove_css_js_version( $src ) {
	if ( strpos( $src, '?ver=' ) && strpos( $src, 'wp-includes') ) {
		$src = remove_query_arg( 'ver', $src );
		$src = add_query_arg('cache', hash('md5', get_bloginfo( 'version' ) ), $src );
	}

	return $src;
}