<?php

namespace security\wordpress\vulnerabilities;

class Rsssl_Folder_Name {
	public $folderName;

	private function __construct() {
		$this->initializeFolderName();
		$this->verifyAndCreateFolder();
	}

	/**
	 * Initializes the folder name for Really Simple SSL.
	 * If the folder name is already set in the option 'rsssl_folder_name',
	 * the folder name is set to the value from the option.
	 * Otherwise, a new folder name is generated using a random unique identifier,
	 * prefixed with 'really-simple-ssl/', and stored in the option 'rsssl_folder_name'.
	 * If the folder name is generated, the old files are deleted using Rsssl_File_Storage::DeleteOldFiles().
	 *
	 * @return void
	 */
	private function initializeFolderName(): void {
		$rsssl_folder = get_option( 'rsssl_folder_name' );

		if ( $rsssl_folder ) {
			$this->folderName = $rsssl_folder;
		} else {
			$newFolderName    = md5( uniqid( mt_rand(), true ) );

			$this->folderName = $newFolderName;

			require_once 'class-rsssl-file-storage.php';
			Rsssl_File_Storage::DeleteOldFiles();
			update_option( 'rsssl_folder_name', $this->folderName );
		}
	}

	/**
	 * Verifies the existence of the folder associated with Really Simple SSL.
	 * If the folder does not exist in the WordPress upload directory,
	 * the createFolder() method is called to create the folder.
	 *
	 * @return void
	 */
	private function verifyAndCreateFolder(): void {
		$upload_dir = rsssl_upload_dir();
		if ( ! file_exists( $upload_dir. '/' . $this->folderName ) ) {
			$this->createFolder();
		}
	}

	/**
	 * Creates a folder for Really Simple SSL.
	 * The folder is created in the uploads directory using the folder name stored in the class property $this->folderName.
	 * If the folder already exists, no action is taken.
	 * If the folder cannot be created, an error message is logged to the error log.
	 *
	 * @return void
	 */
	public function createFolder(): void {
		$upload_dir  = rsssl_upload_dir();
		$folder_path = $upload_dir . '/' . $this->folderName;

		if ( ! file_exists( $folder_path ) && ! mkdir( $folder_path, 0755, true ) && ! is_dir( $folder_path ) ) {
			error_log( sprintf( 'Directory "%s" was not created', $folder_path ) );
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