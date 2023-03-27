<?php

require( 'class-installer.php' );

class RssslInstallerTest extends WP_UnitTestCase {
	/**
	 * @throws Exception
	 */
	public function setUp(): void {
		// Load WordPress environment
		// Make it suitable for localhost and pipeline
		$max_dirs = 10;
		$found_wp_load = defined('WPINC');

		if ( ! $found_wp_load ) {
			for ($i = 1; $i <= $max_dirs; $i++) {
				$path = dirname(__FILE__, $i) . '/wp-load.php';
				if ( file_exists( $path ) ) {
					require_once($path);
					$found_wp_load = true;
					break;
				}
			}
		}

		if (!$found_wp_load) {
			throw new Exception('Unable to locate wp-load.php in the directory hierarchy');
		}

		// Set an active user, otherwise capability checks will fail
		wp_set_current_user(1);
		// Activate any required plugins
		activate_plugin('rlrsssl-really-simple-ssl.php');
	}

	public function test_plugin_installation() {

		$burst_installer           = new rsssl_installer( 'burst-statistics' );
		$complianz_gdpr_installer  = new rsssl_installer( 'complianz-gdpr' );
		$complianz_terms_installer = new rsssl_installer( 'complianz-terms-conditions' );

		$this->assertTrue( $burst_installer->download_plugin(), 'Download of burst-statistics plugin failed.' );
		// Get clean after every download, otherwise issues with ob_level going up
		ob_get_clean();
		$this->assertTrue( $complianz_gdpr_installer->download_plugin(), 'Download of complianz-gdpr plugin failed.' );
		ob_get_clean();
		$this->assertTrue( $complianz_terms_installer->download_plugin(), 'Download of complianz-terms-conditions plugin failed.' );
		ob_get_clean();


		echo "Checking if burst-statistics plugin is downloaded...\n";
		$plugin_file_path = trailingslashit(WP_PLUGIN_DIR) . $burst_installer->get_activation_slug();
		if (file_exists($plugin_file_path)) {
			echo "Plugin file found: {$plugin_file_path}\n";
		} else {
			echo "Plugin file not found: {$plugin_file_path}\n";
		}

		if (is_writable(WP_PLUGIN_DIR)) {
			echo "Plugin directory is writable.\n";
		} else {
			echo "Plugin directory is not writable.\n";
		}
		
		$this->assertTrue( $burst_installer->plugin_is_downloaded(), 'burst-statistics plugin is not downloaded.' );
		$this->assertTrue( $complianz_gdpr_installer->plugin_is_downloaded(), 'complianz-gdpr plugin is not downloaded.' );
		$this->assertTrue( $complianz_terms_installer->plugin_is_downloaded(), 'complianz-terms-conditions plugin is not downloaded.' );

		$this->assertTrue( $burst_installer->activate_plugin(), 'Activation of burst-statistics plugin failed.' );
		$this->assertTrue( $complianz_gdpr_installer->activate_plugin(), 'Activation of complianz-gdpr plugin failed.' );
		$this->assertTrue( $complianz_terms_installer->activate_plugin(), 'Activation of complianz-terms-conditions plugin failed.' );

		$this->assertTrue( $burst_installer->plugin_is_activated(), 'burst-statistics plugin is not activated.' );
		$this->assertTrue( $complianz_gdpr_installer->plugin_is_activated(), 'complianz-gdpr plugin is not activated.' );
		$this->assertTrue( $complianz_terms_installer->plugin_is_activated(), 'complianz-terms-conditions plugin is not activated.' );

	}
}