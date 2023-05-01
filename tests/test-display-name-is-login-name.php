<?php

class RssslJavaScriptTest extends WP_UnitTestCase {

    /**
     * Set up the test environment.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        require_once __DIR__ . '/../security/wordpress/display-name-is-login-name.php';
    }

    /**
     * Test if rsssl_disable_registration_js function outputs the correct JavaScript code
     * when the REQUEST_URI is set to 'user-new.php'.
     *
     * @return void
     */
    public function test_rsssl_disable_registration_js() {
        // Set REQUEST_URI to simulate 'user-new.php' and 'profile.php' pages.
        $_SERVER['REQUEST_URI'] = 'user-new.php';

        ob_start();
        rsssl_disable_registration_js();
        $output = ob_get_clean();

        $this->assertStringContainsString("document.getElementById('first_name').closest('tr').classList.add(\"form-required\");", $output);
        $this->assertStringContainsString("document.getElementById('last_name').closest('tr').classList.add(\"form-required\");", $output);

        // Reset REQUEST_URI
        unset($_SERVER['REQUEST_URI']);
    }

    /**
     * Test if rsssl_strip_userlogin function outputs the correct JavaScript code
     * when the REQUEST_URI is set to 'profile.php'.
     *
     * @return void
     */
    public function test_rsssl_strip_userlogin() {
        // Set REQUEST_URI to simulate 'profile.php' page.
        $_SERVER['REQUEST_URI'] = 'profile.php';

        ob_start();
        rsssl_strip_userlogin();
        $output = ob_get_clean();

        $this->assertStringContainsString("let rsssl_user_login = document.querySelector('input[name=user_login]');", $output);
        $this->assertStringContainsString("let rsssl_display_name = document.querySelector('select[name=display_name]');", $output);

        // Reset REQUEST_URI
        unset($_SERVER['REQUEST_URI']);
    }
}
