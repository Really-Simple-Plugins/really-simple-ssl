<?php
/**
 * Class for creating an email provider.
 *
 * @package Two_Factor
 */

namespace RSSSL\Security\WordPress\Two_Fa;

/**
 * Class for creating an email provider.
 *
 * @since 7.0.6
 *
 * @package Two_Factor
 */
//require_once __DIR__ . '/class-rsssl-two-factor-provider.php';
//require_once __DIR__ . '/interface-rsssl-provider-interface.php';
require_once rsssl_path . 'mailer/class-mail.php';

use RSSSL\Security\WordPress\Two_Fa\Rsssl_Two_Factor_Settings;
use rsssl_mailer;
use Exception;
use WP_User;

/**
 * Generate and email the user token.
 *
 * @param WP_User $user WP_User object of the logged-in user.
 *
 * @return void
 * @since 0.1-dev
 */
class Rsssl_Two_Factor_Email extends Rsssl_Two_Factor_Provider implements Rsssl_Two_Factor_Provider_Interface {

	/**
	 * The user meta token key.
	 *
	 * @var string
	 */
	public const RSSSL_TOKEN_META_KEY = '_rsssl_factor_email_token';

	/**
	 * Store the timestamp when the token was generated.
	 *
	 * @var string
	 */
	public const RSSSL_TOKEN_META_KEY_TIMESTAMP = '_rsssl_factor_email_token_timestamp';

	/**
	 * Name of the input field used for code resend.
	 *
	 * @var string
	 */
	public const RSSSL_INPUT_NAME_RESEND_CODE = 'rsssl-two-factor-email-code-resend';
	public const SECRET_META_KEY              = 'rsssl_two_fa_email_enabled';

	public const METHOD = 'email';

	public const NAME = 'Email';

	/**
	 * Ensures only one instance of this class exists in memory at any one time.
	 *
	 * @since 0.1-dev
	 */
	public static function get_instance() {
		static $instance;
		$class = __CLASS__;
		if ( ! is_a( $instance, $class ) ) {
			$instance = new $class();
		}
		return $instance;
	}

	/**
	 * Class constructor.
	 *
	 * @since 0.1-dev
	 */
	protected function __construct() {
		add_action( 'rsssl_two_factor_user_options_' . __CLASS__, array( $this, 'user_options' ) );
		parent::__construct();
	}

	/**
	 * Returns the name of the provider.
	 *
	 * @since 0.1-dev
	 */
	public function get_label(): string {
		return _x( 'Email', 'Provider Label', 'really-simple-ssl' );
	}

	/**
	 * Generate the user token.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return string
	 * @since 0.1-dev
	 */
	public function generate_token( int $user_id ): string {
		$token = self::get_code();

		update_user_meta( $user_id, self::RSSSL_TOKEN_META_KEY_TIMESTAMP, time() );
		update_user_meta( $user_id, self::RSSSL_TOKEN_META_KEY, wp_hash( $token ) );

		return $token;
	}

