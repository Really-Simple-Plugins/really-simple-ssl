<?php
defined( 'ABSPATH' ) or die();

require_once rsssl_path . 'lib/admin/class-encryption.php';

use RSSSL\lib\admin\Encryption;
use RSSSL\Pro\Security\WordPress\Firewall\Models\Rsssl_404_Block;

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

		if ( ! $this->wp_cli_active() ) {
			return;
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			// Get overview of commands and description/synopsis
			$command_details = $this->get_command_list();

			// Add commands individually.
			foreach ( $command_details as $command => $details ) {

                if (isset($details['inactive']) && $details['inactive'] === true) {
                    continue;
                }

				// Do not add Pro commands on free environment
				if ( ! defined( 'rsssl_pro' ) ) {
					if ( $details['pro'] === true ) {
						continue;
					}
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
	 * @return void
	 */
	public function activate_ssl() {
		try {
			update_option( 'rsssl_onboarding_dismissed', true, false );
			$success = RSSSL()->admin->activate_ssl( false );
			if ( $success ) {
				WP_CLI::success( 'SSL activated successfully' );
			} else {
				WP_CLI::error( 'SSL activation failed' );
			}
		} catch ( Exception $e ) {
			WP_CLI::error( 'Failed to activate SSL: ' . $e->getMessage() );
		}
	}

	/**
	 * Deactivate SSL through WP-CLI.
	 *
	 * @return void
	 */
	public function deactivate_ssl() {
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
	 * return void
	 */
	public function activate_recommended_features() {

		try {
			// Activate Vulnerability Scanner
			rsssl_update_option( 'enable_vulnerability_scanner', true );

			// Activate essential WordPress hardening features
			$recommended_hardening_fields = RSSSL()->onboarding->get_hardening_fields();
			foreach ( $recommended_hardening_fields as $field ) {
				rsssl_update_option( $field, true );
			}

			// Enable Email login protection
			rsssl_update_option( 'login_protection_enabled', true );

			// Enable Mixed Content Fixer
			rsssl_update_option( 'mixed_content_fixer', true );

			// Check if PRO version is active, then activate premium features
			if ( defined( 'rsssl_pro' ) ) {
				// Enable Two-Factor Authentication for administrator role
				rsssl_update_option( 'two_fa_enabled_roles_totp', [ 'administrator' ] );

				// Enable Limit Login Attempts
				rsssl_update_option( 'enable_limited_login_attempts', true );

				// Enable firewall
				rsssl_update_option( 'enable_firewall', true );
				rsssl_update_option( 'event_log_enabled', true );

				// Enable advanced security headers
				$security_headers = [
					'upgrade_insecure_requests',
					'x_content_type_options',
					'hsts',
					'x_xss_protection'    => 'zero',
					'x_frame_options'     => 'SAMEORIGIN',
					'referrer_policy'     => 'strict-origin-when-cross-origin',
					'csp_frame_ancestors' => 'self',
				];
				foreach ( $security_headers as $header_key => $header_value ) {
					if ( is_string( $header_key ) ) {
						rsssl_update_option( $header_key, $header_value );
					} else {
						rsssl_update_option( $header_value, true );
					}
				}

				// Activate password security enforcement
				rsssl_update_option( 'enforce_password_security_enabled', true );
				rsssl_update_option( 'enable_hibp_check', true );
			}

			do_action('rsssl_update_rules');
			WP_CLI::success( 'Recommended features activated.' );
		} catch ( Exception $e ) {
			WP_CLI::error( 'Failed to deactivate recommended features: ' . $e->getMessage() );
		}
	}

	/**
	 * Deactivate all recommended features via CLI
	 *
	 * return void
	 */
	public function deactivate_recommended_features() {

		try {
			// Deactivate Vulnerability Scanner
			rsssl_update_option( 'enable_vulnerability_scanner', false );

			// Deactivate essential WordPress hardening features
			$recommended_hardening_fields = RSSSL()->onboarding->get_hardening_fields();
			foreach ( $recommended_hardening_fields as $field ) {
				rsssl_update_option( $field, false );
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

		try {
			$recommended_hardening_fields = RSSSL()->onboarding->get_hardening_fields();
			foreach ( $recommended_hardening_fields as $field ) {
				rsssl_update_option( $field, true );
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

		try {
			$recommended_hardening_fields = RSSSL()->onboarding->get_hardening_fields();
			foreach ( $recommended_hardening_fields as $field ) {
				rsssl_update_option( $field, false );
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
	public function deactivate_security_headers()
    {
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
	 */
	public function reset_2fa( $args ): void
    {
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

        \RSSSL\Security\WordPress\Two_Fa\Rsssl_Two_Fa_Status::reset_user_two_fa($user);
        WP_CLI::success( 'Successfully reset 2FA for user id ' . $user_id );
	}

	/**
	 * Update the advanced-headers.php with the latest rules
	 *
	 * @return void
	 */
	public function update_advanced_headers() {
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
        $this->handleFirewallTableEntry($args, $assoc_args, 'blocked', 'remove');
	}

    /**
     * Return a table of the current blocked IPs with the headers:
     * IP Address, Note, Permanent
     */
    public function show_blocked_ips() {
        $columns = [
            'ip_address',
            'note',
            'permanent',
        ];

        $blocked404Model = new Rsssl_404_Block();
        $blockedIps = $blocked404Model->get_blocked_ips($columns);

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
        $ip = filter_var($originalIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
        if (strpos($originalIp, ':')) {
            $ip = filter_var($originalIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
        }

        if (empty($ip)) {
            WP_CLI::error('Invalid IP address provided.', true);
        }

        return $ip;
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
				'description' => __( 'Reset the 2FA status of a user to disabled.', 'really-simple-ssl' ),
				'synopsis'    => [],
				'pro'         => false,
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

}

// Add devtools command if present
if ( file_exists( rsssl_path . 'pro/assets/tools/cli/class-rsssl-stub-generator.php' ) ) {
	require_once rsssl_path . 'pro/assets/tools/cli/class-rsssl-stub-generator.php';
}