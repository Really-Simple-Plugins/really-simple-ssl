<?php
/**
 * Class for creating a backup codes provider.
 *
 * @package Two_Factor
 */

namespace RSSSL\Security\WordPress\Two_Fa;

use WP_User;

/**
 * Class for creating a backup codes provider.
 *
 * @since 0.1-dev
 *
 * @package Two_Factor
 */
class Rsssl_Two_Factor_Backup_Codes extends Rsssl_Two_Factor_Provider {

	/**
	 * The user meta backup codes key.
	 *
	 * @type string
	 */
	public const BACKUP_CODES_META_KEY = '_rsssl_two_factor_backup_codes';

	/**
	 * The number backup codes.
	 *
	 * @type int
	 */
	public const NUMBER_OF_CODES = 10;

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
	 *
	 * @codeCoverageIgnore
	 */
	protected function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'two_factor_user_options_' . __CLASS__, array( $this, 'user_options' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

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
			'/generate-backup-codes',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rest_generate_codes' ),
				'permission_callback' => function ( $request ) {
					return current_user_can( 'edit_user', $request['user_id'] );
				},
				'args'                => array(
					'user_id'         => array(
						'required' => true,
						'type'     => 'integer',
					),
					'enable_provider' => array(
						'required' => false,
						'type'     => 'boolean',
						'default'  => false,
					),
				),
			)
		);
	}

	/**
	 * Displays an admin notice when backup codes have run out.
	 *
	 * @since 0.1-dev
	 *
	 * @codeCoverageIgnore
	 */
	public function admin_notices(): void {
		$user = wp_get_current_user();

		// Return if the provider is not enabled.
		if ( ! in_array( __CLASS__, Rsssl_Two_Factor::get_enabled_providers_for_user( $user->ID ), true ) ) {
			return;
		}

		// Return if not out of codes.
		if ( $this->is_available_for_user( $user ) ) {
			return;
		}
		?>
		<div class="error">
			<p>
				<span>
					<?php
					echo wp_kses(
						sprintf(
						/* translators: %s: URL for code regeneration */
							__( 'Two-Factor: You are out of backup codes and need to <a href="%s">regenerate!</a>', 'really-simple-ssl' ),
							esc_url( get_edit_user_link( $user->ID ) . '#two-factor-backup-codes' )
						),
						array( 'a' => array( 'href' => true ) )
					);
					?>
				<span>
			</p>
		</div>
		<?php
	}

	/**
	 * Returns the name of the provider.
	 *
	 * @since 0.1-dev
	 */
	public function get_label(): ?string {
		return _x( 'Backup Verification Codes (Single Use)', 'Provider Label', 'really-simple-ssl' );
	}

	/**
	 * Whether this Two Factor provider is configured and codes are available for the user specified.
	 *
	 * @since 0.1-dev
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 * @return boolean
	 */
	public function is_available_for_user( $user ): bool {
		// Does this user have available codes?
		if ( 0 < self::codes_remaining_for_user( $user ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Inserts markup at the end of the user profile field for this provider.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 *
	 * @since 0.1-dev
	 */
	public function user_options( WP_User $user ): void {
		wp_enqueue_script( 'wp-api-request' );
		wp_enqueue_script( 'jquery' );

		$count = self::codes_remaining_for_user( $user );
		?>
		<p id="two-factor-backup-codes">
			<button type="button" class="button button-two-factor-backup-codes-generate button-secondary hide-if-no-js">
				<?php esc_html_e( 'Generate Verification Codes', 'really-simple-ssl' ); ?>
			</button>
			<span class="two-factor-backup-codes-count">
			<?php
				echo esc_html(
					sprintf(
					/* translators: %s: count */
						_n( '%s unused code remaining.', '%s unused codes remaining.', $count, 'really-simple-ssl' ),
						$count
					)
				);
			?>
				</span>
		</p>
		<div class="two-factor-backup-codes-wrapper" style="display:none;">
			<ol class="two-factor-backup-codes-unused-codes"></ol>
			<p class="description"><?php esc_html_e( 'Write these down!  Once you navigate away from this page, you will not be able to view these codes again.', 'really-simple-ssl' ); ?></p>
			<p>
				<a class="button button-two-factor-backup-codes-download button-secondary hide-if-no-js" href="javascript:void(0);" id="two-factor-backup-codes-download-link" download="two-factor-backup-codes.txt"><?php esc_html_e( 'Download Codes', 'really-simple-ssl' ); ?></a>
			<p>
		</div>
		<script type="text/javascript">
			( function( $ ) {
				$( '.button-two-factor-backup-codes-generate' ).click( function() {
					wp.apiRequest( {
						method: 'POST',
						path: <?php echo wp_json_encode( Rsssl_Two_Factor::REST_NAMESPACE . '/generate-backup-codes' ); ?>,
						data: {
							user_id: <?php echo wp_json_encode( $user->ID ); ?>
						}
					} ).then( function( response ) {
						var $codesList = $( '.two-factor-backup-codes-unused-codes' );

						$( '.two-factor-backup-codes-wrapper' ).show();
						$codesList.html( '' );

						// Append the codes.
						for ( i = 0; i < response.codes.length; i++ ) {
							$codesList.append( '<li>' + response.codes[ i ] + '</li>' );
						}

						// Update counter.
						$( '.two-factor-backup-codes-count' ).html( response.i18n.count );
						$( '#two-factor-backup-codes-download-link' ).attr( 'href', response.download_link );
					} );
				} );
			} )( jQuery );
		</script>
		<?php
	}

	/**
	 * Generates backup codes & updates the user meta.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 * @param array   $args Optional arguments for assigning new codes.
	 *
	 * @return array
	 * @since 0.1-dev
	 */
	public static function generate_codes( WP_User $user, array $args = array() ): array {
		$codes        = array();
		$codes_hashed = array();

		// Check for arguments.
		if ( isset( $args['number'] ) ) {
			$num_codes = (int) $args['number'];
		} else {
			$num_codes = self::NUMBER_OF_CODES;
		}

		// Append or replace (default).
		if ( isset( $args['method'] ) && 'append' === $args['method'] ) {
			$codes_hashed = (array) get_user_meta( $user->ID, self::BACKUP_CODES_META_KEY, true );
		}

		for ( $i = 0; $i < $num_codes; $i++ ) {
			$code           = self::get_code();
			$codes_hashed[] = wp_hash_password( $code );
			$codes[]        = $code;
			unset( $code );
		}
		if ( isset( $args['cached'] ) && $args['cached'] ) {
			// Place the $codes in a transient for 5 minutes.
			set_transient( 'rsssl_two_factor_backup_codes_' . $user->ID, $codes, 300 );
		}
		update_user_meta( $user->ID, self::BACKUP_CODES_META_KEY, $codes_hashed );

		// Unhashed.
		return $codes;
	}

	/**
	 * Generates Backup Codes for returning through the WordPress Rest API.
	 *
	 * @param WP_REST_Request $request The WordPress REST request object.
	 *
	 * @since 0.8.0
	 */
	public function rest_generate_codes( WP_REST_Request $request ) {
		$user_id = $request['user_id'];
		$user    = get_user_by( 'id', $user_id );

		// Hardcode these, the user shouldn't be able to choose them.
		$args = array(
			'number' => self::NUMBER_OF_CODES,
			'method' => 'replace',
		);

		// Setup the return data.
		$codes = $this->generate_codes( $user, $args );
		$count = self::codes_remaining_for_user( $user );
		$title = sprintf(
			/* translators: %s: the site's domain */
			__( 'Two-Factor Backup Codes for %s', 'really-simple-ssl' ),
			home_url( '/' )
		);

		// Generate download content.
		$download_link  = 'data:application/text;charset=utf-8,';
		$download_link .= rawurlencode( "{$title}\r\n\r\n" );

		$i = 1;
		foreach ( $codes as $code ) {
			$download_link .= rawurlencode( "{$i}. {$code}\r\n" );
			++$i;
		}

		$i18n = array(
			/* translators: %s: count */
			'count' => esc_html( sprintf( _n( '%s unused code remaining.', '%s unused codes remaining.', $count, 'really-simple-ssl' ), $count ) ),
		);

		if ( $request->get_param( 'enable_provider' ) && ! Rsssl_Two_Factor::enable_provider_for_user( $user_id, 'Two_Factor_Backup_Codes' ) ) {
			return new WP_Error( 'db_error', __( 'Unable to enable Backup Codes provider for this user.', 'really-simple-ssl' ), array( 'status' => 500 ) );
		}

		return array(
			'codes'         => $codes,
			'download_link' => $download_link,
			'remaining'     => $count,
			'i18n'          => $i18n,
		);
	}

	/**
	 * Returns the number of unused codes for the specified user
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 * @return int $int  The number of unused codes remaining
	 */
	public static function codes_remaining_for_user( $user ) {
		$backup_codes = get_user_meta( $user->ID, self::BACKUP_CODES_META_KEY, true );
		if ( is_array( $backup_codes ) && ! empty( $backup_codes ) ) {
			return count( $backup_codes );
		}
		return 0;
	}

	/**
	 * Prints the form that prompts the user to authenticate.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 *
	 * @since 0.1-dev
	 */
	public function authentication_page( WP_User $user ) {
		require_once ABSPATH . '/wp-admin/includes/template.php';
		?>
		<p class="two-factor-prompt"><?php esc_html_e( 'Enter a backup verification code.', 'really-simple-ssl' ); ?></p>
		<p>
			<label for="authcode"><?php esc_html_e( 'Verification Code:', 'really-simple-ssl' ); ?></label>
			<input type="text" inputmode="numeric" name="two-factor-backup-code" id="authcode" class="input authcode" value="" size="20" pattern="[0-9 ]*" placeholder="1234 5678" data-digits="8" />
		</p>
		<?php
		submit_button( __( 'Submit', 'really-simple-ssl' ) );
	}

	/**
	 * Validates the users input token.
	 *
	 * In this class just return true.
	 *
	 * @since 0.1-dev
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 * @return boolean
	 */
	public function validate_authentication( $user ) {
		$backup_code = self::sanitize_code_from_request( 'two-factor-backup-code' );
		if ( ! $backup_code ) {
			return false;
		}

		return self::validate_code( $user, $backup_code );
	}

	/**
	 * Validates a backup code.
	 *
	 * Backup Codes are single use and are deleted upon a successful validation.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 * @param int $code The backup code.
	 * @return boolean
	 *@since 0.1-dev
	 *
	 */
	public static function validate_code(WP_User $user, int $code, bool $delete = true): bool {
		$backup_codes = get_user_meta( $user->ID, self::BACKUP_CODES_META_KEY, true );

		if ( is_array( $backup_codes ) && ! empty( $backup_codes ) ) {
			foreach ( $backup_codes as $code_index => $code_hashed ) {
				if ( wp_check_password( $code, $code_hashed, $user->ID ) ) {
                    if ( $delete ) {
                        self::delete_code( $user, $code_hashed );
                    }
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Deletes a backup code.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 * @param  string  $code_hashed The hashed the backup code.
	 *
	 * @since 0.1-dev
	 */
	public static function delete_code( WP_User $user, string $code_hashed ): void {
		$backup_codes = get_user_meta( $user->ID, self::BACKUP_CODES_META_KEY, true );

		// Delete the current code from the list since it's been used.
		$backup_codes = array_flip( $backup_codes );
		unset( $backup_codes[ $code_hashed ] );
		$backup_codes = array_values( array_flip( $backup_codes ) );

		// Update the backup code master list.
		update_user_meta( $user->ID, self::BACKUP_CODES_META_KEY, $backup_codes );
	}

	/**
	 * Determines if a user is selectable for a specific authentication method.
	 *
	 * @param  WP_User $user  The user object to check.
	 *
	 * @return bool Returns true if the user is selectable for the authentication method, false otherwise.
	 */
	public static function is_selectable_for_user( WP_User $user ): bool {
		// TODO: Logic for when backup codes are needed (for now only totp).
		if ( Rsssl_Two_Factor_Totp::is_selectable_for_user( $user ) ) {
			return true;
		}
		return false;
	}
}
