<?php

namespace LE_ACME2\Request\Account;
defined('ABSPATH') or die();

use LE_ACME2\Request\AbstractRequest;

use LE_ACME2\Connector;
use LE_ACME2\Cache;
use LE_ACME2\Utilities;
use LE_ACME2\Exception;

use LE_ACME2\Account;

abstract class AbstractLocation extends AbstractRequest {

    protected $_account;

    public function __construct(Account $account) {
        $this->_account = $account;
    }

    /**
     * @return Connector\RawResponse
     * @throws Exception\InvalidResponse
     * @throws Exception\RateLimitReached
     */
    protected function _getRawResponse() : Connector\RawResponse {

        $payload = $this->_getPayload();
        if(count($payload) == 0) {
            $payload['rand-' . rand(100000, 1000000)] = 1;
        }

        $kid = Utilities\RequestSigner::KID(
            $payload,
            Cache\AccountResponse::getInstance()->get($this->_account)->getLocation(),
            Cache\AccountResponse::getInstance()->get($this->_account)->getLocation(),
            Cache\NewNonceResponse::getInstance()->get()->getNonce(),
            $this->_account->getKeyDirectoryPath()
        );

        $result = Connector\Connector::getInstance()->request(
            Connector\Connector::METHOD_POST,
            Cache\AccountResponse::getInstance()->get($this->_account)->getLocation(),
            $kid
        );

        return $result;
    }

    abstract protected function _getPayload() : array;
}