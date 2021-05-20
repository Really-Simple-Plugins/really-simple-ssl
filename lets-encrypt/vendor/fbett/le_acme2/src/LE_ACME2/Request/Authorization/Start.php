<?php

namespace LE_ACME2\Request\Authorization;

use LE_ACME2\Request\AbstractRequest;

use LE_ACME2\Connector;
use LE_ACME2\Cache;
use LE_ACME2\Exception;
use LE_ACME2\Response;
use LE_ACME2\Struct\ChallengeAuthorizationKey;
use LE_ACME2\Utilities;

use LE_ACME2\Account;
use LE_ACME2\Order;

class Start extends AbstractRequest {

    protected $_account;
    protected $_order;
    protected $_challenge;

    public function __construct(Account $account, Order $order, Response\Authorization\Struct\Challenge $challenge) {

        $this->_account = $account;
        $this->_order = $order;
        $this->_challenge = $challenge;
    }

    /**
     * @return Response\AbstractResponse|Response\Authorization\Start
     * @throws Exception\InvalidResponse
     * @throws Exception\RateLimitReached
     * @throws Exception\ExpiredAuthorization
     */
    public function getResponse() : Response\AbstractResponse {

        $payload = [
            'keyAuthorization' => (new ChallengeAuthorizationKey($this->_account))->get($this->_challenge->token)
        ];

        $kid = Utilities\RequestSigner::KID(
            $payload,
            Cache\AccountResponse::getInstance()->get($this->_account)->getLocation(),
            $this->_challenge->url,
            Cache\NewNonceResponse::getInstance()->get()->getNonce(),
            $this->_account->getKeyDirectoryPath()
        );

        $result = Connector\Connector::getInstance()->request(
            Connector\Connector::METHOD_POST,
            $this->_challenge->url,
            $kid
        );

        return new Response\Authorization\Start($result);
    }
}