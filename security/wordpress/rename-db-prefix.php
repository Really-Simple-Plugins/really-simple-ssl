<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!");

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
 * return string
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
 * Copy all current wp_ tables
 * Replace required wp_ prefixed values with new prefix
 */
function rsssl_maybe_rename_db_prefix() {

	global $wpdb;

	if ( $wpdb->prefix === 'wp_' && ! get_option('rsssl_db_prefix_updated') ) {

        // Get all tables starting with wp_
		$tables = $wpdb->get_results("SHOW TABLES LIKE '".$wpdb->prefix."%'", ARRAY_N);;

		$new_prefix = rsssl_generate_random_string( 5 ) . '_';
		// Copy these tables with a new prefix
		foreach ( $tables as $table ) {

            $table_name = $table[0];

            $new_table = str_replace('wp_', $new_prefix, $table_name);

            $wpdb->query("CREATE TABLE IF NOT EXISTS $new_table LIKE $table_name");
            $wpdb->query("INSERT IGNORE $new_table SELECT * FROM $table_name");
        }

        // Array containing the table, column and value to update
        $to_update = array(
            1 => array (
                'table' => 'usermeta',
                'column' => 'meta_key',
                'value_no_prefix' => 'capabilities',
            ),
            2 => array(
                'table' => 'usermeta',
                'column' => 'meta_key',
                'value_no_prefix' => 'user_level',
            ),
            3 => array(
                'table' => 'usermeta',
                'column' => 'meta_key',
                'value_no_prefix' => 'autosave_draft_ids',
            ),
            4 => array(
                'table' => 'options',
                'column' => 'option_name',
                'value_no_prefix' => 'user_roles',
            ),
        );

        // Loop through array and update options accordingly
        foreach ( $to_update as $key => $option ) {
            // Generate a query for each value to update
            $table = $option['table'];
            $column = $option['column'];
            $value_no_prefix = $option['value_no_prefix'];
            $wpdb->query("UPDATE `$new_prefix$table` set `$column` = '$new_prefix$value_no_prefix' where `$column` = '$wpdb->prefix$value_no_prefix'");
        }

        // Verify DB copy
        if ( rsssl_verify_database_copy($new_prefix) !== true ) return;

        // Update the prefix in wp-config.php
        $wpconfig_path = rsssl_find_wp_config_path();
        // Update wp_ prefix to new one
        if ( is_writable( $wpconfig_path ) ) {
            $wpconfig = file_get_contents($wpconfig_path);
            $updated = str_replace('wp_', $new_prefix, $wpconfig);
            file_put_contents($wpconfig_path, $updated);

        } else {
            // Cannot update. Remove new prefixed tables
            $new_prefix_tables = $wpdb->get_results("SHOW TABLES LIKE '".$new_prefix."%'", ARRAY_N);

            foreach ( $new_prefix_tables as $new_table ) {
                $wpdb->query("DROP TABLE IF EXISTS $new_table[0]");
            }

        }

        // Remove old wp_ tables
        foreach ( $tables as $table ) {
            $wpdb->query("DROP TABLE IF EXISTS $table[0]");
        }

		// Clear DB cache
		$wpdb->flush();
        update_option('rsssl_db_prefix_updated', true);

	}
}

/**
 * @return bool
 * Verify database copy
 */
function rsssl_verify_database_copy($new_prefix) {

    global $wpdb;

    $original_tables = $wpdb->get_results("SHOW TABLES LIKE '".$wpdb->prefix."%'", ARRAY_N);
    $new_tables = $wpdb->get_results("SHOW TABLES LIKE '".$new_prefix."%'", ARRAY_N);

    if ( count( $original_tables ) === count( $new_tables ) ) {
        // Count rows in table
        return true;
    }

    return false;
}