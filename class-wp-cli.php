<?php
defined( 'ABSPATH' ) or die();

require_once rsssl_path . 'lib/admin/class-encryption.php';

use RSSSL\lib\admin\Encryption;
use RSSSL\Pro\Security\WordPress\Firewall\Models\Rsssl_404_Block;
use RSSSL\Security\WordPress\Two_Fa\Rsssl_Two_Fa_Status;
use RSSSL\Security\WordPress\Two_Fa\Repositories\Rsssl_Two_Fa_User_Repository;
use RSSSL\Security\WordPress\Two_Fa\Services\Rsssl_Two_Fa_Reminder_Service;
use RSSSL\Security\WordPress\Two_Fa\Models\Rsssl_Two_FA_Data_Parameters;

/**
 * WP-CLI integration for Really Simple Security
 *
 * For an overview of commands use wp help rsssl
 *
 * Usage examples:
 * wp rsssl activate_ssl
 * wp rsssl deactivate_ssl
 * wp rsssl activate_recommended_features
 * wp rsssl deactivate_recommended_features
 * wp rsssl activate_security_headers
 * wp rsssl deactivate_security_headers
 * wp rsssl update_option --name=site_has_ssl --value=true
 *
 * Booleans should be passed to update_option as 0 or 1.
 *
 * To complete all standard dashboard notices (recommended features + .htaccess redirect + HSTS + e-mail verification):
 *
 * wp rsssl activate_recommended_features
 * wp rsssl update_option --name=redirect --value=htaccess
 * wp rsssl update_option --name=hsts --value=1
 * wp rsssl update_option --name=hsts_preload --value=1
 * wp rsssl update_option --name=hsts_subdomains --value=1
 * wp rsssl update_option --name=hsts_max_age --value='63072000'
 * wp rsssl update_option --name=notifications_email_address --value='you@example.com'
 * wp option update rsssl_email_verification_status 'completed'
 */
class rsssl_wp_cli {

	use Encryption;

	public function __construct() {
		if ( $this->wp_cli_active() ) {
			add_action( 'init', [ $this, 'register_wp_cli_commands' ], 0 );
		}
	}

	/**
	 * Checks if the conditions for running a Pro WP-CLI command are met.
	 * This is called *within* the command handler, ensuring plugin is loaded.
	 * Outputs an error and exits if conditions are not met.
	 *
	 * @return bool True if conditions are met, false otherwise (though it usually exits on false).
	 */
	private function check_pro_command_preconditions(bool $skip_license = false ): bool {
		// Skip license check for free (non-pro) commands
		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
		$command = $backtrace[1]['function'] ?? '';
		$command_list = $this->get_command_list();
		if ( isset($command_list[$command]) && $command_list[$command]['pro'] === false ) {
			return true;
		}
		// Check if Pro is active (redundant check, but safe)
		if ( ! defined( 'rsssl_pro' ) ) {
			WP_CLI::error(
				__( 'This command is related to functionality available in Really Simple Security Pro, please consider upgrading to unlock all powerful security features. Read more: https://really-simple-ssl.com/pro', 'really-simple-ssl' ),
				true // Exit after error
			);
			return false; // Should not be reached
		}

		if ( $skip_license ) {
			return true; // Skip license check if explicitly requested
		}
		// Check if license is valid (now safe to call)
		if ( ! RSSSL()->licensing->license_is_valid() ) {
			$activate_command = 'wp rsssl activate_license <YOUR_LICENSE_KEY>';
			// Check if the command exists in the list just to be safe
			if (!isset($this->get_command_list()['activate_license'])) {
				$activate_command = 'activate_license'; // Fallback text
			}
			WP_CLI::error(
				sprintf(
					__( 'It seems that no valid license key is activated for this domain. Activate your license key using the `%s` command, or purchase a valid license key via https://really-simple-ssl.com/pro', 'really-simple-ssl' ),
					$activate_command
				),
				true // Exit after error
			);
			return false; // Should not be reached
		}

		// All checks passed
		return true;
	}

	/**
	 * Check if WP-CLI is active.
	 *
	 * @return bool True if WP-CLI is active, false otherwise.
	 */
	public function wp_cli_active() {
		return defined( 'WP_CLI' ) && WP_CLI;
	}

