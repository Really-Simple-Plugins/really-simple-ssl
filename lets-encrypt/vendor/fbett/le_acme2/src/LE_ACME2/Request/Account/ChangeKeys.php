<?php

namespace LE_ACME2\Request\Account;

use LE_ACME2\Request\AbstractRequest;
use LE_ACME2\Response;

use LE_ACME2\Connector;
use LE_ACME2\Cache;
use LE_ACME2\Utilities;
use LE_ACME2\Exception;

use LE_ACME2\Account;

class ChangeKeys extends AbstractRequest {

    protected $_account;

    public function __construct(Account $account) {
        $this->_account = $account;
    }

    /**
     * @return Response\AbstractResponse|Response\Account\ChangeKeys
     * @throws Exception\InvalidResponse
     * @throws Exception\RateLimitReached
     */
    public function getResponse() : Response\AbstractResponse {

        $currentPrivateKey = openssl_pkey_get_private(
            file_get_contents($this->_account->getKeyDirectoryPath() . 'private.pem')
        );
        $currentPrivateKeyDetails = openssl_pkey_get_details($currentPrivateKey);

        /**
         *  draft-13 Section 7.3.6
         *  "newKey" is deprecated after August 23rd 2018
         */
        $newPrivateKey = openssl_pkey_get_private(
            file_get_contents($this->_account->getKeyDirectoryPath() . 'private-replacement.pem')
        );
        $newPrivateKeyDetails = openssl_pkey_get_details($newPrivateKey);

        $innerPayload = [
            'account' => Cache\AccountResponse::getInstance()->get($this->_account)->getLocation(),
            'oldKey' => [
                "kty" => "RSA",
                "n" => Utilities\Base64::UrlSafeEncode($currentPrivateKeyDetails["rsa"]["n"]),
                "e" => Utilities\Base64::UrlSafeEncode($currentPrivateKeyDetails["rsa"]["e"])
            ],
            'newKey' => [
                "kty" => "RSA",
                "n" => Utilities\Base64::UrlSafeEncode($newPrivateKeyDetails["rsa"]["n"]),
                "e" => Utilities\Base64::UrlSafeEncode($newPrivateKeyDetails["rsa"]["e"])
            ]
        ];

        $outerPayload = Utilities\RequestSigner::JWK(
            $innerPayload,
            Cache\DirectoryResponse::getInstance()->get()->getKeyChange(),
            Cache\NewNonceResponse::getInstance()->get()->getNonce(),
            $this->_account->getKeyDirectoryPath(),
            'private-replacement.pem'
        );

        $data = Utilities\RequestSigner::KID(
            $outerPayload,
            Cache\AccountResponse::getInstance()->get($this->_account)->getLocation(),
            Cache\DirectoryResponse::getInstance()->get()->getKeyChange(),
            Cache\NewNonceResponse::getInstance()->get()->getNonce(),
            $this->_account->getKeyDirectoryPath(),
            'private.pem'
        );

        $result = Connector\Connector::getInstance()->request(
            Connector\Connector::METHOD_POST,
            Cache\DirectoryResponse::getInstance()->get()->getKeyChange(),
            $data
        );

        return new Response\Account\ChangeKeys($result);
    }
}
