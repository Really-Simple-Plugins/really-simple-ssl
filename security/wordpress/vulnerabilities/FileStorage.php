<?php

namespace security\wordpress\vulnerabilities;
defined('ABSPATH') or die();
class FileStorage
{
    private $hash;

    /**
     * FileStorage constructor.
     */
    public function __construct()
    {
        //Fetching the key from the database
        $this->generateHashKey();
    }

    public Static function StoreFile($file, $data)
    {
        $storage = new FileStorage();
        $storage->set($data, $file);
    }

    public Static function GetFile($file)
    {
        $storage = new FileStorage();
        return $storage->get($file);
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
        file_put_contents($file, $data);
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
            update_option('rsssl_hashkey', $this->hash);
        }
    }

    public static function GetDate(string $file)
    {
        if (file_exists($file)) {
            return filemtime($file);
        }
        return false;
    }

    public static function DeleteAll()
    {
        //we get the upload folder
        $upload_dir = wp_upload_dir();

        //we get the really-simple-ssl folder
        $rsssl_dir = $upload_dir['basedir'] . '/really-simple-ssl';

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