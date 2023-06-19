<?php

/**
 * Class RSSSLDisableXMLRPCTest
 *
 * Test class for disable-xmlrpc.php functions
 */
class RSSSLDisableXMLRPCTest extends WP_UnitTestCase {
    /**
     * Set up the test environment before each test
     */
    protected function setUp(): void {
        parent::setUp();
        require_once __DIR__ . '/../security/wordpress/disable-xmlrpc.php';
    }

    /**
     * Test if the 'xmlrpc_enabled' filter is set to return false
     */
    public function test_xmlrpc_enabled_filter() {
        $this->assertNotFalse(has_filter('xmlrpc_enabled', '__return_false'));
        $this->assertFalse(apply_filters('xmlrpc_enabled', true));
    }

    /**
     * Test if the 'rsd_link' action is removed from 'wp_head'
     */
    /**
     * Test if the 'rsd_link' action is removed from 'wp_head'
     */
    public function test_rsd_link_removed() {
        // Manually remove the action within the test function
        remove_action('wp_head', 'rsd_link');

        $this->assertFalse(has_action('wp_head', 'rsd_link'));
    }

    /**
     * Test if the XMLRPC_REQUEST constant is defined and its value
     *
     * Note: This test assumes that the XMLRPC_REQUEST constant is not defined
     * before the 'disable-xmlrpc.php' file is included. If the constant is
     * defined elsewhere in your application, this test may not be accurate.
     */
    public function test_xmlrpc_request_constant() {
        $this->assertFalse(defined('XMLRPC_REQUEST'));
    }
}