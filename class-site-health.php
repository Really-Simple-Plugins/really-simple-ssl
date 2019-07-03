<?php
defined('ABSPATH') or die("you do not have access to this page!");

if (!class_exists("rsssl_site_health")) {
	class rsssl_site_health {

		private static $_this;

		function __construct() {

			if ( isset( self::$_this ) ) {
				wp_die( sprintf( __( '%s is a singleton class and you cannot create a second instance.', 'really-simple-ssl' ), get_class( $this ) ) );

			}

			add_filter( 'site_status_tests', array($this, 'rsssl_hsts_check' ) );

			self::$_this = $this;
		}

		static function this() {
			return self::$_this;
		}

		public function rsssl_hsts_check( $tests ) {
			$tests['direct']['really-simple-ssl'] = array(
				'label' => __( 'Really Simple SSL HSTS test' ),
				'test'  => array($this, "rsssl_hsts_test"),
			);

			return $tests;
		}

		public function rsssl_hsts_test() {
			$result = array(
				'label'       => __( 'HTTP Strict Transport Security is enabled', 'really-simple-ssl' ),
				'status'      => 'good',
				'badge'       => array(
					'label' => __( 'Security' ),
					'color' => 'blue',
				),
				'description' => sprintf(
					'<p>%s</p>',
					__( 'HTTP Strict Transport Security helps to make your site more secure.', 'really-simple-ssl' )
				),
				'actions'     => '',
				'test'        => 'really-simple-ssl',
			);

			if (!RSSSL()->really_simple_ssl->hsts) {
				$result['status']      = 'recommended';
				$result['label']       = __( 'HTTP Strict Transport Security is not enabled.' , 'really-simple-ssl' );
				$result['description'] = sprintf(
					'<p>%s</p>',
					__( 'HTTP Strict Transport Security is not enabled. ' )
				);
				$result['actions']     .= sprintf(
					'<p><a href="%s">%s</a></p>',
					esc_url( admin_url("options-general.php?page=rlrsssl_really_simple_ssl") ),
					__( 'Enable HSTS', 'really-simple-ssl' )
				);
			}

			return $result;

		}
	}
}