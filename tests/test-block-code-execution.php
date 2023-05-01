<?php

class RssslCodeExecutionTest extends WP_UnitTestCase {

    /**
     * Set up the test environment.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        require_once __DIR__ . '/../security/wordpress/block-code-execution-uploads.php';
    }

    /**
     * Test if PHP execution is blocked in the /wp-content/uploads folder.
     *
     * @return void
     */
    public function test_php_execution_in_uploads_folder() {
        $uploads_dir = wp_upload_dir();
        $uploads_path = $uploads_dir['basedir'];
        $test_file_path = $uploads_path . '/test.php';

        // Create a temporary test file in the /wp-content/uploads folder.
        file_put_contents($test_file_path, '<?php echo "executed"; ?>');

        // Perform an HTTP request to the test file.
        $test_file_url = $uploads_dir['baseurl'] . '/test.php';
        $response = wp_remote_get($test_file_url);
        $response_body = wp_remote_retrieve_body($response);

        // Delete the test file.
        unlink($test_file_path);

        // Check if the test file was executed.
        $this->assertNotEquals('executed', $response_body, 'PHP file in the /wp-content/uploads folder should not be executable.');
    }
}
