<?php

class RssslTestIPFunctions extends WP_UnitTestCase {

	protected $ip_in_range_test_cases;
	protected $get_ips_test_cases;
	protected $check_ip_test_cases_normal_and_range;

	public function setUp(): void {
		parent::setUp();

		// Extensive set of test cases for `rsssl_ip_in_range`
		$this->ip_in_range_test_cases = [
			[ 'ip' => '192.0.2.0', 'range' => '192.0.2.0/24', 'expected' => true ],
			[ 'ip' => '192.0.2.255', 'range' => '192.0.2.0/24', 'expected' => true ],
			[ 'ip' => '192.0.2.256', 'range' => '192.0.2.0/24', 'expected' => false ],
			[ 'ip' => '192.0.3.0', 'range' => '192.0.2.0/24', 'expected' => false ],
			[ 'ip' => '2001:db8::', 'range' => '2001:db8::/32', 'expected' => true ],
			[ 'ip' => '2001:db8::ffff:ffff:ffff:ffff', 'range' => '2001:db8::/32', 'expected' => true ],
			[ 'ip' => '2001:db9::', 'range' => '2001:db8::/32', 'expected' => false ],
			[ 'ip' => '192.0.2.0', 'range' => '192.0.2.0', 'expected' => true ],
			[ 'ip' => '192.0.2.1', 'range' => '192.0.2.0', 'expected' => false ],
			[ 'ip' => '2001:db8::', 'range' => '2001:db8::', 'expected' => true ],
			[ 'ip' => '2001:db8::1', 'range' => '2001:db8::', 'expected' => false ],
			[ 'ip' => '2001:db8::1', 'range' => '2001:db8::/64', 'expected' => true ],
			[ 'ip' => '2001:db8:0:1::', 'range' => '2001:db8::/64', 'expected' => false ],
			[ 'ip' => '2001:db8::1', 'range' => '2001:db8::/128', 'expected' => true ],
			[ 'ip' => '2001:db8::2', 'range' => '2001:db8::/128', 'expected' => false ],
			[ 'ip' => 'not an IP address', 'range' => '192.0.2.0/24', 'expected' => 'InvalidArgumentException' ],
			[ 'ip' => '192.0.2.0', 'range' => 'not a range', 'expected' => 'InvalidArgumentException:' ],
			[ 'ip' => 'not an IP address', 'range' => 'not a range', 'expected' => 'InvalidArgumentException' ],
		];

		// Test cases for `rsssl_get_ips`
		$this->get_ips_test_cases = [
			[
				'headers'  => [
					'REMOTE_ADDR'              => '192.0.2.0',
					'HTTP_X_FORWARDED_FOR'     => '192.0.2.1, 192.0.2.2',
					'HTTP_X_FORWARDED'         => '192.0.2.3',
					'HTTP_FORWARDED_FOR'       => '192.0.2.4',
					'HTTP_FORWARDED'           => '192.0.2.5',
					'HTTP_CF_CONNECTING_IP'    => '192.0.2.6',
					'HTTP_FASTLY_CLIENT_IP'    => '192.0.2.7',
					'HTTP_X_CLUSTER_CLIENT_IP' => '192.0.2.8',
					'HTTP_X_REAL_IP'           => '192.0.2.9',
				],
				'expected' => [
					'192.0.2.0',
					'192.0.2.1',
					'192.0.2.2',
					'192.0.2.3',
					'192.0.2.4',
					'192.0.2.5',
					'192.0.2.6',
					'192.0.2.7',
					'192.0.2.8',
					'192.0.2.9'
				],
			],
			[
				'headers'  => [
					'REMOTE_ADDR'              => '192.0.2.0',
					'HTTP_X_FORWARDED_FOR'     => '192.0.2.1',
					'HTTP_X_FORWARDED'         => '192.0.2.3',
					'HTTP_FORWARDED_FOR'       => '192.0.2.4',
					'HTTP_FORWARDED'           => '192.0.2.5',
					'HTTP_CF_CONNECTING_IP'    => '192.0.2.6',
					'HTTP_FASTLY_CLIENT_IP'    => '192.0.2.7',
					'HTTP_X_CLUSTER_CLIENT_IP' => '192.0.2.8',
					'HTTP_X_REAL_IP'           => '192.0.2.9',
				],
				'expected' => [
					'192.0.2.0',
					'192.0.2.1',
					'192.0.2.3',
					'192.0.2.4',
					'192.0.2.5',
					'192.0.2.6',
					'192.0.2.7',
					'192.0.2.8',
					'192.0.2.9'
				],
			],
			[
				'headers'  => [
					'REMOTE_ADDR' => '192.0.2.0',
				],
				'expected' => [ '192.0.2.0' ],
			],
			[
				'headers'  => [],
				'expected' => [],
			],
		];

		$this->check_ip_test_cases_normal_and_range = [
			[ 'ip' => ['192.0.2.0'], 'expected' => ['192.0.2.0' => true] ],
			[ 'ip' => ['2001:db8::'], 'expected' => ['2001:db8::' => true] ],
			[ 'ip' => ['192.0.2.0/24'], 'expected' => ['192.0.2.0/24' => true] ],
			[ 'ip' => ['2001:db8::/32'], 'expected' => ['2001:db8::/32' => true] ],
			[ 'ip' => ['not an IP address'], 'expected' => 'InvalidArgumentException' ],
			[ 'ip' => ['192.0.2.0/24', '2001:db8::/32'], 'expected' => ['192.0.2.0/24' => true, '2001:db8::/32' => true] ],
			[ 'ip' => ['not an IP address/24'], 'expected' => 'InvalidArgumentException' ],
		];

		require_once __DIR__ . '/../security/functions.php';
	}

	public function test_rsssl_check_ip() {
		foreach ( $this->check_ip_test_cases_normal_and_range as $test_case ) {
			// Given
			$ip = $test_case['ip'];

			// Handle InvalidArgumentException
			if ( $test_case['expected'] == 'InvalidArgumentException') {
				$this->expectException(InvalidArgumentException::class);

				// When
				rsssl_check_ip_address( $ip );
				continue;
			}

			// When
			$result = rsssl_check_ip_address( $ip );

			// Then
			$this->assertEquals( $test_case['expected'], $result );
		}
	}

	public function test_rsssl_get_ips() {
		foreach ( $this->get_ips_test_cases as $test_case ) {
			// Given
			$_SERVER = $test_case['headers'];

			// When
			$ips = rsssl_get_ips();

			// Then
			$this->assertEquals( $test_case['expected'], $ips );
		}
	}

	public function test_rsssl_ip_in_range() {
		$test_cases = $this->ip_in_range_test_cases;
		foreach ( $test_cases as $test_case ) {
			// Given
			$ip    = $test_case['ip'];
			$range = $test_case['range'];

			// Handle InvalidArgumentException
			if ( $test_case['expected'] == 'InvalidArgumentException') {
				$this->expectException(InvalidArgumentException::class);
			}

			// When
			$inRange = rsssl_ip_in_range( $ip, $range );

			// Then
			$this->assertEquals( $test_case['expected'], $inRange );
		}

	}
	
}
