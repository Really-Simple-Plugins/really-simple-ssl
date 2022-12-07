<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class to send an e-mail
 */

if ( !class_exists('rsssl_mailer') ) {
	class rsssl_mailer {

		public $to;
		public $headers;
		public $body;
		public $subject;

		public function __construct( $args ) {
			if ( ! rsssl_user_can_manage() ) {
				return;
			}

			$this->to = $args['to'] ?? '';
			$this->headers = $args['headers'] ?? '';
			$this->body = $args['body'] ?? '';
			$this->subject = $args['subject'] ?? '';

			$this->send_mail();
		}

		/**
		 * Send an e-mail with the correct login URL
		 * @return void
		 */
		private function send_mail() {
			// Prevent spam
			if ( get_transient('rsssl_email_recently_sent') ) {
				return;
			}

			if ( !is_email($this->to) ){
				return;
			}

			wp_mail( $this->to, sanitize_text_field($this->subject), wp_kses_post($this->body), $this->headers );

			set_transient('rsssl_email_recently_sent', true, 15 * MINUTE_IN_SECONDS );
		}

	}
}