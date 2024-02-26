<?php

namespace security\wordpress\vulnerabilities;

class Rsssl_Folder_Name {
	public $folderName;

	private function __construct() {
		$this->folderPath();
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
	private function folderPath(): void {
		$rsssl_folder = get_option( 'rsssl_folder_name' );

		if ( $rsssl_folder ) {
			$this->folderName = $rsssl_folder;
		} else {
			$newFolderName    = md5( uniqid( mt_rand(), true ) );

			$this->folderName = rsssl_upload_dir( $newFolderName );

			require_once 'class-rsssl-file-storage.php';
			Rsssl_File_Storage::DeleteOldFiles();
			update_option( 'rsssl_folder_name', $this->folderName );
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