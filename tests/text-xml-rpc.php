<?php
class XmlRpcDisableTest extends WP_UnitTestCase {

    public function setUp(): void {
        parent::setUp();
        // Include the file containing the functions to be tested.
        require_once __DIR__ . '/../security/wordpress/disable-xmlrpc.php';
    }

    public function test_xmlrpc_enabled_filter() {
        $this->assertFalse(apply_filters('xmlrpc_enabled', true));
    }

    public function test_rsd_link_removed() {
        $hooked = has_action('wp_head', 'rsd_link');
        $this->assertFalse($hooked);
    }

    public function test_xmlrpc_request_exit() {
        if (defined('XMLRPC_REQUEST')) {
            $this->markTestSkipped('XMLRPC_REQUEST is already defined. Cannot test exit scenario.');
        } else {
            define('XMLRPC_REQUEST', true);
            // Catch exit call
            try {
                require __DIR__ . '/../security/wordpress/disable-xmlrpc.php';
            } catch (ExitException $e) {
                $this->assertTrue(true);
                return;
            }
            $this->fail('Exit not called when XMLRPC_REQUEST is defined.');
        }
    }
}

class ExitException extends Exception {}
