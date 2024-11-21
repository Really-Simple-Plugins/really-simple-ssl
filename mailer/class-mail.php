<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to send an e-mail
 */

if ( ! class_exists( 'rsssl_mailer' ) ) {
	class rsssl_mailer {

		public $to;
		public $title;
		public $headers;
		public $message;
		public $branded = true;
		public $subject;
		public $button_text;
		public $change_text;
		public $sent_to_text;
		public $what_now_text;
		public $sent_by_text;
		public $warning_blocks;
		public $error = '';
		public $template_filename;
		public $block_template_filename;

		public function __construct() {

			$this->sent_by_text  = __( "This email is part of the Really Simple Security Notification System", "really-simple-ssl" );
			$this->subject       = __( "Notification by Really Simple Security", "really-simple-ssl" );
			$this->button_text   = __( "Learn more", "really-simple-ssl" );
			$this->to            = rsssl_get_option( 'notifications_email_address', get_bloginfo( 'admin_email' ) );
			$this->title         = __( "Learn more about our features!", "really-simple-ssl" );
			$this->sent_to_text  = __( "This email was sent to", "really-simple-ssl" );
			$this->what_now_text = __( "Learn more", "really-simple-ssl" );
			$this->change_text   = __( "Why did I receive this email?", "really-simple-ssl" );

			$domain        = '<a href="' . site_url() . '">' . site_url() . '</a>';
			$this->message = sprintf( __( "You have enabled a feature on %s. We think it's important to let you know a little bit more about this feature so you can use it without worries.", "really-simple-ssl" ), $domain );

			add_action( 'wp_mail_failed', array( $this, 'log_mailer_errors' ), 10, 1 );

		}

		/**
		 * Send a test email
		 * @return array
		 */
		public function send_test_mail() {
			if ( ! rsssl_user_can_manage() ) {
				return [ 'success' => false, 'message' => 'Not allowed' ];
			}

			if ( ! is_email( $this->to ) ) {
				return [
					'success' => false,
					'title'   => __( "Test notification email error", 'really-simple-ssl' ),
					'message' => __( 'Email address not valid', "really-simple-ssl" ),
				];
			}
			$this->title          = __( "Really Simple Security - Notification Test", "really-simple-ssl" );
			$this->message        = __( "This email is confirmation that any security notices are likely to reach your inbox.", "really-simple-ssl" );
			$this->warning_blocks = [
				[
					'title'   => __( "About notifications", "really-simple-ssl" ),
					'message' => __( "Email notifications are only sent for important updates, security notices or when certain features are enabled.", "really-simple-ssl" ),
					'url'     => rsssl_link('email-notifications/'),
				]
			];

			return $this->send_mail( true );
		}

		public function send_verification_mail() {
			if ( ! rsssl_user_can_manage() ) {
				return [
					'success' => false,
					'message' => 'Not allowed',
					'title'   => __( "Email verification error", 'really-simple-ssl' ),
				];
			}

			$verification_code       = str_pad( rand( 0, 999999 ), 6, '0', STR_PAD_LEFT );
			$verification_expiration = strtotime( "+15 minutes" );

			// Delete existing option
			delete_option( 'rsssl_email_verification_code' );

			update_option( 'rsssl_email_verification_code', $verification_code, false );
			update_option( 'rsssl_email_verification_code_expiration', $verification_expiration, false );
			update_option( 'rsssl_email_verification_status', 'started', false );

			if ( ! is_email( $this->to ) ) {
				return [
					'success' => false,
					'title'   => __( "Email verification error", 'really-simple-ssl' ),
					'message' => __( 'Email address not valid', "really-simple-ssl" )
				];
			}

			$user_id = get_current_user_id();

			$verification_url = add_query_arg(
				array(
					'page'                    => 'really-simple-security',
					'rsssl_nonce'             => wp_create_nonce( 'rsssl_email_verification_' . $user_id ),
					'rsssl_verification_code' => $verification_code,
				),
				rsssl_admin_url([], '#settings/general')
			);

			$this->subject          = __( "Really Simple Security - Verify your email address", "really-simple-ssl" );
			$this->title            = __( "Please verify your email", "really-simple-ssl" );
			$this->message          = __('To use certain features in Really Simple Security we need to confirm emails are delivered without issues.', 'really-simple-ssl');
			$this->button_text      = __( "Verify email", "really-simple-ssl" );
			$this->warning_blocks[] = [
				'title'   => '',
				'message' => sprintf( __( "Click the button below to confirm your email address, or copy the following URL: %s", "really-simple-ssl" ), '{url}' ),
				'url'     => $verification_url,
			];

			return $this->send_mail();
		}

		public function log_mailer_errors( $wp_error ) {
			if ( is_wp_error( $wp_error ) ) {
				$this->error = $wp_error->get_error_message();
			}
		}

		/**
		 * Send an e-mail with the correct login URL
		 *
		 * @return array
		 */
		public function send_mail(): array {
			if ( empty( $this->message ) || empty( $this->subject ) ) {
				$this->error = __( "Email could not be sent. No message or subject set.", "really-simple-ssl" );
			}

			if ( ! is_email( $this->to ) ) {
				$this->error = __( "Email address not valid", "really-simple-ssl" );
			}
			$block_template                = $this->branded ? rsssl_path . '/mailer/templates/block.html' : rsssl_path . '/mailer/templates/block-unbranded.html';
			$email_template                = $this->branded ? rsssl_path . '/mailer/templates/email.html' : rsssl_path . '/mailer/templates/email-unbranded.html';
			$this->block_template_filename = apply_filters( 'rsssl_email_block_template', $block_template );
			$this->template_filename       = apply_filters( 'rsssl_email_template', $email_template );

			$template   = file_get_contents( $this->template_filename );
			$block_html = '';
			if ( is_array( $this->warning_blocks ) && count( $this->warning_blocks ) > 0 ) {
				$block_template = file_get_contents( $this->block_template_filename );
				foreach ( $this->warning_blocks as $warning_block ) {
					$block_html .= str_replace(
						[ '{title}', '{message}', '{url}' ],
						[
							sanitize_text_field( $warning_block['title'] ),
							wp_kses_post( $warning_block['message'] ),
							esc_url_raw( $warning_block['url'] )
						],
						$block_template );
				}
			}
			$username  = rsssl_get_option( 'new_admin_user_login' );
			$login_url = ! empty( rsssl_get_option( 'change_login_url' ) )
				? trailingslashit( site_url() ) . rsssl_get_option( 'change_login_url' )
				: wp_login_url();
			$body      = str_replace(
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
					'{sent_by_text}',
				],
				[
					sanitize_text_field( $this->title ),
					wp_kses_post( $this->message ),
					$block_html,
					$this->to,
					$this->button_text,
					site_url(),
					$login_url,
					$username,
					$this->change_text,
					$this->what_now_text,
					$this->sent_to_text,
					$this->sent_by_text,
				], $template );
			$success   = wp_mail( $this->to, sanitize_text_field( $this->subject ), $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
			if ( $success ) {
				return [
					'success' => true,
					'title'   => __( "Email verification", 'really-simple-ssl' ),
					'message' => __( 'Email sent! Please check your mail', "really-simple-ssl" )
				];
			}

			if ( empty( $this->error ) ) {
				$this->error = __( 'Email could not be sent.', "really-simple-ssl" );
			} else {
				$this->error = __( 'An error occurred:', "really-simple-ssl" ) . '<br>' . $this->error;
			}

			return [
				'success' => false,
				'title'   => __( "Email notification error", 'really-simple-ssl' ),
				'message' => $this->error
			];
		}

	}
}
