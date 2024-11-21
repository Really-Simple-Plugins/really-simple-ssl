<?php
/**
 * Trait for sending emails related to two-factor authentication.
 *
 * @package RSSSL\Pro\Security\WordPress\Two_Fa\Traits
 */

namespace RSSSL\Security\WordPress\Two_Fa\Traits;

use rsssl_mailer;
use WP_User;

/**
 * Trait Rsssl_Email_Trait
 *
 * This trait handles email notifications related to password reset and compromised passwords.
 */
trait Rsssl_Email_Trait {

	/**
	 * Notify the user that their password has been compromised and reset.
	 *
	 * @param WP_User $user The user to notify.
	 *
	 * @return void
	 */
	public static function notify_user_password_reset( WP_User $user ): void {
		$subject = __( 'Your password was compromised and has been reset', 'really-simple-ssl' );
		$message = self::create_user_message( $user );

		if ( ! class_exists( 'rsssl_mailer' ) ) {
			require_once rsssl_path . 'mailer/class-mail.php';
		}

		$mailer = self::initialize_mailer( $subject, $message, $user );
		$mailer->send_mail();
	}

	/**
	 * Create a user message for failed login attempts.
	 *
	 * @param WP_User $user The user object.
	 *
	 * @return string The user message.
	 */
	private static function create_user_message( WP_User $user ): string {
		$message = sprintf(
		/* translators: %1$s: user login, %2$s: site url, %3$s: password best practices link, %4$s: lost password url */
			__(
				'Hello %1$s, an unusually high number of failed login attempts have been detected on your account at %2$s.

These attempts successfully entered your password, and were only blocked because they failed to enter your second authentication factor. Despite not being able to access your account, this behavior indicates that the attackers have compromised your password. The most common reasons for this are that your password was easy to guess, or was reused on another site which has been compromised.

To protect your account, your password has been reset, and you will need to create a new one. For advice on setting a strong password, please read %3$s

To pick a new password, please visit %4$s

This is an automated notification. If you would like to speak to a site administrator, please contact them directly.',
				'really-simple-ssl'
			),
			esc_html( $user->user_login ),
			home_url(),
			'https://wordpress.org/documentation/article/password-best-practices/',
			esc_url( add_query_arg( 'action', 'lostpassword', rsssl_wp_login_url() ) )
		);

		return str_replace( "\t", '', $message );
	}


	/**
	 * Notify the admin that a user's password was compromised and reset.
	 *
	 * @param WP_User $user The user whose password was reset.
	 *
	 * @return void
	 */
	public static function notify_admin_user_password_reset( WP_User $user ): void {
		if ( ! class_exists( 'rsssl_mailer' ) ) {
			require_once rsssl_path . 'mailer/class-mail.php';
		}

		$subject = self::create_subject( $user );
		$message = self::create_message( $user );

		$mailer = self::initialize_mailer( $subject, $message, $user );

		$mailer->send_mail();
	}

	/**
	 * Create subject for the compromised password reset email.
	 *
	 * @param WP_User $user The user object.
	 *
	 * @return string The subject of the email.
	 */
	private static function create_subject( WP_User $user ): string {
		/* translators: %s: user login */
		return sprintf(
			__( 'Compromised password for %s has been reset', 'really-simple-ssl' ),
			esc_html( $user->user_login )
		);
	}

	/**
	 * Generate a message for notifying the user about a high number of failed login attempts.
	 *
	 * @param WP_User $user The user for whom the message is created.
	 *
	 * @return string The generated message.
	 */
	private static function create_message( WP_User $user ): string {
		$documentation_url = 'https://developer.wordpress.org/plugins/hooks/';

		return str_replace(
			"\t",
			'',
			// translators: %1$s: user login, %2$d: user ID, %3$s: documentation URL.
			sprintf(
				__( 'Hello, this is a notice from your website to inform you that an unusually high number of failed login attempts have been detected on the %1$s account (ID %2$d). Those attempts successfully entered the user\'s password, and were only blocked because they entered invalid second authentication factors. To protect their account, the password has automatically been reset, and they have been notified that they will need to create a new one. If you do not wish to receive these notifications, you can disable them with the `two_factor_notify_admin_user_password_reset` filter. See %3$s for more information. Thank you', 'really-simple-ssl' ),
				esc_html( $user->user_login ),
				$user->ID,
				$documentation_url
			)
		);
	}

	/**
	 * Initialize the mailer for sending a notification email.
	 *
	 * @param string  $subject The subject of the email.
	 * @param string  $message The message content of the email.
	 * @param WP_User $user The user object to send the email to.
	 *
	 * @return rsssl_mailer The initialized mailer object.
	 */
	private static function initialize_mailer( string $subject, string $message, WP_User $user ): rsssl_mailer {
		$mailer                    = new rsssl_mailer();
		$mailer->subject           = $subject;
		$mailer->branded           = false;
		$mailer->sent_by_text      = "<b>" . sprintf( __( 'Notification by %s', 'really-simple-ssl' ), site_url() ) . "</b>";
		$mailer->template_filename = apply_filters( 'rsssl_email_template', rsssl_path . '/mailer/templates/email-unbranded.html' );
		$mailer->to                = $user->user_email;
		$mailer->title             = __( 'Compromised password reset', 'really-simple-ssl' );
		$mailer->message           = $message;

		return $mailer;
	}
}
