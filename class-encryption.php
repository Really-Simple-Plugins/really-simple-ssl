<?php defined( 'ABSPATH' ) or die();

if ( ! class_exists( 'rsssl_encryption' ) ) {
	class rsssl_encryption {

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
		 * Check if detection failed
		 * @return string
		 */
		public function get_key(): string {
			//if the key 'RSSSL_KEY' is not defined, write it to the wp-config.php
			if ( ! defined( 'RSSSL_KEY' ) ) {
				$key = $this->generate_key();
				$success = $this->write_key_to_wp_config( $key );
				if ( $success ) {
					return $key;
				}

				//fallback in case we can't write the wp-config.
				//disadvantage is that this can be rotated, causing our keys to become invalid.
				return LOGGED_IN_KEY;
			} else {
				return RSSSL_KEY;
			}
		}


		private function generate_key(){
			return wp_generate_password( 64, true, true );
		}

		/**
		 * Write the key to the wp-config.php
		 * @param string $key
		 *
		 * @return bool
		 */
		private function write_key_to_wp_config( string $key ) {
			$wp_config_path = RSSSL()->admin->find_wp_config_path();
			if ( empty( $wp_config_path ) ) {
				return false;
			}

			if ( ! is_writable( $wp_config_path ) ) {
				return false;
			}

			//replace the line 'define('RSSSL_KEY', $key);' in the wp-config.php with '', using regex
			$new = "define('RSSSL_KEY', $key);";
			$wp_config = file_get_contents( $wp_config_path );

			if ( str_contains( $wp_config, "define('RSSSL_KEY',") ) {
				$wp_config = preg_replace( '/define\(\'RSSSL_KEY\',\s?\'(.*?)\'\);/s', $new, $wp_config );
			} else {
				$marker = '/**#@-*/'."\n";
				$wp_config = str_replace( $marker, $marker.$new."\n", $wp_config );
			}

			file_put_contents( $wp_config_path, $wp_config );
			return true;
		}
	}
}
