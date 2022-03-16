<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

// Add notice in backend
if ( is_admin() ) {
	add_filter('rsssl_notices', 'rsssl_db_prefix_notice', 50, 3);
}

/**
 * @param $notices
 * @return mixed
 * Notice function
 */
function rsssl_db_prefix_notice( $notices ) {
	$notices['db-prefix-notice'] = array(
		'callback' => 'rsssl_check_db_prefix',
		'score' => 5,
		'output' => array(
			'not-default' => array(
				'msg' => __("Database prefix is not default. Awesome!", "really-simple-ssl"),
				'icon' => 'open',
				'dismissible' => true,
			),
			'default' => array(
				'msg' => __("Database prefix set to default wp_", "really-simple-ssl"),
				'icon' => 'open',
				'dismissible' => true,
			),
		),
	);

	return $notices;
}

/**
 * Check DB prefix
 */
function rsssl_check_db_prefix() {
	global $wpdb;

	if ( $wpdb->prefix !== 'wp_' ) {
		return 'not-default';
	}
	else {
		return 'default';
	}
}

/**
 * Rename DB prefix
 */
function rsssl_rename_db_prefix() {

	global $wpdb;

	if ( $wpdb->prefix === 'wp_' ) {

		$tables = rsssl_get_tables_to_rename();

		foreach ( $tables as $table ) {
			$table_name = $table[0];
			$new_prefix = 'rsssl_';
			$copy = str_replace('wp_', $new_prefix, $table_name);
			$wpdb->query("CREATE TABLE IF NOT EXISTS $copy LIKE $table_name");
			$wpdb->query("INSERT IGNORE $copy SELECT * FROM $table_name");

			$options = $new_prefix . 'options';
			$options_names_old_prefix = $wpdb->get_results("SELECT * FROM $options WHERE `option_name` LIKE '%wp_%'");

			foreach ( $options_names_old_prefix as $to_replace ) {
				$new_val = str_replace('wp_', $new_prefix, $to_replace->option_name);
				// update
				$wpdb->update($new_prefix.$table_name, array('meta_key' => ));
			}

			$meta_key = $new_prefix . 'usermeta';

			$usermeta_old_prefix = $wpdb->get_results("SELECT * FROM $meta_key WHERE `meta_key` LIKE '%wp_%'");

			foreach( $usermeta_old_prefix as $to_replace_usermeta ) {
				$new_val = str_replace('wp_', $new_prefix, $to_replace_usermeta->meta_key);
				$wpdb->update();
			}

		}
	}
}

/**
 * @return array|null
 * List tables that start with prefix_
 */
function rsssl_get_tables_to_rename() {
	global $wpdb;
	return $wpdb->get_results("SHOW TABLES LIKE '".$wpdb->prefix."%'", ARRAY_N);

}

/**
 * @return void
 * Copy the database
 */
function rsssl_copy_database() {
	global $wpdb;

//	$sql = "CREATE TABLE $copy  LIKE $db_name;
//	INSERT $copy SELECT * FROM $db_name;";

}

/**
 * @return void
 * Verify database copy
 */
function rsssl_verify_database_copy() {

}

add_action('admin_init', 'rsssl_rename_db_prefix');