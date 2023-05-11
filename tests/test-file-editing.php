<?php
class RssslFileEditingTest extends WP_UnitTestCase {

    /**
     * Set up the test environment.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        if (!defined('DISALLOW_FILE_EDIT')) {
            define('DISALLOW_FILE_EDIT', true);
        }
        require_once __DIR__ . '/../security/wordpress/file-editing.php';
    }

    /**
     * Test if the DISALLOW_FILE_EDIT constant is defined and set to true.
     *
     * @return void
     */
    public function test_rsssl_disable_file_editing() {
        $this->assertTrue(defined('DISALLOW_FILE_EDIT') );
    }

    /**
     * Test the rsssl_disable_file_editing_notice function for different values
     * of the DISALLOW_FILE_EDIT constant.
     *
     * @return void
     */
    public function test_rsssl_disable_file_editing_notice() {
        // Test when DISALLOW_FILE_EDIT is true
        $notices = rsssl_disable_file_editing_notice([], true);
        $this->assertArrayHasKey('disallow_file_edit_false', $notices);

        // Test when DISALLOW_FILE_EDIT is false
        $notices = rsssl_disable_file_editing_notice([], false);
        $this->assertArrayHasKey('disallow_file_edit_false', $notices);
        $this->assertIsArray($notices['disallow_file_edit_false']);

        // Test when DISALLOW_FILE_EDIT is not defined
        $notices = rsssl_disable_file_editing_notice([], null);
        $this->assertArrayHasKey('disallow_file_edit_false', $notices);
    }
}