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
	$schedules['rsssl_daily']   = array(
		'interval' => DAY_IN_SECONDS,
		'display'  => __( 'Once every day' )
	);
	return $schedules;
}

register_deactivation_hook( __FILE__, 'rsssl_clear_scheduled_hooks' );
function rsssl_clear_scheduled_hooks() {
	wp_clear_scheduled_hook( 'rsssl_every_day_hook' );
}



