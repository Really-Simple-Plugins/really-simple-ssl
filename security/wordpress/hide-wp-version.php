<?php
defined( 'ABSPATH' ) or die();
/**
 * @return void
 * Remove WordPress version info from page source
 */
function rsssl_remove_wp_version() {
	// remove <meta name="generator" content="WordPress VERSION" />
	add_filter( 'the_generator', function() { return '';} );
	// remove WP ?ver=5.X.X from css/js
	add_filter( 'style_loader_src', 'rsssl_remove_css_js_version', 9999 );
	add_filter( 'script_loader_src', 'rsssl_remove_css_js_version', 9999 );
	remove_action('wp_head', 'wp_generator'); // remove wordpress version
	remove_action('wp_head', 'index_rel_link'); // remove link to index page
	remove_action('wp_head', 'wlwmanifest_link'); // remove wlwmanifest.xml (needed to support windows live writer)
	remove_action('wp_head', 'wp_shortlink_wp_head', 10 ); // Remove shortlink
}
add_action('init', 'rsssl_remove_wp_version');

function rsssl_replace_wp_version($html){
	$wp_version = get_bloginfo( 'version' );
	$new_version = hash('md5', get_bloginfo( 'version' ) );
	return str_replace('?ver='.$wp_version, '?ver='.$new_version, $html);
}
add_filter('rsssl_fixer_output', 'rsssl_replace_wp_version');
/**
 * @param $src
 * @return mixed|string
 * Remove WordPress version from css and js strings
 */
function rsssl_remove_css_js_version( $src ) {

	if ( strpos( $src, '?ver=' ) && strpos( $src, 'wp-includes') ) {
		$wp_version = get_bloginfo( 'version' );
		$new_version = hash('md5', get_bloginfo( 'version' ) );
		$src = str_replace('?ver='.$wp_version, '?ver='.$new_version, $src);
	}

	return $src;
}