<?php
defined('ABSPATH') or die();
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

/**
 * Ensure the hook as a function attached to it.
 */
add_action( 'rsssl_every_day_hook', 'rsssl_daily_cron' );
function rsssl_daily_cron(){
	do_action('rsssl_daily_cron');
}

if ( !RSSSL_USE_CRON ) {
	add_action( 'admin_init', 'rsssl_schedule_non_cron' );
	function rsssl_schedule_non_cron(){
		do_action( 'rsssl_every_day_hook' );
	}
}

add_filter( 'cron_schedules', 'rsssl_filter_cron_schedules' );
function rsssl_filter_cron_schedules( $schedules ) {
	$schedules['rsssl_one_minute'] = array(
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
	if ( get_site_option('rsssl_ssl_activation_active') ) {
		if ( !wp_next_scheduled('rsssl_ssl_process_hook') ) {
			wp_schedule_event(time(), 'rsssl_one_minute', 'rsssl_ssl_process_hook');
		}
	} else {
		wp_clear_scheduled_hook('rsssl_ssl_process_hook');
	}
	add_action( 'rsssl_ssl_process_hook', array( RSSSL()->multisite, 'run_ssl_process' ) );
}


