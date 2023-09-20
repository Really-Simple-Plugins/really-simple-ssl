<?php

class RSSSLTestRenameAdminUser extends WP_UnitTestCase {

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        require_once __DIR__ . '/../security/wordpress/rename-admin-user.php';
    }

    /**
     * Test if the rsssl_admin_username_changed function adds the expected
     * notice structure to the original notices array.
     *
     * @return void
     */
    public function test_rsssl_admin_username_changed() {
        $original_notices = array();

        $new_notices = rsssl_admin_username_changed($original_notices);
        $this->assertArrayHasKey('username_admin_changed', $new_notices);

        $username_admin_changed_notice = $new_notices['username_admin_changed'];
        $this->assertArrayHasKey('condition', $username_admin_changed_notice);
        $this->assertArrayHasKey('callback', $username_admin_changed_notice);
        $this->assertArrayHasKey('score', $username_admin_changed_notice);
        $this->assertArrayHasKey('output', $username_admin_changed_notice);
    }

    /**
     * Test if the rsssl_prevent_admin_user_add function adds the expected
     * user logins to the original illegal_user_logins array.
     *
     * @return void
     */
    public function test_rsssl_prevent_admin_user_add() {
        $original_illegal_user_logins = array('testuser');
        $new_illegal_user_logins = rsssl_prevent_admin_user_add($original_illegal_user_logins);

        $this->assertContains('admin', $new_illegal_user_logins);
        $this->assertContains('administrator', $new_illegal_user_logins);
        $this->assertEquals($original_illegal_user_logins, array('testuser'));
    }
}
