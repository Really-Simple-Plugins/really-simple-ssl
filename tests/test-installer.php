<?php
class RSSSLInstallerTest extends \WP_UnitTestCase {
    private $rsssl_installer;

    public function setUp(): void {
        parent::setUp();
        $this->rsssl_installer = new rsssl_installer('burst-statistics');
    }

    public function test_construct() {
        $this->assertInstanceOf(rsssl_installer::class, $this->rsssl_installer);
    }

    public function test_plugin_is_downloaded() {
        $result = $this->rsssl_installer->plugin_is_downloaded();
        $this->assertIsBool($result);
    }

    public function test_plugin_is_activated() {
        $result = $this->rsssl_installer->plugin_is_activated();
        $this->assertIsBool($result);
    }

    public function test_install_download() {
        $result = $this->rsssl_installer->install('download');
        $this->assertIsBool($result);
    }

    public function test_install_activate() {
        $result = $this->rsssl_installer->install('activate');
        $this->assertIsBool($result);
    }

    public function test_get_activation_slug() {
        $result = $this->rsssl_installer->get_activation_slug();
        $this->assertIsString($result);
    }

    public function test_cancel_tour() {
        $this->rsssl_installer->cancel_tour();
        $prefix = 'burst';
        $tour_started = get_site_option( $prefix.'_tour_started');
        $tour_shown_once = get_site_option( $prefix.'_tour_shown_once');
        $redirect_to_settings = get_transient($prefix.'_redirect_to_settings');
        $this->assertFalse($tour_started);
        $this->assertTrue($tour_shown_once);
        $this->assertFalse($redirect_to_settings);
    }

    public function test_download_plugin() {
        $result = $this->rsssl_installer->download_plugin();
        $this->assertIsBool($result);
    }

    public function test_activate_plugin() {
        $result = $this->rsssl_installer->activate_plugin();
        $this->assertIsBool($result);
    }
}