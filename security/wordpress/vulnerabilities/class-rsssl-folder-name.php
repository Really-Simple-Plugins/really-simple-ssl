<?php

namespace security\wordpress\vulnerabilities;

class Rsssl_Folder_Name {
	private static $instance;
	private $folderName;

	/**
	 * Rsssl_Folder_Name constructor.
	 */
	private function __construct()
	{
		// Fetch the folder name from the settings if it exists
		if (rsssl_get_option('vulnerability_folder_name')) {
			$this->folderName = rsssl_get_option('vulnerability_folder_name');
			// We need to check if the folder exists, if not we need to create it
			if (!file_exists($this->folderName)) {
				$this->createFolder();
			}
		} else {
			// Generate a new folder name and save it in the settings
			$this->folderName = md5(uniqid(mt_rand(), true));
			$this->createFolder();
			rsssl_update_option('vulnerability_folder_name', $this->folderName);
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
	 * Get the singleton instance of this class
	 *
	 * @return Rsssl_Folder_Name
	 */
	public static function getInstance(): Rsssl_Folder_Name {
		if (self::$instance === null)
		{
			self::$instance = new Rsssl_Folder_Name();
		}

		return self::$instance;
	}

	/**
	 * Creates a new folder name and saves it in the settings
	 *
	 * @return string
	 */
	public function getFolderName(): string
	{
		return $this->folderName;
	}
}