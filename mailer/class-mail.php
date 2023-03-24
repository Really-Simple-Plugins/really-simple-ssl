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
        public $change_text;
        public $sent_to_text;
        public $what_now_text;
        public $sent_by_text;
		public $warning_blocks;
		public $error = '';

		public function __construct() {
			$this->sent_by_text = __("This email is part of the Really Simple SSL Notification System", "really-simple-ssl");
			$this->subject = __("Notification by Really Simple SSL", "really-simple-ssl");
			$this->title = __("Learn more about our features!", "really-simple-ssl");
			$this->sent_to_text = __("This email was sent to", "really-simple-ssl");
			$this->what_now_text = __( "What now?", "really-simple-ssl");
			$this->change_text = __("I didn't change any settings in the plugin.", "really-simple-ssl");
			$domain = '<a href="'.site_url().'">'.site_url().'</a>';
			$this->message = sprintf(__("You have enabled a feature on %s. We think it's important to let you know a little bit more about this feature so you can use it without worries.","really-simple-ssl"), $domain);

			add_action('wp_mail_failed', array($this, 'log_mailer_errors'), 10, 1);
		}

		/**
		 * Send a test email
		 * @return array
		 */
		public function send_test_mail(){
			if ( !rsssl_user_can_manage() ) {
				return ['success' => false, 'message' => 'Not allowed'];
			}
			$this->to = rsssl_get_option('notifications_email_address', get_bloginfo('admin_email') );
			if ( !is_email($this->to) ){
				return ['success' => false, 'message' => __('Email address not valid',"really-simple-ssl")];
			}
			$this->title = __("Really Simple SSL - Notification Test", "really-simple-ssl");
			$this->message = __("This email is confirmation that any security notices are likely to reach your inbox.", "really-simple-ssl");

			$this->warning_blocks = [
				[
					'title' => __("About notifications","really-simple-ssl"),
					'message' => __("Email notifications are only sent for important updates, security notices or when certain features are enabled.","really-simple-ssl"),
					'url' => 'https://really-simple-ssl.com/email-notifications/',
				]
			];
			$success = $this->send_mail(true);
			if ($success) {
				return ['success' => true, 'message' => __('Email sent! Please check your mail', "really-simple-ssl")];
			}

			if (empty($this->error)) {
				$this->error = __('Email could not be sent.', "really-simple-ssl");
			} else {
				$this->error = __('An error occurred:', "really-simple-ssl").'<br>'.$this->error;
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
			if ( empty($this->message) || empty($this->subject) ) {
				return false;
			}

			$this->to = rsssl_get_option('notifications_email_address', get_bloginfo('admin_email') );
			if ( !is_email($this->to) ){
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
			$login_url = wp_login_url();
			$body = str_replace(
				[
					'{title}',
					'{message}',
					'{warnings}',
					'{email-address}',
					'{learn-more}',
					'{site_url}',
					'{login_url}',
					'{username}',
                    '{change_text}',
                    '{what_now}',
                    '{sent_to_text}',
                    '{sent_by_text}'
				],
				[
					sanitize_text_field( $this->title ),
					wp_kses_post( $this->message ),
					$block_html,
					$this->to,
					__( "Learn more", 'really-simple-ssl' ),
					site_url(),
					$login_url,
					$username,
                    $this->change_text,
                    $this->what_now_text,
                    $this->sent_to_text,
                    $this->sent_by_text
				], $template );
			$success = wp_mail( $this->to, sanitize_text_field($this->subject), $body, array('Content-Type: text/html; charset=UTF-8') );
			set_transient('rsssl_email_recently_sent', true, 5 * MINUTE_IN_SECONDS );
			return $success;
		}

	}
}
