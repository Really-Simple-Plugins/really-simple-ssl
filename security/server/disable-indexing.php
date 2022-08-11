<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );
error_log("load integration");
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
