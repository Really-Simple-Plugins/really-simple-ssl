<?php

namespace LE_ACME2\Request\Account;
defined('ABSPATH') or die();

use LE_ACME2\Request\AbstractRequest;
use LE_ACME2\Response;

use LE_ACME2\Connector;
use LE_ACME2\Cache;
use LE_ACME2\Utilities;
use LE_ACME2\Exception;

use LE_ACME2\Account;

class Create extends AbstractRequest {
    
    protected $_account;
    
    public function __construct(Account $account) {
        $this->_account = $account;
    }

    /**
     * @return Response\AbstractResponse|Response\Account\Create
     * @throws Exception\InvalidResponse
     * @throws Exception\RateLimitReached
     */
    public function getResponse() : Response\AbstractResponse {

        $payload = [
            'contact' => $this->_buildContactPayload($this->_account->getEmail()),
            'termsOfServiceAgreed' => true,
        ];
        
        $jwk = Utilities\RequestSigner::JWKString(
            $payload,
            Cache\DirectoryResponse::getInstance()->get()->getNewAccount(),
            Cache\NewNonceResponse::getInstance()->get()->getNonce(),
            $this->_account->getKeyDirectoryPath()
        );
        
        $result = Connector\Connector::getInstance()->request(
            Connector\Connector::METHOD_POST,
            Cache\DirectoryResponse::getInstance()->get()->getNewAccount(),
            $jwk
        );
        
        return new Response\Account\Create($result);
    }
}