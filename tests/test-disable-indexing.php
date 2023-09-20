<?php

class RSSSLDisableIndexingTest extends WP_UnitTestCase {

	/**
	 * Class RSSSLDisableXMLRPCTest
	 *
	 * Test class for disable-xmlrpc.php functions
	 */
    public function setUp(): void {
        parent::setUp();
        require_once __DIR__ . '/../security/server/disable-indexing.php';
    }

    /**
     * Test if the 'rsssl_disable_indexing_rules' function is added to the 'rsssl_htaccess_security_rules' filter
     */
    public function test_rsssl_disable_indexing_rules_added() {
        $this->assertNotFalse(has_filter('rsssl_htaccess_security_rules', 'rsssl_disable_indexing_rules'));
    }

    /**
     * Test if the 'rsssl_disable_indexing_rules' function modifies the rules as expected
     */
    public function test_rsssl_disable_indexing_rules() {
        $rules = [];

        $expected_rules = [
            [
                'rules' => "\n" . 'Options -Indexes',
                'identifier' => 'Options -Indexes',
            ],
        ];

        // Test the 'rsssl_disable_indexing_rules' function directly
        $modified_rules = rsssl_disable_indexing_rules($rules);
        $this->assertEquals($expected_rules, $modified_rules);
    }

}