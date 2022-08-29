<?php
defined('ABSPATH') or die();

add_action('plugins_loaded', 'rsssl_upgrade', 20);
function rsssl_upgrade() {
	$prev_version = get_option( 'rsssl_current_version', false );

	//dismiss notices that should be dismissed on plugin upgrade
	if ( $prev_version && version_compare( $prev_version, rsssl_version, '!=' )) {
		$dismiss_options = RSSSL()->really_simple_ssl->get_notices_list( array(
			'dismiss_on_upgrade' => true,
		) );
		foreach ($dismiss_options as $dismiss_option ) {
			update_option( "rsssl_" . $dismiss_option . "_dismissed" , true, false );
		}
		delete_transient( 'rsssl_plusone_count' );
	}

	if ( $prev_version && version_compare( $prev_version, '4.0', '<' ) ) {
		update_option('rsssl_remaining_tasks', true, false );
	}

	if ( $prev_version && version_compare( $prev_version, '4.0.10', '<=' ) ) {
		if (function_exists('is_wpe') && is_wpe()) {
			rsssl_update_option('redirect', 'wp_redirect');
			RSSSL()->really_simple_ssl->save_options();
		}
	}
	if ( $prev_version && version_compare( $prev_version, '5.1.3', '<=' ) ) {
		if ( get_option( 'rsssl_disable_ocsp' ) ) {
			$options = get_option( 'rsssl_options_lets-encrypt' );
			$options['disable_ocsp'] = true;
			update_option( 'rsssl_options_lets-encrypt', $options, false );
			delete_option('rsssl_disable_ocsp');
		}
	}

	if ( $prev_version && version_compare( $prev_version, '5.3.0', '<=' ) ) {
		if ( file_exists(RSSSL()->really_simple_ssl->htaccess_file() ) && is_writable(RSSSL()->really_simple_ssl->htaccess_file() ) ) {
			$htaccess = file_get_contents( RSSSL()->really_simple_ssl->htaccess_file() );
			$pattern_start = "/rlrssslReallySimpleSSL rsssl_version\[.*.]/";
			$pattern_end = "/rlrssslReallySimpleSSL/";

			if ( preg_match_all( $pattern_start, $htaccess ) ) {
				$htaccess = preg_replace( $pattern_start, "Really Simple SSL Redirect " . rsssl_version, $htaccess );
				$htaccess = preg_replace( $pattern_end, "Really Simple SSL Redirect", $htaccess );
				file_put_contents( RSSSL()->really_simple_ssl->htaccess_file(), $htaccess );
			}
		}
	}

	if ( $prev_version && version_compare( $prev_version, '6.0.0', '<' ) ) {
		update_option('rsssl_show_onboarding', true, false);

		//upgrade both site and network settings
		$options = get_option( 'rlrsssl_options' );
		$autoreplace_insecure_links = isset( $options['autoreplace_insecure_links'] ) ? $options['autoreplace_insecure_links'] : true;
		rsssl_update_option('mixed_content_fixer', $autoreplace_insecure_links);

		$wp_redirect  = isset( $options['wp_redirect'] ) ? $options['wp_redirect'] : false;
		$htaccess_redirect = isset( $options['htaccess_redirect'] ) ? $options['htaccess_redirect'] : false;
		$redirect = 'none;';
		if ( $htaccess_redirect ) {
			$redirect = 'htaccess';
		} else if ( $wp_redirect ) {
			$redirect = 'wp_redirect';
		}
		rsssl_update_option('redirect', $redirect);
		$do_not_edit_htaccess            = isset( $options['do_not_edit_htaccess'] ) ? $options['do_not_edit_htaccess'] : false;
		rsssl_update_option('do_not_edit_htaccess', $do_not_edit_htaccess);
		$dismiss_all_notices             = isset( $options['dismiss_all_notices'] ) ? $options['dismiss_all_notices'] : false;
		rsssl_update_option('dismiss_all_notices', $dismiss_all_notices);

		$switch_mixed_content_fixer_hook = isset( $options['switch_mixed_content_fixer_hook'] ) ? $options['switch_mixed_content_fixer_hook'] : false;
		rsssl_update_option('switch_mixed_content_fixer_hook', $switch_mixed_content_fixer_hook);

		delete_option( "rsssl_upgraded_to_four" );

		/**
		 * Multisite
		 */
		if ( is_multisite() && rsssl_is_networkwide_active() ) {
			$network_options = get_site_option('rlrsssl_network_options');
			$enabled_network_wide = isset($network_options["ssl_enabled_networkwide"]) ? $options["ssl_enabled_networkwide"] : false;
			if ( $enabled_network_wide ) {
				update_site_option('rsssl_network_activation_status', 'completed');
			} else {
				//convert entire site to SSL
				RSSSL()->rsssl_multisite->start_ssl_activation();
			}
			//ensure this doesn't run again
			$network_options["ssl_enabled_networkwide"] = false;
			update_site_option('rlrsssl_network_options', $network_options);

			$dismiss_all_notices = isset($network_options["dismiss_all_notices"]) ? $network_options["dismiss_all_notices"] : false;
			rsssl_update_option('dismiss_all_notices', $dismiss_all_notices);

			$wp_redirect = isset($network_options["wp_redirect"]) ? $network_options["wp_redirect"] : false;
			if ($wp_redirect) rsssl_update_option('redirect', 'wp_redirect');
			$htaccess_redirect = isset($network_options["htaccess_redirect"]) ? $network_options["htaccess_redirect"] : false;
			if ($htaccess_redirect) rsssl_update_option('redirect', 'htaccess');
			$do_not_edit_htaccess = isset($network_options["do_not_edit_htaccess"]) ? $network_options["do_not_edit_htaccess"] : false;
			rsssl_update_option('do_not_edit_htaccess', $do_not_edit_htaccess);
			$autoreplace_mixed_content = isset($network_options["autoreplace_mixed_content"]) ? $network_options["autoreplace_mixed_content"] : false;
			rsssl_update_option('mixed_content_fixer', $autoreplace_mixed_content);
		}
	}

	//delete in future upgrade
	//delete_option( 'rlrsssl_options' );
	//delete_site_option( 'rlrsssl_network_options' );

	do_action("rsssl_upgrade", $prev_version);
	update_option( 'rsssl_current_version', rsssl_version );
}