<?php
namespace LE_ACME2\Cache;
defined('ABSPATH') or die();

use LE_ACME2\Connector;

use LE_ACME2\Account;
use LE_ACME2\SingletonTrait;

use LE_ACME2\Exception;
use LE_ACME2\Request;
use LE_ACME2\Response;

class DirectoryResponse {
    
    use SingletonTrait;

    private const _FILE = 'DirectoryResponse';
    
    private function __construct() {}
    
    private $_responses = [];
    private $_index = 0;

    /**
     * @return Response\GetDirectory
     * @throws Exception\InvalidResponse
     * @throws Exception\RateLimitReached
     */
    public function get() : Response\GetDirectory {

        if(array_key_exists($this->_index, $this->_responses)) {
            return $this->_responses[$this->_index];
        }
        $this->_responses[$this->_index] = null;

        $cacheFile = Account::getCommonKeyDirectoryPath() . self::_FILE;

        if(file_exists($cacheFile) && filemtime($cacheFile) > strtotime('-2 days')) {

            $rawResponse = Connector\RawResponse::getFromString(file_get_contents($cacheFile));

            try {
                return $this->_responses[$this->_index] = new Response\GetDirectory($rawResponse);

            } catch(Exception\AbstractException $e) {
                unlink($cacheFile);
            }
        }

        $request = new Request\GetDirectory();
        $response = $request->getResponse();
        $this->set($response);

        return $response;
    }

    public function set(Response\GetDirectory $response) : void {

        $cacheFile = Account::getCommonKeyDirectoryPath() . self::_FILE;

        $this->_responses[$this->_index] = $response;
        file_put_contents($cacheFile, $response->getRaw()->toString());
    }
}