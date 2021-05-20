<?php

namespace LE_ACME2\Struct;

use LE_ACME2\Account;
use LE_ACME2\Utilities;

class ChallengeAuthorizationKey {

    private $_account;

    public function __construct(Account $account) {
        $this->_account = $account;
    }

    public function get(string $token) : string {
        return $token . '.' . $this->_getDigest();
    }

    public function getEncoded(string $token) : string {
        return Utilities\Base64::UrlSafeEncode(
            hash('sha256', $this->get($token), true)
        );
    }

    private function _getDigest() : string {

        $privateKey = openssl_pkey_get_private(file_get_contents($this->_account->getKeyDirectoryPath() . 'private.pem'));
        $details = openssl_pkey_get_details($privateKey);

        $header = array(
            "e" => Utilities\Base64::UrlSafeEncode($details["rsa"]["e"]),
            "kty" => "RSA",
            "n" => Utilities\Base64::UrlSafeEncode($details["rsa"]["n"])

        );
        return Utilities\Base64::UrlSafeEncode(hash('sha256', json_encode($header), true));
    }
}