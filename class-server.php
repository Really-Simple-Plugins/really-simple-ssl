<?php
defined( 'ABSPATH' ) or die( 'you do not have access to this page!' );

if ( ! class_exists( 'rsssl_server' ) ) {
	class rsssl_server {
		private static $_this;
		private $sapi = false;

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
		 * @Since 2.5.1
		 * Checks if the server uses .htaccess
		 * @return bool
		 */

		public function uses_htaccess() {
			// No .htaccess on WP Engine
			if ( function_exists( 'is_wpe' ) && is_wpe() ) {
				return false;
			}

			if ( $this->get_server() === 'apache' || $this->get_server() === 'litespeed' ) {
				return true;
			}

			return false;
		}

		/**
		 * Returns the server type of the plugin user.
		 *
		 * @return string|bool server type the user is using of false if undetectable.
		 */

		public function get_server() {
			//Allows to override server authentication for testing or other reasons.
			if ( defined( 'RSSSL_SERVER_OVERRIDE' ) ) {
				return RSSSL_SERVER_OVERRIDE;
			}

			$server_raw = strtolower( htmlspecialchars( $_SERVER['SERVER_SOFTWARE'] ) );

			//figure out what server they're using
			if ( strpos( $server_raw, 'apache' ) !== false ) {
				return 'apache';
			} elseif ( strpos( $server_raw, 'nginx' ) !== false ) {
				return 'nginx';
			} elseif ( strpos( $server_raw, 'litespeed' ) !== false ) {
				return 'litespeed';
			} else { //unsupported server
				return false;
			}
		}



		public function auto_prepend_config(){
			if ( $this->isApacheModPHP() ){
				return "apache-mod_php"; //Apache _ modphp
			} else if ( $this->isApacheSuPHP() ) {
				return "apache-suphp"; //Apache + SuPHP
			} else if ( $this->isApache() && !$this->isApacheSuPHP() && ($this->isCGI() || $this->isFastCGI()) ) {
				return "cgi"; //Apache + CGI/FastCGI
			} else if ($this->isLiteSpeed()){
				return "litespeed";
			} else if ( $this->isNGINX() ) {
				return "nginx";
			} else if ( $this->isIIS() ) {
				return "iis";
			} else {
				return "manual";
			}
		}

		/**
		 * @return bool
		 */
		public function isApache() {
			return $this->get_server() === 'apache';
		}

		/**
		 * @return bool
		 */
		public function isNGINX() {
			return $this->get_server() === 'nginx';
		}

		/**
		 * @return bool
		 */
		public function isLiteSpeed() {
			return $this->get_server() === 'litespeed';
		}

		/**
		 * @return bool
		 */
		public function isIIS() {
			return $this->get_server() === 'iis';
		}

		/**
		 * @return bool
		 */
		public function isApacheModPHP() {
			return $this->isApache() && function_exists('apache_get_modules');
		}

		/**
		 * Not sure if this can be implemented at the PHP level.
		 * @return bool
		 */
		public function isApacheSuPHP() {
			return $this->isApache() && $this->isCGI() &&
			       function_exists('posix_getuid') &&
			       getmyuid() === posix_getuid();
		}

		/**
		 * @return bool
		 */
		public function isCGI() {
			return !$this->isFastCGI() && stripos($this->sapi(), 'cgi') !== false;
		}

		/**
		 * @return bool
		 */
		public function isFastCGI() {
			return stripos($this->sapi(), 'fastcgi') !== false || stripos($this->sapi(), 'fpm-fcgi') !== false;
		}


		private function sapi(){
			if ( !$this->sapi ) {
				$this->sapi = function_exists('php_sapi_name') ? php_sapi_name() : 'false';
			}
			if ( 'false' === $this->sapi ) {
				return false;
			}
			return $this->sapi;
		}


		/**
		 * Check if the apache version is at least 2.4
		 * @return bool
		 */
		public function apache_version_min_24() {
			$version = $_SERVER['SERVER_SOFTWARE'] ?? false;
			//check if version is higher then 2.4.
			if ( preg_match( '/Apache\/(2\.[4-9])/', $version, $matches ) ) {
				return true;
			}
			return false;
		}
	} //class closure
}
