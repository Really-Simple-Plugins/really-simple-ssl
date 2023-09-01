<?php

class TestLimitLoginAttempts extends WP_UnitTestCase
{
    private Rsssl_Limit_Login_Attempts $limit_login_attempts;

    public function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../security/wordpress/limit-login-attempts.php';

        global $wpdb;
        
        // Create an instance of the class
        $this->limit_login_attempts = new Rsssl_Limit_Login_Attempts();

        $allowlist_table = $wpdb->prefix . 'rsssl_allowlist';
        $blocklist_table = $wpdb->prefix . 'rsssl_blocklist';

        // Create allowlist and blocklist tables
        $wpdb->query("CREATE TABLE IF NOT EXISTS $allowlist_table (ip varchar(39) NOT NULL, PRIMARY KEY  (ip))");
        $wpdb->query("CREATE TABLE IF NOT EXISTS $blocklist_table (ip varchar(39) NOT NULL, PRIMARY KEY  (ip))");


        // Insert IPs into tables
        $allowed_ipv4 = ['192.0.2.10', '192.0.2.11', '192.0.2.12'];
        $allowed_ipv6 = ['2001:0db8:85a3:0000:0000:8a2e:0370:7334', '2001:0db8:85a3:0000:0000:8a2e:0370:7333'];
        foreach (array_merge($allowed_ipv4, $allowed_ipv6) as $ip) {
            $wpdb->insert($allowlist_table, ['ip' => $ip]);
        }

        $blocked_ipv4 = ['192.0.3.0', '192.0.3.1', '192.0.3.2'];
        $blocked_ipv6 = ['2001:0db8:85a3:0000:0000:8a2e:0370:7335', '2001:0db8:85a3:0000:0000:8a2e:0370:7336'];
        foreach (array_merge($blocked_ipv4, $blocked_ipv6) as $ip) {
            $wpdb->insert($blocklist_table, ['ip' => $ip]);
        }

    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_get_ip_address_valid_ipv4()
    {
        // additional test cases for valid IPv4
        $ipAddresses = ['192.0.2.10', '172.16.254.1', '10.0.0.0', '127.0.0.1', '255.255.255.255'];
        foreach ($ipAddresses as $ip) {
            $_SERVER['REMOTE_ADDR'] = $ip;
            $result = $this->limit_login_attempts->get_ip_address();
            $this->assertEquals([$ip], $result);
        }
    }

    public function test_get_ip_address_valid_ipv6()
    {
        // additional test cases for valid IPv6
        $ipAddresses = ['2001:0db8:85a3:0000:0000:8a2e:0370:7334', '::1', '2607:f0d0:1002:51::4', 'fe80::', 'ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff'];
        foreach ($ipAddresses as $ip) {
            $_SERVER['REMOTE_ADDR'] = $ip;
            $result = $this->limit_login_attempts->get_ip_address();
            $this->assertEquals([$ip], $result);
        }
    }

    public function test_get_ip_address_invalid()
    {
        // additional test cases for invalid IP addresses
        $ipAddresses = ['invalid ip', '256.0.0.0', '192.0.2', '127.0.0', '192.0.2.300', '2001::85a3::7334', '::1::', 'fe80:::'];
        foreach ($ipAddresses as $ip) {
            $_SERVER['REMOTE_ADDR'] = $ip;
            $result = $this->limit_login_attempts->get_ip_address();
            $this->assertEquals([], $result);
        }
    }

    public function test_check_ip_address_blocked_ipv4()
    {
        // additional test cases for blocked IPv4
        $ipAddresses = ['192.0.3.0', '192.0.3.1', '192.0.3.2'];  // Add more blocked IPs according to your business logic
        foreach ($ipAddresses as $ip) {
            $result = $this->limit_login_attempts->check_ip_address([$ip]);
            $this->assertEquals('blocked', $result);
        }
    }

    public function test_check_ip_address_blocked_ipv6()
    {
        // additional test cases for blocked IPv6
        $ipAddresses = ['2001:0db8:85a3:0000:0000:8a2e:0370:7335', '2001:0db8:85a3:0000:0000:8a2e:0370:7336'];  // Add more blocked IPs according to your business logic
        foreach ($ipAddresses as $ip) {
            $result = $this->limit_login_attempts->check_ip_address([$ip]);
            $this->assertEquals('blocked', $result);
        }
    }

    public function test_check_ip_address_allowed_ipv4()
    {
        // additional test cases for allowed IPv4
        $ipAddresses = ['192.0.2.10', '192.0.2.11', '192.0.2.12'];  // Add more allowed IPs according to your business logic
        foreach ($ipAddresses as $ip) {
            $result = $this->limit_login_attempts->check_ip_address([$ip]);
            $this->assertEquals('allowed', $result);
        }
    }

    public function test_check_ip_address_allowed_ipv6()
    {
        // additional test cases for allowed IPv6
        $ipAddresses = ['2001:0db8:85a3:0000:0000:8a2e:0370:7334', '2001:0db8:85a3:0000:0000:8a2e:0370:7333'];  // Add more allowed IPs according to your business logic
        foreach ($ipAddresses as $ip) {
            $result = $this->limit_login_attempts->check_ip_address([$ip]);
            $this->assertEquals('allowed', $result);
        }
    }

    /**
     * @dataProvider provide_ip_in_range_data
     */
    public function test_ip_in_range($ip, $range, $expected)
    {
        $result = $this->limit_login_attempts->ip_in_range($ip, $range);
        $this->assertSame($expected, $result);
    }

    public function provide_ip_in_range_data()
    {
        return [
            ['192.0.2.10', '192.0.2.10', true],
            ['192.0.2.10', '192.0.2.10/24', true],
            ['192.0.2.0', '192.0.2.10', false],
            ['192.0.2.10', '192.0.2.0/24', true],
            ['192.0.2.255', '192.0.2.0/24', true],
            ['192.0.3.0', '192.0.2.0/24', false],
            ['2001:0db8:85a3:0000:0000:8a2e:0370:7334', '2001:0db8:85a3:0000:0000:8a2e:0370:7334', true],
            ['2001:0db8:85a3:0000:0000:8a2e:0370:7334', '2001:0db8:85a3:0000:0000:8a2e:0370:7334/64', true],
            ['2001:0db8:85a3:0000:0000:8a2e:0370:7335', '2001:0db8:85a3:0000:0000:8a2e:0370:7334/64', true],
            ['2001:0db8:85a3:0000:0000:8a2e:0370:7335', '2001:0db8:85a3:0000:0000:8a2e:0370:7334', false],
            ['2001:0db8:85a3:0000:0000:8a2e:0370:7334', '2001:0db8:85a3:0000:0000:8a2e:0370:7330/64', true],
            ['2001:0db8:85a3:0000:0000:8a2e:0370:7334', '2001:0db8:85a3:0000:0000:8a2e:0370:7330/128', false],
            ['invalid ip', '192.0.2.10', false],
            ['192.0.2.10', 'invalid range', false],
            ['192.0.2.10', '192.0.2.10/129', false],
        ];
    }

}