	/**
	 * Check if user has a valid token already.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return boolean      If user has a valid email token.
	 */
	public function user_has_token( int $user_id ): bool {
		$hashed_token = $this->get_user_token( $user_id );

		if ( ! empty( $hashed_token ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Has the user token validity timestamp expired.
	 *
	 * @param integer $user_id User ID.
	 *
	 * @return boolean
	 */
	public function user_token_has_expired( int $user_id ): bool {

		$token_lifetime = $this->user_token_lifetime( $user_id );
		$token_ttl      = $this->user_token_ttl( $user_id );

		// Invalid token lifetime is considered an expired token.
		return ! ( is_int( $token_lifetime ) && $token_lifetime <= $token_ttl );
	}

	/**
	 * Get the lifetime of a user token in seconds.
	 *
	 * @param integer $user_id User ID.
	 *
	 * @return integer|null Return `null` if the lifetime can't be measured.
	 */
	public function user_token_lifetime( $user_id ) {
		$timestamp = (int) get_user_meta( $user_id, self::RSSSL_TOKEN_META_KEY_TIMESTAMP, true );

		if ( ! empty( $timestamp ) ) {
			return time() - $timestamp;
		}

		return null;
	}

	/**
	 * Return the token time-to-live for a user.
	 *
	 * @param integer $user_id User ID.
	 *
	 * @return integer
	 */
	public function user_token_ttl( int $user_id ): int {
		$token_ttl = 15 * MINUTE_IN_SECONDS;

		/**
		 * Number of seconds the token is considered valid
		 * after the generation.
		 *
		 * @param integer $token_ttl Token time-to-live in seconds.
		 * @param integer $user_id User ID.
		 */
		return (int) apply_filters( 'rsssl_two_factor_token_ttl', $token_ttl, $user_id );
	}

	/**
	 * Get the authentication token for the user.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return string|boolean  User token or `false` if no token found.
	 */
	public function get_user_token( int $user_id ) {
		$hashed_token = get_user_meta( $user_id, self::RSSSL_TOKEN_META_KEY, true );

		if ( ! empty( $hashed_token ) && is_string( $hashed_token ) ) {
			return $hashed_token;
		}

		return false;
	}

	/**
	 * Validate the user token.
	 *
	 * @param int    $user_id User ID.
	 * @param string $token   User token.
	 *
	 * @return boolean
	 * @since 0.1-dev
	 */
	public function validate_token( int $user_id, string $token ): bool {

		$hashed_token = $this->get_user_token( $user_id );

		// Bail if token is empty or it doesn't match.
		if ( empty( $hashed_token ) || ! hash_equals( wp_hash( $token ), $hashed_token ) ) {
			return false;
		}

		if ( $this->user_token_has_expired( $user_id ) ) {
			return false;
		}

		// Ensure the token can be used only once.
		$this->delete_token( $user_id );

		update_user_meta( $user_id, 'rsssl_two_fa_status_email', 'active' );

		return true;
	}

	/**
	 * Delete the user token.
	 *
	 * @param int $user_id User ID.
	 *
	 * @since 0.1-dev
	 */
	public function delete_token( int $user_id ): void {
		delete_user_meta( $user_id, self::RSSSL_TOKEN_META_KEY );
	}

	/**
	 * Generate and email the user token.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 *
	 * @return void
	 * @since 0.1-dev
	 */
	public function generate_and_email_token( WP_User $user, $profile = false ): void {
		$token = $this->generate_token( $user->ID );

        $skip_two_fa_url = Rsssl_Two_Factor_Settings::rsssl_one_time_login_url( $user->ID, false, $profile );
		// Add skip button to email content.
		$skip_button_html = sprintf(
			'<a href="%s" class="button" style="padding: 10px 30px; background: #2A7ABF; border-color: #2A7ABF; color: #fff; text-decoration: none; text-shadow: none; display: inline-block; margin-top: 15px; font-size: 0.8125rem; font-weight: 300; transition: all .3s ease; min-height: 10px;">' . __( 'Continue', 'really-simple-ssl' ) . '</a>',
			esc_url( $skip_two_fa_url )
		);

		/* translators: %s: site name */
		$subject = wp_strip_all_tags( sprintf( __( 'Your login confirmation code for %s', 'really-simple-ssl' ), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) ) );
		/* translators: %s: token */
		$token_cleaned = wp_strip_all_tags( $token );

		// insert whitespace after four characters in the $token, for readability.
		$token_cleaned = preg_replace( '/(.{4})/', '$1 ', $token_cleaned );

		$token_html = sprintf(
			'
<table cellspacing="0" cellpadding="0" border="0" width="100%%" style="margin-top: 25px;background-color:white; box-shadow: 1px 3px 0 1px rgba(211, 211, 211, 0.3); height: 180px;"> <!-- Further increased height for white box -->
    <tr>
        <td style="padding: 45px 10px 10px 10px; vertical-align: middle; font-size: 18px; font-weight:700; text-align: center;">%s</td> <!-- Increased padding for top and bottom -->
    </tr>
    <tr>
        <td style="padding: 10px 20px 45px 20px; vertical-align: middle; text-align: center;">%s</td> <!-- Increased padding for bottom -->
    </tr>
</table>',
			$token_cleaned,
			$skip_button_html
		);

        if($profile) {
            $message = sprintf(
                __( "Below you'll find the email activation code for %1\$s. It's valid for 15 minutes. %2\$s", 'really-simple-ssl' ),
                site_url(),
                $token_html
            );
        } else {
            $message = sprintf(
                __( "Below you will find your login code for %1\$s. It's valid for 15 minutes. %2\$s", 'really-simple-ssl' ),
                site_url(),
                $token_html
            );
        }


		/**
		 * Filter the token email subject.
		 *
		 * @param string $subject The email subject line.
		 * @param int    $user_id The ID of the user.
		 */
		$subject = apply_filters( 'rsssl_two_factor_token_email_subject', $subject, $user->ID );

		/**
		 * Filter the token email message.
		 *
		 * @param string $message The email message.
		 * @param string $token   The token.
		 * @param int    $user_id The ID of the user.
		 */
		$message = apply_filters( 'rsssl_two_factor_token_email_message', $message, $token, $user->ID );

		if ( ! class_exists( 'rsssl_mailer' ) ) {
			require_once rsssl_path . 'mailer/class-mail.php';
		}

		$mailer          = new rsssl_mailer();
		$mailer->subject = $subject;
		$mailer->branded = false;
		/* translators: %s is replaced with the site url */
		$mailer->sent_by_text      = "<b>" . sprintf( __( 'Notification by %s', 'really-simple-ssl' ), site_url() ) . "</b>";
		$mailer->template_filename = apply_filters( 'rsssl_email_template', rsssl_path . '/mailer/templates/email-unbranded.html' );
		$mailer->to                = $user->user_email;
		$mailer->title             = __( 'Hi', 'really-simple-ssl' ) . ' ' . $user->display_name . ',';
		$mailer->message           = $message;
		$mailer->send_mail();
	}

	/**
	 * Prints the form that prompts the user to authenticate.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 *
	 * @since 0.1-dev
	 */
	public function authentication_page( WP_User $user ): void {

		if ( ! $user ) {
			return;
		}

		if ( ! $this->user_has_token( $user->ID ) || $this->user_token_has_expired( $user->ID ) ) {
			$this->generate_and_email_token( $user );
		}

		require_once ABSPATH . '/wp-admin/includes/template.php';
		?>
		<p class="two-factor-prompt"><?php esc_html_e( 'A verification code has been sent to the email address associated with your account.', 'really-simple-ssl' ); ?></p>
		<p>
			<label for="rsssl-authcode"><?php esc_html_e( 'Verification Code:', 'really-simple-ssl' ); ?></label>
			<input type="text" inputmode="numeric" name="rsssl-two-factor-email-code" id="rsssl-authcode" class="input rsssl-authcode" value="" size="20" pattern="[0-9 ]*" placeholder="1234 5678" data-digits="8" />
			<?php submit_button( __( 'Log In', 'really-simple-ssl' ) ); ?>
		</p>
		<p class="rsssl-two-factor-email-resend">
			<input type="submit" class="button" name="<?php echo esc_attr( self::RSSSL_INPUT_NAME_RESEND_CODE ); ?>" value="<?php esc_attr_e( 'Resend Code', 'really-simple-ssl' ); ?>" />
		</p>
		<script type="text/javascript">
			setTimeout( function(){
				var d;
				try{
					d = document.getElementById('rsssl-authcode');
					d.value = '';
					d.focus();
				} catch(e){}
			}, 200);
		</script>
		<?php
		$provider = get_user_meta( $user->ID, 'rsssl_two_fa_status_email', true );

		foreach ( $user->roles as $role ) {
			// Never show the skip link if a role is a forced role.
			$two_fa_forced_roles = is_array(rsssl_get_option('two_fa_forced_roles'))
				? rsssl_get_option('two_fa_forced_roles')
				: [];

			if (in_array($role, $two_fa_forced_roles, true)) {
				break;
			}

			// If optional and open, allow the user to skip 2FA for now.
			if ( 'open' === $provider && in_array( $role, rsssl_get_option( 'two_fa_enabled_roles_email', array() ), true ) ) {

				$skip_two_fa_url = Rsssl_Two_Factor_Settings::rsssl_one_time_login_url( $user->ID, true );

				?>
				<a class="rsssl-skip-link" href="<?php echo esc_url( $skip_two_fa_url ); ?>" style="display: flex; justify-content: center; margin: 15px 20px 0 0;">
					<?php esc_html_e( "Don't use Two-Factor Authentication", 'really-simple-ssl' ); ?>
				</a>
				<?php

			}
		}
	}

	/**
	 * Send the email code if missing or requested. Stop the authentication
	 * validation if a new token has been generated and sent.
	 *
	 * @param  WP_USer $user WP_User object of the logged-in user.
	 * @return boolean
	 */
	public function pre_process_authentication( $user ): bool {
		if ( isset( $user->ID ) && isset( $_REQUEST[ self::RSSSL_INPUT_NAME_RESEND_CODE ] ) ) {
			$this->generate_and_email_token( $user );
			return true;
		}

		return false;
	}

	/**
	 * Validates the users input token.
	 *
	 * @since 0.1-dev
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 * @return boolean
	 */
	public function validate_authentication( $user ): bool {

		$code = self::sanitize_code_from_request( 'rsssl-two-factor-email-code' );
		if ( ! isset( $user->ID ) || ! $code ) {
			return false;
		}

		return $this->validate_token( $user->ID, $code );
	}

	/**
	 * Whether this Two Factor provider is configured and available for the user specified.
	 *
	 * @since 0.1-dev
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 * @return boolean
	 */
	public function is_available_for_user( $user ): bool {
		return true;
	}

	/**
	 * Inserts markup at the end of the user profile field for this provider.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 *
	 * @since 0.1-dev
	 */
	public function user_options( WP_User $user ): void {
		$email = $user->user_email;
		?>
		<div>
			<?php
			echo esc_html(
				sprintf(
				/* translators: %s: email address */
					__( 'Authentication codes will be sent to %s.', 'really-simple-ssl' ),
					$email
				)
			);
			?>
		</div>
		<?php
	}

	/**
	 * Check if the user is forced to use two-factor authentication.
	 *
	 * @param  WP_User $user  The user object.
	 *
	 * @return bool Whether the user is forced to use two-factor authentication.
	 */
	public static function is_forced( WP_User $user ): bool {
		// If there is no user logged in, it can't check if the user is forced.
		if ( ! $user->exists() ) {
			return false;
		}
		return Rsssl_Two_Factor_Settings::get_role_status( 'email', $user->ID ) === 'forced';
	}

	/**
	 * Check if a user is Optional.
	 *
	 * @param  WP_User $user  The user object.
	 *
	 * @return bool Whether the user is optional or not.
	 */
	public static function is_optional( WP_User $user ): bool {
		if ( ! $user->exists() ) {
			return false;
		}
		$user_roles     = $user->roles;
		$optional_roles = rsssl_get_option( 'two_fa_enabled_roles_email' );
		if ( empty( $optional_roles ) ) {
			$optional_roles = array();
		}
		if ( 'disabled' === Rsssl_Two_Factor_Settings::get_user_status( 'email', $user->ID ) ) {
			return false;
		}
		return in_array( $user_roles[0], $optional_roles, true );
	}

	/**
	 * Set user status for two-factor authentication.
	 *
	 * @param int    $user_id User ID.
	 * @param string $status The status to set.
	 *
	 * @return void
	 */
	public static function set_user_status( int $user_id, string $status ): void {
		update_user_meta( $user_id, 'rsssl_two_fa_status_email', $status );
	}


	/**
	 * Returns the HTML for the selection option.
	 *
	 * @param WP_User $user The user object.
	 * @param  bool    $checked Whether the option is checked or not.
	 *
	 * @return void
	 * @throws Exception Throws an exception if the template file is not found.
	 */
	public static function get_selection_option( $user, bool $checked = false ): void {
		// Get the preferred method meta, which could be a string or an array.
		$preferred_method_meta = get_user_meta( $user->ID, 'rsssl_two_fa_set_provider', true );
		// Normalize the preferred method to always be an array.
		$preferred_methods = is_array( $preferred_method_meta ) ? $preferred_method_meta : (array) $preferred_method_meta;
		// Check if 'Rsssl_Two_Factor_Email' is the preferred method.
		$is_preferred      = in_array( 'Rsssl_Two_Factor_Email', $preferred_methods, true );
		$is_enabled        = (bool) get_user_meta( $user->ID, self::SECRET_META_KEY, true );
		$badge_class       = $is_enabled ? 'badge-enabled' : 'badge-default';
		$enabled_text      = $is_enabled ? esc_html__( 'Enabled', 'really-simple-ssl' ) : esc_html__( 'Disabled', 'really-simple-ssl' );
		$checked_attribute = $checked ? 'checked' : '';
		$title             = esc_html__( 'Email', 'really-simple-ssl' );
		$description       = esc_html__( 'Receive a code by email', 'really-simple-ssl' );

		// Load the template.
		rsssl_load_template(
			'selectable-option.php',
			array(
				'badge_class'       => $badge_class,
				'enabled_text'      => $enabled_text,
				'checked_attribute' => $checked_attribute,
				'title'             => $title,
				'type'              => 'email', // Used this to identify the provider.
				'forcible'          => in_array( $user->roles[0], (array) rsssl_get_option( 'two_fa_forced_roles' ), true ),
				'description'       => $description,
				'user'              => $user,
			),
			rsssl_path . 'assets/templates/two_fa'
		);
	}

	/**
	 * Check if a user is enabled based on their role.
	 *
	 * @param  WP_User $user  The user object to check.
	 *
	 * @return bool Whether the user is enabled or not.
	 */
	public static function is_enabled( WP_User $user ): bool {
		if ( ! $user->exists() ) {
			return false;
		}
		// Get all the user roles.
		$user_roles = $user->roles;
		// Then get the enabled roles.
		$enabled_roles = rsssl_get_option( 'two_fa_enabled_roles_email' );

        if( is_multisite() ) {
            $user_roles = Rsssl_Two_Factor_Settings::get_strictest_role_across_sites($user->ID, ['email']);
        }

		// if the user has the role that is enabled, return true.
		if ( ! is_array( $enabled_roles ) ) {
			$enabled_roles = array();
		}

        if(is_multisite()) {
            //compare the user roles with the enabled roles and if there is a match, return true
            return count(array_intersect($user_roles, $enabled_roles)) > 0;
        }

		return in_array( $user_roles[0], $enabled_roles, true );
	}

    public static function is_configured( WP_User $user ): bool {
        $status = get_user_meta( $user->ID, 'rsssl_two_fa_status_email', true );
        return 'active' === $status;
    }

	public static function get_status( WP_User $user ): string {
		return Rsssl_Two_Factor_Settings::get_user_status( 'email', $user->ID );
	}
}