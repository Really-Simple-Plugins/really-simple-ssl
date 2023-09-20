<?php
defined( 'ABSPATH' ) or die();
if ( rsssl_is_in_deactivation_list('disable-indexing') ){
	rsssl_remove_from_deactivation_list('disable-indexing');
}

/**
 * Disable indexing
 * @param array $rules
 * @return []
 */

function rsssl_disable_indexing_rules( $rules ) {
	$rules[] = ['rules' => "\n" . 'Options -Indexes', 'identifier' => 'Options -Indexes'];
	return $rules;
}
add_filter('rsssl_htaccess_security_rules', 'rsssl_disable_indexing_rules');

/**
 * Dropped suggestions for indexing in NGINX as indexing in NGINX is by default disabled.
 */
