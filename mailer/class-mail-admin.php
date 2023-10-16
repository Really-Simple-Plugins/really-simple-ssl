<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class to send an e-mail
 */

if ( !class_exists('rsssl_mailer_admin') ) {
	class rsssl_mailer_admin {

		public function __construct() {
			add_filter( 'rsssl_five_minutes_cron', array( $this, 'maybe_send_mail' ) );
			add_filter( 'rsssl_five_minutes_cron', array( $this, 'rsssl_clear_expired_tokens' ) );
			add_action( 'admin_init', array( $this, 'maybe_verify_user_email' ) );
			add_action( 'rsssl_after_save_field', array( $this, 'maybe_allow_restart_email_verification' ), 10, 4 );
		}

		/**
		 * @return void
		 *
		 * Clear expired verification tokens from DB
		 */
		public function rsssl_clear_expired_tokens() {

			$token_expiration = get_option( 'rsssl_email_verification_code_expiration' );
			if ( $token_expiration > time() ) {
				delete_option( 'rsssl_email_verification_code' );
				delete_option( 'rsssl_email_verification_code_expiration' );
			}
		}

		/**
		 * @return void
		 *
		 * Verify user e-mail
		 */
		public function maybe_verify_user_email() {

			if ( ! rsssl_user_can_manage() ) {
				return;
			}

			if ( ! isset( $_GET['rsssl_verification_code'] ) ) {
				return;
			}

			// Handle e-mail verification
			$verification_code = $_GET['rsssl_verification_code'];
			$verification_code = preg_replace( "/[^0-9]/", "", $verification_code );
			$verification_code = substr( $verification_code, 0, 6 );

			// verify code
			$user_id = get_current_user_id();
			$nonce   = $_GET['rsssl_nonce'];
			if ( ! wp_verify_nonce( $nonce, 'rsssl_email_verification_' . $user_id ) ) {
				return;
			}

			$current_time                  = time();
			$saved_verification_code       = get_option('rsssl_email_verification_code');
			$saved_verification_expiration = get_option('rsssl_email_verification_code_expiration');

			if ( $verification_code === $saved_verification_code && $saved_verification_expiration && $current_time < $saved_verification_expiration ) {
				// If the verification code is correct and hasn't expired, update the verification status
				update_option( 'rsssl_email_verification_status', 'completed', false );
				set_transient('rsssl_redirect_to_settings_page', true, HOUR_IN_SECONDS );
			}
		}

		/**
		 * @return void
		 */
		public function maybe_send_mail() {
			if ( ! rsssl_get_option( 'send_notifications_email' ) ) {
				return;
			}

			$fields     = get_option( 'rsssl_email_warning_fields', [] );
			$time_saved = get_option( 'rsssl_email_warning_fields_saved' );
			if ( ! $time_saved ) {
				return;
			}

			$thirty_minutes_ago = $time_saved < strtotime( "-10 minutes" );
			$warning_blocks     = array_column( $fields, 'email' );
			if ( $thirty_minutes_ago && count( $warning_blocks ) > 0 ) {
				//clear the option
				delete_option( 'rsssl_email_warning_fields', [] );
				delete_option( 'rsssl_email_warning_fields_saved' );
				$mailer                 = new rsssl_mailer();
				$mailer->warning_blocks = $warning_blocks;
				$mailer->send_mail();
			}
		}

		/**
		 * @return bool|void
		 *
		 * E-mail verification status callback
		 */
		public function email_verification_completed() {
			$status = get_option( 'rsssl_email_verification_status' );

			if ( $status === 'started' ) {
				return false;
			}

			if ( $status === 'completed' ) {
				return true;
			}

			if ( $status === 'email_changed' ) {
				return false;
			}

		}

		/**
		 * @param $field_id
		 * @param $field_value
		 * @param $prev_value
		 * @param $field_type
		 *
		 * @return void
		 *
		 * Maybe allow the user to re-verify their e-mail address after the notifications e-mail address has changed
		 */
		public function maybe_allow_restart_email_verification( $field_id, $field_value, $prev_value, $field_type ) {
			if ( $field_id === 'notifications_email_address' && $field_value !== $prev_value && rsssl_user_can_manage() ) {
				update_option( 'rsssl_email_verification_status', 'email_changed' );
			}
		}
	}
}