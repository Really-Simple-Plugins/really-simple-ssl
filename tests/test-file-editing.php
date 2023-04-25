<?php

use WP_UnitTestCase;

class RssslFileEditingTest extends WP_UnitTestCase {

    public function setUp(): void {
        parent::setUp();
        require_once __DIR__ . '/../security/wordpress/file-editing.php';
    }

    public function test_rsssl_disable_file_editing() {
        // Make sure DISALLOW_FILE_EDIT is not defined at first
        $this->assertFalse(defined('DISALLOW_FILE_EDIT'));

        // Call the function to define DISALLOW_FILE_EDIT
        rsssl_disable_file_editing();

        // Check if DISALLOW_FILE_EDIT is defined and set to true
        $this->assertTrue(defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT);
    }

    public function test_rsssl_file_editing_defined_but_disabled() {
        // Test when DISALLOW_FILE_EDIT is not defined
        $this->assertFalse(rsssl_file_editing_defined_but_disabled());

        // Test when DISALLOW_FILE_EDIT is defined and set to true
        define('DISALLOW_FILE_EDIT', true);
        $this->assertFalse(rsssl_file_editing_defined_but_disabled());

        // Test when DISALLOW_FILE_EDIT is defined and set to false
        define('DISALLOW_FILE_EDIT', false);
        $this->assertTrue(rsssl_file_editing_defined_but_disabled());
    }

    public function test_rsssl_disable_file_editing_notice() {
        // Test with an empty array of notices
        $notices = rsssl_disable_file_editing_notice([]);
        $this->assertArrayHasKey('disallow_file_edit_false', $notices);
        $this->assertIsArray($notices['disallow_file_edit_false']);

        // Test with existing notices
        $existing_notices = ['existing-notice' => ['callback' => 'test_callback']];
        $notices = rsssl_disable_file_editing_notice($existing_notices);
        $this->assertArrayHasKey('existing-notice', $notices);
        $this->assertArrayHasKey('disallow_file_edit_false', $notices);
    }
}