<?php

class RssslJavaScriptTest extends WP_UnitTestCase {

    public function setUp(): void {
        parent::setUp();
        require_once __DIR__ . '/../security/wordpress/user-registration.php';
    }

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
