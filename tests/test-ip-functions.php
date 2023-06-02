<?php

class Rsssl_Limit_Login_Attempts_Test extends WP_UnitTestCase
{

    public function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../security/wordpress/limit-login-attempts.php';

        // Create an instance of the class
        $this->limit_login_attempts = new Rsssl_Limit_Login_Attempts();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        // Clean up any database tables created during testing
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}rsssl_allowlist");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}rsssl_blocklist");
    }

    public function testIpInRange() {
        $this->assertTrue($this->limit_login_attempts->ip_in_range("192.168.0.1", "192.168.0.1/24"));
        $this->assertFalse($this->limit_login_attempts->ip_in_range("192.168.1.1", "192.168.0.1/24"));
    }

    public function testCheckAgainstIps() {
        // You will have to mock database operations (using e.g. wp_cache_set in the setup)
        // to simulate certain conditions for testing check_against_ips.
        // Below is just an example usage of the method
        $this->assertEquals('not_found', $this->limit_login_attempts->check_against_ips(["192.168.0.1"]));
    }

    public function testCheckAgainstRanges() {
        // Similarly as with testCheckAgainstIps, this will also require setting up
        // certain conditions to test against.
        $this->assertEquals('not_found', $this->limit_login_attempts->check_against_ranges(["192.168.0.1"]));
    }

    public function testAddRemoveToAllowlist() {
        // Assumes that you have the rsssl_user_can_manage() function mocked in your test environment to return true
        $this->limit_login_attempts->add_to_allowlist("192.168.0.1");
        $this->assertEquals('allowed', $this->limit_login_attempts->check_against_ips(["192.168.0.1"]));
        $this->limit_login_attempts->remove_from_allowlist("192.168.0.1");
        $this->assertEquals('not_found', $this->limit_login_attempts->check_against_ips(["192.168.0.1"]));
    }

    public function testAddRemoveToBlocklist() {
        // Assumes that you have the rsssl_user_can_manage() function mocked in your test environment to return true
        $this->limit_login_attempts->add_to_blocklist("192.168.0.1");
        $this->assertEquals('blocked', $this->limit_login_attempts->check_against_ips(["192.168.0.1"]));
        $this->limit_login_attempts->remove_from_blocklist("192.168.0.1");
        $this->assertEquals('not_found', $this->limit_login_attempts->check_against_ips(["192.168.0.1"]));
    }
}