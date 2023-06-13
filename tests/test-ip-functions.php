<?php

class TestIPClass extends WP_UnitTestCase {

    public function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../security/wordpress/limit-login-attempts.php';

        // Create an instance of the class
        $this->ipClass = new Rsssl_Limit_Login_Attempts();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_check_request_valid_ipv4() {
        $_SERVER['REMOTE_ADDR'] = '1.1.1.1';
        // Assuming check_against_ips and get_ip_range_status methods are stubbed to return 'allowed'
        $this->ipClass->check_request();
        $this->expectOutputString('');
    }

    public function test_check_request_valid_ipv6() {
        $_SERVER['REMOTE_ADDR'] = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';
        // Assuming check_against_ips and get_ip_range_status methods are stubbed to return 'allowed'
        $this->ipClass->check_request();
        $this->expectOutputString('');
    }

    public function test_check_request_blocked_ipv4() {
        $_SERVER['REMOTE_ADDR'] = '2.2.2.2';
        // Assuming check_against_ips and get_ip_range_status methods are stubbed to return 'blocked'
        $this->expectException(ExitException::class);
        $this->ipClass->check_request();
    }

    public function test_check_request_blocked_ipv6() {
        $_SERVER['REMOTE_ADDR'] = '2001:0db8:85a3:0000:0000:8a2e:0370:7335';
        // Assuming check_against_ips and get_ip_range_status methods are stubbed to return 'blocked'
        $this->expectException(ExitException::class);
        $this->ipClass->check_request();
    }

    public function test_get_ip_address_valid_ipv4() {
        $_SERVER['REMOTE_ADDR'] = '1.1.1.1';
        $result = $this->ipClass->get_ip_address();
        $this->assertEquals($result, ['1.1.1.1']);
    }

    public function test_get_ip_address_valid_ipv6() {
        $_SERVER['REMOTE_ADDR'] = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';
        $result = $this->ipClass->get_ip_address();
        $this->assertEquals($result, ['2001:0db8:85a3:0000:0000:8a2e:0370:7334']);
    }

    public function test_get_ip_address_invalid() {
        $_SERVER['REMOTE_ADDR'] = '999.999.999.999'; // Invalid IP
        $result = $this->ipClass->get_ip_address();
        $this->assertEquals($result, []);
    }

    public function test_check_ip_address_blocked_ipv4() {
        // Assuming check_against_ips and get_ip_range_status methods are stubbed to return 'blocked'
        $result = $this->ipClass->check_ip_address(['2.2.2.2']);
        $this->assertEquals($result, 'blocked');
    }

    public function test_check_ip_address_blocked_ipv6() {
        // Assuming check_against_ips and get_ip_range_status methods are stubbed to return 'blocked'
        $result = $this->ipClass->check_ip_address(['2001:0db8:85a3:0000:0000:8a2e:0370:7335']);
        $this->assertEquals($result, 'blocked');
    }

    public function test_check_ip_address_allowed_ipv4() {
        // Assuming check_against_ips and get_ip_range_status methods are stubbed to return 'allowed'
        $result = $this->ipClass->check_ip_address(['1.1.1.1']);
        $this->assertEquals($result, 'allowed');
    }

    public function test_check_ip_address_allowed_ipv6() {
        // Assuming check_against_ips and get_ip_range_status methods are stubbed to return 'allowed'
        $result = $this->ipClass->check_ip_address(['2001:0db8:85a3:0000:0000:8a2e:0370:7334']);
        $this->assertEquals($result, 'allowed');
    }

    public function test_ip_in_range_true_ipv4() {
        $result = $this->ipClass->ip_in_range('192.0.2.1', '192.0.2.0/24');
        $this->assertTrue($result);
    }

    public function test_ip_in_range_false_ipv4() {
        $result = $this->ipClass->ip_in_range('192.0.3.1', '192.0.2.0/24');
        $this->assertFalse($result);
    }

    public function test_ip_in_range_true_ipv6() {
        $result = $this->ipClass->ip_in_range('2001:0db8:85a3:0000:0000:8a2e:0370:7334', '2001:0db8:85a3:0000:0000:8a2e:0370:7334/64');
        $this->assertTrue($result);
    }

    public function test_ip_in_range_false_ipv6() {
        $result = $this->ipClass->ip_in_range('2001:0db8:85a3:0000:0000:8a2e:0370:7335', '2001:0db8:85a3:0000:0000:8a2e:0370:7334/64');
        $this->assertFalse($result);
    }

}