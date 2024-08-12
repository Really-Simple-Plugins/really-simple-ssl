<?php
namespace RSSSL\lib\admin;
use function cli\err;

require_once __DIR__ . '/class-helper.php';

/**
 * Trait admin helper
 *
 *
 * @package RSSSL\lib\admin\encryption
 * @since   8.2
 *
 * @author  Really Simple SSL
 * @see     https://really-simple-ssl.com
 */
trait Encryption {
	use Helper;

	/**
	 * Encrypt a string with a prefix. If the prefix is already there, it's already encrypted
	 *
	 * @param string $data
	 * @param string $prefix
	 *
	 * @return string
	 */

	public function encrypt_with_prefix( string $data, string $prefix = 'rsssl_'):string {
		if ( strpos($data, $prefix) === 0 ) {
			return $data;
		}

		$data = $this->encrypt($data);
		return $prefix . $data;
	}

	/**
	 * Decrypt data if prefixed. If not prefixed, return the data, as it is already decrypted
	 *
	 * @param string $data
	 * @param string $prefix
	 *
	 * @return string
	 */
	public function decrypt_if_prefixed( string $data, string $prefix = 'rsssl_', string $deprecated_key = '' ):string{
		if ( strpos($data, $prefix) !== 0 ) {
			return $data;
		}
		$data = substr($data, strlen($prefix));
		return $this->decrypt($data, 'string', $deprecated_key);
	}

	/**
	 * Encrypt a string.
	 *
	 * @param array|string $data
	 * @param string $type //ARRAY or STRING
	 *
	 * @return string
	 */
	public function encrypt( $data, string $type = 'string' ): string {

		$key = $this->get_encryption_key();
		if ( 'array' === strtolower( $type ) ) {
			$data = serialize($data);
		}

		if ( strlen( trim( $data ) ) === 0 ) {
			return '';
		}

		$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
		$encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
		return base64_encode($encrypted . '::' . $iv);
	}
	/**
	 * Decrypt data
	 * @param mixed $data
	 * @param string $type
	 * @param string|bool $deprecated_key
	 *
	 * @return array|string
	 */
	public function decrypt( $data, string $type = 'string', $deprecated_key = '' ) {
		// Check if user is logged in
		if ( ! rsssl_admin_logged_in() ) {
			return strtolower( $type ) === 'string' ? '' : [];
		}

		$key = ! empty( $deprecated_key ) ? $deprecated_key : $this->get_encryption_key();

		// If $data is empty, return appropriate empty value based on type
		if ( empty( $data ) ) {
			return strtolower( $type ) === 'string' ? '' : [];
		}

		// If $data is not a string (i.e., it's already an array), return it as is
		if ( ! is_string( $data ) ) {
			return $data;
		}

		$decoded = base64_decode( $data );
		if ( false === $decoded ) {
			return strtolower( $type ) === 'string' ? '' : [];
		}

		if ( strpos( $decoded, '::' ) !== false ) {
			[ $encrypted_data, $iv ] = explode( '::', $decoded, 2 );
		} else {
			// Deprecated method, for backwards compatibility (license decryption)
			$ivlength       = openssl_cipher_iv_length( 'aes-256-cbc' );
			$iv             = substr( $decoded, 0, $ivlength );
			$encrypted_data = substr( $decoded, $ivlength );
		}

		if ( function_exists( 'openssl_decrypt' ) ) {
			$decrypted_data = openssl_decrypt( $encrypted_data, 'aes-256-cbc', $key, 0, $iv );
		} else {
			$this->log( 'The function openssl_decrypt does not exist. Check with your host if the OpenSSL library for PHP can be enabled.' );

			return strtolower( $type ) === 'string' ? '' : [];
		}

		if ( 'array' === strtolower( $type ) ) {
			$unserialized_data = @unserialize( $decrypted_data );

			return ( is_array( $unserialized_data ) ) ? $unserialized_data : [];
		}

		return $decrypted_data;
	}

	/**
	 * Get the encryption key. The key is NOT autoloaded, so if encryption is required on the front-end, this should be changed.
	 * @return string
	 */
	private function get_encryption_key(): string {
		$wp_config_path = $this->wpconfig_path();

		//first, check if we have a key
		if ( defined('RSSSL_KEY' ) ) {
			return RSSSL_KEY;
		}

		$key = get_site_option('rsssl_main_key' );
		$new_generated = false;
		//if we don't have a key, generate one
		if ( !$key ) {
			$new_generated = true;
			$key = wp_generate_password( 64, false );
		}

		if ( is_writable($wp_config_path) ) {
			//add the key to the wp-config file
			$rule = '//Begin Really Simple SSL key' . "\n";
			$rule .= "define('RSSSL_KEY', '" . $key . "');" . "\n";
			$rule .= '//END Really Simple SSL key' . "\n";
			$insert_after = '<?php';

			$contents = file_get_contents( $wp_config_path );
			$pos          = strpos( $contents, $insert_after );
			if ( false !== $pos && strpos( $contents, 'RSSSL_KEY' ) === false ) {
				$contents = substr_replace( $contents, $rule, $pos + 1 + strlen( $insert_after ), 0 );
				file_put_contents( $wp_config_path, $contents );
			}

			//if the wp-config was just set to writable, we can delete the key from the database now.
			delete_site_option( 'rsssl_main_key' );
		} else if ( $new_generated ) {
			//if we can't write to the wp-config file, store the key in the database
			//when wp-config is set to writable, auto upgrade to constant
			update_site_option( 'rsssl_main_key', $key, false );
		}

		return $key;
	}
}
