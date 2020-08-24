<?php
/**
 * Plugin Name: Really Simple SSL
 * Plugin URI: https://www.really-simple-ssl.com
 * Description: Lightweight plugin without any setup to make your site SSL proof
 * Version: 3.3.5
 * Text Domain: really-simple-ssl
 * Domain Path: /languages
 * Author: Really Simple Plugins
 * Author URI: https://really-simple-plugins.com
 * License: GPL2
 */
/*  Copyright 2014  Rogier Lankhorst  (email : rogier@rogierlankhorst.com)
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
defined('ABSPATH') or die("you do not have access to this page!");

class REALLY_SIMPLE_SSL
{
	private static $instance;
	public $rsssl_front_end;
	public $rsssl_mixed_content_fixer;
	public $rsssl_multisite;
	public $rsssl_cache;
	public $rsssl_server;
	public $really_simple_ssl;
	public $rsssl_help;
	public $rsssl_certificate;

	private function __construct()
	{
	}

	public static function instance()
	{
		if (!isset(self::$instance) && !(self::$instance instanceof REALLY_SIMPLE_SSL)) {
			self::$instance = new REALLY_SIMPLE_SSL;
			self::$instance->setup_constants();
			self::$instance->includes();
			self::$instance->rsssl_front_end = new rsssl_front_end();
			self::$instance->rsssl_mixed_content_fixer = new rsssl_mixed_content_fixer();


			// Backwards compatibility for add-ons
			global $rsssl_front_end, $rsssl_mixed_content_fixer;
			$rsssl_front_end = self::$instance->rsssl_front_end;
			$rsssl_mixed_content_fixer = self::$instance->rsssl_mixed_content_fixer;

			$wpcli = defined( 'WP_CLI' ) && WP_CLI;

			if (is_admin() || is_multisite() || $wpcli) {
				if (is_multisite()) {
					self::$instance->rsssl_multisite = new rsssl_multisite();
				}
				self::$instance->rsssl_cache = new rsssl_cache();
				self::$instance->rsssl_server = new rsssl_server();
				self::$instance->really_simple_ssl = new rsssl_admin();
				self::$instance->rsssl_help = new rsssl_help();
				self::$instance->rsssl_certificate = new rsssl_certificate();
				self::$instance->rsssl_site_health = new rsssl_site_health();

				// Backwards compatibility for add-ons
				global $rsssl_cache, $rsssl_server, $really_simple_ssl, $rsssl_help;
				$rsssl_cache = self::$instance->rsssl_cache;
				$rsssl_server = self::$instance->rsssl_server;
				$really_simple_ssl = self::$instance->really_simple_ssl;
				$rsssl_help = self::$instance->rsssl_help;

				if ( $wpcli ) {
					self::$instance->rsssl_wp_cli = new rsssl_wp_cli();
				}
			}

			self::$instance->hooks();
		}
		return self::$instance;
	}

	private function setup_constants()
	{
		define('rsssl_url', plugin_dir_url(__FILE__));
		define('rsssl_path', trailingslashit(plugin_dir_path(__FILE__)));
		define('rsssl_plugin', plugin_basename(__FILE__));
		require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		$plugin_data = get_plugin_data(__FILE__);
		define('rsssl_version', $plugin_data['Version']);
	}

	private function includes()
	{
		require_once(rsssl_path . 'class-front-end.php');
		require_once(rsssl_path . 'class-mixed-content-fixer.php');

		$wpcli = defined( 'WP_CLI' ) && WP_CLI;

		if ( $wpcli ) {
			require_once(rsssl_path . 'class-rsssl-wp-cli.php');
		}

		if (is_admin() || is_multisite() || $wpcli) {
			if (is_multisite()) {
				require_once(rsssl_path . 'class-multisite.php');
				require_once(rsssl_path . 'multisite-cron.php');
			}
			require_once(rsssl_path . 'class-admin.php');
			require_once(rsssl_path . 'class-cache.php');
			require_once(rsssl_path . 'class-server.php');
			require_once(rsssl_path . 'class-help.php');
			require_once(rsssl_path . 'class-certificate.php');
			require_once(rsssl_path . 'class-site-health.php');
		}
	}

	private function hooks()
	{
		
		add_action('wp_loaded', array(self::$instance->rsssl_front_end, 'force_ssl'), 20);
		if (is_admin() || is_multisite()) {
			add_action('plugins_loaded', array(self::$instance->really_simple_ssl, 'init'), 10);
		}
	}
}

function RSSSL()
{
	return REALLY_SIMPLE_SSL::instance();
}

add_action('plugins_loaded', 'RSSSL', 8);