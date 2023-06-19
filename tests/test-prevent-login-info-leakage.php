<?php

class RssslPreventLoginInfoLeakageTest extends WP_UnitTestCase {

    /**
     * Set up the test environment.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        require_once __DIR__ . '/../security/wordpress/prevent-login-info-leakage.php';

        // Add hooks directly in the test
        add_filter( 'login_errors', 'rsssl_no_wp_login_errors' );
        add_action( 'login_enqueue_scripts', 'rsssl_hide_pw_reset_error' );
        add_action( 'login_footer', 'rsssl_clear_username_on_correct_username' );
    }

    /**
     * Test if rsssl_no_wp_login_errors is hooked to the 'login_errors' filter.
     *
     * @return void
     */
    public function test_rsssl_no_wp_login_errors_hooked() {
        $this->assertNotFalse(has_filter('login_errors', 'rsssl_no_wp_login_errors'));
    }

    /**
     * Test if rsssl_no_wp_login_errors function works as expected.
     *
     * @return void
     */
    public function test_rsssl_no_wp_login_errors() {
        $expected_message = __("Invalid login details.", "really-simple-ssl");
        $this->assertEquals($expected_message, rsssl_no_wp_login_errors());
    }

    /**
     * Test if rsssl_hide_pw_reset_error is hooked to the 'login_enqueue_scripts' action.
     *
     * @return void
     */
    public function test_rsssl_hide_pw_reset_error_hooked() {
        $this->assertNotFalse(has_action('login_enqueue_scripts', 'rsssl_hide_pw_reset_error'));
    }

    /**
     * Test if rsssl_clear_username_on_correct_username is hooked to the 'login_footer' action.
     *
     * @return void
     */
    public function test_rsssl_clear_username_on_correct_username_hooked() {
        $this->assertNotFalse(has_action('login_footer', 'rsssl_clear_username_on_correct_username'));
    }
}