<?php defined( 'ABSPATH' ) or die();

if ( ! class_exists( 'rsssl_certificate' ) ) {
	class rsssl_certificate {

		private static $_this;
		public function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die( 'you cannot create a second instance.' );
			}
			self::$_this = $this;
		}

		public static function this() {
			return self::$_this;
		}

		/**
		 *
		 * @since 3.0
		 *
		 * Check if the certificate is valid
		 *
		 * @return bool
		 *
		 */

		public function is_valid(): bool {
			$domain = $this->get_domain();
			if ( ! $domain || ! function_exists( 'stream_context_get_params' ) ) {
				set_transient( 'rsssl_certinfo', 'no-response', DAY_IN_SECONDS );
			} else {
				$certinfo = $this->get_certinfo( $domain );
				if ( ! $certinfo ) {
					return false;
				}

				$domain_valid = $this->is_domain_valid( $certinfo, $domain );
				$date_valid   = $this->is_date_valid( $certinfo );
				if ( $domain_valid && $date_valid ) {
					return true;
				}
			}
			return false;
		}

		/**
		 * get domain
		 * @return string
		 */
		public function get_domain() {
			$domain = site_url();
			//Parse to strip off any /subfolder/
			$parse = parse_url( $domain );
			return isset( $parse['host'] ) ? $parse['host'] : false;
		}

		/**
		 *
		 * Check common name(s) and alternative name(s) on certificate and match them to the site_url ($domain)
		 *
		 * @since 3.0
		 *
		 * @access public
		 * @param $certinfo
		 * @param $domain
		 * @return bool
		 *
		 */

		public function is_domain_valid( $certinfo, $domain ): bool {
			//first check standard situation
			//Get both the common name(s) and the alternative names from the certificate
			$certificate_common_names      = isset( $certinfo['subject']['CN'] ) ? $certinfo['subject']['CN'] : '';
			$certificate_alternative_names = isset( $certinfo['extensions']['subjectAltName'] ) ? $certinfo['extensions']['subjectAltName'] : '';
			//Check if the domain is found in either the certificate common name(s) (CN) or alternative name(s) (AN)
			$pos_cn = strpos( $certificate_common_names, $domain );
			$pos_an = strpos( $certificate_alternative_names, $domain );

			//If the domain is found, return true
			if ( false !== $pos_cn || false !== $pos_an ) {
				return true;
			}

			//if nothing found, we check for wildcard
			//strip of asterisk, and check if the wildcard domain is part of current domain
			$cert_domains = array();
			if ( $this->is_wildcard() ) {
				$certificate_alternative_names = explode( ', ', $certificate_alternative_names );
				$cert_domains[]                = trim( str_replace( '*', '', $certificate_common_names ) );
				foreach ( $certificate_alternative_names as $subject_alt_name ) {
					$cert_domains[] = trim( str_replace( '*', '', $subject_alt_name ) );
				}

				foreach ( $cert_domains as $cert_domain ) {
					//If the wildcard domain is found, return true
					if ( ( strpos( $domain, $cert_domain ) !== false ) ) {
						return true;
					}
				}
			}

			return false;
		}

		/**
		 * Check if detection failed
		 * @return bool
		 */
		public function detection_failed() {
			$certinfo = get_transient( 'rsssl_certinfo' );
			if ( $certinfo && 'no-response' === $certinfo ) {
				return true;
			}

			return false;
		}

		/**
		 *
		 * Check if the date is valid by looking at the validFrom and validTo times
		 *
		 * @since 3.0
		 *
		 * @access public
		 *
		 * @return bool
		 *
		 */

		public function is_date_valid( $certinfo ) {

			//Get the start date and end date from the certificate
			$start_date   = isset( $certinfo['validFrom_time_t'] ) ? $certinfo['validFrom_time_t'] : false;
			$end_date     = isset( $certinfo['validTo_time_t'] ) ? $certinfo['validTo_time_t'] : false;
			$current_date = time();

			//Check if the current date is between the start date and end date. If so, return true
			if ( $current_date > $start_date && ( $current_date < $end_date ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Check if the certificate is valid, but about to expire.
		 * @return bool
		 */
		public function about_to_expire() {
			//if not valid, it's already expired
			if ( ! $this->is_valid() ) {
				return true;
			}

			//we have now renewed the cert info transient
			$certinfo         = get_transient( 'rsssl_certinfo' );
			$end_date         = isset( $certinfo['validTo_time_t'] ) ? $certinfo['validTo_time_t'] : false;
			$expiry_days_time = strtotime( '+' . rsssl_le_manual_generation_renewal_check . ' days' );
			if ( $expiry_days_time < $end_date ) {
				return false;
			} else {
				return true;
			}
		}

		/**
		 *
		 * Check if the certificate is a wildcard certificate
		 * Function is used in class-multisite.php to determine whether to show a notice for multisite subfolder installations without a wildcard certificate
		 *
		 * @since 3.0
		 *
		 * @access public
		 *
		 * @return bool
		 *
		 */

		public function is_wildcard() {
			$domain   = network_site_url();
			$certinfo = $this->get_certinfo( $domain );
			//Get the certificate common name
			$certificate_common_name = isset( $certinfo['subject']['CN'] ) ? $certinfo['subject']['CN'] : false;
			$subject_alt_names       = isset( $certinfo['extensions']['subjectAltName'] ) ? explode( ', ', $certinfo['extensions']['subjectAltName'] ) : false;

			//Check if the common name(s) contain an *
			if ( strpos( $certificate_common_name, '*' ) ) {
				return true;
			}

			if ( is_array( $subject_alt_names ) ) {
				foreach ( $subject_alt_names as $subject_alt_name ) {
					if ( strpos( $subject_alt_name, '*' ) !== false ) {
						return true;
					}
				}
			}
			return false;
		}

		/**
		 *
		 * Get the certificate info
		 *
		 * @since 3.0
		 * @param string $url
		 * @return string|bool
		 * @access public
		 *
		 */

		public function get_certinfo( $url ) {
			$certinfo = get_transient( 'rsssl_certinfo' );
			//if the last check resulted in a "no response", we skip this check for a day.
			if ( 'no-response' === $certinfo ) {
				return false;
			}

			if ( ! $certinfo || RSSSL()->admin->is_settings_page() ) {
				$url            = 'https://' . str_replace( array( 'https://', 'http://' ), '', $url );
				$original_parse = parse_url( $url, PHP_URL_HOST );
				if ( $original_parse ) {
					$get = stream_context_create( array( 'ssl' => array( 'capture_peer_cert' => true ) ) );
					if ( $get ) {
						set_error_handler( array( $this, 'custom_error_handling' ) );
						$read = stream_socket_client( 'ssl://' . $original_parse . ':443', $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $get );
						restore_error_handler();

						if ( ! $read ) {
							$certinfo = 'no-response';
						}

						if ( 0 === $errno && $read ) {
							$cert = stream_context_get_params( $read );
							if ( isset( $cert['options']['ssl']['peer_certificate'] ) ) {
								$certinfo = openssl_x509_parse( $cert['options']['ssl']['peer_certificate'] );
							} else {
								$certinfo = 'no-response';
							}
						}
					}
				}
				set_transient( 'rsssl_certinfo', $certinfo, DAY_IN_SECONDS );
			}
			if ( 'not-valid' === $certinfo ) {
				return false;
			}
			if ( ! empty( $certinfo ) ) {
				return $certinfo;
			}

			return false;
		}

		/**
		 * Catch errors
		 *
		 * @since 3.0
		 *
		 * @access public
		 * @param       $errno
		 * @param       $errstr
		 * @param       $errfile
		 * @param       $errline
		 * @param array $errcontext
		 *
		 * @return bool
		 */

		public function custom_error_handling( $errno, $errstr, $errfile, $errline, $errcontext = array() ) {
			return true;
		}


		//class closure
	}
}
