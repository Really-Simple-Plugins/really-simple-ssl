<?php
defined( 'ABSPATH' ) or die();

/*
  Schedule cron jobs if useCron is true
  Else start the functions.
*/
add_action( 'plugins_loaded', 'rsssl_le_schedule_cron' );
function rsssl_le_schedule_cron() {
	$useCron = true;
	if ( $useCron ) {
		if ( ! wp_next_scheduled( 'rsssl_le_every_week_hook' ) ) {
			wp_schedule_event( time(), 'rsssl_le_weekly',
				'rsssl_le_every_week_hook' );
		}

		if ( ! wp_next_scheduled( 'rsssl_le_every_day_hook' ) ) {
			wp_schedule_event( time(), 'rsssl_le_daily', 'rsssl_le_every_day_hook' );
		}
		if ( ! wp_next_scheduled( 'rsssl_le_every_five_minutes_hook' ) ) {
			wp_schedule_event( time(), 'rsssl_le_five_minutes', 'rsssl_le_every_five_minutes_hook' );
		}
		add_action( 'rsssl_le_every_week_hook', 'rsssl_le_maybe_start_renewal' );
		add_action( 'rsssl_le_every_five_minutes_hook', 'rsssl_le_check_renewal_status' );
	} else {
		add_action( 'init', 'rsssl_le_maybe_start_renewal' );
		add_action( 'init', 'rsssl_le_check_renewal_status' );
	}
}

/**
 * Check if the certificate is generated by RSSSL. If so, renew if necessary
 */
function rsssl_le_maybe_start_renewal(){
	if ( !RSSSL_LE()->letsencrypt_handler->generated_by_rsssl() ) return;

	if ( RSSSL_LE()->letsencrypt_handler->certificate_needs_renewal() ) {
		update_option("rsssl_le_start_renewal", true);
	}

	if ( RSSSL_LE()->letsencrypt_handler->certificate_requires_install_on_renewal() ) {
		update_option("rsssl_le_start_installation", true);
	}
}

function rsssl_le_check_renewal_status(){
	$renewal_active = get_option("rsssl_le_start_renewal");
	$installation_active = get_option("rsssl_le_start_installation");

	if ( $renewal_active ) {
		RSSSL_LE()->letsencrypt_handler->create_bundle_or_renew();
	} else if ( $installation_active ) {
		RSSSL_LE()->letsencrypt_handler->cron_renew_installation();
	}
}

add_filter( 'cron_schedules', 'rsssl_le_filter_cron_schedules' );
function rsssl_le_filter_cron_schedules( $schedules ) {
	$schedules['rsssl_le_weekly']  = array(
		'interval' => WEEK_IN_SECONDS,
		'display'  => __( 'Once every week' )
	);
	$schedules['rsssl_le_daily']   = array(
		'interval' => DAY_IN_SECONDS,
		'display'  => __( 'Once every day' )
	);
	$schedules['rsssl_le_five_minutes']   = array(
		'interval' => 5 * MINUTE_IN_SECONDS,
		'display'  => __( 'Once every 5 minutes' )
	);

	return $schedules;
}


register_deactivation_hook( rsssl_file, 'rsssl_le_clear_scheduled_hooks' );
function rsssl_le_clear_scheduled_hooks() {
	wp_clear_scheduled_hook( 'rsssl_le_every_week_hook' );
	wp_clear_scheduled_hook( 'rsssl_le_every_day_hook' );
}




