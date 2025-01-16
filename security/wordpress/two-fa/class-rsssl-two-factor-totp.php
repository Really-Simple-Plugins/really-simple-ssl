<?php
/**
 * Class for creating a Time Based One-Time Password provider.
 *
 * @package Two_Factor
 */

namespace RSSSL\Security\WordPress\Two_Fa;

use Exception;
use WP_Error;
use WP_Rest_Request;
use WP_REST_Server;
use WP_User;
use RSSSL\Security\WordPress\Two_Fa\Rsssl_Two_Factor_Settings;

/**
 * Class Two_Factor_Totp
 */
class Rsssl_Two_Factor_Totp extends Rsssl_Two_Factor_Provider implements Rsssl_Two_Factor_Provider_Interface {

	/**
	 * The user meta key for the TOTP Secret key.
	 *
	 * @var string
	 */
	public const SECRET_META_KEY = '_two_factor_totp_key';

	/**
	 * The user meta key for the last successful TOTP token timestamp logged in with.
	 *
	 * @var string
	 */
	public const LAST_SUCCESSFUL_LOGIN_META_KEY = '_rsssl_two_factor_totp_last_successful_login';

	public const DEFAULT_KEY_BIT_SIZE        = 160;
	public const DEFAULT_CRYPTO              = 'sha1';
	public const DEFAULT_DIGIT_COUNT         = 6;
	public const DEFAULT_TIME_STEP_SEC       = 30;
	public const DEFAULT_TIME_STEP_ALLOWANCE = 4;

	public const METHOD = 'totp';

	public const NAME = 'Authenticator App';

	/**
	 * Characters used in base32 encoding.
	 *
	 * @var string
	 */
	private static $base_32_chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

	/**
	 * Ensures only one instance of this class exists in memory at any one time.
	 *
	 * @codeCoverageIgnore
	 */
	public static function get_instance() {
		static $instance;
		if ( ! isset( $instance ) ) {
			$instance = new self();
		}
		return $instance;
	}

