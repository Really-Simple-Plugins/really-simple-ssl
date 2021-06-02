<?php

namespace LE_ACME2\Authorizer;

use LE_ACME2\Request;
use LE_ACME2\Response;

use LE_ACME2\Struct\ChallengeAuthorizationKey;
use LE_ACME2\Utilities;
use LE_ACME2\Exception;

use LE_ACME2\Order;

class HTTP extends AbstractAuthorizer {

    protected static $_directoryPath = null;

    public static function setDirectoryPath(string $directoryPath) {

        if(!file_exists($directoryPath)) {
            throw new \RuntimeException('HTTP authorization directory path does not exist');
        }

        self::$_directoryPath = realpath($directoryPath) . DIRECTORY_SEPARATOR;
    }

    public static function getDirectoryPath() : ?string {
        return self::$_directoryPath;
    }

    protected function _getChallengeType(): string {
        return Order::CHALLENGE_TYPE_HTTP;
    }

    /**
     * @param Response\Authorization\Struct\Challenge $challenge
     * @param Response\Authorization\Get $authorizationResponse
     * @return bool
     *
     * @throws Exception\AuthorizationInvalid
     * @throws Exception\ExpiredAuthorization
     * @throws Exception\InvalidResponse
     * @throws Exception\RateLimitReached
     */
    protected function _existsNotValidChallenges(Response\Authorization\Struct\Challenge $challenge,
                                                 Response\Authorization\Get $authorizationResponse
    ) : bool {

        Utilities\Logger::getInstance()->add(
            Utilities\Logger::LEVEL_DEBUG,
            'Challenge "' . $challenge->token . '" has status:' . $challenge->status
        );

        if($challenge->status == Response\Authorization\Struct\Challenge::STATUS_PENDING) {

            $this->_writeToFile($challenge);
            if($this->_validateFile($authorizationResponse->getIdentifier()->value, $challenge)) {

                $request = new Request\Authorization\Start($this->_account, $this->_order, $challenge);
                /* $response = */ $request->getResponse();
            } else {

                Utilities\Logger::getInstance()->add(Utilities\Logger::LEVEL_INFO, 'Could not validate HTTP Authorization file');
            }
        }

        if($challenge->status == Response\Authorization\Struct\Challenge::STATUS_INVALID) {
            throw new Exception\HTTPAuthorizationInvalid(
                'Received status "' . Response\Authorization\Struct\Challenge::STATUS_INVALID . '" while challenge should be verified'
            );
        }

        return parent::_existsNotValidChallenges($challenge, $authorizationResponse);
    }

    private function _writeToFile(Response\Authorization\Struct\Challenge $challenge) : void {

        file_put_contents(
            self::$_directoryPath . $challenge->token,
            (new ChallengeAuthorizationKey($this->_account))->get($challenge->token)
        );
    }

    /**
     * @param string $domain
     * @param Response\Authorization\Struct\Challenge $challenge
     * @return bool
     *
     * @throws Exception\HTTPAuthorizationInvalid
     */
    private function _validateFile(string $domain, Response\Authorization\Struct\Challenge $challenge) : bool {

        $challengeAuthorizationKey = new ChallengeAuthorizationKey($this->_account);

        $requestURL = 'http://' . $domain . '/.well-known/acme-challenge/' . $challenge->token;
        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $requestURL);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($handle);

        $result = !empty($response) && $response == $challengeAuthorizationKey->get($challenge->token);

        if(!$result) {

            throw new Exception\HTTPAuthorizationInvalid(
                'HTTP challenge for "' . $domain . '"": ' .
                $domain . '/.well-known/acme-challenge/' . $challenge->token .
                ' tested, found invalid. CURL response: ' . var_export($response, true)
            );
        }
        return true;
    }
}