	/**
	 * Activate SSL through WP-CLI.
	 *
	 * Provides options for verbose output, forcing activation despite warnings,
	 * skipping confirmation, and performing a dry run.
	 *
	 * ## OPTIONS
	 *
	 * [--verbose]
	 * : Show detailed steps during activation.
	 *
	 * [--force]
	 * : Force activation even if pre-flight checks issue warnings and skip confirmation prompt.
	 *
	 * [--yes]
	 * : Skip the confirmation prompt before activating.
	 *
	 * [--dry-run]
	 * : Perform checks and report intended actions without making changes.
	 *
	 * ## EXAMPLES
	 *
	 *     wp rsssl activate_ssl
	 *     wp rsssl activate_ssl --verbose --yes
	 *     wp rsssl activate_ssl --dry-run
	 *
	 * @param array $args Positional arguments (none used here).
	 * @param array $assoc_args Associative arguments (--verbose, --force, --yes, --dry-run).
	 * @return void
	 */
	public function activate_ssl( $args, $assoc_args ) {
		if ( ! $this->check_pro_command_preconditions() ) return;
		$is_verbose = WP_CLI\Utils\get_flag_value( $assoc_args, 'verbose', false );
		$is_force   = WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false );
		$skip_confirm = WP_CLI\Utils\get_flag_value( $assoc_args, 'yes', false );
		$is_dry_run = WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );

		if ( $is_dry_run ) {
			WP_CLI::line( "-- Dry Run Enabled: No changes will be made. --" );
		}

		try {
			// --- Suggestion 3: Pre-flight Checks ---
			if ( $is_verbose || $is_dry_run ) WP_CLI::debug( 'Running pre-activation checks...', 'rsssl-cli' );

			// Assume this function now exists and returns ['success' => bool, 'message' => string, 'warnings' => array]
			$checks = $this->perform_pre_flight_checks();

			if ( ! empty( $checks['warnings'] ) ) {
				foreach ( $checks['warnings'] as $warning ) {
					WP_CLI::warning( $warning );
				}
				if ( ! $is_force && ! $is_dry_run ) {
					WP_CLI::error( 'Pre-flight checks issued warnings. Use --force to proceed anyway.', false ); // Use false to allow dry-run continue
					if (!$is_dry_run) return; // Stop if not dry run
				}
			}

			if ( ! $checks['success'] ) {
				 // If checks outright fail (not just warnings)
				 WP_CLI::error( 'Pre-flight checks failed: ' . $checks['message'] );
				 return;
			}

			if ( $is_verbose || $is_dry_run ) WP_CLI::debug( 'Pre-flight checks passed.', 'rsssl-cli' );


			// --- Report Intended Actions (Dry Run) ---
			if ( $is_dry_run ) {
				 WP_CLI::line( "Intended actions:" );
				 WP_CLI::line( "- Update WordPress Site URL and Home URL to HTTPS." );
				 WP_CLI::line( "- Configure redirects (method depends on settings)." );
				 WP_CLI::line( "- Update internal links/content (if mixed content fixer enabled)." );
				 WP_CLI::line( "- Dismiss onboarding notice." );
				 WP_CLI::success( "Dry run complete. No changes were made." );
				 return; // End dry run here
			}


			// --- Suggestion 4: Confirmation Prompt ---
			// Skip confirmation if --yes or --force is used
			if ( ! $skip_confirm && ! $is_force ) {
				WP_CLI::confirm( 'Are you sure you want to activate SSL for this site?' );
				// WP_CLI::confirm exits script if user doesn't confirm
			}

			// --- Core Activation Logic ---
			if ( $is_verbose ) WP_CLI::debug( 'Attempting SSL activation...', 'rsssl-cli' );

			// --- Suggestion 5: Clarify Side Effects ---
			// Move onboarding dismissal inside the main activation logic or make it explicit
			// update_option( 'rsssl_onboarding_dismissed', true, false ); // Optionally moved inside activate_ssl or reported

			// --- Suggestion 1: Granular Failure Reasons ---
			// Assume RSSSL()->admin->activate_ssl() now returns an array or throws specific exceptions
			// Passing $is_verbose allows the underlying function to potentially output debug info too
			$result = RSSSL()->admin->activate_ssl( $is_verbose );

			// Check if $result is structured like ['success' => bool, 'message' => string]
			if ( is_array( $result ) && isset( $result['success'] ) ) {
				if ( $result['success'] ) {
					$success_message = 'SSL activated successfully.';
					// Suggestion 5: Clarify Side Effects (Example)
					 if ( get_option('rsssl_onboarding_dismissed') ) {
						 $success_message .= ' Onboarding notice dismissed.';
					 }
					WP_CLI::success( $success_message );
				} else {
					// Use the detailed message from the function
					WP_CLI::error( 'SSL activation failed: ' . ( $result['message'] ?? 'Unknown reason.' ) );
				}
			} else if ( $result === true ) { // Handle simple boolean success
				 WP_CLI::success( 'SSL activated successfully. Onboarding notice dismissed.' );
			} else { // Handle simple boolean failure or unexpected return
				 WP_CLI::error( 'SSL activation failed (unknown reason).' );
			}


		} catch ( Exception $e ) { // Catch specific exceptions if activate_ssl throws them
			// Suggestion 1 & 2: More specific error based on exception type if possible
			WP_CLI::error( 'Failed to activate SSL due to an unexpected error: ' . $e->getMessage() );
		}
	}

	/**
	 * Deactivate SSL through WP-CLI.
	 *
	 * @return void
	 */
	public function deactivate_ssl() {
		if ( ! $this->check_pro_command_preconditions() ) return;
		try {
			RSSSL()->admin->deactivate();
			WP_CLI::success( 'SSL deactivated' );
		} catch ( Exception $e ) {
			WP_CLI::error( 'Failed to deactivate SSL: ' . $e->getMessage() );
		}
	}

	/**
	 * Update a Really Simple Security option via WP-CLI.
	 * Booleans should be passed as 0 or 1.
	 *
	 * @param array $args Command-line positional arguments.
	 * @param array $assoc_args Command-line associative arguments.
	 *
	 * @return void
	 */
	public function update_option( $args, $assoc_args ) {
		if ( ! isset( $assoc_args['name'] ) || ! isset( $assoc_args['value'] ) ) {
			WP_CLI::error( 'Both --name and --value parameters are required.' );
		}

		$name  = sanitize_title( $assoc_args['name'] );
		$value = $assoc_args['value'];

		try {
			rsssl_update_option( $name, $value );
			WP_CLI::success( "Option $name updated to $value" );
		} catch ( Exception $e ) {
			WP_CLI::error( 'Failed to update option: ' . $e->getMessage() );
		}
	}

	/**
	 * Activate all recommended features via CLI
	 *
	 * @throws Exception
	 * return void
	 */
	public function activate_recommended_features() {
		if ( ! $this->check_pro_command_preconditions() ) return;
		try {
			RSSSL()->admin->activate_recommended_features();
		} catch ( Exception $e ) {
			WP_CLI::error( 'Failed to activate recommended features. ' . $e->getMessage() );
		}

		WP_CLI::success( 'Recommended features activated.' );
	}

	/**
	 * Deactivate all recommended features via CLI
	 *
	 * return void
	 */
	public function deactivate_recommended_features() {
		if ( ! $this->check_pro_command_preconditions() ) return;
		try {
			// Deactivate Vulnerability Scanner
			rsssl_update_option( 'enable_vulnerability_scanner', false );

			// Deactivate essential WordPress hardening features
            if (isset(RSSSL()->settingsConfigService)) {
                $recommended_hardening_fields = RSSSL()->settingsConfigService->getRecommendedHardeningSettings();
                foreach ( $recommended_hardening_fields as $field ) {
                    rsssl_update_option( $field, false );
                }
            }

			// Disable Email login protection
			rsssl_update_option( 'login_protection_enabled', false );

			// Disable Mixed Content Fixer
			rsssl_update_option( 'mixed_content_fixer', false );

			// Disable firewall
			rsssl_update_option( 'enable_firewall', false );
			rsssl_update_option( 'event_log_enabled', false );
			// Check if PRO version is active, then deactivate premium features
			if ( defined( 'rsssl_pro' ) ) {
				// Disable Two-Factor Authentication
				rsssl_update_option( 'two_fa_enabled_roles_totp', [] );

				// Disable Limit Login Attempts
				rsssl_update_option( 'enable_limited_login_attempts', false );

				// Disable advanced security headers
				$security_headers = [
					'upgrade_insecure_requests',
					'x_content_type_options',
					'hsts',
					'x_xss_protection',
					'x_frame_options',
					'referrer_policy',
					'csp_frame_ancestors',
				];
				foreach ( $security_headers as $header_key => $header_value ) {
					if ( is_string( $header_key ) ) {
						rsssl_update_option( $header_key, false );
					} else {
						rsssl_update_option( $header_value, false );
					}
				}

				// Deactivate password security enforcement
				rsssl_update_option( 'enforce_password_security_enabled', false );
				rsssl_update_option( 'enable_hibp_check', false );
			}

			do_action('rsssl_update_rules');
			WP_CLI::success( 'Recommended features deactivated.' );
		} catch ( Exception $e ) {
			WP_CLI::error( 'Failed to deactivate recommended features: ' . $e->getMessage() );
		}
	}

	/**
	 * Activate all recommended hardening features via CLI
	 *
	 * return void
	 */
	public function activate_recommended_hardening_features() {
		if ( ! $this->check_pro_command_preconditions() ) return;
		try {
            if (isset(RSSSL()->settingsConfigService)) {
                $recommended_hardening_fields = RSSSL()->settingsConfigService->getRecommendedHardeningSettings();
                foreach ( $recommended_hardening_fields as $field ) {
                    rsssl_update_option( $field, true );
                }
            }
			do_action('rsssl_update_rules');
			WP_CLI::success( 'Recommended hardening features activated.' );
		} catch ( Exception $e ) {
			WP_CLI::error( 'Failed to activate recommended hardening features: ' . $e->getMessage() );
		}
	}

	/**
	 * Deactivate all recommended features via CLI
	 *
	 * return void
	 */
	public function deactivate_recommended_hardening_features() {
		if ( ! $this->check_pro_command_preconditions() ) return;
		try {
            if (isset(RSSSL()->settingsConfigService)) {
                $recommended_hardening_fields = RSSSL()->settingsConfigService->getRecommendedHardeningSettings();
                foreach ( $recommended_hardening_fields as $field ) {
                    rsssl_update_option( $field, false );
                }
            }
			do_action('rsssl_update_rules');
			WP_CLI::success( 'Recommended hardening features deactivated.' );
		} catch ( Exception $e ) {
			WP_CLI::error( 'Failed to deactivate recommended hardening features: ' . $e->getMessage() );
		}
	}


	/**
	 * Activate recommended security headers via CLI
	 */
    public function activate_security_headers() {
        if ( ! $this->check_pro_command_preconditions() ) return;
        try {
            foreach (RSSSL()->headers->get_recommended_security_headers() as $header ) {
                if (isset($header['option_name'], $header['recommended_setting'])) {
                    rsssl_update_option( $header['option_name'], $header['recommended_setting'] );
                }
            }
            WP_CLI::success( 'Recommended security header settings saved. Run "update_advanced_headers" command to activate them.' );
            do_action('rsssl_update_rules');
        } catch ( Exception $e ) {
            WP_CLI::error( 'Failed to activate security headers: ' . $e->getMessage() );
        }
    }


	/**
	 * Deactivate recommended security headers via CLI
	 */
	public function deactivate_security_headers() {
		if ( ! $this->check_pro_command_preconditions() ) return;
		try {
			$recommended_headers = RSSSL()->headers->get_recommended_security_headers();

			foreach ( $recommended_headers as $header ) {
                if ( isset( $header['option_name'] ) && isset( $header['disabled_setting'] ) ) {
                    rsssl_update_option($header['option_name'], $header['disabled_setting']);
                }
			}
			do_action('rsssl_update_rules');
			WP_CLI::success( 'Recommended security headers deactivated.' );
		} catch ( Exception $e ) {
			WP_CLI::error( 'Failed to deactivate security headers: ' . $e->getMessage() );
		}
	}

	/**
	 * Activate firewall via CLI
	 *
	 * return void
	 */

	public function activate_firewall() {
		if ( ! $this->check_pro_command_preconditions() ) return;
		try {
			rsssl_update_option( 'enable_firewall', true );
			rsssl_update_option( 'event_log_enabled', true );
			do_action('rsssl_update_rules');
			WP_CLI::success( 'Firewall activated.' );
		} catch ( Exception $e ) {
			WP_CLI::error( 'Failed to activate firewall: ' . $e->getMessage() );
		}
	}

	/**
	 * Deactivate firewall via CLI
	 *
	 * return void
	 */
	public function deactivate_firewall() {
		if ( ! $this->check_pro_command_preconditions() ) return;
		try {
			rsssl_update_option( 'enable_firewall', false );
			rsssl_update_option( 'event_log_enabled', false );
			do_action('rsssl_update_rules');
			WP_CLI::success( 'Firewall deactivated.' );
		} catch ( Exception $e ) {
			WP_CLI::error( 'Failed to deactivate firewall: ' . $e->getMessage() );
		}
	}

	/**
	 * Activate Two-Factor Authentication via CLI
	 *
	 * return void
	 */
	public function activate_2fa() {
		if ( ! $this->check_pro_command_preconditions() ) return;
		try {
			rsssl_update_option( 'two_fa_enabled_roles_totp', [ 'administrator' ] );
			rsssl_update_option( 'login_protection_enabled', true );
			WP_CLI::success( 'Two-Factor Authentication activated.' );
		} catch ( Exception $e ) {
			WP_CLI::error( 'Failed to activate Two-Factor Authentication: ' . $e->getMessage() );
		}
	}

	/**
	 * Deactivate Two-Factor Authentication via CLI
	 *
	 * return void
	 */
	public function deactivate_2fa() {
		if ( ! $this->check_pro_command_preconditions() ) return;
		try {
			rsssl_update_option( 'two_fa_enabled_roles_totp', [] );
			rsssl_update_option( 'login_protection_enabled', false );
			WP_CLI::success( 'Two-Factor Authentication deactivated.' );
		} catch ( Exception $e ) {
			WP_CLI::error( 'Failed to deactivate Two-Factor Authentication: ' . $e->getMessage() );
		}
	}

	/**
	 * Activate password security via CLI
	 *
	 * return void
	 */
	public function activate_password_security() {
		if ( ! $this->check_pro_command_preconditions() ) return;
		try {
			rsssl_update_option( 'enforce_password_security_enabled', true );
			rsssl_update_option( 'enforce_frequent_password_change', true );
			rsssl_update_option( 'hide_rememberme', true );
			rsssl_update_option( 'enable_hibp_check', true );
			WP_CLI::success( 'Password security features activated.' );
		} catch ( Exception $e ) {
			WP_CLI::error( 'Failed to activate password security: ' . $e->getMessage() );
		}
	}

	/**
	 * Deactivate password security via CLI
	 *
	 * return void
	 */
	public function deactivate_password_security() {
		if ( ! $this->check_pro_command_preconditions() ) return;
		try {
			rsssl_update_option( 'enforce_password_security_enabled', false );
            rsssl_update_option( 'enforce_frequent_password_change', false );
            rsssl_update_option( 'hide_rememberme', false );
			rsssl_update_option( 'enable_hibp_check', false );
			do_action('rsssl_update_rules');
			WP_CLI::success( 'Password security features deactivated.' );
		} catch ( Exception $e ) {
			WP_CLI::error( 'Failed to deactivate password security: ' . $e->getMessage() );
		}
	}

	/**
	 * Activate login attempts limitation via CLI
	 *
	 * return void
	 */
	public function activate_lla() {
		if ( ! $this->check_pro_command_preconditions() ) return;
		try {
			rsssl_update_option( 'enable_limited_login_attempts', true );
			rsssl_update_option( 'event_log_enabled', true );
			WP_CLI::success( 'Limit login attempts activated.' );
			do_action('rsssl_update_rules');
		} catch ( Exception $e ) {
			WP_CLI::error( 'Failed to activate limit login attempts: ' . $e->getMessage() );
		}
	}

	/**
	 * Deactivate login attempts limitation via CLI
	 *
	 * return void
	 */
	public function deactivate_lla() {
		if ( ! $this->check_pro_command_preconditions() ) return;
		try {
			rsssl_update_option( 'enable_limited_login_attempts', false );
			rsssl_update_option( 'event_log_enabled', false );
			do_action('rsssl_update_rules');
			WP_CLI::success( 'Limit login attempts deactivated.' );
		} catch ( Exception $e ) {
			WP_CLI::error( 'Failed to deactivate limit login attempts: ' . $e->getMessage() );
		}
	}

	/**
	 * Activate vulnerability scanning via CLI
	 *
	 * return void
	 */
	public function activate_vulnerability_scanning() {
		if ( ! $this->check_pro_command_preconditions() ) return;
		try {
			rsssl_update_option( 'enable_vulnerability_scanner', true );

			WP_CLI::success( 'Vulnerability scanning activated.' );
		} catch ( Exception $e ) {
			WP_CLI::error( 'Failed to activate vulnerability scanning: ' . $e->getMessage() );
		}
	}

	/**
	 * Deactivate vulnerability scanning via CLI
	 *
	 * return void
	 */
	public function deactivate_vulnerability_scanning() {
		if ( ! $this->check_pro_command_preconditions() ) return;
		try {
			rsssl_update_option( 'enable_vulnerability_scanner', false );

			WP_CLI::success( 'Vulnerability scanning deactivated.' );
		} catch ( Exception $e ) {
			WP_CLI::error( 'Failed to deactivate vulnerability scanning: ' . $e->getMessage() );
		}
	}

	/**
	 * Activate license via CLI
	 *
	 * @param array $args Positional arguments. License should be passed as first and only argument
	 *
	 * @return void
	 */
	public function activate_license( $args ) {
		if ( ! $this->check_pro_command_preconditions(true) ) return;
		try {
			// Check if license key is provided
			if ( empty( $args[0] ) ) {
				WP_CLI::error( 'Please provide a license key: wp rsssl activate_license YOUR_LICENSE_KEY' );

				return;
			}

			$license_key = sanitize_text_field( $args[0] );

			rsssl_update_option( 'license', $this->encrypt_with_prefix( $license_key, 'really_simple_ssl_' ) );
			$status = RSSSL()->licensing->get_license_status( 'check_license', true );

			update_option( 'rsssl_onboarding_dismissed', true, false );

			if ( $status === 'valid' ) {
				WP_CLI::success( 'License activated successfully.' );
			} elseif ( $status === 'invalid' || $status === 'missing' ) {
				WP_CLI::error( 'Invalid license key. You can find your license key on https://really-simple-ssl.com/account' );
			} elseif ( $status === 'expired' ) {
				WP_CLI::error( 'License has expired. Please renew via https://really-simple-ssl.com/account/subscriptions' );
			} elseif ( $status === 'no_activations_left' ) {
				WP_CLI::error( 'No activations left. Please upgrade your license via https://really-simple-ssl.com/account/subscriptions' );
			} elseif ( $status === 'disabled' ) {
				WP_CLI::error( 'This license is not valid. Find out why on your account page at https://really-simple-ssl.com/account' );
			}
		} catch ( Exception $e ) {
			WP_CLI::error( 'Failed to activate license: ' . $e->getMessage() );
		}
	}

	/**
	 * Deactivate license via CLI
	 *
	 * @return void
	 */
	public function deactivate_license() {
		if ( ! $this->check_pro_command_preconditions() ) return;
		try {
			rsssl_update_option( 'license', '' );
			$status = RSSSL()->licensing->get_license_status( 'check_license', true );
			update_option( 'rsssl_onboarding_dismissed', true, false );

			// License key should now be empty
			if ( $status === 'empty' ) {
				WP_CLI::success( 'License deactivated successfully.' );
			} else {
				WP_CLI::error( 'Something went wrong when deactivating your license. Please try again.' );
			}

		} catch ( Exception $e ) {
			WP_CLI::error( 'Failed to deactivate license: ' . $e->getMessage() );
		}
	}

	/**
	 * Add lock file for safe mode
	 *
	 * @return void
	 */
	public function add_lock_file() {
		if ( ! $this->check_pro_command_preconditions() ) return;
		try {
			$lock_file = WP_CONTENT_DIR . '/rsssl-safe-mode.lock';

			// Check if file already exists
			if ( file_exists( $lock_file ) ) {
				WP_CLI::warning( 'Lock file already exists.' );

				return;
			}

			// Create lock file
			$result = file_put_contents( $lock_file, time() );

			if ( $result === false ) {
				WP_CLI::error( 'Unable to create lock file.' );
			}

			// Set proper permissions
			chmod( $lock_file, 0644 );

			WP_CLI::success( 'Safe mode lock file created successfully.' );
		} catch ( Exception $e ) {
			WP_CLI::error( 'Failed to create lock file: ' . $e->getMessage() );
		}
	}

	/**
	 * Remove lock file for safe mode
	 *
	 * @return void
	 */
	public function remove_lock_file() {
		if ( ! $this->check_pro_command_preconditions() ) return;
		try {
			$lock_file = WP_CONTENT_DIR . '/rsssl-safe-mode.lock';

			// Check if file exists
			if ( ! file_exists( $lock_file ) ) {
				WP_CLI::warning( 'Lock file does not exist.' );

				return;
			}

			// Remove lock file
			if ( ! unlink( $lock_file ) ) {
				WP_CLI::error( 'Unable to remove lock file.' );
			}

			WP_CLI::success( 'Safe mode lock file removed successfully.' );
		} catch ( Exception $e ) {
			WP_CLI::error( 'Failed to remove lock file: ' . $e->getMessage() );
		}
	}

	/**
	 * Reset the 2FA status of a user to disabled
	 *
	 * Usage: wp rsssl reset_2fa 123
	 *
	 * @param array $args User ID should be the first element
	 *
	 * @throws \WP_CLI\ExitException
	 */
	public function reset_2fa( $args ): void
    {
        if ( ! $this->check_pro_command_preconditions() ) return;
        // When empty array is passed, WP_CLI will return an error
        if ( empty( $args ) ) {
            WP_CLI::error( 'Please provide a user ID.', true );
        }
        $user_id = intval( $args[0] );
        $user = get_user_by('id', $user_id);

        if (empty($user)) {
            WP_CLI::error('User not found.', true);
        }

        if (!class_exists('Rsssl_Two_Fa_Status')) {
            require_once rsssl_path . '/security/wordpress/two-fa/class-rsssl-two-fa-status.php';
        }

	    if ( $user ) {
		    // Delete all 2fa related user meta.
		    Rsssl_Two_Fa_Status::delete_two_fa_meta( $user->ID );
		    // Set the last login to now, so the user will be forced to use 2fa.
		    update_user_meta( $user->ID, 'rsssl_two_fa_last_login', gmdate( 'Y-m-d H:i:s' ) );
		    delete_user_meta( $user->ID, 'rsssl_passkey_configured'); // Remove passkey configuration if it exists
	    }

        WP_CLI::success( 'Successfully reset 2FA for user id ' . $user_id );
	}

    /**
     * Preview (dry-run) which users are in scope for 2FA reminders, optionally across subsites.
     *
     * Usage examples:
     *   wp rsssl twofa_preview
     *   wp rsssl twofa_preview --role=editor
     *   wp rsssl twofa_preview --include-subsites
     *   wp rsssl twofa_preview --site=7 --format=json
     *   wp rsssl twofa_preview --reset-meta
     */
    public function twofa_preview( $args, $assoc_args ) {
        if ( ! $this->check_pro_command_preconditions() ) return;

        $role           = $assoc_args['role'] ?? 'all';
        $format         = $assoc_args['format'] ?? 'table';
        $includeNetwork = \WP_CLI\Utils\get_flag_value( $assoc_args, 'include-subsites', false );
        $specificSiteId = $assoc_args['site'] ?? null;
        $doResetMeta    = \WP_CLI\Utils\get_flag_value( $assoc_args, 'reset-meta', false );

        $rows = $this->collect_twofa_rows( $role, $includeNetwork, $specificSiteId, $doResetMeta );

        if ( empty( $rows ) ) {
            \WP_CLI::success( 'Geen gebruikers gevonden in de huidige 2FA scope.' );
            return;
        }

        \WP_CLI\Utils\format_items( $format, $rows, [ 'blog_id','user_id','user_login','email','roles','reminder_sent' ] );
    }

    /**
     * Send 2FA reminders for the current selection. Explicitly triggers the send flow per (sub)site.
     *
     * Usage examples:
     *   wp rsssl twofa_send
     *   wp rsssl twofa_send --role=author --site=3
     *   wp rsssl twofa_send --include-subsites --reset-meta
     */
    public function twofa_send( $args, $assoc_args ) {
        if ( ! $this->check_pro_command_preconditions() ) return;

        $role           = $assoc_args['role'] ?? 'all';
        $includeNetwork = \WP_CLI\Utils\get_flag_value( $assoc_args, 'include-subsites', false );
        $specificSiteId = $assoc_args['site'] ?? null;
        $doResetMeta    = \WP_CLI\Utils\get_flag_value( $assoc_args, 'reset-meta', false );

        $service = new Rsssl_Two_Fa_Reminder_Service();
        $siteIds = $this->determine_sites_for_twofa( $includeNetwork, $specificSiteId );
        $total   = 0;

        foreach ( $siteIds as $blog_id ) {
            $this->with_blog_for_twofa( (int) $blog_id, function() use ( $role, $service, $doResetMeta, &$total, $blog_id ) {
                $repo   = new Rsssl_Two_Fa_User_Repository();
                $params = new Rsssl_Two_FA_Data_Parameters([
                    'filter_column' => 'user_role',
                    'filter_value'  => $role,
                ]);
                $collection = $repo->getForcedTwoFaUsersWithOpenStatus( $params );

                if ( $doResetMeta ) {
                    foreach ( $collection->getUsers() as $u ) {
                        delete_user_meta( $u->getId(), 'rsssl_two_fa_reminder_sent' );
                    }
                }

                $countBefore = (int) $collection->getTotalRecords();
                if ( $countBefore > 0 ) {
                    \WP_CLI::log( sprintf( 'Blog %d: verstuur reminders naar %d gebruiker(s)...', (int) $blog_id, $countBefore ) );
                    $service->processReminders( $collection );
                    $total += $countBefore;
                } else {
                    \WP_CLI::log( sprintf( 'Blog %d: geen kandidaten.', (int) $blog_id ) );
                }
            } );
        }

        \WP_CLI::success( sprintf( 'Verzenden gereed. Totaal verstuurd: %d', (int) $total ) );
    }

    /** ----------------- Helpers (private) ----------------- */

    /**
     * Build preview rows for users in scope.
     */
    private function collect_twofa_rows( string $role, bool $includeNetwork, $specificSiteId, bool $doResetMeta ): array {
        $rows    = [];
        $siteIds = $this->determine_sites_for_twofa( $includeNetwork, $specificSiteId );

        foreach ( $siteIds as $blog_id ) {
            $this->with_blog_for_twofa( (int) $blog_id, function() use ( $role, $doResetMeta, $blog_id, &$rows ) {
                $repo   = new Rsssl_Two_Fa_User_Repository();
                $params = new Rsssl_Two_FA_Data_Parameters([
                    'filter_column' => 'user_role',
                    'filter_value'  => $role,
                ]);

	            foreach ( $repo->getForcedTwoFaUsersWithOpenStatus( $params )->getUsers() as $u ) {
                    $user_id = (int) $u->getId();
                    $wp_user = get_userdata( $user_id );
                    if ( ! $wp_user ) {
                        continue;
                    }

                    if ( $doResetMeta ) {
                        delete_user_meta( $user_id, 'rsssl_two_fa_reminder_sent' );
                    }

                    $rows[] = [
                        'blog_id'       => (string) $blog_id,
                        'user_id'       => (string) $user_id,
                        'user_login'    => $wp_user->user_login,
                        'email'         => $wp_user->user_email,
                        'roles'         => implode( ',', $wp_user->roles ?? [] ),
                        'reminder_sent' => get_user_meta( $user_id, 'rsssl_two_fa_reminder_sent', true ) ? 'yes' : 'no',
                    ];
                }
            } );
        }

        return $rows;
    }

    /**
     * Decide which sites to traverse for multisite support.
     */
    private function determine_sites_for_twofa( bool $includeNetwork, $specificSiteId ): array {
        if ( is_multisite() ) {
            if ( ! empty( $specificSiteId ) ) {
                return [ (int) $specificSiteId ];
            }
            if ( $includeNetwork ) {
                $ids = [];
                foreach ( get_sites( [ 'fields' => 'ids', 'number' => 0 ] ) as $bid ) {
                    $ids[] = (int) $bid;
                }
                return $ids;
            }
            return [ get_current_blog_id() ];
        }
        return [ 0 ];
    }

    /**
     * Execute a callback within the context of a (sub)site.
     */
    private function with_blog_for_twofa( int $blog_id, callable $cb ): void {
        if ( is_multisite() && $blog_id > 0 ) {
            switch_to_blog( $blog_id );
            try {
                $cb();
            } finally {
                restore_current_blog();
            }
        } else {
            $cb();
        }
    }

	/**
	 * Update the advanced-headers.php with the latest rules
	 *
	 * @return void
	 */
	public function update_advanced_headers() {
		if ( ! $this->check_pro_command_preconditions() ) return;
		do_action('rsssl_update_rules');
        WP_CLI::success( 'Successfully update advanced headers.' );
	}

    /**
     * Add an IP to the firewall blocklist.
     *
     * @example wp rsssl add_firewall_ip_block 123.123.123.1 --note="This is a temporary block"
     * @example wp rsssl add_firewall_ip_block 123.123.123.1 --permanent --note="This is a permanent block"
     *
     * @param array $args Should contain IP as the first element
     * @param array $assoc_args Can contain a note with a 'note' key
     */
    public function add_firewall_ip_block(array $args, array $assoc_args): void
    {
        if ( ! $this->check_pro_command_preconditions() ) return;
        $this->handleFirewallTableEntry($args, $assoc_args, 'blocked', 'add');
    }

	/**
     * Can be used to remove a (temporary) block from the firewall blocklist.
     * @example wp rsssl remove_firewall_ip_block 123.123.123.1
     *
	 * @param $args array Should contain the ip address
	 */
	public function remove_firewall_ip_block(array $args, array $assoc_args ): void
    {
        if ( ! $this->check_pro_command_preconditions() ) return;
        $this->handleFirewallTableEntry($args, $assoc_args, 'blocked', 'remove');
	}

    /**
     * Return a table of the current blocked IPs with the headers:
     * IP Address, Note, Permanent
     */
    public function show_blocked_ips() {
        if ( ! $this->check_pro_command_preconditions() ) return;
        $columns = [
            'ip_address',
            'note',
            'permanent',
        ];

	    $blockedIps = ( new Rsssl_404_Block() )->get_blocked_ips($columns);

        WP_CLI\Utils\format_items('table', $blockedIps, $columns);
    }

	/**
	 * Add an IP to the firewall's trusted list.
	 *
	 * Usage: wp rsssl add_firewall_trusted_ip 123.123.123.1
	 *
	 * @param array $args Should contain IP as the first element
	 * @param array $assoc_args Can contain a note with a 'note' key
     * @uses handleFirewallTableEntry()
	 */
	public function add_firewall_trusted_ip(array $args, array $assoc_args) {
        if ( ! $this->check_pro_command_preconditions() ) return;
        $this->handleFirewallTableEntry($args, $assoc_args, 'trusted', 'add');
	}

    /**
     * Remove an IP from the firewall's trusted list.
     *
     * Usage: wp rsssl remove_firewall_trusted_ip 123.123.123.1
     *
     * @param array $args Should contain IP as the first element
     * @param array $assoc_args Can contain a note with a 'note' key
     * @uses handleFirewallTableEntry()
     */
    public function remove_firewall_trusted_ip(array $args, array $assoc_args) {
        if ( ! $this->check_pro_command_preconditions() ) return;
        $this->handleFirewallTableEntry($args, $assoc_args, 'trusted', 'remove');
    }

    /**
     * Add an IP to the LLA's trusted list.
     *
     * Usage: wp rsssl add_lla_trusted_ip 123.123.123.1
     *
     * @param array $args Command arguments.
     * @uses handleLlaTableEntry()
     */
    public function add_lla_trusted_ip( $args ) {
        if ( ! $this->check_pro_command_preconditions() ) return;
        $this->handleLlaTableEntry($args, 'allowed', 'source_ip', 'add');
    }

    /**
     * Add an IP to the LLA's blocklist.
     *
     * Usage: wp rsssl remove_lla_trusted_ip 123.123.123.1
     *
     * @param array $args Command arguments.
     * @uses handleLlaTableEntry()
     */
    public function remove_lla_trusted_ip( $args ) {
        if ( ! $this->check_pro_command_preconditions() ) return;
        $this->handleLlaTableEntry($args, 'allowed', 'source_ip', 'remove');
    }

    /**
     * Remove an IP from the LLA's trusted list.
     *
     * Usage: wp rsssl add_lla_blocked_ip 123.123.123.1
     * Usage: wp rsssl add_lla_blocked_ip 123.123.123.1 --permanent
     *
     * @param array $args Command arguments.
     * @param array $assoc_args Associative arguments.
     * @uses handleLlaTableEntry()
     */
    public function add_lla_blocked_ip( $args, $assoc_args ) {
        if ( ! $this->check_pro_command_preconditions() ) return;
        $status = (isset($assoc_args['permanent']) ? 'blocked' : 'locked');
        $this->handleLlaTableEntry($args, $status, 'source_ip', 'add');
    }

    /**
     * Remove an IP from the LLA's blocklist.
     *
     * Usage: wp rsssl remove_lla_blocked_ip 123.123.123.1
     * Usage: wp rsssl remove_lla_blocked_ip 123.123.123.1 --permanent
     *
     * @param array $args Command arguments.
     * @param array $assoc_args Associative arguments.
     * @uses handleLlaTableEntry()
     */
    public function remove_lla_blocked_ip( $args, $assoc_args ) {
        if ( ! $this->check_pro_command_preconditions() ) return;
        $status = (isset($assoc_args['permanent']) ? 'blocked' : 'locked');
        $this->handleLlaTableEntry($args, $status, 'source_ip', 'remove');
    }

    /**
     * Add a username to the LLA's trusted list.
     *
     * Usage: wp rsssl add_lla_trusted_username username
     *
     * @param array $args Command arguments.
     * @uses handleLlaTableEntry()
     */
    public function add_lla_trusted_username( $args ) {
        if ( ! $this->check_pro_command_preconditions() ) return;
        $this->handleLlaTableEntry($args, 'allowed', 'username', 'add');
    }

    /**
     * Remove a username to the LLA's trusted list.
     *
     * Usage: wp rsssl remove_lla_trusted_username username
     *
     * @param array $args Command arguments.
     * @uses handleLlaTableEntry()
     */
    public function remove_lla_trusted_username( $args ) {
        if ( ! $this->check_pro_command_preconditions() ) return;
        $this->handleLlaTableEntry($args, 'allowed', 'username', 'remove');
    }

    /**
     * Add a username to the LLA's blocked list.
     *
     * Usage: wp rsssl add_lla_blocked_username username
     * Usage: wp rsssl add_lla_blocked_username username --permanent
     *
     * @param array $args Command arguments.
     * @param array $assoc_args Associative arguments.
     * @uses handleLlaTableEntry()
     */
    public function add_lla_blocked_username( array $args, array $assoc_args ) {
        if ( ! $this->check_pro_command_preconditions() ) return;
        $status = (isset($assoc_args['permanent']) ? 'blocked' : 'locked');
        $this->handleLlaTableEntry($args, $status, 'username', 'add');
    }

    /**
     * Remove a username to the LLA's blocked list.
     *
     * Usage: wp rsssl remove_lla_blocked_username username
     * Usage: wp rsssl remove_lla_blocked_username username --permanent
     *
     * @param array $args Command arguments.
     * @param array $assoc_args Associative arguments.
     * @uses handleLlaTableEntry()
     */
    public function remove_lla_blocked_username( $args, $assoc_args ) {
        if ( ! $this->check_pro_command_preconditions() ) return;
        $status = (isset($assoc_args['permanent']) ? 'blocked' : 'locked');
        $this->handleLlaTableEntry($args, $status, 'username', 'remove');
    }

    /**
     * Handle an action for the firewall table for a specific IP address.
     *
     * @param array $args Command arguments.
     * @param array $assoc_args Associative arguments.
     * @param string $status Should be either 'trusted' or 'blocked'.
     * @param string $action Should be either 'add' or 'remove'.
     *
     * @uses remove_white_list_ip() & add_white_list_ip() from Rsssl_Geo_Block -
     * Those also handle a block request for an IP address.
     */
    protected function handleFirewallTableEntry(array $args, array $assoc_args, string $status, string $action)
    {
        if (rsssl_get_option('enable_firewall', false) !== true) {
            WP_CLI::error('The firewall is not enabled.', true);
        }

        if (!in_array($status, ['trusted', 'blocked']) || !in_array($action, ['add', 'remove'])) {
            WP_CLI::error('Could not handle action for the firewall table.', true);
        }

        if (empty($args[0])) {
            WP_CLI::error('Please provide an IP address.', true);
        }

        $ip = $this->getFilteredIpAddress($args[0]);

        // Prepare data for adding to the whitelist.
        $data = [
            'ip_address' => $ip,
            'note'       => $assoc_args['note'] ?? '',
            'status'     => $status,
            'permanent'  => isset($assoc_args['permanent']),
        ];

        // Use the Rsssl_Geo_Block class to add the trusted IP.
        if (!class_exists('\RSSSL\Pro\Security\WordPress\Rsssl_Geo_Block')) {
            require_once rsssl_path . 'pro/security/wordpress/rsssl-geo-block.php';
        }

        try {
            $geo_block = new \RSSSL\Pro\Security\WordPress\Rsssl_Geo_Block();

            // fallback
            $response = ['success' => false, 'message' => 'Something went wrong!'];

            if ($action === 'remove') {
                $response = $geo_block->remove_white_list_ip( $data );
            }

            if ($action === 'add') {
                $response = $geo_block->add_white_list_ip( $data );
            }
        } catch ( \Exception $e ) {
            WP_CLI::error( 'Failed to handle IP entry: ' . $e->getMessage(), true );
        }

        // Handle response.
        if ( $response['success'] ) {
            WP_CLI::success( $response['message'] );
            return;
        }

        WP_CLI::error( $response['message'], true );
    }

    /**
     * Handle an action for the LLA table for a specific IP address.
     *
     * @param array $args Command arguments.
     * @param string $status Should be either 'allowed' or 'blocked'.
     * @param string $type Should be either 'source_ip' or 'username'.
     * @param string $action Should be either 'add' or 'remove'.
     * @return void
     */
    protected function handleLlaTableEntry(array $args, string $status, string $type, string $action): void
    {
        if (rsssl_get_option('enable_limited_login_attempts', false) !== true) {
            WP_CLI::error('The LLA feature is not enabled.', true);
        }

        if (empty($args[0])) {
            WP_CLI::error('Please provide the command the necessary arguments', true);
        }

        if (!in_array($status, ['allowed', 'blocked', 'locked']) || !in_array($type, ['source_ip', 'username'])) {
            WP_CLI::error('Something went wrong! Could not handle command.', true);
        }

        $value = '';
        if ($type === 'source_ip') {
            $value = $this->getFilteredIpAddress($args[0]);
        }

        if ($type === 'username') {
            $value = sanitize_text_field($args[0]);
        }

        // Use the Rsssl_Limit_Login_Admin class to add the trusted IP.
        if (!class_exists('\RSSSL\Pro\Security\WordPress\Rsssl_Limit_Login_Admin')) {
            require_once rsssl_path . 'pro/security/wordpress/class-rsssl-limit-login-admin.php';
        }

        try {
            $lla = new \RSSSL\Pro\Security\WordPress\Rsssl_Limit_Login_Admin();

            // fallback
            $response = ['success' => false, 'message' => 'Something went wrong!'];

            if ($action === 'add') {
                $response = $lla->handle_entity([
                    'value' => $value,
                    'status'  => sanitize_text_field($status),
                ], $type);
            }

            if ($action === 'remove') {
                $entry = $lla->get_entry($type, $value, $status);
                $response = $lla->delete_entries([
                    'id' => $entry['id'],
                ]);
            }
        } catch ( Exception $e ) {
            WP_CLI::error( 'Failed to handle LLA entry: ' . $e->getMessage(), true );
        }

        // Handle response.
        if ( $response['success'] ) {
            WP_CLI::success( $response['message'] );
            return;
        }

        WP_CLI::error( $response['message'], true );
    }

    /**
     * Return a filtered IP address. Method will exit() if the IP address is
     * invalid with the WP_CLI error message: Invalid IP address provided.
     */
    protected function getFilteredIpAddress(string $originalIp): string
    {
        // Check if the input is potentially a CIDR
        if (strpos($originalIp, '/') !== false) {
            list($address, $mask_str) = explode('/', $originalIp, 2);

            // Validate the IP address part
            if (!filter_var($address, FILTER_VALIDATE_IP)) {
                WP_CLI::error('Invalid IP address part in CIDR notation: ' . $address, true);
            }

            // Validate the mask part
            if (!is_numeric($mask_str)) {
                WP_CLI::error('CIDR mask is not numeric: ' . $mask_str, true);
            }
            $mask = (int)$mask_str;

            // Determine IP version for mask validation
            $is_ipv4 = filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
            $is_ipv6 = filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);

            if ($is_ipv4) {
                if ($mask < 0 || $mask > 32) {
                    WP_CLI::error('Invalid IPv4 CIDR mask (must be 0-32): ' . $mask, true);
                }
            } elseif ($is_ipv6) {
                if ($mask < 0 || $mask > 128) {
                    WP_CLI::error('Invalid IPv6 CIDR mask (must be 0-128): ' . $mask, true);
                }
            } else {
                // This case should ideally not be reached if filter_var($address, FILTER_VALIDATE_IP) passed
                WP_CLI::error('Unknown IP address type for CIDR validation: ' . $address, true);
            }

            // If all checks pass for CIDR, return the original CIDR string
            return $originalIp;

        } else {
            // Validate as a plain IP address
            $ip = filter_var($originalIp, FILTER_VALIDATE_IP);
            if (empty($ip)) {
                WP_CLI::error('Invalid IP address provided: ' . $originalIp, true);
            }
            return $ip;
        }
    }

	/**
	 * Performs pre-flight checks before SSL activation.
	 * Checks for HTTPS reachability and potentially other issues like .htaccess writability.
	 *
	 * @return array ['success' => bool, 'message' => string, 'warnings' => array]
	 */
	private function perform_pre_flight_checks(): array {
		$warnings = [];
		$message = '';

		// --- Check 1: HTTPS Reachability ---
		$home_url = home_url();
		$https_url = set_url_scheme( $home_url, 'https' );

		// Use wp_remote_get to see if the HTTPS version is reachable
		// 'sslverify' => false is important for local/staging with self-signed certs
		// Timeout set low to avoid long waits on failure
		$response = wp_remote_get( $https_url, [
			'timeout'   => 10, // seconds
			'sslverify' => false,
			'redirection' => 5, // Follow redirects
		] );

		if ( is_wp_error( $response ) ) {
			$error_code = $response->get_error_code();
			$error_message = $response->get_error_message();
			$friendly_message = sprintf(
				__( 'Failed to reach %s. The site does not appear to be accessible over HTTPS. Please ensure your server is configured for SSL.', 'really-simple-ssl' ),
				$https_url
			);

			// Check if WP_DEBUG is enabled
			$wp_debug_enabled = ( defined( 'WP_DEBUG' ) && WP_DEBUG );

			if ( $wp_debug_enabled ) {
				// Log the detailed error when WP_DEBUG is on
				// Using WP_CLI::debug requires the --debug flag for wp-cli command itself
				 WP_CLI::debug( sprintf( "HTTPS Check Error Details: Code=%s, Message=%s", $error_code, $error_message ), 'rsssl-cli-debug' );
				 // Alternatively, or in addition, use standard PHP error logging:
				 // error_log( sprintf("Really Simple SSL WP-CLI HTTPS Check Error: Code=%s, Message=%s", $error_code, $error_message) );

				 // Optionally, still show a slightly more informative message than the friendly one
				 $message_to_show = sprintf(
					__( 'Failed to reach %s. The site does not appear to be accessible over HTTPS (Error: %s). Check debug logs for details.', 'really-simple-ssl' ),
					 $https_url,
					 $error_code // Show the code, but maybe not the full verbose message
				 );
			} else {
				// Show only the user-friendly message if WP_DEBUG is off
				$message_to_show = $friendly_message;
			}

			return [
				'success' => false,
				'message' => $message_to_show,
				'warnings' => $warnings
			];

		} else {
			// Connected, check the response code
			$response_code = wp_remote_retrieve_response_code( $response );
			if ( $response_code < 200 || $response_code >= 400 ) {
				// Reached server, but got an error response (e.g., 404 Not Found, 500 Internal Server Error)
				 return [
					'success' => false,
					'message' => sprintf( __( 'Reached %s, but received an error response code: %d. HTTPS is not properly configured.', 'really-simple-ssl' ), $https_url, $response_code ),
					'warnings' => $warnings
				 ];
			}
			// If response code is 2xx or 3xx, we consider HTTPS reachable.
			// A more robust check could analyze the body for expected content, but this is usually sufficient.
		}

		// --- Check 2: .htaccess Writability (if needed) ---
		// Keep the previous check for .htaccess if the redirect method is set to htaccess
		// $htaccess_writable = true; // Replace with actual check logic (e.g., check if WP_Filesystem allows writing)
		if ( rsssl_get_option('redirect') === 'htaccess' ) {
			 // Get the path to the .htaccess file
			 $htaccess_file = RSSSL()->admin->htaccess_file(); // Assuming a method to get the correct path
			 if ( ! is_writable( $htaccess_file ) ) {
				 $warnings[] = sprintf( __( '.htaccess file (%s) is not writable. Redirects cannot be configured automatically.', 'really-simple-ssl' ), $htaccess_file );
				 // This remains a warning, as activation might still work partially (WP URLs change)
			 }
		}

		// Add more checks as needed (e.g., specific certificate details if possible/required)...

		$message = __( 'Pre-flight checks passed.', 'really-simple-ssl' );
		return ['success' => true, 'message' => $message, 'warnings' => $warnings];
	}

	/**
	 * Get command details for WP-CLI commands.
	 *
	 * @return array Command details.
	 */
	protected function get_command_list() {
		return [
			'activate_ssl'                      => [
				'description' => __( 'Activate SSL on the site.', 'really-simple-ssl' ),
				'synopsis'    => [],
				'pro'         => false,
			],
			'deactivate_ssl'                    => [
				'description' => __( 'Deactivate SSL on the site.', 'really-simple-ssl' ),
				'synopsis'    => [],
				'pro'         => false,
			],
			'update_option'                     => [
				'description' => __( 'Update a Really Simple Security option. Usage: wp rsssl update_option --name=option_name --value=option_value. Use 0 and 1 for booleans.', 'really-simple-ssl' ),
				'synopsis'    => [
					[
						'type'        => 'assoc',
						'name'        => 'name',
						'optional'    => false,
						'description' => __( 'Name of the option to update.', 'really-simple-ssl' ),
					],
					[
						'type'        => 'assoc',
						'name'        => 'value',
						'optional'    => false,
						'description' => __( 'Value to set for the option.', 'really-simple-ssl' ),
					],
				],
				'pro'         => false,
			],
			'activate_recommended_features'     => [
				'description' => __( 'Activate all recommended features.', 'really-simple-ssl' ),
				'synopsis'    => [],
				'pro'         => false,
			],
			'deactivate_recommended_features'   => [
				'description' => __( 'Deactivate all recommended features.', 'really-simple-ssl' ),
				'synopsis'    => [],
				'pro'         => false,
			],
			'activate_security_headers'         => [
				'description' => __( 'Activate essential security headers.', 'really-simple-ssl' ),
				'synopsis'    => [],
				'pro'         => true,
			],
			'deactivate_security_headers'       => [
				'description' => __( 'Deactivate essential security headers.', 'really-simple-ssl' ),
				'synopsis'    => [],
				'pro'         => true,
			],
			'activate_firewall'                 => [
				'description' => __( 'Activate the firewall.', 'really-simple-ssl' ),
				'synopsis'    => [],
				'pro'         => true,
			],
			'deactivate_firewall'               => [
				'description' => __( 'Deactivate the firewall.', 'really-simple-ssl' ),
				'synopsis'    => [],
				'pro'         => true,
			],
			'activate_2fa'                      => [
				'description' => __( 'Activate Two-Factor Authentication.', 'really-simple-ssl' ),
				'synopsis'    => [],
				'pro'         => false,
			],
			'deactivate_2fa'                    => [
				'description' => __( 'Deactivate Two-Factor Authentication.', 'really-simple-ssl' ),
				'synopsis'    => [],
				'pro'         => false,
			],
			'activate_password_security'        => [
				'description' => __( 'Activate password security features.', 'really-simple-ssl' ),
				'synopsis'    => [],
				'pro'         => true,
			],
			'deactivate_password_security'      => [
				'description' => __( 'Deactivate password security features.', 'really-simple-ssl' ),
				'synopsis'    => [],
				'pro'         => true,
			],
			'activate_lla'                      => [
				'description' => __( 'Activate limit login attempts.', 'really-simple-ssl' ),
				'synopsis'    => [],
				'pro'         => true,
			],
			'deactivate_lla'                    => [
				'description' => __( 'Deactivate limit login attempts.', 'really-simple-ssl' ),
				'synopsis'    => [],
				'pro'         => true,
			],
			'activate_vulnerability_scanning'   => [
				'description' => __( 'Activate vulnerability scanning.', 'really-simple-ssl' ),
				'synopsis'    => [],
				'pro'         => false,
			],
			'deactivate_vulnerability_scanning' => [
				'description' => __( 'Deactivate vulnerability scanning.', 'really-simple-ssl' ),
				'synopsis'    => [],
				'pro'         => false,
			],
			'activate_license'                  => [
				'description' => __( 'Activate a license key. Usage: wp rsssl activate_license YOUR_LICENSE_KEY.', 'really-simple-ssl' ),
				'synopsis'    => [
					[
						'type'        => 'positional',
						'name'        => 'license_key',
						'optional'    => false,
						'description' => __( 'The license key to activate.', 'really-simple-ssl' ),
					],
				],
				'pro'         => true,
			],
			'deactivate_license'                => [
				'description' => __( 'Deactivate the license.', 'really-simple-ssl' ),
				'synopsis'    => [],
				'pro'         => true,
			],
			'add_lock_file'                     => [
				'description' => __( 'Add a lock file for safe mode.', 'really-simple-ssl' ),
				'synopsis'    => [],
				'pro'         => false,
			],
			'remove_lock_file'                  => [
				'description' => __( 'Remove the lock file for safe mode.', 'really-simple-ssl' ),
				'synopsis'    => [],
				'pro'         => false,
			],
			'reset_2fa'    => [
				'description' => __( 'Reset the 2FA status and methods for a user.', 'really-simple-ssl' ),
				'synopsis'    => [
					[
						'type'        => 'positional',
						'name'        => 'user_id',
						'optional'    => false,
						'description' => __( 'The user ID to reset 2FA for.', 'really-simple-ssl' ),
					],
				],
				'pro'         => false,
			],
            'twofa_preview' => [
                'description' => __( 'Preview users in scope for 2FA reminders (dry-run).', 'really-simple-ssl' ),
                'synopsis'    => [
                    [ 'type' => 'assoc', 'name' => 'role',  'optional' => true,  'description' => __( 'Filter by user role (default: all).', 'really-simple-ssl' ) ],
                    [ 'type' => 'assoc', 'name' => 'site',  'optional' => true,  'description' => __( 'Limit to a single blog_id (multisite).', 'really-simple-ssl' ) ],
                    [ 'type' => 'flag',  'name' => 'include-subsites', 'optional' => true, 'description' => __( 'Traverse all subsites in the network.', 'really-simple-ssl' ) ],
                    [ 'type' => 'assoc', 'name' => 'format','optional' => true,  'description' => __( 'Output format: table|json|csv (default: table).', 'really-simple-ssl' ) ],
                    [ 'type' => 'flag',  'name' => 'reset-meta', 'optional' => true, 'description' => __( 'Reset rsssl_two_fa_reminder_sent meta for a clean run.', 'really-simple-ssl' ) ],
                ],
                'pro' => true,
            ],
            'twofa_send' => [
                'description' => __( 'Send 2FA reminders for the current selection.', 'really-simple-ssl' ),
                'synopsis'    => [
                    [ 'type' => 'assoc', 'name' => 'role',  'optional' => true,  'description' => __( 'Filter by user role (default: all).', 'really-simple-ssl' ) ],
                    [ 'type' => 'assoc', 'name' => 'site',  'optional' => true,  'description' => __( 'Limit to a single blog_id (multisite).', 'really-simple-ssl' ) ],
                    [ 'type' => 'flag',  'name' => 'include-subsites', 'optional' => true, 'description' => __( 'Traverse all subsites in the network.', 'really-simple-ssl' ) ],
                    [ 'type' => 'flag',  'name' => 'reset-meta', 'optional' => true, 'description' => __( 'Reset rsssl_two_fa_reminder_sent meta before sending.', 'really-simple-ssl' ) ],
                ],
                'pro' => true,
            ],
			'update_advanced_headers' => [
				'description' => __( 'Update the advanced-headers.php with the latest rules.', 'really-simple-ssl' ),
				'synopsis'    => [],
				'pro'         => false,
			],
            'add_firewall_ip_block' => [
                'description' => __( 'Add IP block.', 'really-simple-ssl' ),
                'synopsis'    => [
                    [
                        'type'        => 'positional',
                        'name'        => 'ip_address',
                        'optional'    => false,
                        'description' => __( 'The IP to block.', 'really-simple-ssl' ),
                    ],
                    [
                        'type'        => 'flag',
                        'name'        => 'permanent',
                        'optional'    => true,
                        'description' => __( 'Flag to add a permanent block.', 'really-simple-ssl' ),
                    ],
                    [
                        'type'        => 'assoc',
                        'name'        => 'note',
                        'optional'    => true,
                        'description' => __( 'Optional note for the block.', 'really-simple-ssl' ),
                    ],
                ],
                'pro'         => true,
            ],
			'remove_firewall_ip_block' => [
				'description' => __( 'Remove IP block.', 'really-simple-ssl' ),
                'synopsis'    => [
                    [
                        'type'        => 'positional',
                        'name'        => 'ip_address',
                        'optional'    => false,
                        'description' => __( 'The IP to remove the block for.', 'really-simple-ssl' ),
                    ],
                ],
				'pro'         => true,
			],
			'show_blocked_ips' => [
				'description' => __( 'Show blocked IP\'s.', 'really-simple-ssl' ),
				'synopsis'    => [],
				'pro'         => true,
			],
			'add_firewall_trusted_ip' => [
				'description' => __( 'Add a trusted IP to the firewall.', 'really-simple-ssl' ),
				'synopsis'    => [],
				'pro'         => true,
			],
            'remove_firewall_trusted_ip' => [
				'description' => __( 'Remove a trusted IP from the firewall.', 'really-simple-ssl' ),
				'synopsis'    => [],
				'pro'         => true,
			],
            'add_lla_trusted_ip' => [
                'description' => __( 'Add a trusted IP to the limit login attempts table.', 'really-simple-ssl' ),
                'synopsis'    => [],
                'pro'         => true,
            ],
            'remove_lla_trusted_ip' => [
                'description' => __( 'Remove a trusted IP from the limit login attempts table.', 'really-simple-ssl' ),
                'synopsis'    => [],
                'pro'         => true,
            ],
            'add_lla_blocked_ip' => [
                'description' => __( 'Add a blocked IP to the limit login attempts table.', 'really-simple-ssl' ),
                'synopsis'    => [
                    [
                        'type'        => 'positional',
                        'name'        => 'ip_address',
                        'optional'    => false,
                        'description' => __( 'The IP to block.', 'really-simple-ssl' ),
                    ],
                    [
                        'type'        => 'flag',
                        'name'        => 'permanent',
                        'optional'    => true,
                        'description' => __( 'Flag to add a permanent block.', 'really-simple-ssl' ),
                    ],
                ],
                'pro'         => true,
            ],
            'remove_lla_blocked_ip' => [
                'description' => __( 'Remove a blocked IP from the limit login attempts table.', 'really-simple-ssl' ),
                'synopsis'    => [
                    [
                        'type'        => 'positional',
                        'name'        => 'ip_address',
                        'optional'    => false,
                        'description' => __( 'The IP to block.', 'really-simple-ssl' ),
                    ],
                    [
                        'type'        => 'flag',
                        'name'        => 'permanent',
                        'optional'    => true,
                        'description' => __( 'Flag to add a permanent block.', 'really-simple-ssl' ),
                    ],
                ],
                'pro'         => true,
            ],
            'add_lla_trusted_username' => [
                'description' => __( 'Add a trusted username to the limit login attempts table.', 'really-simple-ssl' ),
                'synopsis'    => [],
                'pro'         => true,
            ],
            'remove_lla_trusted_username' => [
                'description' => __( 'Remove a trusted username from the limit login attempts table.', 'really-simple-ssl' ),
                'synopsis'    => [],
                'pro'         => true,
            ],
            'add_lla_blocked_username' => [
                'description' => __( 'Add a blocked username to the limit login attempts table.', 'really-simple-ssl' ),
                'synopsis'    => [
                    [
                        'type'        => 'positional',
                        'name'        => 'ip_address',
                        'optional'    => false,
                        'description' => __( 'The username to block.', 'really-simple-ssl' ),
                    ],
                    [
                        'type'        => 'flag',
                        'name'        => 'permanent',
                        'optional'    => true,
                        'description' => __( 'Flag to add a permanent block.', 'really-simple-ssl' ),
                    ],
                ],
                'pro'         => true,
            ],
            'remove_lla_blocked_username' => [
                'description' => __( 'Remove a blocked username from the limit login attempts table.', 'really-simple-ssl' ),
                'synopsis'    => [
                    [
                        'type'        => 'positional',
                        'name'        => 'username',
                        'optional'    => false,
                        'description' => __( 'The username to remove the block for.', 'really-simple-ssl' ),
                    ],
                    [
                        'type'        => 'flag',
                        'name'        => 'permanent',
                        'optional'    => true,
                        'description' => __( 'Flag to remove a permanent block.', 'really-simple-ssl' ),
                    ],
                ],
                'pro'         => true,
            ],
		];
	}

	/**
	 * This method registers our WP-CLI commands and uses {@see get_command_list()}
	 * to retrieve the list. Do not execute this method before the init hook.
	 */
	public function register_wp_cli_commands() {
		$command_details = $this->get_command_list();
		foreach ( $command_details as $command => $details ) {
			if ( isset( $details['inactive'] ) && $details['inactive'] === true ) {
				continue;
			}
			WP_CLI::add_command(
				"rsssl $command",
				[ $this, $command ],
				[
					'shortdesc' => $details['description'],
					'synopsis'  => $details['synopsis'],
				]
			);
		}
	}
}

// Add devtools command if present
if ( file_exists( rsssl_path . 'pro/assets/tools/cli/class-rsssl-stub-generator.php' ) ) {
	require_once rsssl_path . 'pro/assets/tools/cli/class-rsssl-stub-generator.php';
}