	/**
	 * Class constructor. Sets up hooks, etc.
	 *
	 * @codeCoverageIgnore
	 */
	protected function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'two_factor_user_options_' . __CLASS__, array( $this, 'user_two_factor_options' ) );

		parent::__construct();
	}

	/**
	 * Register the rest-api endpoints required for this provider.
	 *
	 * @codeCoverageIgnore
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			Rsssl_Two_Factor::REST_NAMESPACE,
			'/v1/totp',
			array(
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'rest_delete_totp' ),
					'permission_callback' => function ( $request ) {
						return current_user_can( 'edit_user', $request['user_id'] );
					},
					'args'                => array(
						'user_id' => array(
							'required' => true,
							'type'     => 'integer',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'rest_setup_totp' ),
					'permission_callback' => function ( $request ) {
						return current_user_can( 'edit_user', $request['user_id'] );
					},
					'args'                => array(
						'user_id'         => array(
							'required' => true,
							'type'     => 'integer',
						),
						'key'             => array(
							'type'              => 'string',
							'default'           => '',
							'validate_callback' => null, // Note: validation handled in ::rest_setup_totp().
						),
						'code'            => array(
							'type'              => 'string',
							'default'           => '',
							'validate_callback' => null, // Note: validation handled in ::rest_setup_totp().
						),
						'enable_provider' => array(
							'required' => false,
							'type'     => 'boolean',
							'default'  => false,
						),
					),
				),
			)
		);
	}

	/**
	 * Returns the name of the provider.
	 */
	public function get_label(): ?string {
		return _x( 'Time Based One-Time Password (TOTP)', 'Provider Label', 'really-simple-ssl' );
	}

	/**
	 * Rest API endpoint for handling deactivation of TOTP.
	 *
	 * @param WP_Rest_Request $request The Rest Request object.
	 *
	 * @return array Success array.
	 */
	public function rest_delete_totp( WP_Rest_Request $request ): array {
		$user_id = $request['user_id'];
		$user    = get_user_by( 'id', $user_id );

		$this->delete_user_totp_key( $user_id );

		ob_start();
		$this->user_two_factor_options( $user );
		$html = ob_get_clean();

		return array(
			'success' => true,
			'html'    => $html,
		);
	}

	/**
	 * Set up the Two-Factor Authentication Time-Based One-Time Password (TOTP) for the specified user.
	 *
	 * @param WP_User $user  WP_User object representing the user for whom to setup TOTP.
	 * @param string  $key  The secret key for TOTP.
	 * @param string  $code  The authentication code entered by the user.
	 *
	 * @return boolean Returns true if TOTP setup is successful. Returns an error message string if there is an error during setup.
	 */
	public static function setup_totp( WP_User $user, string $key, string $code ): bool {
		$code = preg_replace( '/\s+/', '', $code );

		if ( ! self::is_valid_key( $key ) ) {
			// Set an error message for after redirect login using transients.
			set_transient( 'rsssl_error_message_' . $user->ID, __( 'Invalid Two Factor Authentication secret key.', 'really-simple-ssl' ), 60 );
			return false;
		}

		if ( ! self::is_valid_authcode( $key, $code ) ) {
			// Set an error message for after redirect login.
			set_transient( 'rsssl_error_message_' . $user->ID, __( 'Invalid Two Factor Authentication code.', 'really-simple-ssl' ), 60 );
			return false;
		}

		if ( ! self::set_user_totp_key( $user->ID, $key ) ) {
			// Set an error message for after redirect login.
			set_transient( 'rsssl_error_message_' . $user->ID, __( 'Unable to save Two Factor Authentication code. Please re-scan the QR code and enter the code provided by your application.', 'really-simple-ssl' ), 60 );
			return false;
		}

		return true;
	}


	/**
	 * REST API endpoint for setting up TOTP.
	 *
	 * @param WP_Rest_Request $request The Rest Request object.
	 *
	 * @return WP_Error|array Array of data on success, WP_Error on error.
	 */
	public function rest_setup_totp( WP_Rest_Request $request ) {
		$user_id = $request['user_id'];
		$user    = get_user_by( 'id', $user_id );

		$key  = $request['key'];
		$code = preg_replace( '/\s+/', '', $request['code'] );

		if ( ! self::is_valid_key( $key ) ) {
			return new WP_Error( 'invalid_key', __( 'Invalid Two Factor Authentication secret key.', 'really-simple-ssl' ), array( 'status' => 400 ) );
		}

		if ( ! self::is_valid_authcode( $key, $code ) ) {
			return new WP_Error( 'invalid_key_code', __( 'Invalid Two Factor Authentication code.', 'really-simple-ssl' ), array( 'status' => 400 ) );
		}

		if ( ! self::set_user_totp_key( $user_id, $key ) ) {
			return new WP_Error( 'db_error', __( 'Unable to save Two Factor Authentication code. Please re-scan the QR code and enter the code provided by your application.', 'really-simple-ssl' ), array( 'status' => 500 ) );
		}

		if ( $request->get_param( 'enable_provider' ) && ! Rsssl_Two_Factor::enable_provider_for_user( $user_id, 'Two_Factor_Totp' ) ) {
			return new WP_Error( 'db_error', __( 'Unable to enable TOTP provider for this user.', 'really-simple-ssl' ), array( 'status' => 500 ) );
		}

		ob_start();
		$this->user_two_factor_options( $user );
		$html = ob_get_clean();

		return array(
			'success' => true,
			'html'    => $html,
		);
	}

	/**
	 * Generates a URL that can be used to create a QR code.
	 *
	 * @param WP_User $user The user to generate a URL for.
	 * @param string  $secret_key The secret key to use for the TOTP.
	 *
	 * @return string
	 */
	public static function generate_qr_code_url( WP_User $user, string $secret_key ) {
		$issuer = get_bloginfo( 'name', 'display' );

		/**
		 * Filter the Issuer for the TOTP.
		 *
		 * Must follow the TOTP format for a "issuer". Do not URL Encode.
		 *
		 * @see https://github.com/google/google-authenticator/wiki/Key-Uri-Format#issuer
		 * @param string $issuer The issuer for TOTP.
		 */
		$issuer = apply_filters( 'two_factor_totp_issuer', $issuer );

		/**
		 * Filter the Label for the TOTP.
		 *
		 * Must follow the TOTP format for a "label". Do not URL Encode.
		 *
		 * @see https://github.com/google/google-authenticator/wiki/Key-Uri-Format#label
		 * @param string  $totp_title The label for the TOTP.
		 * @param WP_User $user       The User object.
		 * @param string  $issuer     The issuer of the TOTP. This should be the prefix of the result.
		 */
		$totp_title = apply_filters( 'two_factor_totp_title', $issuer . ':' . $user->user_login, $user, $issuer );

		$totp_url = add_query_arg(
			array(
				'secret' => rawurlencode( $secret_key ),
				'issuer' => rawurlencode( $issuer ),
			),
			'otpauth://totp/' . rawurlencode( $totp_title )
		);

		/**
		 * Filter the TOTP generated URL.
		 *
		 * Must follow the TOTP format. Do not URL Encode.
		 *
		 * @see https://github.com/google/google-authenticator/wiki/Key-Uri-Format
		 * @param string  $totp_url The TOTP URL.
		 * @param WP_User $user     The user object.
		 */
		$totp_url = apply_filters( 'two_factor_totp_url', $totp_url, $user );
		$totp_url = esc_url( $totp_url, array( 'otpauth' ) );

		return $totp_url;
	}

	/**
	 * Display TOTP options on the user settings page.
	 *
	 * @param WP_User $user The current user being edited.
	 *
	 * @return false
	 *
	 * @codeCoverageIgnore
	 */
	public function user_two_factor_options( WP_User $user ): bool {
		if ( ! isset( $user->ID ) ) {
			return false;
		}

		$key = $this->get_user_totp_key( $user->ID );

		wp_enqueue_script( 'two-factor-qr-code-generator' );
		wp_enqueue_script( 'wp-api-request' );
		wp_enqueue_script( 'jquery' );

		?>
		<div id="two-factor-totp-options">
		<?php
		if ( empty( $key ) ) :

			$key      = self::generate_key();
			$totp_url = self::generate_qr_code_url( $user, $key );

			?>

			<p>
				<?php esc_html_e( 'Please scan the QR code or manually enter the key, then enter an authentication code from your app in order to complete setup.', 'really-simple-ssl' ); ?>
			</p>
			<p id="two-factor-qr-code">
				<a href="<?php echo esc_url( $totp_url ); ?>">
					Loading...
					<img src="<?php echo esc_url( admin_url( 'images/spinner.gif' ) ); ?>" alt="" />
				</a>
			</p>

			<style>
				#two-factor-qr-code {
					/* The size of the image will change based on the length of the URL inside it. */
					min-width: 205px;
					min-height: 205px;
				}
			</style>

			<script>
				(function(){
					var qr_generator = function() {
						/*
						* 0 = Automatically select the version, to avoid going over the limit of URL
						*     length.
						* L = Least amount of error correction, because it's not needed when scanning
						*     on a monitor, and it lowers the image size.
						*/
						var qr = qrcode( 0, 'L' );

						qr.addData( <?php echo wp_json_encode( $totp_url ); ?> );
						qr.make();

						document.querySelector( '#two-factor-qr-code a' ).innerHTML = qr.createSvgTag( 5 );
					};

					// Run now if the document is loaded, otherwise on DOMContentLoaded.
					if ( document.readyState === 'complete' ) {
						qr_generator();
					} else {
						window.addEventListener( 'DOMContentLoaded', qr_generator );
					}
				})();
			</script>

			<p>
				<code><?php echo esc_html( $key ); ?></code>
			</p>
			<p>
				<input type="hidden" id="two-factor-totp-key" name="two-factor-totp-key" value="<?php echo esc_attr( $key ); ?>" />
				<label for="two-factor-totp-authcode">
					<?php esc_html_e( 'Authentication Code:', 'really-simple-ssl' ); ?>
					<?php
						/* translators: Example auth code. */
						$placeholder = sprintf( __( 'eg. %s', 'really-simple-ssl' ), '123456' );
					?>
					<input type="tel" name="two-factor-totp-authcode" id="two-factor-totp-authcode" class="input" value="" size="20" pattern="[0-9 ]*" placeholder="<?php echo esc_attr( $placeholder ); ?>" />
				</label>
				<input type="submit" class="button totp-submit" name="two-factor-totp-submit" value="<?php esc_attr_e( 'Submit', 'really-simple-ssl' ); ?>" />
			</p>

			<script>
				(function($){
					$('.totp-submit').click( function( e ) {
						e.preventDefault();
						var key = $('#two-factor-totp-key').val(),
							code = $('#two-factor-totp-authcode').val();

						wp.apiRequest( {
							method: 'POST',
							path: <?php echo wp_json_encode( Two_Factor_Core::REST_NAMESPACE . '/totp' ); ?>,
							data: {
								user_id: <?php echo wp_json_encode( $user->ID ); ?>,
								key: key,
								code: code,
							}
						} ).fail( function( response, status ) {
							var errorMessage = response.responseJSON.message || status,
								$error = $( '#totp-setup-error' );

							if ( ! $error.length ) {
								$error = $('<div class="error" id="totp-setup-error"><p></p></div>').insertAfter( $('.totp-submit') );
							}

							$error.find('p').text( errorMessage );

							$('#two-factor-totp-authcode').val('');
						} ).then( function( response ) {
							$( '#two-factor-totp-options' ).html( response.html );
						} );
					} );
				})(jQuery);
			</script>

		<?php else : ?>
			<p class="success">
				<?php esc_html_e( 'Secret key is configured and registered. It is not possible to view it again for security reasons.', 'really-simple-ssl' ); ?>
			</p>
			<p>
				<a class="button reset-totp-key" href="#"><?php esc_html_e( 'Reset Key', 'really-simple-ssl' ); ?></a>
				<em class="description">
					<?php esc_html_e( 'You will have to re-scan the QR code on all devices as the previous codes will stop working.', 'really-simple-ssl' ); ?>
				</em>
				<script>
					( function( $ ) {
						$( 'a.reset-totp-key' ).click( function( e ) {
							e.preventDefault();

							wp.apiRequest( {
								method: 'DELETE',
								path: <?php echo wp_json_encode( Two_Factor_Core::REST_NAMESPACE . '/totp' ); ?>,
								data: {
									user_id: <?php echo wp_json_encode( $user->ID ); ?>,
								}
							} ).then( function( response ) {
								$( '#two-factor-totp-options' ).html( response.html );
							} );
						} );
					} )( jQuery );
				</script>
			</p>
		<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Get the TOTP secret key for a user.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return string
	 */
	public function get_user_totp_key( int $user_id ): string {
		return (string) get_user_meta( $user_id, self::SECRET_META_KEY, true );
	}

	/**
	 * Set the TOTP secret key for a user.
	 *
	 * @param int    $user_id User ID.
	 * @param string $key TOTP secret key.
	 *
	 * @return boolean If the key was stored successfully.
	 */
	public static function set_user_totp_key( int $user_id, string $key ): bool {
		return update_user_meta( $user_id, self::SECRET_META_KEY, $key );
	}

	/**
	 * Delete the TOTP secret key for a user.
	 *
	 * @param  int $user_id User ID.
	 *
	 * @return boolean If the key was deleted successfully.
	 */
	public function delete_user_totp_key( $user_id ): bool {
		delete_user_meta( $user_id, self::LAST_SUCCESSFUL_LOGIN_META_KEY );
		return delete_user_meta( $user_id, self::SECRET_META_KEY );
	}

	/**
	 * Check if the TOTP secret key has a proper format.
	 *
	 * @param string $key TOTP secret key.
	 *
	 * @return boolean
	 */
	public static function is_valid_key( string $key ): bool {
		$check = sprintf( '/^[%s]+$/', self::$base_32_chars );

		return 1 === preg_match( $check, $key );
	}

	/**
	 * Validates authentication.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 *
	 * @return bool Whether the user gave a valid code.
	 */
	public function validate_authentication( $user ): bool {
		// Run the backup codes first, because they are the fallback.
		$code_backup = Rsssl_Two_Factor_Backup_Codes::sanitize_code_from_request( 'authcode', 8 );
		if ( $code_backup && Rsssl_Two_Factor_Backup_Codes::validate_code( $user, $code_backup ) ) {
			return true;
		}

		$code = self::sanitize_code_from_request( 'authcode', self::DEFAULT_DIGIT_COUNT );
		if ( ! $code ) {
			return false;
		}

		return $this->validate_code_for_user( $user, $code );
	}


	/**
	 * Validates an authentication code for a given user, preventing re-use and older TOTP keys.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 * @param int     $code The TOTP token to validate.
	 *
	 * @return bool Whether the code is valid for the user and a newer code has not been used.
	 */
	public function validate_code_for_user( WP_User $user, int $code ) {
		$valid_timestamp = self::get_authcode_valid_ticktime(
			$this->get_user_totp_key( $user->ID ),
			$code
		);

		if ( ! $valid_timestamp ) {
			return false;
		}

		$last_totp_login = (int) get_user_meta( $user->ID, self::LAST_SUCCESSFUL_LOGIN_META_KEY, true );

//		// The TOTP authentication is not valid, if we've seen the same or newer code.
//		if ( $last_totp_login && $last_totp_login >= $valid_timestamp ) {
//			return false;
//		}

		update_user_meta( $user->ID, self::LAST_SUCCESSFUL_LOGIN_META_KEY, $valid_timestamp );
        delete_user_meta( $user->ID, '_rsssl_two_factor_failed_login_attempts');
        delete_user_meta( $user->ID, '_rsssl_two_factor_last_login_failure');

		return true;
	}


	/**
	 * Checks if a given code is valid for a given key, allowing for a certain amount of time drift.
	 *
	 * @param string $key      The share secret key to use.
	 * @param string $authcode The code to test.
	 *
	 * @return bool Whether the code is valid within the time frame.
	 */
	public static function is_valid_authcode( string $key, string $authcode ): bool {
		return (bool) self::get_authcode_valid_ticktime( $key, $authcode );
	}

	/**
	 * Checks if a given code is valid for a given key, allowing for a certain amount of time drift.
	 *
	 * @param string $key      The share secret key to use.
	 * @param string $authcode The code to test.
	 *
	 * @return false|int Returns the timestamp of the authcode on success, False otherwise.
	 */
	public static function get_authcode_valid_ticktime( string $key, string $authcode ) {
		/**
		 * Filter the maximum ticks to allow when checking valid codes.
		 *
		 * Ticks are the allowed offset from the correct time in 30 second increments,
		 * so the default of 4 allows codes that are two minutes to either side of server time
		 *
		 * @deprecated 0.7.0 Use {@see 'two_factor_totp_time_step_allowance'} instead.
		 * @param int $max_ticks Max ticks of time correction to allow. Default 4.
		 */
		$max_ticks = apply_filters_deprecated( 'two-factor-totp-time-step-allowance', array( self::DEFAULT_TIME_STEP_ALLOWANCE ), '0.7.0', 'two_factor_totp_time_step_allowance' );

		$max_ticks = apply_filters( 'two_factor_totp_time_step_allowance', self::DEFAULT_TIME_STEP_ALLOWANCE );

		// Array of all ticks to allow, sorted using absolute value to test closest match first.
		$ticks = range( - $max_ticks, $max_ticks );
		usort( $ticks, array( __CLASS__, 'abssort' ) );

		$time = floor( time() / self::DEFAULT_TIME_STEP_SEC );

		foreach ( $ticks as $offset ) {
			$log_time = $time + $offset;
			if ( hash_equals( self::calc_totp( $key, $log_time ), $authcode ) ) {
				// Return the tick timestamp.
				return $log_time * self::DEFAULT_TIME_STEP_SEC;
			}
		}

		return false;
	}

	/**
	 * Generates key
	 *
	 * @param int $bitsize Nume of bits to use for key.
	 *
	 * @return string $bitsize long string composed of available base32 chars.
	 */
	public static function generate_key( $bitsize = self::DEFAULT_KEY_BIT_SIZE ) {
		$bytes  = ceil( $bitsize / 8 );
		$secret = wp_generate_password( $bytes, true, true );

		return self::base32_encode( $secret );
	}

	/**
	 * Pack stuff
	 *
	 * @param string $value The value to be packed.
	 *
	 * @return string Binary packed string.
	 */
	public static function pack64( $value ) {
		// 64bit mode (PHP_INT_SIZE == 8).
		if ( PHP_INT_SIZE >= 8 ) {
			// If we're on PHP 5.6.3+ we can use the new 64bit pack functionality.
			if ( version_compare( PHP_VERSION, '5.6.3', '>=' ) && PHP_INT_SIZE >= 8 ) {
				return pack( 'J', $value ); // phpcs:ignore PHPCompatibility.ParameterValues.NewPackFormat.NewFormatFound
			}
			$highmap = 0xffffffff << 32;
			$higher  = ( $value & $highmap ) >> 32;
		} else {
			/*
			 * 32bit PHP can't shift 32 bits like that, so we have to assume 0 for the higher
			 * and not pack anything beyond it's limits.
			 */
			$higher = 0;
		}

		$lowmap = 0xffffffff;
		$lower  = $value & $lowmap;

		return pack( 'NN', $higher, $lower );
	}

	/**
	 * Calculate a valid code given the shared secret key
	 *
	 * @param string $key        The shared secret key to use for calculating code.
	 * @param mixed  $step_count The time step used to calculate the code, which is the floor of time() divided by step size.
	 * @param int    $digits     The number of digits in the returned code.
	 * @param string $hash       The hash used to calculate the code.
	 * @param int    $time_step  The size of the time step.
	 *
	 * @return string The totp code
	 */
	public static function calc_totp( $key, $step_count = false, $digits = self::DEFAULT_DIGIT_COUNT, $hash = self::DEFAULT_CRYPTO, $time_step = self::DEFAULT_TIME_STEP_SEC ) {
		$secret = self::base32_decode( $key );

		if ( false === $step_count ) {
			$step_count = floor( time() / $time_step );
		}

		$timestamp = self::pack64( $step_count );

		$hash = hash_hmac( $hash, $timestamp, $secret, true );

		$offset = ord( $hash[19] ) & 0xf;

		$code = (
				( ( ord( $hash[ $offset + 0 ] ) & 0x7f ) << 24 ) |
				( ( ord( $hash[ $offset + 1 ] ) & 0xff ) << 16 ) |
				( ( ord( $hash[ $offset + 2 ] ) & 0xff ) << 8 ) |
				( ord( $hash[ $offset + 3 ] ) & 0xff )
			) % pow( 10, $digits );

		return str_pad( $code, $digits, '0', STR_PAD_LEFT );
	}

	/**
	 * Whether this Two Factor provider is configured and available for the user specified.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 *
	 * @return boolean
	 */
	public function is_available_for_user( $user ) {
		// Only available if the secret key has been saved for the user.
		$key = $this->get_user_totp_key( $user->ID );

		return ! empty( $key );
	}

	/**
	 * Prints the form that prompts the user to authenticate.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 *
	 * @codeCoverageIgnore
	 */
	public function authentication_page( WP_User $user ) {
		require_once ABSPATH . '/wp-admin/includes/template.php';
		?>
		<p class="two-factor-prompt">
			<?php esc_html_e( 'Please enter the code generated by your authenticator app.', 'really-simple-ssl' ); ?>
		</p>
		<p>
			<label for="authcode"><?php esc_html_e( 'Authentication Code:', 'really-simple-ssl' ); ?></label>
			<input type="text" inputmode="numeric" autocomplete="one-time-code" name="authcode" id="authcode" class="input authcode" value="" size="20" pattern="[0-9 ]*" placeholder="123 456" data-digits="<?php echo esc_attr( self::DEFAULT_DIGIT_COUNT ); ?>" />
		</p>
		<script type="text/javascript">
			setTimeout( function(){
				var d;
				try{
					d = document.getElementById('authcode');
					d.focus();
				} catch(e){}
			}, 200);
		</script>
		<?php
		submit_button( __( 'Authenticate', 'really-simple-ssl' ) );
	}

	/**
	 * Returns a base32 encoded string.
	 *
	 * @param string $encoding_string String to be encoded using base32.
	 *
	 * @return string base32 encoded string without padding.
	 */
	public static function base32_encode( string $encoding_string ): string {
		if ( empty( $encoding_string ) ) {
			return '';
		}

		$binary_string = '';

		foreach ( str_split( $encoding_string ) as $character ) {
			$binary_string .= str_pad( base_convert( ord( $character ), 10, 2 ), 8, '0', STR_PAD_LEFT );
		}

		$five_bit_sections = str_split( $binary_string, 5 );
		$base32_string     = '';

		foreach ( $five_bit_sections as $five_bit_section ) {
			$base32_string .= self::$base_32_chars[ base_convert( str_pad( $five_bit_section, 5, '0' ), 2, 10 ) ];
		}

		return $base32_string;
	}

	/**
	 * Decode a base32 string and return a binary representation
	 *
	 * @param string $base32_string The base 32 string to decode.
	 *
	 * @return string Binary representation of decoded string
	 * @throws Exception If string contains non-base32 characters.
	 */
	public static function base32_decode( string $base32_string ): string {

		$base32_string = strtoupper( $base32_string );

		if ( ! preg_match( '/^[' . self::$base_32_chars . ']+$/', $base32_string, $match ) ) {
			throw new Exception( 'Invalid characters in the base32 string.' );
		}

		$l      = strlen( $base32_string );
		$n      = 0;
		$j      = 0;
		$binary = '';

		for ( $i = 0; $i < $l; $i++ ) {

			$n  = $n << 5; // Move buffer left by 5 to make room.
			$n  = $n + strpos( self::$base_32_chars, $base32_string[ $i ] );    // Add value into buffer.
			$j += 5; // Keep track of number of bits in buffer.

			if ( $j >= 8 ) {
				$j      -= 8;
				$binary .= chr( ( $n & ( 0xFF << $j ) ) >> $j );
			}
		}

		return $binary;
	}

	/**
	 * Used with usort to sort an array by distance from 0
	 *
	 * @param int $a First array element.
	 * @param int $b Second array element.
	 *
	 * @return int -1, 0, or 1 as needed by usort
	 */
	private static function abssort( $a, $b ): int {
		$a = abs( $a );
		$b = abs( $b );
		if ( $a === $b ) {
			return 0;
		}
		return ( $a < $b ) ? -1 : 1;
	}

	/**
	 * Determines if Two-Factor Authentication is forced for a given user.
	 *
	 * @param  WP_User $user  The user object to check.
	 *
	 * @return bool Returns true if Two-Factor Authentication is forced for the user, false otherwise.
	 * @since 1.0.0
	 */
	public static function is_forced( WP_User $user ): bool {
		if ( ! $user->exists() ) {
			return false;
		}
		return 'forced' === Rsssl_Two_Factor_Settings::get_role_status( 'totp', $user->ID );
	}

	/**
	 * Check if the user is optional for two-factor authentication.
	 *
	 * @param WP_User $user The user object.
	 *
	 * @return bool Returns true if the user is optional for two-factor authentication, otherwise false.
	 */
	public static function is_optional( WP_User $user ): bool {
		if ( ! $user->exists() ) {
			return false;
		}
		$user_roles = $user->roles;
		$fallback   = rsssl_get_option( 'two_fa_enabled_roles_email' );

		if ( empty( $fallback ) ) {
			return false;
		}
		if ( 'disabled' === Rsssl_Two_Factor_Settings::get_user_status( 'totp', $user->ID ) ) {
			return false;
		}

		return in_array( $user_roles[0], $fallback, true );
	}

	/**
	 * Get the selection option for a user.
	 *
	 * @param WP_User $user  The user for whom the selection option is retrieved.
	 * @param bool    $checked Whether the option is checked.
	 *
	 * @throws Exception If the template file is not found.
	 */
	public static function get_selection_option( WP_User $user, bool $checked = false ): void {
		// Get the preferred method meta, which could be a string or an array.
		$preferred_method_meta = get_user_meta( $user->ID, 'rsssl_two_fa_set_provider', true );
		// Normalize the preferred method to always be an array.
		$preferred_methods = is_array( $preferred_method_meta ) ? $preferred_method_meta : (array) $preferred_method_meta;
		// Check if 'Rsssl_Two_Factor_Email' is the preferred method.
		$is_preferred = in_array( 'Rsssl_Two_Factor_Totp', $preferred_methods, true );
		// if the META key is set than the current method is enabled based of the user META.
		$is_enabled = (bool) get_user_meta( $user->ID, self::SECRET_META_KEY, true );

		$badge_class       = $is_enabled ? 'badge-enabled' : 'badge-default';
		$enabled_text      = $is_enabled ? esc_html__(
			'Enabled',
			'really-simple-ssl'
		) : esc_html__( 'Disabled', 'really-simple-ssl' );
		$checked_attribute = $checked ? 'checked' : '';

		// Load the template.
		rsssl_load_template(
			'selectable-option.php',
			array(
				'badge_class'       => $badge_class,
				'enabled_text'      => $enabled_text,
				'checked_attribute' => $checked_attribute,
				'type'              => 'totp',
				'forcible'          => in_array( $user->roles[0], (array) rsssl_get_option( 'two_fa_forced_roles_totp' ), true ),
				'title'             => __( 'Authenticator app', 'really-simple-ssl' ),
				'description'       => __( 'Use an authenticator app on your mobile device to generate a code.', 'really-simple-ssl' ),
				'user'              => $user,
			),
			rsssl_path . 'assets/templates/two_fa/'
		);
	}

	/**
	 * Prints the QrCode generator.
	 *
	 * @return void
	 */
	public static function enqueue_qrcode_script(): void {
		$script_path = rsssl_url . 'assets/js/qrcode.min.js';
		wp_enqueue_script(
			'rsssl-qr-code-generator',
			$script_path,
			array(),
			rsssl_version,
			true
		);
	}

	/**
	 * Prints the onboarding form for selecting the preferred 2FA method.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 *
	 * @return void
	 * @throws Exception If the template file is not found.
	 */
	public static function display_onboarding_step_totp( WP_User $user ): void {
		// let us throw an error in for science’s sake.
		require_once ABSPATH . '/wp-admin/includes/template.php';
		self::enqueue_qrcode_script();
		$key          = self::generate_key();
		$totp_url     = self::generate_qr_code_url( $user, $key );
		$backup_codes = Rsssl_Two_Factor_Backup_Codes::generate_codes( $user );

        $totp_data = array(
            'totp_url'     => $totp_url,
            'key'          => $key,
            'backup_codes' => $backup_codes,
        );
        $translatables =  [
            'download_codes' => esc_html__('Download Backup Codes', 'really-simple-ssl'),
            'keyCopied' => __('Key copied', 'really-simple-ssl'),
            'keyCopiedFailed' => __('Could not copy text: ', 'really-simple-ssl')
        ];
        $totp_data_js = 'rsssl_onboard.totp_data = ' . json_encode($totp_data) . ';';
        $translatables_data_js = 'rsssl_onboard.translatables = ' . json_encode($translatables) . ';';

        $combined_js = $totp_data_js . ' ' . $translatables_data_js;


		wp_add_inline_script('rsssl-profile-settings', $combined_js, 'after');

		// Display the onboarding step content here.
		rsssl_load_template(
			'totp-config.php',
			array(
				'user'         => $user,
				'key'          => $key,
			),
			rsssl_path . 'assets/templates/two_fa/'
		);
	}

	/**
	 * Check if a user has two-factor provider authentication enabled.
	 *
	 * @param  WP_User $user  The WordPress user object to check.
	 *
	 * @return bool Returns true if two-factor authentication is enabled for the user, false otherwise.
	 */
	public static function is_enabled( WP_User $user ): bool {
        // if the pro is defined, return false and if the licence is not active.
        if(defined('rsssl_pro') && !rsssl_pro ) {
            return false;
        }
		if ( ! $user->exists()) {
			return false;
		}

		// Get all the user roles.
		$user_roles = $user->roles;
		// Then get the enabled roles.
		$enabled_roles = rsssl_get_option( 'two_fa_enabled_roles_totp' );
        if( is_multisite() ) {
            $user_roles = Rsssl_Two_Factor_Settings::get_strictest_role_across_sites($user->ID, ['totp']);
        }

        // if the user has the role that is enabled, return true.
        if ( ! is_array( $enabled_roles ) ) {
            $enabled_roles = array();
        }

        if(is_multisite()) {
            //compare the user roles with the enabled roles and if there is a match, return true
            return count(array_intersect($user_roles, $enabled_roles)) > 0;
        }
		// if the user has the role that is enabled, return true.
		return in_array( $user_roles[0], $enabled_roles, true );
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
		update_user_meta( $user_id, 'rsssl_two_fa_status_totp', $status );
	}

    public static function is_configured( WP_User $user ): bool {
        $status = get_user_meta( $user->ID, 'rsssl_two_fa_status_totp', true );
        return 'active' === $status;
    }

    public static function get_status( WP_User $user ): string {
	    return Rsssl_Two_Factor_Settings::get_user_status( 'totp', $user->ID );
    }
}
