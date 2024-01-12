<?php

namespace security\wordpress\vulnerabilities;

class Rsssl_Folder_Name {
	public $folderName;

	/**
	 * Rsssl_Folder_Name constructor.
	 */
	private function __construct()
	{
		// Fetch the folder name from the settings if it exists
		if (get_option('rsssl_folder_name')) {
			$this->folderName = 'really-simple-ssl/' . get_option('rsssl_folder_name');
			// We need to check if the folder exists, if not we need to create it
			$upload_dir = wp_upload_dir();

			if (!file_exists($upload_dir['basedir'] . '/' . $this->folderName)) {
				$this->createFolder();
			}
		} else {
			// Generate a new folder name and save it in the settings
			$this->folderName = md5(uniqid(mt_rand(), true));
			$this->createFolder();
			update_option('rsssl_folder_name', $this->folderName);
		}
	}

	/**
	 * Creates a new folder in the uploads directory with the folder name
	 * and sets the permissions.
	 *
	 * @return void
	 */
	public function createFolder(): void
	{
		$upload_dir = wp_upload_dir();
		$folder_path = $upload_dir['basedir'] . '/' . $this->folderName;

		if (!file_exists($folder_path)) {
			if ( ! mkdir( $folder_path, 0755, true ) && ! is_dir( $folder_path ) ) {
				throw new \RuntimeException( sprintf( 'Directory "%s" was not created', $folder_path ) );
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