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
			if (is_multisite() && is_super_admin() ){
				$url = add_query_arg(array('page' => 'really-simple-ssl'), network_admin_url('settings.php'));
			} else {
				$url = add_query_arg(array('page' => 'rlrsssl_really_simple_ssl'), admin_url("options-general.php") );
			}

			if (!RSSSL()->really_simple_ssl->ssl_enabled) {
				if ( RSSSL()->really_simple_ssl->site_has_ssl ) {
					$result['status']      = 'recommended';
					$result['label']       = __( 'SSL is not enabled.', 'really-simple-ssl' );
					$result['description'] = sprintf(
						'<p>%s</p>',
						__( 'Really Simple SSL detected an SSL certificate, but has not been configured to enforce SSL.',
							'really-simple-ssl' )
					);
					$result['actions']     .= sprintf(
						'<p><a href="%s">%s</a></p>',
						 $url ,
						__( 'Activate SSL', 'really-simple-ssl' )
					);
				} else {
					$result['status']      = 'recommended';
					$result['label']       = __( 'No SSL detected.' , 'really-simple-ssl' );
					$result['description'] = sprintf(
						'<p>%s</p>',
						__( 'Really Simple SSL is installed, but no valid SSL certificate is detected.', 'really-simple-ssl' )
					);
				}
			} else {
				if (!RSSSL()->really_simple_ssl->has_301_redirect()) {
					$result['status']      = 'recommended';
					$result['label']       = __( 'No 301 redirect to SSL not enabled.' , 'really-simple-ssl' );
					$result['description'] = sprintf(
						'<p>%s</p>',
						__( 'To ensure all traffic passes through SSL, please enable a 301 redirect.', 'really-simple-ssl' )
					);
					$result['actions']     .= sprintf(
						'<p><a href="%s">%s</a></p>',
						$url,
						__( 'Enable 301 redirect', 'really-simple-ssl' )
					);
				} else if ( !is_multisite() && RSSSL()->rsssl_server->uses_htaccess() && !RSSSL()->really_simple_ssl->htaccess_redirect) {
					$result['status']      = 'recommended';
					$result['label']       = __( '301 .htaccess redirect is not enabled.' , 'really-simple-ssl' );
					$result['description'] = sprintf(
						'<p>%s</p>',
						__( 'The 301 .htaccess redirect is the fastest and most reliable redirect option.', 'really-simple-ssl' )
					);
					$result['actions']     .= sprintf(
						'<p><a href="%s">%s</a></p>',
						$url,
						__( 'Enable 301 .htaccess redirect', 'really-simple-ssl' )
					);
				} else {
					$result = array(
						'label'       => __( '301 SSL redirect enabled', 'really-simple-ssl' ),
						'status'      => 'good',
						'description' => sprintf(
							'<p>%s</p>',
							__( 'You have set a 301 redirect to SSL. This is important for SEO purposes', 'really-simple-ssl' )
						),
						'actions'     => '',
					);
				}
			}

			if (isset($result['status'])) {
				$result['badge'] = array(
					'label' => __( 'SSL' ),
					'color' => 'blue',
				);
				$result['test']= 'really-simple-ssl';
			}
			return $result;

		}
	}
}