<?php defined( 'ABSPATH' ) or die();

if ( ! class_exists( 'rsssl_site_health' ) ) {
	class rsssl_site_health {
		private static $_this;
		public function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die( 'you cannot create a second instance.' );
			}

			add_filter( 'site_status_tests', array( $this, 'health_check' ), 1, 10 );
			self::$_this = $this;
		}

		public static function this() {
			return self::$_this;
		}

		/**
		 * Add SSL dedicated health check
		 * @param array $tests
		 *
		 * @return array
		 */
		public function health_check( $tests ) {
			unset( $tests['async']['https_status'] );
			if ( ! rsssl_get_option( 'dismiss_all_notices' ) ) {
				$tests['direct']['rsssl_ssl_health'] = array(
					'label' => __( 'SSL Status Test', 'really-simple-ssl' ),
					'test'  => array( $this, 'ssl_tests' ),
				);

				$tests['direct']['headers_test'] = array(
					'label' => __( 'Security Headers Test', 'really-simple-ssl' ),
					'test'  => array( $this, 'headers_test' ),
				);

				unset( $tests['direct']['debug_enabled'] );
				if ( rsssl_is_debugging_enabled() && rsssl_debug_log_value_is_default() ) {
					$tests['direct']['rsssl_debug_log'] = array(
						'test' => array( $this, 'site_health_debug_log_test' ),
					);
				}

				if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY ) {
					$tests['direct']['rsssl_debug_display'] = array(
						'test' => array( $this, 'site_health_debug_display_test' ),
					);
				}

				if ( rsssl_get_option( 'enable_vulnerability_scanner' ) ) {
					$vulnerabilities                          = new rsssl_vulnerabilities();
					$tests['direct']['rsssl_vulnerabilities'] = array(
						'test' => [ $vulnerabilities, 'get_site_health_notice' ],
					);
				}
			}

			return $tests;
		}

		/**
		 * Generate the WP_DEBUG notice
		 *
		 */
		public function site_health_debug_log_test() {
			$result = array(
				'label'       => __( 'Your site is set to log errors to a potentially public file' ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
				'status'      => 'critical',
				'badge'       => array(
					'label' => __( 'Security' ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
					'color' => 'blue',
				),
				'description' => sprintf(
					'<p>%s</p>',
					__( 'The value, WP_DEBUG_LOG, has been added to this website’s configuration file. This means any errors on the site will be written to a file which is potentially available to all users.', 'really-simple-ssl' )
				),
				'actions'     => sprintf(
					'<p><a href="%s" target="_blank" rel="noopener noreferrer">%s <span class="screen-reader-text">%s</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a></p>',
					/* translators: Documentation explaining debugging in WordPress. */
					esc_url( add_query_arg( array( 'page' => 'really-simple-security#settings/hardening' ), rsssl_admin_url() ) ),
					__( 'Remove from public location with Really Simple SSL', 'really-simple-ssl' ),
					/* translators: Accessibility text. */
					__( '(opens in a new tab)' )// phpcs:ignore WordPress.WP.I18n.MissingArgDomain
				),
				'test'        => 'rsssl_debug_log',
			);

			return $result;
		}

		/**
		 * Explain users about risks of debug display
		 *
		 */
		public function site_health_debug_display_test() {
			$result = array(
				'label'       => __( 'Your site is set to display errors on your website', 'really-simple-ssl' ),
				'status'      => 'critical',
				'badge'       => array(
					'label' => __( 'Security' ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
					'color' => 'blue',
				),
				'description' => sprintf(
					'<p>%s</p>',
					__( 'The value, WP_DEBUG_DISPLAY, has either been enabled by WP_DEBUG or added to your configuration file. This will make errors display on the front end of your site.', 'really-simple-ssl' )
				),
				'actions'     => sprintf(
					'<p><a href="%s" target="_blank" rel="noopener noreferrer">%s <span class="screen-reader-text">%s</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a></p>',
					/* translators: Documentation explaining debugging in WordPress. */
					esc_url( 'https://really-simple-ssl.com/security/debug-display-enabled' ),
					__( 'Read more about security concerns with debug display enabled', 'really-simple-ssl' ),
					/* translators: Accessibility text. */
					__( '(opens in a new tab)' )// phpcs:ignore WordPress.WP.I18n.MissingArgDomain
				),
				'test'        => 'rsssl_debug_display',
			);

			return $result;
		}

		/**
		 * Test to check if the recommended security headers are present
		 * @return array
		 */

		public function headers_test() {
			$result = array(
				'label'       => __( 'Recommended security headers installed', 'really-simple-ssl' ),
				'status'      => 'good',
				'badge'       => array(
					'label' => __( 'Security' ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
					'color' => 'blue',
				),
				'description' => sprintf(
					'<p>%s</p>',
					__( 'The recommended security headers are detected on your site.', 'really-simple-ssl' )
				),
				'actions'     => '',
				'test'        => 'headers_test',
			);

			//returns empty for sites without .htaccess, or if all headers are already in use
			$recommended_headers = RSSSL()->admin->get_recommended_security_headers();
			if ( ! empty( $recommended_headers ) ) {
				$style                 = '<style>.rsssl-sec-headers-list li {list-style-type:disc;margin-left:20px;}</style>';
				$list                  = '<ul class="rsssl-sec-headers-list"><li>' . implode( '</li><li>', $recommended_headers ) . '</li></ul>';
				$result['status']      = 'recommended';
				$result['label']       = __( 'Not all recommended security headers are installed', 'really-simple-ssl' );
				$result['description'] = sprintf( '<p>%s</p>', __( 'Your website does not send all recommended security headers.', 'really-simple-ssl' ) . $style . $list );
				$result['actions']     = sprintf(
					'<p><a href="%s" target="_blank" rel="noopener noreferrer">%s</a></p>',
					'https://really-simple-ssl.com/site-health-recommended-security-headers/',
					__( 'Learn more about security headers', 'really-simple-ssl' )
				);
			}

			return $result;
		}

		/**
		 * Some basic SSL health checks
		 * @return array
		 */
		public function ssl_tests() {
			$url = add_query_arg( array( 'page' => 'really-simple-security' ), rsssl_admin_url() );

			$result = array(
				'label'       => __( '301 SSL redirect enabled', 'really-simple-ssl' ),
				'status'      => 'good',
				'badge'       => array(
					'label' => __( 'Security' ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
					'color' => 'blue',
				),
				'description' => sprintf(
					'<p>%s</p>',
					__( 'You have set a 301 redirect to SSL. This is important for SEO purposes', 'really-simple-ssl' )
				),
				'actions'     => '',
				'test'        => 'rsssl_ssl_health',
			);

			if ( ! rsssl_get_option( 'ssl_enabled' ) ) {
				if ( rsssl_get_option( 'site_has_ssl' ) ) {
					$result['status']      = 'critical';
					$result['label']       = __( 'SSL is not enabled.', 'really-simple-ssl' );
					$result['description'] = sprintf(
						'<p>%s</p>',
						__(
							'Really Simple SSL detected an SSL certificate, but has not been configured to enforce SSL.',
							'really-simple-ssl'
						)
					);
					$result['actions']    .= sprintf(
						'<p><a href="%s">%s</a></p>',
						$url,
						__( 'Activate SSL', 'really-simple-ssl' )
					);
				} else {
					$result['status']      = 'critical';
					$result['label']       = __( 'No SSL detected', 'really-simple-ssl' );
					$result['description'] = sprintf(
						'<p>%s</p>',
						__( 'Really Simple SSL is installed, but no valid SSL certificate is detected.', 'really-simple-ssl' )
					);
				}
			} else {
				if ( ! RSSSL()->admin->has_301_redirect() ) {
					$result['status']      = 'recommended';
					$result['label']       = __( 'No 301 redirect to SSL enabled.', 'really-simple-ssl' );
					$result['description'] = sprintf(
						'<p>%s</p>',
						__( 'To ensure all traffic passes through SSL, please enable a 301 redirect.', 'really-simple-ssl' )
					);
					$result['actions']    .= sprintf(
						'<p><a href="%s">%s</a></p>',
						$url,
						__( 'Enable 301 redirect', 'really-simple-ssl' )
					);
				} elseif ( RSSSL()->server->uses_htaccess() && rsssl_get_option( 'redirect' ) !== 'htaccess' ) {
					$result['status']      = 'recommended';
					$result['label']       = __( '301 .htaccess redirect is not enabled.', 'really-simple-ssl' );
					$result['description'] = sprintf(
						'<p>%s</p>',
						__( 'The 301 .htaccess redirect is the fastest and most reliable redirect option.', 'really-simple-ssl' )
					);
					$result['actions']    .= sprintf(
						'<p><a href="%s">%s</a></p>',
						$url,
						__( 'Enable 301 .htaccess redirect', 'really-simple-ssl' )
					);
				}
			}
			return $result;
		}
	}
}
