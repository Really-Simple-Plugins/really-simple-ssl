<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

function rsssl_handle_xmlrpc_request() {
    rsssl_filter_xmlrpc_requests();
	rsssl_log_xmlrpc_request();
}

/**
 * @return void
 * Filter XMLRPC requests based on whitelist/blacklist
 */
function rsssl_filter_xmlrpc_requests()
{
    // if in_array($ip, $whitelist) {
     // allow
    // } else {
        // deny
    //}

    if ( is_user_logged_in() ) {
        // allow
    }

    // default deny
    // return false;
}

/**
 * @param $method
 * @return array
 * Log XMLRPC requests
 */
function rsssl_log_xmlrpc_request()
{
	$data = array(
		'type' => 'xmlrpc',
		'action' => 'xmlrpc_request',
		'referrer' => wp_get_referer(),
		'user_id' => get_current_user_id(),
	);

	rsssl_log_to_learning_mode_table( $data );
}

/**
 * @return void
 * Maybe disable XMLRPC
 */
add_filter('xmlrpc_enabled', '__return_false');


add_action( 'template_redirect', 'rsssl_check_requests' );
function rsssl_check_requests() {

	//XML-RPC
	if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
		add_action( 'xmlrpc_call', 'rsssl_handle_xmlrpc_request' );
	}
}

/**
 * @return void
 * Add the learning mode table
 */
function rsssl_add_learning_mode_table() {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$prev_db_version = get_option( 'rsssl_learning_mode_db_version', false );
	if ( rsssl_version === $prev_db_version ) {
		return;
	}

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	global $wpdb;
	$table_name = $wpdb->prefix . "rsssl_learning_mode";

	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		type text NOT NULL,
		action text  NOT NULL,
		referer text  NOT NULL,
		user_id int(10)  NOT NULL,
		PRIMARY KEY  (id)
		) $charset_collate";

	dbDelta( $sql );

	update_option( 'rsssl_learning_mode_db_version', rsssl_version, false );
}

/**
 * @param $data
 *
 * @return void
 *
 * Log requests to learning mode table
 */
function rsssl_log_to_learning_mode_table( $data ) {

	global $wpdb;
	$table_name = $wpdb->prefix . "rsssl_learning_mode";

	$wpdb->insert( $table_name, array(
		'time'    => current_time( 'mysql' ),
		'type'    => $data['type'],
		'action'  => $data['action'],
		'referer' => $data['referer'],
		'user_id' => $data['user_id'],
	) );
}

/**
 * Add the Learning Mode table
 */

add_action( 'admin_init', 'rsssl_add_learning_mode_table' );