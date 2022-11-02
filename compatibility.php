<?php
defined('ABSPATH') or die();
/**
 * File to prevent fatal errors when used with older pro versions
 * @deprecated
 */
if ( is_admin() && rsssl_user_can_manage() ) {
	class really_simple_ssl_legacy{
		public $site_has_ssl;
		public $ssl_enabled;
		public function generate_enable_link(){}
		public function find_wp_config_path(){return '-';}
		public function contains_hsts(){}
		public function get_recommended_security_headers(){return [];}
		public function notice_html(){}
	}
	class rsssl_help_legacy {
		public function get_help_tip(){}
	}
	class rsssl_multisite_legacy {
		public $ssl_enabled_networkwide;
		public $mixed_content_admin;
		public $selected_networkwide_or_per_site;
		public function plugin_network_wide_active(){
			return false;
		}
	}

	add_action('plugins_loaded', 'rsssl_compatibility_mode', 9);
	function rsssl_compatibility_mode() {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		$plugin_data = false;
		$ms_file     = WP_CONTENT_DIR . '/plugins/really-simple-ssl-pro-multisite/really-simple-ssl-pro-multisite.php';
		$pro_file    = WP_CONTENT_DIR . '/plugins/really-simple-ssl-pro/really-simple-ssl-pro.php';
		if ( file_exists( $ms_file ) && is_plugin_active('really-simple-ssl-pro-multisite/really-simple-ssl-pro-multisite.php') ) {
			$plugin_data = get_plugin_data( $ms_file );
		} else if ( file_exists( $pro_file ) && is_plugin_active('really-simple-ssl-pro/really-simple-ssl-pro.php')) {
			$plugin_data = get_plugin_data( $pro_file );
		}

		if ( $plugin_data ) {
			$version = $plugin_data['Version'] ?? false;
			if ( version_compare( $version, '6.0', '<' ) ) {
				REALLY_SIMPLE_SSL::instance()->really_simple_ssl = new really_simple_ssl_legacy();
				REALLY_SIMPLE_SSL::instance()->rsssl_help        = new rsssl_help_legacy();
				REALLY_SIMPLE_SSL::instance()->rsssl_multisite   = new rsssl_multisite_legacy();
			}
		}

	}
}