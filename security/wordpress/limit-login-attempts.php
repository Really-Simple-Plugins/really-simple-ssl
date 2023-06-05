<?php

class Rsssl_Limit_Login_Attempts {

	public function __construct() {
		$this->create_db_tables();
	}

	/**
	 * Create the tables to store the IP addresses and ranges in.
	 *
	 * This function creates two tables in the database: `wp_rsssl_allowlist` and `wp_rsssl_blocklist`
	 * Which can contain both IPv4 and IPv6 addresses and ranges
	 *
	 * This function should be called when the integration is first activated.
	 */
	public function create_db_tables(): void {

		if ( ! rsssl_user_can_manage() ) {
			return;
		}

		if ( ! get_option( 'rsssl_limit_login_attempts_db_version' ) || get_option( 'rsssl_limit_login_attempts_db_version' ) != rsssl_version ) {

			global $wpdb;

			$charset_collate = $wpdb->get_charset_collate();

			// SQL to create the tables
			$sql = /** @lang text */
				"
			    CREATE TABLE {$wpdb->prefix}rsssl_allowlist (
			        id mediumint(9) NOT NULL AUTO_INCREMENT,
			        ip_or_range varchar(43) NOT NULL,
			        PRIMARY KEY (id)
			    ) $charset_collate;
			
			    CREATE TABLE {$wpdb->prefix}rsssl_blocklist (
			        id mediumint(9) NOT NULL AUTO_INCREMENT,
			        ip_or_range varchar(43) NOT NULL,
			        PRIMARY KEY (id)
			    ) $charset_collate;
			    ";

			// Include the upgrade file to use dbDelta()
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
			update_option( 'rsssl_limit_login_attempts_db_version', rsssl_version );

		}
	}

	/**
	 * Retrieves a list of unique, validated IP addresses from various headers.
	 *
	 * This function attempts to retrieve the client's IP address from a variety of HTTP headers,
	 * including 'X-Forwarded-For', 'X-Forwarded', 'Forwarded-For', and 'Forwarded'. The function
	 * prefers rightmost IPs in these headers as they are less likely to be spoofed. It also checks
	 * if each IP is valid and not in a private or reserved range. Duplicate IP addresses are removed
	 * from the returned array.
	 *
	 * Note: While this function strives to obtain accurate IP addresses, the nature of HTTP headers
	 * means that it cannot guarantee the authenticity of the IP addresses.
	 *
	 * @return array An array of unique, validated IP addresses. If no valid IP addresses are found,
	 *               an empty array is returned.
	 */

	public function get_ip_address(): array {
		// Initialize an array to hold all discovered IP addresses
		$ip_addresses = [];
		// Initialize a variable to hold the rightmost IP address
		$rightmost_ip = null;

		// Define an array of headers to check for possible client IP addresses
		$headers_to_check = array(
			'REMOTE_ADDR',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'HTTP_CF_CONNECTING_IP',
			'HTTP_FASTLY_CLIENT_IP',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_X_REAL_IP',
			'True-Client-IP',
		);

		// Loop through each header
		foreach ( $headers_to_check as $header ) {
			// If the header exists in the $_SERVER array
			if ( isset( $_SERVER[ $header ] ) ) {
				// Remove all spaces from the header value and explode it by comma
				// to get a list of IP addresses
				$ips = explode( ',', str_replace( ' ', '', $_SERVER[ $header ] ) );

				// Reverse the array to process rightmost IP first, which is less likely to be spoofed
				$ips = array_reverse($ips);

				// Loop through each IP address in the list
				foreach ( $ips as $ip ) {
					$ip = trim( $ip );

					// If the IP address is valid and does not belong to a private or reserved range
					if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
						// Add the IP address to the array
						$ip_addresses[] = $ip;

						// If we haven't stored a rightmost IP yet, store this one
						if ($rightmost_ip === null) {
							$rightmost_ip = $ip;
						}
					}
				}
			}
		}

		// If we found a rightmost IP address
		if ( $rightmost_ip !== null ) {
			// Get all keys in the IP addresses array that match the rightmost IP
			$rightmost_ip_keys = array_keys( $ip_addresses, $rightmost_ip );

			// Loop through each key
			foreach ( $rightmost_ip_keys as $key ) {
				// If this is not the first instance of the rightmost IP
				if ( $key > 0 ) {
					// Remove this instance from the array
					unset( $ip_addresses[ $key ] );
				}
			}
		}

