<?php

namespace security\wordpress\vulnerabilities;

defined( 'ABSPATH' ) or die();

require_once 'class-rsssl-folder-name.php';

class Rsssl_File_Storage {
	private $hash;
	public $folder; //for the folder name

	/**
	 * Rsssl_File_Storage constructor.
	 */
	public function __construct() {
		//Fetching the key from the database
		$this->generateHashKey();
		$upload_dir   = wp_upload_dir();
		$this->folder = $upload_dir['basedir'] . '/' . Rsssl_Folder_Name::getFolderName();
	}

	public static function StoreFile( $file, $data ): void {
		$storage = new Rsssl_File_Storage();
		//first we check if the storage folder is already in the $file string
		if ( strpos( $file, $storage->folder ) !== false ) {
			$file = str_replace( $storage->folder . '/', '', $file );
		}
		$storage->set( $data, $storage->folder . '/' . $file );
	}

	public static function GetFile( $file ) {
		$storage = new Rsssl_File_Storage();

		//first we check if the storage folder is already in the $file string
		if ( strpos( $file, $storage->folder ) !== false ) {
			$file = str_replace( $storage->folder . '/', '', $file );
		}

		return $storage->get( $storage->folder . '/' . $file );
	}

	/** Get the data from the file
	 *
	 * @param $file
	 *
	 * @return bool|mixed
	 */
	public function get( $file ) {
		if ( file_exists( $file ) ) {
			$data = file_get_contents( $file );
			$data = $this->Decode64WithHash( $data );

			return json_decode( $data );
		}

		return false;
	}

	/** Save the data to the file
	 *
	 * @param $data
	 * @param $file
	 */
	public function set( $data, $file ) {
		$data = $this->Encode64WithHash( json_encode( $data ) );
		//first we check if the storage folder is already in the $file string
		if ( strpos( $file, $this->folder ) !== false ) {
			$file = str_replace( $this->folder . '/', '', $file );
		}

		file_put_contents( $this->folder . '/' . $file, $data );
	}

	/** encode the data with a hash
	 *
	 * @param $data
	 *
	 * @return string
	 * @throws \Exception
	 */
	private function Encode64WithHash( $data ): string {
		$crypto_strong = false;
		$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'), $crypto_strong);

		// Check if IV generation was successful and cryptographically strong
		if ($iv === false || $crypto_strong === false) {
			throw new \RuntimeException( __('Could not generate a secure initialization vector.', 'really-simple-ssl'));
		}

		$encrypted = openssl_encrypt($data, 'aes-256-cbc', $this->hash, 0, $iv);
		// Store the $iv along with the $encrypted data, so we can use it during decryption
		$encrypted = base64_encode($encrypted . '::' . $iv);
		return $encrypted;
	}

	/** decode the data with a hash
	 *
	 * @param $data
	 *
	 * @return string
	 * @throws \Exception
	 */
	private function Decode64WithHash($data): string {
		$data = base64_decode( $data );
		[ $encrypted_data, $iv ] = explode( '::', $data, 2 );

		// Check if IV was successfully retrieved
		if ( $iv === false ) {
			throw new \RuntimeException( __('Could not retrieve the initialization vector.', 'really-simple-ssl') );
		}

		$decrypted = openssl_decrypt( $encrypted_data, 'aes-256-cbc', $this->hash, 0, $iv );

		return $decrypted;
	}


	/** Generate a hashkey and store it in the database
	 * @return void
	 */
	private function generateHashKey(): void {
		if ( get_option( 'rsssl_hashkey' ) && get_option( 'rsssl_hashkey' ) !== "" ) {
			$this->hash = get_option( 'rsssl_hashkey' );
		} else {
			$this->hash = md5( uniqid( rand(), true ) );
			update_option( 'rsssl_hashkey', $this->hash, false );
		}
	}

	public static function GetDate( string $file ) {
		$storage = new Rsssl_File_Storage();
		$file    = $storage->folder . '/' . $file;
		if ( file_exists( $file ) ) {
			return filemtime( $file );
		}

		return false;
	}

	public static function get_upload_dir() {
		return ( new Rsssl_File_Storage() )->folder;
	}

	public static function validateFile( string $file ): bool {
		$storage = new Rsssl_File_Storage();
		$file    = $storage->folder . '/' . $file;
		if ( file_exists( $file ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Delete all files in the storage folder
	 *
	 * @return void
	 */
	public static function DeleteAll(): void {
		$storage = new Rsssl_File_Storage();
		//we get the really-simple-ssl folder
		$rsssl_dir = $storage->folder;
		//then we delete the following files from that folder: manifest.json, components.json and core.json
		$files = array( 'manifest.json', 'components.json', 'core.json' );
		foreach ( $files as $file ) {
			//we delete the file
			$file = $rsssl_dir . '/' . $file;
			if ( file_exists( $file ) ) {
				unlink( $file );
			}
		}
		//we delete the folder
		if ( file_exists( $rsssl_dir ) ) {
			self::DeleteFolder($rsssl_dir);
			//we delete the option
			delete_option( 'rsssl_folder_name' );
		}
	}

	/**
	 * Recursively delete a folder and its contents.
	 *
	 * @param  string  $dir  The path to the folder to be deleted.
	 *
	 * @return bool Returns true if the folder was successfully deleted, false otherwise.
	 */
	public static function DeleteFolder($dir): bool {
		if (substr($dir, strlen($dir) - 1, 1) != '/')
			$dir .= '/';

		if ($handle = opendir($dir)) {
			while ($obj = readdir($handle)) {
				if ($obj != '.' && $obj != '..') {
					if (is_dir($dir.$obj)) {
						if (!self::DeleteFolder($dir.$obj))
							return false;
					}
					elseif (is_file($dir.$obj)) {
						if (!unlink($dir.$obj))
							return false;
					}
				}
			}

			closedir($handle);

			if (!rmdir($dir))
				return false;
			return true;
		}
		return false;
	}

	/**
	 * Delete all files in the storage folder
	 *
	 * @return void
	 */
	public static function DeleteOldFiles(): void {
		$rsssl_dir = wp_upload_dir()['basedir'] . '/really-simple-ssl';
		//then we delete the following files from that folder: manifest.json, components.json and core.json
		$files = array( 'manifest.json', 'components.json', 'core.json' );
		foreach ( $files as $file ) {
			//we delete the file
			$file = $rsssl_dir . '/' . $file;
			if ( file_exists( $file ) ) {
				unlink( $file );
			}
		}
	}
}