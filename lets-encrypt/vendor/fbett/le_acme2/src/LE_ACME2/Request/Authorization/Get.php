<?php

namespace LE_ACME2\Request\Authorization;
defined('ABSPATH') or die();

use LE_ACME2\Request\AbstractRequest;

use LE_ACME2\Connector;
use LE_ACME2\Cache;
use LE_ACME2\Exception;
use LE_ACME2\Response;
use LE_ACME2\Utilities;

use LE_ACME2\Account;

class Get extends AbstractRequest {

    protected $_account;
    protected $_authorizationURL;

    public function __construct(Account $account, string $authorizationURL) {

        $this->_account = $account;
        $this->_authorizationURL = $authorizationURL;
    }

    /**
     * @return Response\AbstractResponse|Response\Authorization\Get
     * @throws Exception\InvalidResponse
     * @throws Exception\RateLimitReached
     * @throws Exception\ExpiredAuthorization
     */
    public function getResponse() : Response\AbstractResponse {

        $kid = Utilities\RequestSigner::KID(
            null,
            Cache\AccountResponse::getInstance()->get($this->_account)->getLocation(),
            $this->_authorizationURL,
            Cache\NewNonceResponse::getInstance()->get()->getNonce(),
            $this->_account->getKeyDirectoryPath()
        );

        $result = Connector\Connector::getInstance()->request(
            Connector\Connector::METHOD_POST,
            $this->_authorizationURL,
            $kid
        );

        return new Response\Authorization\Get($result);
    }
}