		// Remove duplicate IP addresses from the array and reindex the array
		return array_values( array_unique( $ip_addresses ) );
	}

	public function check_request(){
		$ips = $this->get_ip_address();
		$status = $this->check_ip_address( $ips );
		if ( $status === 'blocked' ) {
			exit();
		}
		
	}

	/**
	 * Processes an IP or range and calls the appropriate function.
	 *
	 * This function determines whether the provided input is an IP address or an IP range,
	 * and then calls the appropriate function accordingly.
	 *
	 * @param $ip_addresses
	 *
	 * @return array Returns a status representing the check result: 'allowed' for allowlist hit, 'blocked' for blocklist hit, 'not found' for no hits.
	 */
	public function check_ip_address( $ip_addresses ): array {

		$results = [];
		$found_blocked_ip = false;
		foreach ( $ip_addresses as $ip ) {
			// Remove any white space around the input
			$item = trim( $ip );
			// Validate the input to determine whether it's an IP or a range
			if ( filter_var( $item, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 ) ) {
				// It's a valid IP address
				$results[ $item ] = $this->check_against_ips( [ $item ] );

				$status = $this->get_ip_status([$item]);
				if ( $status === 'allowed' ) {
					return 'allowed';
				} else if ($status === 'blocked') {
					$found_blocked_ip = true;
				}
			}

			if ( strpos( $item, '/' ) !== false ) {
				// It's a range, but we need to make sure it's in CIDR notation
				[ $subnet, $bits ] = explode( '/', $item );
				if ( is_numeric( $bits ) && $bits >= 0 && $bits <= 128 && filter_var( $subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 ) ) {
					// It's a valid range in CIDR notation
					$results[ $item ] = $this->check_against_ranges( [ $item ] );
					$status = $this->get_ip_range_status([$item]);
					if ( $status === 'allowed' ) {
						return 'allowed';
					} else if ($status === 'blocked') {
						$found_blocked_ip = true;
					}
				}
			} else {
				continue;
				// The input was not a valid IP or a valid range in CIDR notation
			}
		}

		if ($found_blocked_ip) {
			return 'blocked';
		}
		return 'none';

		return $results;
	}

	/**
	 * Checks if a given IP address is within a specified IP range.
	 *
	 * This function supports both IPv4 and IPv6 addresses, and can handle ranges in
	 * both standard notation (e.g. "192.0.2.0") and CIDR notation (e.g. "192.0.2.0/24").
	 *
	 * In CIDR notation, the function uses a bitmask to check if the IP address falls within
	 * the range. For IPv4 addresses, it uses the `ip2long()` function to convert the IP
	 * address and subnet to their integer representations, and then uses the bitmask to
	 * compare them. For IPv6 addresses, it uses the `inet_pton()` function to convert the IP
	 * address and subnet to their binary representations, and uses a similar bitmask approach.
	 *
	 * If the range is not in CIDR notation, it simply checks if the IP equals the range.
	 *
	 * @param string $ip The IP address to check.
	 * @param string $range The range to check the IP address against.
	 *
	 * @return bool True if the IP address is within the range, false otherwise.
	 */
	public function ip_in_range( string $ip, string $range ): bool {
		// Check if the IP address is properly formatted
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 ) ) {
			throw new InvalidArgumentException( 'Invalid IP address format.' );
		}
		// Check if the range is in CIDR notation
		if ( strpos( $range, '/' ) !== false ) {
			// The range is in CIDR notation, so we split it into the subnet and the bit count
			[ $subnet, $bits ] = explode( '/', $range );

			if ( ! is_numeric( $bits ) || $bits < 0 || $bits > 128 ) {
				throw new InvalidArgumentException( 'Invalid range format. The bit count in CIDR notation must be a number between 0 and 128.' );
			}

			// Check if the subnet is a valid IPv4 address
			if ( filter_var( $subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
				// Convert the IP address and subnet to their integer representations
				$ip     = ip2long( $ip );
				$subnet = ip2long( $subnet );

				// Create a mask based on the number of bits
				$mask = - 1 << ( 32 - $bits );

				// Apply the mask to the subnet
				$subnet &= $mask;

				// Compare the masked IP address and subnet
				return ( $ip & $mask ) === $subnet;
			}

			// Check if the subnet is a valid IPv6 address
			if ( filter_var( $subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
				// Convert the IP address and subnet to their binary representations
				$ip     = inet_pton( $ip );
				$subnet = inet_pton( $subnet );
				// Divide the number of bits by 8 to find the number of full bytes
				$full_bytes = floor( $bits / 8 );
				// Find the number of remaining bits after the full bytes
				$partial_byte = $bits % 8;
				// Initialize the mask
				$mask = '';
				// Add the full bytes to the mask, each byte being "\xff" (255 in binary)
				$mask .= str_repeat( "\xff", $full_bytes );
				// If there are any remaining bits...
				if ( $partial_byte !== 0 ) {
					// Add a byte to the mask with the correct number of 1 bits
					// First, create a string with the correct number of 1s
					// Then, pad the string to 8 bits with 0s
					// Convert the binary string to a decimal number
					// Convert the decimal number to a character and add it to the mask
					$mask .= chr( bindec( str_pad( str_repeat( '1', $partial_byte ), 8, '0' ) ) );
				}

				// Fill in the rest of the mask with "\x00" (0 in binary)
				// The total length of the mask should be 16 bytes, so subtract the number of bytes already added
				// If we added a partial byte, we need to subtract 1 more from the number of bytes to add
				$mask .= str_repeat( "\x00", 16 - $full_bytes - ( $partial_byte != 0 ? 1 : 0 ) );

				// Compare the masked IP address and subnet
				return ( $ip & $mask ) === $subnet;
			}

			// The subnet was not a valid IP address
			throw new InvalidArgumentException( 'Invalid range format. The subnet in CIDR notation must be a valid IP address.' );
		}

		if ( ! filter_var( $range, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 ) ) {
			// The range was not in CIDR notation and was not a valid IP address
			throw new InvalidArgumentException( 'Invalid range format. If not in CIDR notation, the range must be a valid IP address.' );
		}

		// The range is not in CIDR notation, so we simply check if the IP equals the range
		return $ip === $range;
	}

	/**
	 * Checks a list of IP addresses against allowlist and blocklist.
	 *
	 * This function fetches explicit IP addresses from the database tables and checks if the supplied IPs are in the allowlist or blocklist.
	 * If an IP is found in the allowlist or blocklist, it is stored in the corresponding database table and a status is returned.
	 *
	 * @param array $ip_addresses The list of IP addresses to check.
	 *
	 * @return string|null Status representing the check result: 'allowed' for allowlist hit, 'blocked' for blocklist hit, 'not found' for no hits.
	 */
	public function check_against_ips( array $ip_addresses ): string {

		global $wpdb;

		$cache_key_allowlist = 'rsssl_allowlist_ips';
		$cache_key_blocklist = 'rsssl_blocklist_ips';

		// Try to get the lists from cache
		$allowlist_ips = wp_cache_get( $cache_key_allowlist );
		$blocklist_ips = wp_cache_get( $cache_key_blocklist );

		// If not cached, fetch from the database and then cache
		if ($allowlist_ips === false) {
			$allowlist_ips = $wpdb->get_col("SELECT ip_or_range FROM {$wpdb->prefix}rsssl_allowlist WHERE ip_or_range NOT LIKE '%/%'");
			wp_cache_set($cache_key_allowlist, $allowlist_ips);
		}

		if ($blocklist_ips === false) {
			$blocklist_ips = $wpdb->get_col("SELECT ip_or_range FROM {$wpdb->prefix}rsssl_blocklist WHERE ip_or_range NOT LIKE '%/%'");
			wp_cache_set($cache_key_blocklist, $blocklist_ips);
		}

		// Check the IP addresses
		foreach ( $ip_addresses as $ip ) {
			if ( in_array( $ip, $allowlist_ips, true ) ) {
				return 'allowed';
			}
			if ( in_array( $ip, $blocklist_ips, true ) ) {
				return 'blocked';
			}
		}

		return 'not_found';
	}

	/**
	 * Checks a list of IP addresses against allowlist and blocklist ranges.
	 *
	 * This function fetches IP ranges from the database tables and checks if the supplied IPs are within the allowlist or blocklist ranges.
	 * If an IP is found in the allowlist or blocklist range, it is stored in the corresponding database table and a status is returned.
	 *
	 * @param array $ip_addresses The list of IP addresses to check.
	 *
	 * @return string|null Status representing the check result: 'allowed' for allowlist hit, 'blocked' for blocklist hit, 'not found' for no hits.
	 */
	public function check_against_ranges( array $ip_addresses ): string {

		global $wpdb;

		$cache_key_allowlist_ranges = 'rsssl_allowlist_ranges';
		$cache_key_blocklist_ranges = 'rsssl_blocklist_ranges';

		// Try to get the lists from cache
		$allowlist_ranges = wp_cache_get( $cache_key_allowlist_ranges );
		$blocklist_ranges = wp_cache_get( $cache_key_blocklist_ranges );

		// If not cached, fetch from the database and then cache
		if ($allowlist_ranges === false) {
			$allowlist_ranges = $wpdb->get_col("SELECT ip_or_range FROM {$wpdb->prefix}rsssl_allowlist WHERE ip_or_range LIKE '%/%'");
			wp_cache_set($cache_key_allowlist_ranges, $allowlist_ranges);
		}

		if ($blocklist_ranges === false) {
			$blocklist_ranges = $wpdb->get_col("SELECT ip_or_range FROM {$wpdb->prefix}rsssl_blocklist WHERE ip_or_range LIKE '%/%'");
			wp_cache_set($cache_key_blocklist_ranges, $blocklist_ranges);
		}

		// Check the IP addresses
		foreach ( $ip_addresses as $ip ) {
			foreach ( $allowlist_ranges as $range ) {
				if ( $this->ip_in_range( $ip, $range ) ) {
					return 'allowed';
				}
			}
			foreach ( $blocklist_ranges as $range ) {
				if ( $this->ip_in_range( $ip, $range ) ) {
					return 'blocked';
				}
			}
		}

		return 'not_found';
	}

	/**
	 * Adds an IP address to the allowlist.
	 *
	 * @param string $ip The IP address to add.
	 */
	public function add_to_allowlist( string $ip ): void {

		if ( ! rsssl_user_can_manage() ) {
			return;
		}

		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'rsssl_allowlist',
			[ 'ip_or_range' => $ip ],
			[ '%s ']
		);

		$this->invalidate_cache( 'rsssl_allowlist',  $ip );
	}

	/**
	 * Adds an IP address to the blocklist.
	 *
	 * @param string $ip The IP address to add.
	 */
	public function add_to_blocklist( string $ip ): void {

		if ( ! rsssl_user_can_manage() ) {
			return;
		}

		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'rsssl_blocklist',
			[ 'ip_or_range' => $ip ],
			[ '%s ']
		);

		// Invalidate the blocklist cache
		$this->invalidate_cache( 'rsssl_blocklist',  $ip );
	}

	/**
	 * Removes an IP address from the allowlist.
	 *
	 * @param string $ip The IP address to remove.
	 */
	public function remove_from_allowlist( string $ip ): void {

		if ( ! rsssl_user_can_manage() ) {
			return;
		}

		global $wpdb;

		$wpdb->delete(
			$wpdb->prefix . 'rsssl_allowlist',
			[ 'ip_or_range' => $ip ],
			[ '%s ']
		);

		// Invalidate the allowlist cache
		$this->invalidate_cache( 'rsssl_allowlist',  $ip );
	}

	/**
	 * Removes an IP address from the blocklist.
	 *
	 * @param string $ip The IP address to remove.
	 */
	public function remove_from_blocklist( string $ip ): void {

		if ( ! rsssl_user_can_manage() ) {
			return;
		}

		global $wpdb;

		$wpdb->delete(
			$wpdb->prefix . 'rsssl_blocklist',
			[ 'ip_or_range' => $ip ],
			[ '%s ']
		);

		// Invalidate the blocklist cache
		$this->invalidate_cache( 'rsssl_blocklist',  $ip );
	}

	/**
	 * Invalidates the cache for the specified table and IP address.
	 *
	 * This function clears the cache for the allowlist or blocklist based on the provided table and IP address.
	 * If the IP address is a range, it clears the cache for the corresponding range cache key. Otherwise, it clears
	 * the cache for the corresponding IP cache key.
	 *
	 * @param string $table The table name ('rsssl_allowlist' or 'rsssl_blocklist').
	 * @param string $ip The IP address or range.
	 *
	 * @return void
	 */
	public function invalidate_cache( $table, $ip ): void {

		if ( $table === 'rsssl_allowlist' ) {
			// Check if range or IP
			if ( strpos( $ip, '/' ) !== false ) {
				wp_cache_delete( 'rsssl_allowlist_ranges' );
			} else {
				wp_cache_delete( 'rsssl_allowlist_ips' );
			}
		}

		if ( $table === 'rsssl_blocklist' ) {
			if ( strpos( $ip, '/' ) !== false ) {
				wp_cache_delete( 'rsssl_blocklist_ranges' );
			} else {
				wp_cache_delete( 'rsssl_blocklist_ips' );
			}
		}
	}

}

new Rsssl_Limit_Login_Attempts();