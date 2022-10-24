<?php
defined('ABSPATH') or die();
/**
 * File to prevent fatal errors when used with older pro versions
 */
if ( is_admin() ) {
	class really_simple_ssl_legacy{
		public $site_has_ssl;
		public $ssl_enabled;
		public function generate_enable_link(){}
		public function find_wp_config_path(){}
		public function contains_hsts(){}
	}
	class rsssl_help_legacy {
		public function get_help_tip(){}
	}
	add_action('plugins_loaded', 'rsssl_compatibility_mode');
	function rsssl_compatibility_mode(){
		if ( defined("rsssl_pro_version") && version_compare(rsssl_pro_version, '6.0','<') ) {
			REALLY_SIMPLE_SSL::instance()->really_simple_ssl = new really_simple_ssl_legacy();
			REALLY_SIMPLE_SSL::instance()->rsssl_help        = new rsssl_help_legacy();
		}
	}
}