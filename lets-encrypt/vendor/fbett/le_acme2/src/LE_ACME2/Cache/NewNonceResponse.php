<?php
namespace LE_ACME2\Cache;

use LE_ACME2\SingletonTrait;

use LE_ACME2\Exception;
use LE_ACME2\Request;
use LE_ACME2\Response;

class NewNonceResponse {

    use SingletonTrait;

    private function __construct() {}

    private $_responses = [];
    private $_index = 0;

    /**
     * @return Response\GetNewNonce
     * @throws Exception\InvalidResponse
     * @throws Exception\RateLimitReached
     */
    public function get() : Response\GetNewNonce {

        if(array_key_exists($this->_index, $this->_responses)) {
            return $this->_responses[$this->_index];
        }
        $this->_responses[$this->_index] = null;

        $request = new Request\GetNewNonce();
        $response = $request->getResponse();
        $this->set($response);

        return $response;
    }

    public function set(Response\GetNewNonce $response) : void {
        $this->_responses[$this->_index] = $response;
    }
}