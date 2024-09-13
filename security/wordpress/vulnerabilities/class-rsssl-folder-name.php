<?php

namespace security\wordpress\vulnerabilities;
require_once rsssl_path . '/lib/admin/class-helper.php';
use RSSSL\lib\admin\Helper;
class Rsssl_Folder_Name {
	use Helper;
	public $folderName;

	private function __construct() {
		$this->initializeFolderName();
		$this->verifyAndCreateFolder();
	}

	private function initializeFolderName(): void {
		$rsssl_folder = get_option( 'rsssl_folder_name' );

		if ( $rsssl_folder ) {
			$this->folderName = $this->folderName( $rsssl_folder );
		} else {
			$newFolderName    = 'really-simple-ssl/' . md5( uniqid( mt_rand(), true ) );
			$this->folderName = $this->folderName( $newFolderName );

			require_once 'class-rsssl-file-storage.php';
			Rsssl_File_Storage::DeleteOldFiles();
			update_option( 'rsssl_folder_name', $this->folderName );
		}
	}

	private function folderName( $name ): string {
		return $name;
	}

	private function verifyAndCreateFolder(): void {
		$upload_dir = wp_upload_dir();
		if ( ! file_exists( $upload_dir['basedir'] . '/' . $this->folderName ) ) {
			$this->createFolder();
		}
	}

	public function createFolder(): void {
		$upload_dir  = wp_upload_dir();
		$folder_path = $upload_dir['basedir'] . '/' . $this->folderName;

		if ( ! file_exists( $folder_path ) && is_writable($upload_dir['basedir'] ) ) {
			if ( ! mkdir( $folder_path, 0755, true ) && ! is_dir( $folder_path ) ) {
				$this->log( sprintf( 'Really Simple Security: Directory "%s" was not created', $folder_path ) );
			}
		}
	}

	/**
	 * Creates a new folder name and saves it in the settings
	 *
	 * @return string
	 */
	public static function getFolderName(): string
	{
		return (new Rsssl_Folder_Name())->folderName;
	}
}