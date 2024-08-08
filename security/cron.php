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

		if ( ! wp_next_scheduled( 'rsssl_every_three_hours_hook' ) ) {
			wp_schedule_event( time(), 'rsssl_every_three_hours', 'rsssl_every_three_hours_hook' );
		}

		if ( ! wp_next_scheduled( 'rsssl_every_five_minutes_hook' ) ) {
			wp_schedule_event( time(), 'rsssl_five_minutes', 'rsssl_every_five_minutes_hook' );
		}
		if ( ! wp_next_scheduled( 'rsssl_every_week_hook' ) ) {
			wp_schedule_event( time(), 'rsssl_weekly', 'rsssl_every_week_hook' );
		}
		if ( ! wp_next_scheduled( 'rsssl_every_month_hook' ) ) {
			wp_schedule_event( time(), 'rsssl_monthly', 'rsssl_every_month_hook' );
		}
	}
}
/**
 * Fire three hours cron hook
 * @return void
 */
function rsssl_three_hours_cron(){
	do_action('rsssl_three_hours_cron');
}
add_action( 'rsssl_every_three_hours_hook', 'rsssl_three_hours_cron' );

/**
 * Fire daily cron hook
 */
function rsssl_daily_cron(){
	do_action('rsssl_daily_cron');
}
add_action( 'rsssl_every_day_hook', 'rsssl_daily_cron' );
/**
 * Fire five minutes cron hook
 */
function rsssl_five_minutes_cron() {
	do_action( 'rsssl_five_minutes_cron' );
}
add_action( 'rsssl_every_five_minutes_hook', 'rsssl_five_minutes_cron' );
/**
 * Fire weekly cron hook
 */
function rsssl_weekly_cron() {
	do_action( 'rsssl_weekly_cron' );
}
add_action( 'rsssl_every_week_hook', 'rsssl_weekly_cron' );
/**
 * Fire montly cron hook
 */
function rsssl_monthly_cron() {
	do_action( 'rsssl_monthly_cron' );
}
add_action( 'rsssl_every_month_hook', 'rsssl_monthly_cron' );


/**
 * For testing without cron enabled. Not recommended for production
 */
if ( !RSSSL_USE_CRON ) {
	add_action( 'admin_init', 'rsssl_schedule_non_cron' );
	function rsssl_schedule_non_cron(){
		do_action( 'rsssl_daily_cron' );
		do_action( 'rsssl_five_minutes_cron' );
		do_action('rsssl_week_cron');
		do_action('rsssl_month_cron');
	}
}
/**
 * Add our schedules
 * @param array $schedules
 *
 * @return array
 */
function rsssl_filter_cron_schedules( $schedules ) {
	$schedules['rsssl_five_minutes'] = array(
		'interval' => 5 * MINUTE_IN_SECONDS, // seconds
		'display' => __('Once every 5 minutes')
	);
	$schedules['rsssl_daily']   = array(
		'interval' => DAY_IN_SECONDS,
		'display'  => __( 'Once every day' )
	);
	$schedules['rsssl_every_three_hours']   = array(
		'interval' => 3 * HOUR_IN_SECONDS,
		'display'  => __( 'Every three hours' )
	);
	$schedules['rsssl_weekly']   = array(
		'interval' => WEEK_IN_SECONDS,
		'display'  => __( 'Once every week' )
	);
	$schedules['rsssl_monthly']   = array(
		'interval' => MONTH_IN_SECONDS,
		'display'  => __( 'Once every month' )
	);
	return $schedules;
}
add_filter( 'cron_schedules', 'rsssl_filter_cron_schedules' );
/**
 * Clear on deactivation
 *
 * @return void
 */
function rsssl_clear_scheduled_hooks() {
	wp_clear_scheduled_hook( 'rsssl_every_day_hook' );
	wp_clear_scheduled_hook( 'rsssl_every_week_hook' );
	wp_clear_scheduled_hook( 'rsssl_every_month_hook' );
	wp_clear_scheduled_hook( 'rsssl_every_five_minutes_hook' );
	wp_clear_scheduled_hook( 'rsssl_every_three_hours_hook' );
	wp_clear_scheduled_hook( 'rsssl_ssl_process_hook' );
}
register_deactivation_hook( rsssl_file, 'rsssl_clear_scheduled_hooks' );

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


