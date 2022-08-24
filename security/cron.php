<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

/**
	Schedule cron jobs if useCron is true
	Else start the functions for testing
 */
define('RSSSL_USE_CRON', true );
if ( RSSSL_USE_CRON ) {
	add_action( 'plugins_loaded', 'rsssl_schedule_cron' );
	function rsssl_schedule_cron() {
		if ( ! wp_next_scheduled( 'rsssl_every_day_hook' ) ) {
			wp_schedule_event( time(), 'rsssl_daily', 'rsssl_every_day_hook' );
		}
	}
}

if ( !RSSSL_USE_CRON ) {
	add_action( 'admin_init', 'rsssl_schedule_non_cron' );
	function rsssl_schedule_non_cron(){
		do_action( 'rsssl_every_day_hook' );
	}
}

add_filter( 'cron_schedules', 'rsssl_filter_cron_schedules' );
function rsssl_filter_cron_schedules( $schedules ) {
	$schedules['oneminute'] = array(
		'interval' => 60, // seconds
		'display' => __('Once every minute')
	);
	$schedules['rsssl_daily']   = array(
		'interval' => DAY_IN_SECONDS,
		'display'  => __( 'Once every day' )
	);
	return $schedules;
}

register_deactivation_hook( rsssl_file, 'rsssl_clear_scheduled_hooks' );
function rsssl_clear_scheduled_hooks() {
	wp_clear_scheduled_hook( 'rsssl_every_day_hook' );
	wp_clear_scheduled_hook( 'rsssl_ssl_process_hook' );
}

/**
 * Multisite cron
 */

add_action('plugins_loaded', 'rsssl_multisite_schedule_cron', 15);
function rsssl_multisite_schedule_cron()
{
	if (get_site_option('rsssl_ssl_activation_active') || get_site_option('rsssl_ssl_deactivation_active')) {
		if (!wp_next_scheduled('rsssl_ssl_process_hook')) {
			wp_schedule_event(time(), 'oneminute', 'rsssl_ssl_process_hook');
		}
	} else {
		wp_clear_scheduled_hook('rsssl_ssl_process_hook');
	}

	/**
	 * On some sites rsssl_ssl_process_hook will prevent conversion from happening (stuck on 0%).
	 * If that happens, user can click a link (in class-multisite.php) to fire ssl process on admin_init hook
	 *
	 */

	if (get_site_option('run_ssl_process_hook_switched') !== false) {
		add_action('admin_init', array(RSSSL()->rsssl_multisite, 'run_ssl_process'));
	} else {
		add_action('rsssl_ssl_process_hook', array(RSSSL()->rsssl_multisite, 'run_ssl_process'));
	}
}



