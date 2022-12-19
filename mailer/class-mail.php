<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class to send an e-mail
 */

if ( !class_exists('rsssl_mailer') ) {
	class rsssl_mailer {

		public $to;
		public $title;
		public $headers;
		public $message;
		public $subject;
		public $warning_blocks;
		public $error = '';

		public function __construct() {
			add_action('wp_mail_failed', array($this, 'log_mailer_errors'), 10, 1);
		}

		/**
		 * Send a test email
		 * @return array
		 */
		public function send_test_mail(){
			if (!rsssl_user_can_manage()) {
				return ['success' => false, 'message' => 'Not allowed'];
			}

			if (!rsssl_get_option('send_notifications_email')) {
				return ['success' => false, 'message' => __('Email notifications not enabled yet. Save your settings first.', "really-simple-ssl")];
			}

			$this->to = rsssl_get_option('notifications_email_address', get_bloginfo('admin_email'));
			$this->title = __("Test email", "really-simple-ssl");
			$this->message = __("Successfully sent a test email from Really Simple SSL", "really-simple-ssl");
			$this->subject = __("Really Simple SSL test email", "really-simple-ssl");
			$success = $this->send_mail(true);
			if ($success) {
				return ['success' => true, 'message' => __('Email successfully sent. Please check your mail', "really-simple-ssl")];
			}

			if (empty($this->error)) {
				$this->error = __('Error during sending of email.', "really-simple-ssl");
			}
			return ['success' => false, 'message' => $this->error];
		}

		public function log_mailer_errors( $wp_error ){
			if (is_wp_error($wp_error)) {
				$this->error = $wp_error->get_error_message();
			}
		}
		/**
		 * Send an e-mail with the correct login URL
		 *
		 * @param bool $override_rate_limit
		 *
		 * @return bool
		 */
		public function send_mail($override_rate_limit = false) {
			if (empty($this->to) || empty($this->message) || empty($this->subject) ) {
				return false;
			}

			if (empty($this->title)) {
				$this->title = __("Learn more about our features!", "really-simple-ssl");
			}

			if ( !is_email($this->to) ){
				return false;
			}

			// Prevent spam
			if ( !$override_rate_limit && get_transient('rsssl_email_recently_sent') ) {
				return false;
			}

			$template = file_get_contents(__DIR__.'/templates/email.html');

			$block_html = '';
			if (is_array($this->warning_blocks) && count($this->warning_blocks)>0) {
				$block_template = file_get_contents(__DIR__.'/templates/block.html');
				foreach ($this->warning_blocks as $warning_block){
					$block_html .= str_replace(
						['{title}','{message}','{url}'],
						[ sanitize_text_field($warning_block['title']), wp_kses_post($warning_block['message']), esc_url_raw($warning_block['url']) ],
						$block_template);
				}
			}
			$username = rsssl_get_option('new_admin_user_login');
			$body = str_replace(
				[
					'{title}',
					'{message}',
					'{warnings}',
					'{email-address}',
					'{learn-more}',
					'{site_url}',
					'{username}'
				],
				[
					sanitize_text_field( $this->title ),
					wp_kses_post( $this->message ),
					$block_html,
					$this->to,
					__( "Learn more", 'really-simple-ssl' ),
					site_url(),
					$username
				],
				$template );
			$success = wp_mail( $this->to, sanitize_text_field($this->subject), $body, array('Content-Type: text/html; charset=UTF-8') );
			set_transient('rsssl_email_recently_sent', true, 5 * MINUTE_IN_SECONDS );
			return $success;
		}

	}
}