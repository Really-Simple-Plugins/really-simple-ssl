<?php

namespace security\wordpress\vulnerabilities;
defined('ABSPATH') or die();

require_once 'class-rsssl-folder-name.php';
class FileStorage
{
	private $hash;
	public $folder; //for the foldername

    /**
     * FileStorage constructor.
     */
    public function __construct()
    {
        //Fetching the key from the database
        $this->generateHashKey();
	    $upload_dir = wp_upload_dir();
		$this->folder =  $upload_dir['basedir'] . Rsssl_Folder_Name::getInstance()->getFolderName();
    }

    public Static function StoreFile($file, $data): void {
        $storage = new FileStorage();
	    //first we check if the storage folder is already in the $file string
	    if (strpos($file, $storage->folder) !== false) {
		    $file = str_replace($storage->folder . '/', '', $file);
	    }
        $storage->set($data, $storage->folder . '/' . $file);
    }

    public Static function GetFile($file)
    {
        $storage = new FileStorage();

		//first we check if the storage folder is already in the $file string
	   	if (strpos($file, $storage->folder) !== false) {
			$file = str_replace($storage->folder . '/', '', $file);
		}

        return $storage->get($storage->folder . '/' . $file);
    }

    /** Get the data from the file
     * @param $file
     * @return bool|mixed
     */
    public function get($file)
    {
        if (file_exists($file)) {
            $data = file_get_contents($file);
            $data = $this->Decode64WithHash($data);
            return json_decode($data);
        }
        return false;
    }

    /** Save the data to the file
     * @param $data
     * @param $file
     */
    public function set($data, $file)
    {
        $data = $this->Encode64WithHash(json_encode($data));
        file_put_contents($this->folder . '/' . $file, $data);
    }

    /** encode the data with a hash
     * @param $data
     * @return string
     */
    private function Encode64WithHash($data): string
    {
        //we create a simple encoding, using the hashkey as a salt
        $data = base64_encode($data);
        return base64_encode($data . $this->hash);
    }

    /** decode the data with a hash
     * @param $data
     * @return string
     */
    private function Decode64WithHash($data): string
    {
        //we create a simple decoding, using the hashkey as a salt
        $data = base64_decode($data);
        $data = substr($data, 0, -strlen($this->hash));
        return base64_decode($data);
    }

    /** Generate a hashkey and store it in the database
     * @return void
     */
    private function generateHashKey(): void
    {
        if (get_option('rsssl_hashkey') && get_option('rsssl_hashkey') !== "") {
            $this->hash = get_option('rsssl_hashkey');
        } else {
            $this->hash = md5(uniqid(rand(), true));
            update_option('rsssl_hashkey', $this->hash, false);
        }
    }

    public static function GetDate(string $file)
    {
		$storage = new FileStorage();
		$file = $storage->folder . '/' . $file;
        if (file_exists($file)) {
            return filemtime($file);
        }
        return false;
    }

	public static function get_upload_dir() {
		return ( new FileStorage() )->folder;
	}

	public static function validateFile(string $file): bool {
		$storage = new FileStorage();
		$file = $storage->folder . '/' . $file;
		if (file_exists($file)) {
			return true;
		}
		return false;
	}

    public static function DeleteAll()
    {
		$storage = new FileStorage();

        //we get the really-simple-ssl folder
        $rsssl_dir = $storage->folder;

        //then we delete the following files from that folder: manifest.json, components.json and core.json
        $files = array('manifest.json', 'components.json', 'core.json');
        foreach ($files as $file) {
            //we delete the file
            $file = $rsssl_dir . '/' . $file;
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}