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
					'help'        => "https://really-simple-ssl.com/knowledge-base/fix-blocked-resources-content-files",
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
					'help'        => "https://really-simple-ssl.com/knowledge-base/fix-css-and-js-files-with-mixed-content",
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
					'help'        => "https://really-simple-ssl.com/knowledge-base/fix-css-js-files-mixed-content-domains/",
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
					'help'        => "https://really-simple-ssl.com/fix-posts-with-blocked-resources-domains-without-ssl-certificate/",
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
					'help'        => "https://really-simple-ssl.com/knowledge-base/fix-blocked-resources-content-postmeta",
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
					'help'        => "https://really-simple-ssl.com/knowledge-base/locating-mixed-content-in-widgets/",
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


	}
}
