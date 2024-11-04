<?php
defined( 'ABSPATH' ) or die();

add_action( 'plugins_loaded', 'rsssl_upgrade', 20 );
function rsssl_upgrade() {

	#only run upgrade check if cron, or if admin.
	if ( ! rsssl_admin_logged_in() ) {
		return;
	}

	$prev_version = get_option( 'rsssl_current_version', false );

	//no version change, skip upgrade.
	if ( $prev_version && version_compare( $prev_version, rsssl_version, '==' ) ) {
		return;
	}
	//dismiss notices that should be dismissed on plugin upgrade
	if ( $prev_version && version_compare( $prev_version, rsssl_version, '!=' ) ) {
		$dismiss_options = RSSSL()->admin->get_notices_list(
			array(
				'dismiss_on_upgrade' => true,
			)
		);
		foreach ( $dismiss_options as $dismiss_option ) {
			if ( !is_string($dismiss_option) ) continue;
			update_option( 'rsssl_' . $dismiss_option . '_dismissed', true, false );
		}
		delete_transient( 'rsssl_plusone_count' );
	}

	if ( $prev_version && version_compare( $prev_version, '5.1.3', '<=' ) ) {
		if ( get_option( 'rsssl_disable_ocsp' ) ) {
			$options                 = get_option( 'rsssl_options_lets-encrypt' );
			$options['disable_ocsp'] = true;
			update_option( 'rsssl_options_lets-encrypt', $options, false );
			delete_option( 'rsssl_disable_ocsp' );
		}
	}

	if ( $prev_version && version_compare( $prev_version, '5.3.0', '<=' ) ) {
		if ( file_exists( RSSSL()->admin->htaccess_file() ) && is_writable( RSSSL()->admin->htaccess_file() ) ) {
			$htaccess      = file_get_contents( RSSSL()->admin->htaccess_file() );
			$pattern_start = '/rlrssslReallySimpleSSL rsssl_version\[.*.]/';
			if ( preg_match_all( $pattern_start, $htaccess ) ) {
				$htaccess = preg_replace( $pattern_start, 'Really Simple Security Redirect ' . rsssl_version, $htaccess );
				$htaccess = str_replace( 'rlrssslReallySimpleSSL', 'Really Simple Security Redirect', $htaccess );
				file_put_contents( RSSSL()->admin->htaccess_file(), $htaccess );
			}
		}
	}

	if ( $prev_version && version_compare( $prev_version, '6.0.0', '<' ) ) {
		delete_option( 'rsssl_admin_notices' );
		update_option( 'rsssl_show_onboarding', true, false );
		//upgrade both site and network settings
		$options = get_option( 'rlrsssl_options' );
		if ( is_multisite() && rsssl_is_networkwide_active() ) {
			$new_options = get_site_option( 'rsssl_options', [] );
		} else {
			$new_options = get_option( 'rsssl_options', [] );
		}

		$ssl_enabled                = isset( $options['ssl_enabled'] ) ? $options['ssl_enabled'] : false;
		$new_options['ssl_enabled'] = (bool) $ssl_enabled;

		$autoreplace_insecure_links         = isset( $options['autoreplace_insecure_links'] ) ? $options['autoreplace_insecure_links'] : true;
		$new_options['mixed_content_fixer'] = (bool) $autoreplace_insecure_links;

		$wp_redirect       = isset( $options['wp_redirect'] ) ? $options['wp_redirect'] : false;
		$htaccess_redirect = isset( $options['htaccess_redirect'] ) ? $options['htaccess_redirect'] : false;
		$redirect          = 'none;';
		if ( $htaccess_redirect ) {
			$redirect = 'htaccess';
		} elseif ( $wp_redirect ) {
			$redirect = 'wp_redirect';
		}
		$new_options['redirect'] = sanitize_title( $redirect );

		$do_not_edit_htaccess                = isset( $options['do_not_edit_htaccess'] ) ? $options['do_not_edit_htaccess'] : false;
		$new_options['do_not_edit_htaccess'] = (bool) $do_not_edit_htaccess;

		$dismiss_all_notices                = isset( $options['dismiss_all_notices'] ) ? $options['dismiss_all_notices'] : false;
		$new_options['dismiss_all_notices'] = (bool) $dismiss_all_notices;

		$switch_mixed_content_fixer_hook                = isset( $options['switch_mixed_content_fixer_hook'] ) ? $options['switch_mixed_content_fixer_hook'] : false;
		$new_options['switch_mixed_content_fixer_hook'] = (bool) $switch_mixed_content_fixer_hook;

		delete_option( 'rsssl_upgraded_to_four' );

		/**
		 * Multisite
		 */
		if ( is_multisite() && rsssl_is_networkwide_active() ) {
			$network_options      = get_site_option( 'rlrsssl_network_options' );
			$enabled_network_wide = isset( $network_options['ssl_enabled_networkwide'] ) ? $network_options['ssl_enabled_networkwide'] : false;
			if ( $ssl_enabled && $enabled_network_wide ) {
				update_site_option( 'rsssl_network_activation_status', 'completed' );
			} elseif ( $ssl_enabled ) {
				//convert entire site to SSL
				RSSSL()->multisite->start_ssl_activation();
			}
			//ensure this doesn't run again
			$network_options['ssl_enabled_networkwide'] = false;
			update_site_option( 'rlrsssl_network_options', $network_options );

			$dismiss_all_notices                = isset( $network_options['dismiss_all_notices'] ) ? $network_options['dismiss_all_notices'] : false;
			$new_options['dismiss_all_notices'] = (bool) $dismiss_all_notices;

			$wp_redirect = isset( $network_options['wp_redirect'] ) ? $network_options['wp_redirect'] : false;
			if ( $wp_redirect ) {
				$redirect = 'wp_redirect';
			}
			$htaccess_redirect = isset( $network_options['htaccess_redirect'] ) ? $network_options['htaccess_redirect'] : false;
			if ( $htaccess_redirect ) {
				$redirect = 'htaccess';
			}
			$new_options['redirect'] = sanitize_title( $redirect );

			$do_not_edit_htaccess                = isset( $network_options['do_not_edit_htaccess'] ) ? $network_options['do_not_edit_htaccess'] : false;
			$new_options['do_not_edit_htaccess'] = (bool) $do_not_edit_htaccess;

			$autoreplace_mixed_content          = isset( $network_options['autoreplace_mixed_content'] ) ? $network_options['autoreplace_mixed_content'] : false;
			$new_options['mixed_content_fixer'] = (bool) $autoreplace_mixed_content;

			//upgrade lets encrypt options
			$le_options        = get_option( 'rsssl_options_lets-encrypt' );
			$verification_type = get_option( 'rsssl_verification_type' );
			if ( $verification_type ) {
				$new_options['verification_type'] = strtolower( sanitize_title( $verification_type ) );
			}
			if ( ! empty( $le_options ) ) {
				foreach ( $options as $fieldname => $value ) {
					$new_options[ $fieldname ] = sanitize_text_field( $value );
				}
			}
		}

		if ( is_multisite() && rsssl_is_networkwide_active() ) {
			update_site_option( 'rsssl_options', $new_options );
		} else {
			update_option( 'rsssl_options', $new_options );
		}
		update_option( 'rsssl_flush_rewrite_rules', time() );
	}

	#clean up old rest api optimizer on upgrade
	if ( $prev_version && version_compare( $prev_version, '6.0.5', '<' ) ) {
		if ( file_exists( trailingslashit( WPMU_PLUGIN_DIR ) . 'rsssl_rest_api_optimizer.php' ) ) {
			unlink( trailingslashit( WPMU_PLUGIN_DIR ) . 'rsssl_rest_api_optimizer.php' );
		}
	}

	#clear notices cache for multisite on upgrade, for the subsite notice
	if ( version_compare( $prev_version, '6.0.9', '<' ) ) {
		if ( is_multisite() ) {
			delete_option( 'rsssl_admin_notices' );
		}
	}

	#ensure administrators have the manage_security capability
	if ( version_compare( $prev_version, '6.0.10', '<' ) ) {
		rsssl_add_manage_security_capability();
	}

	#move notices transient to option, for better persistence
	if ( $prev_version && version_compare( $prev_version, '6.0.13', '<' ) ) {
		$notices   = get_transient( 'rsssl_admin_notices' );
		$plus_ones = get_transient( 'rsssl_plusone_count' );
		update_option( 'rsssl_admin_notices', $notices );
		update_option( 'rsssl_plusone_count', $plus_ones );
	}

	if ( $prev_version && version_compare( $prev_version, '6.2.3', '<' ) ) {
		rsssl_update_option( 'send_notifications_email', 1 );
	}

	if ( $prev_version && version_compare( $prev_version, '6.2.4', '<' ) ) {
		delete_option( 'rsssl_6_upgrade_completed' );
	}

	if ( $prev_version && version_compare( $prev_version, '7.1.0', '<' ) ) {
		do_action( 'rsssl_update_rules' );
	}

	// Update the config to auto prepend
	if ( $prev_version && version_compare( $prev_version, '8.0', '<' ) ) {
		RSSSL_SECURITY()->firewall_manager->update_wp_config_rule();
	}
	//free
	if ( $prev_version && version_compare( $prev_version, '8.1.2', '<' ) ) {
		do_action('rsssl_update_rules');
	}

	if ( $prev_version && version_compare( $prev_version, '8.3.0', '<' ) ) {
		wp_clear_scheduled_hook('rsssl_pro_every_hour_hook');
		wp_clear_scheduled_hook('rsssl_pro_every_day_hook');
		wp_clear_scheduled_hook('rsssl_pro_five_minutes_hook');
		wp_clear_scheduled_hook('rsssl_le_every_week_hook');
		wp_clear_scheduled_hook('rsssl_le_every_day_hook');

		//split rsssl_key in two options so we can upgrade separately
		$key = get_option( 'rsssl_key');
		$site_key = get_site_option( 'rsssl_key');
		if ( $key ) {
			update_option( 'rsssl_license_key', $key, false );
		}
		if ( $site_key ) {
			update_site_option( 'rsssl_le_key', $site_key );
		}

		delete_site_option('rsssl_key');
		delete_option('rsssl_key');
		update_option('rsssl_upgrade_le_key', true, false);
	}

	if ( $prev_version && version_compare( $prev_version, '9.0', '<' ) ) {
		// Replace Really Simple SSL with Really Simple Security in wp-config.php, .htaccess,
		// advanced-headers.php
		RSSSL()->admin->update_branding_in_files();
		RSSSL()->admin->clear_admin_notices_cache();
	}

	if ( $prev_version && version_compare( $prev_version, '9.1.1', '<' ) ) {
		do_action('rsssl_update_rules');
	}
    if ( $prev_version && version_compare( $prev_version, '9.1.1.1', '<=' ) ) {
        update_option('rsssl_reset_fix', true, false);
    }

	//don't clear on each update.
	//RSSSL()->admin->clear_admin_notices_cache();

	//delete in future upgrade. We want to check the review notice dismissed as fallback still.
	//delete_option( 'rlrsssl_options' );
	//delete_site_option( 'rlrsssl_network_options' );
	//delete_option( 'rsssl_options_lets-encrypt' );
	update_option( 'rsssl_previous_version', $prev_version, false );
	do_action( 'rsssl_upgrade', $prev_version );
	update_option( 'rsssl_current_version', rsssl_version, false );
}
