<?php

require( 'class-installer.php' );

class RssslInstallerTest extends WP_UnitTestCase {
	public function setUp(): void {
		// Load WordPress environment
		require_once( dirname( __FILE__, 4 ) . '/wp-load.php' );
		// Set an active user, otherwise capability checks will fail
		wp_set_current_user( 1 );
		// Activate any required plugins
		activate_plugin( 'rlrsssl-really-simple-ssl.php' );
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