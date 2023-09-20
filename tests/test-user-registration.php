<?php

class RssslUsersCanRegisterTest extends WP_UnitTestCase {

        /**
     * Set up the test environment.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        // Include the file containing the functions to be tested.
        require_once __DIR__ . '/../security/wordpress/user-registration.php';
    }

    /**
     * Test if the users_can_register option is filtered correctly to disable user registration.
     *
     * @return void
     */
    public function test_rsssl_users_can_register_filter() {
        $value = true; // Initial value of the users_can_register option
        $option = 'users_can_register'; // Option name

        $result = rsssl_users_can_register($value, $option);

        $this->assertFalse($result);
    }
}