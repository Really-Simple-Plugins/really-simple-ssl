<?php

namespace security\wordpress\vulnerabilities;

defined( 'ABSPATH' ) or die();
require_once rsssl_path . 'lib/admin/class-encryption.php';
require_once 'class-rsssl-folder-name.php';

use RSSSL\lib\admin\Encryption;

class Rsssl_File_Storage {
	use Encryption;
	public $folder; //for the folder name

	/**
	 * Rsssl_File_Storage constructor.
	 */
	public function __construct() {
		//Fetching the key from the database
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
			$data = $this->decrypt( $data );
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
		if ( ! is_dir( $this->folder ) ) {
			return;
		}

		if ( ! is_writable( $this->folder ) ) {
			return;
		}

		$data = $this->encrypt( json_encode( $data ) );
		//first we check if the storage folder is already in the $file string
		if ( strpos( $file, $this->folder ) !== false ) {
			$file = str_replace( $this->folder . '/', '', $file );
		}

		file_put_contents( $this->folder . '/' . $file, $data );
	}

	public static function GetDate( string $file ) {
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