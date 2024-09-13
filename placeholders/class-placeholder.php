<?php
defined( 'ABSPATH' ) or die( "" );
if ( ! class_exists( 'rsssl_placeholder' ) ) {
	class rsssl_placeholder {
		private static $_this;

		public function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die();
			}

			add_filter( "rsssl_run_test", array( $this, 'mixed_content_scan' ), 9, 3 );
			add_filter( 'rsssl_do_action', array( $this, 'learningmode_table_data' ), 10, 3 );

//			add_filter( 'rsssl_do_action', array( $this, 'two_factor_users_data' ), 11, 3 );

                // really-simple-ssl-pro plugin is active
            add_filter( 'rsssl_do_action', array( $this, 'limit_login_attempts_data' ), 11, 3 );

			self::$_this = $this;

		}

		/**
		 * Catch rest api request
		 *
		 * @param $response
		 * @param $test
		 * @param $data
		 *
		 * @return mixed
		 */

		public function mixed_content_scan( $response, $test, $data ) {
			if ( $test === 'mixed_content_scan' ) {
				$response = $this->mixed_content_data();
			}

			return $response;
		}

		/**
		 * @param array  $response
		 * @param string $action
		 * @param array  $data
		 *
		 * @return array
		 */
		public function learningmode_table_data( array $response, string $action, $data ): array {
			if ( ! rsssl_user_can_manage() ) {
				return $response;
			}

			if ( $action === 'learning_mode_data' ) {
				if ( isset( $data['type'] ) && $data['type'] === 'content_security_policy') {
					return $this->csp_data();
				}
				if ( isset( $data['type'] ) && $data['type'] === 'xmlrpc_allow_list') {
					return $this->xml_data();
				}
			}
			return $response;
		}

		/**
		 * Set some placeholder data for CSP
		 *
		 * @return array
		 */
		public function csp_data() {
			$rules = array(
				'script-src-data'  => array(
					'violateddirective' => 'script-src',
					'blockeduri'        => 'data:',
				),
				'script-src-eval'  => array(
					'violateddirective' => 'script-src',
					'blockeduri'        => 'unsafe-eval',
				),
				'img-src-gravatar' => array(
					'violateddirective' => 'img-src',
					'blockeduri'        => 'https://secure.gravatar.com',
				),
				'img-src-data'     => array(
					'violateddirective' => 'img-src',
					'blockeduri'        => 'data:',
				),
				'img-src-self'     => array(
					'violateddirective' => 'img-src',
					'blockeduri'        => 'self',
				),
				'font-src-self'    => array(
					'violateddirective' => 'font-src',
					'blockeduri'        => 'self',
				),
				'font-src-data'    => array(
					'violateddirective' => 'font-src',
					'blockeduri'        => 'data:',
				),
			);

			$output = [];
			foreach ( $rules as $rule ) {
				$output[] = [
					'documenturi'       => site_url(),
					'violateddirective' => $rule['violateddirective'],
					'blockeduri'        => $rule['blockeduri'],
					'status'            => 0,
				];
			}

			return $output;
		}

        public function xml_data() {
			$data = [
				[
					'id'           => 1,
					'method'       => 'wp.deletePost',
					'login_status' => 1,
					'count'        => 63,
					'status'       => 1,
				],
				[
					'id'           => 2,
					'method'       => 'wp.getPost',
					'login_status' => 1,
					'count'        => 78,
					'status'       => 1,
				],
				[
					'id'           => 3,
					'method'       => 'wp.editTerm',
					'login_status' => 1,
					'count'        => 9,
					'status'       => 1,
				],
				[
					'id'           => 4,
					'method'       => 'wp.getPosts',
					'login_status' => 1,
					'count'        => 9,
					'status'       => 1,
				],
			];

			return $data;
		}

        public function demo_vulnerabilities_data() {
            $data[] = [
                'id'          => 1,
                'component'   => 'wordpress',
                'risk'        => 'high',
                'date'        => '2020-01-01',

                ];
        }

        public function limit_login_attempts_data( array $response, string $action, $data ): array
        {
            if ( ! rsssl_user_can_manage() ) {
                return $response;
            }
	        if ( defined('rsssl_pro')) {
		        return $response;
	        }

            switch ( $action ) {
                case 'ip_list':
                    $response['data'] = [
                        [
                            'id' => 12,
                            'first_failed' => 1678903200,
                            'last_failed' => 1678924800,
                            'attempt_type' => 'source_ip',
                            'attempt_value' => '192.168.1.12',
                            'user_agent' => 'Mozilla/5.0',
                            'status' => 'locked',
                            'attempts' => 2,
                            'endpoint' => 'https://example.com/wp-admin',
                            'blocked' => 0,
                            'datetime' => '10:51, Sep 30',
                        ],
                        [
                            'id' => 13,
                            'first_failed' => 1678906800,
                            'last_failed' => 1678928400,
                            'attempt_type' => 'source_ip',
                            'attempt_value' => '192.168.1.13',
                            'user_agent' => 'Mozilla/5.0',
                            'status' => 'locked',
                            'attempts' => 1,
                            'endpoint' => 'https://example.com/wp-login.php',
                            'blocked' => 1,
                            'datetime' => '10:51, Sep 30',
                        ],
                    ];
                    break;
                case 'user_list':
                    $response['data'] = [
                        [
                            'id' => 1,
                            'first_failed' => 1678888800,
                            'last_failed' => 1678910400,
                            'attempt_type' => 'username',
                            'attempt_value' => 'john_doe',
                            'user_agent' => 'Mozilla/5.0',
                            'status' => 'locked',
                            'attempts' => 5,
                            'endpoint' => 'https://example.com/wp-admin',
                            'blocked' => 1,
                            'datetime' => '10:51, Sep 30',
                        ],
                        [
                            'id' => 2,
                            'first_failed' => 1678892400,
                            'last_failed' => 1678914000,
                            'attempt_type' => 'username',
                            'attempt_value' => 'john_doe2',
                            'user_agent' => 'Mozilla/5.0',
                            'status' => 'locked',
                            'attempts' => 3,
                            'endpoint' => 'https://example.com/wp-login.php',
                            'blocked' => 1,
                            'datetime' => '10:51, Sep 30',
                        ],
                    ];
                    break;
                case 'country_list':
                    $response['data'] = [
                        [
                            'id' => 1,
                            'first_failed' => 1678888800,
                            'last_failed' => 1678910400,
                            'attempt_type' => 'country',
                            'attempt_value' => 'US',
                            'country_name' => 'United States',
                            'region' => 'North America',
                            'user_agent' => 'Mozilla/5.0',
                            'status' => 'blocked',
                            'attempts' => 5,
                            'endpoint' => 'https://example.com/wp-admin',
                            'blocked' => 1,
                            'datetime' => '10:51, Sep 30',
                        ],
                        [
                            'id' => 2,
                            'first_failed' => 1678892400,
                            'last_failed' => 1678914000,
                            'attempt_type' => 'country',
                            'attempt_value' => 'US',
                            'country_name' => 'United States',
                            'region' => 'North America',
                            'user_agent' => 'Mozilla/5.0',
                            'status' => 'blocked',
                            'attempts' => 3,
                            'endpoint' => 'https://example.com/wp-login.php',
                            'blocked' => 1,
                            'datetime' => '10:51, Sep 30',
                        ],
                    ];
                    break;
                case 'event_log':
                    $response['data'] = [
                        [
                            'id' => 969,
                            'timestamp' => 1693565480,
                            'event_id' => 1026,
                            'event_type' => 'login-protection',
                            'iso2_code' => 'PW',
                            'country_name' => 'Palau',
                            'severity' => 'informational',
                            'username' => '',
                            'source_ip' => '',
                            'description' => 'Country Palau added to geo-ip blocklist (Login-protection)',
                            'datetime' => '10:51, Sep 30',
                        ],
                        [
                            'id' => 970,
                            'timestamp' => 1693565480,
                            'event_id' => 1026,
                            'event_type' => 'login-protection',
                            'iso2_code' => 'PG',
                            'country_name' => 'Papua New Guinea',
                            'severity' => 'informational',
                            'username' => '',
                            'source_ip' => '',
                            'description' => 'Country Papua New Guinea added to geo-ip blocklist (Login-protection)',
                            'datetime' => '10:51, Sep 30',
                        ],
                        [
                            'id' => 994,
                            'timestamp' => 1693573989,
                            'event_id' => 1000,
                            'event_type' => 'authentication',
                            'iso2_code' => 'NL',
                            'country_name' => 'Netherlands',
                            'severity' => 'informational',
                            'username' => 'johndoe',
                            'source_ip' => '192.168.1.1',
                            'description' => 'Login successful (Authentication)',
                            'datetime' => '10:51, Sep 30',
                        ],
                    ];
                default:
                    break;
            }

            $response['pagination'] =  [
                'total' => 2,
                'per_page' => 10,
                'current_page' => 1,
                'last_page' => 1,
                'from' => 1,
                'to' => 4,
            ];

            return $response;
        }

		public function mixed_content_data() {
			$data[] = [
				'id'          => 1,
				'ignored'     => false,
				'type'        => 'blocked_url',
				'description' => sprintf( __( "Mixed content in PHP file in %s", "really-simple-ssl" ), 'themes' ),
				'blocked_url' => '#',
				'location'    => site_url(),
				'details'     => [
					'title'       => __( "Details", "really-simple-ssl" ),
					'description' => [],
					'view'        => '#',
					'edit'        => '#',
					'help'        => "knowledge-base/fix-blocked-resources-content-files",
					'action'      => 'ignore_url',
				],
			];

			$data[] = [
				'id'          => 2,
				'ignored'     => false,
				'description' => sprintf( __( "Mixed content in %s", "really-simple-ssl" ), 'Theme file' ),
				'type'        => 'css_js_thirdparty',
				'blocked_url' => '#',
				'location'    => site_url(),
				'details'     => [
					'title'       => __( "Details", "really-simple-ssl" ),
					'description' => [],
					'view'        => '#',
					'edit'        => '#',
					'help'        => "knowledge-base/fix-css-and-js-files-with-mixed-content",
					'action'      => 'ignore_url',
				],
				'fix'         => [
					'title'       => __( "Import and insert file", "really-simple-ssl" ),
					'subtitle'    => __( "Copyright warning!", "really-simple-ssl" ),
					'description' => '',
					'action'      => "fix_file",
					'path'        => '#',
				]
			];

			$data[] = [
				'id'          => 3,
				'ignored'     => false,
				'type'        => 'css_js_other_domains',
				'description' => __( "Mixed content in CSS/JS file from other domain", "really-simple-ssl" ),
				'blocked_url' => '#',
				'location'    => site_url(),
				'details'     => [
					'title'       => __( "Details", "really-simple-ssl" ),
					'description' => [],
					'view'        => '',
					'edit'        => '',
					'help'        => "knowledge-base/fix-css-js-files-mixed-content-domains/",
					'action'      => 'ignore_url',
				]
			];

			$data[] = [
				'id'          => 4,
				'ignored'     => false,
				'type'        => 'posts',
				'description' => sprintf(__( "Mixed content in post: %s", "really-simple-ssl" ), 'Hello World'),
				'blocked_url' => '#',
				'location'    => site_url(),
				'details'     => [
					'title'       => __( "Details", "really-simple-ssl" ),
					'description' => [],
					'view'        => '',
					'edit'        => get_admin_url( null, 'post.php?post=1&action=edit' ),
					'help'        => "fix-posts-with-blocked-resources-domains-without-ssl-certificate/",
					'action'      => 'ignore_url'
				],
				'fix'         => [
					'title'       => __( "Import and insert file", "really-simple-ssl" ),
					'subtitle'    => __( "Copyright warning!", "really-simple-ssl" ),
					'description' => '',
					'action'      => 'fix_post',
					'post_id'     => 1,
				]
			];

			//check if item is coming from an iframe
			$data[] = [
				'id'          => 5,
				'ignored'     => false,
				'type'        => 'postmeta',
				'description' => __( "Mixed content in the postmeta table", "really-simple-ssl" ),
				'blocked_url' => '#',
				'location'    => site_url(),
				'meta_key'    => '',
				'details'     => [
					'title'       => __( "Details", "really-simple-ssl" ),
					'description' => [],
					'view'        => '#',
					'edit'        => get_admin_url( null, 'post.php?post=1&action=edit' ),
					'help'        => "knowledge-base/fix-blocked-resources-content-postmeta",
					'action'      => 'ignore_url'
				],
				'fix'         => [
					'title'       => __( "Import and insert file", "really-simple-ssl" ),
					'subtitle'    => __( "Copyright warning!", "really-simple-ssl" ),
					'description' => '',
					'action'      => 'fix_postmeta',
					'post_id'     => 1,
				]
			];

			$file   = sprintf( __( "Widget area", "really-simple-ssl" ), '' );
			$data[] = [
				'id'          => 5,
				'ignored'     => false,
				'type'        => 'widgets',
				'description' => __( "Widget with mixed content", "really-simple-ssl" ),
				'blocked_url' => '#',
				'location'    => $file,
				'details'     => [
					'title'       => __( "Details", "really-simple-ssl" ),
					'description' => [],
					'view'        => '',
					'edit'        => get_admin_url( null, '/widgets.php' ),
					'help'        => "knowledge-base/locating-mixed-content-in-widgets/",
					'action'      => 'ignore_url'
				],
				'fix'         => [
					'title'       => __( "Import and insert file", "really-simple-ssl" ),
					'subtitle'    => __( "Copyright warning!", "really-simple-ssl" ),
					'description' => '',
					'action'      => 'fix_widget',
					'widget_id'   => '#',
				]
			];

			return [ 'data' => $data, 'progress' => 80, 'state' => 'stop', 'action' => '', 'nonce' => wp_create_nonce( 'fix_mixed_content' ) ];
		}

		/**
		 * @return void
		 *
		 * Dummy data for two factor Email block
		 */
		public function two_factor_email_data() {

		}


		/**
		 * @return array
		 *
		 * Dummy data for two factor Users block
		 */
		public function two_factor_users_data( array $response, string $action, $data ) {
			if ( defined('rsssl_pro')) {
				return $response;
			}

			if ( $action === 'two_fa_table' ) {

				$response['data'] = [
					[
						'id'                  => 1,
						'user'                => 'JaneDoe',
						'rsssl_two_fa_providers' => 'email',
						'user_role'           => 'Administrator',
						'status_for_user'     => 'active'
					],
					[
						'id'                  => 2,
						'user'                => 'JohnDoe',
						'rsssl_two_fa_providers' => 'email',
						'user_role'           => 'Editor',
						'status_for_user'     => 'open'
					],
					[
						'id'                  => 3,
						'user'                => 'JanieDoe',
						'rsssl_two_fa_providers' => 'disabled',
						'user_role'           => 'Subscriber',
						'status_for_user'     => 'Disabled'
					],
					[
						'id'                  => 4,
						'user'                => 'JonnyDoe',
						'rsssl_two_fa_providers' => 'Active',
						'user_role'           => 'Contributor',
						'status_for_user'     => 'Active'
					],
					[
						'id'                  => 5,
						'user'                => 'BabyDoe',
						'rsssl_two_fa_providers' => 'open',
						'user_role'           => 'Author',
						'status_for_user'     => 'open'
					],
				];

			}

			return $response;

		}

	}
}
