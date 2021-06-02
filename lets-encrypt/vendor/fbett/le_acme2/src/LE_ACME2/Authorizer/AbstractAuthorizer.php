<?php

namespace LE_ACME2\Authorizer;

use LE_ACME2\Request;
use LE_ACME2\Response;

use LE_ACME2\Cache;
use LE_ACME2\Utilities;
use LE_ACME2\Exception;

use LE_ACME2\Account;
use LE_ACME2\Order;

abstract class AbstractAuthorizer {

    protected $_account;
    protected $_order;

    /**
     * AbstractAuthorizer constructor.
     *
     * @param Account $account
     * @param Order $order
     *
     * @throws Exception\InvalidResponse
     * @throws Exception\RateLimitReached
     * @throws Exception\ExpiredAuthorization
     */
    public function __construct(Account $account, Order $order) {

        $this->_account = $account;
        $this->_order = $order;

        $this->_fetchAuthorizationResponses();
    }

    /** @var Response\Authorization\Get[] $_authorizationResponses */
    protected $_authorizationResponses = [];

    /**
     * @throws Exception\InvalidResponse
     * @throws Exception\RateLimitReached
     * @throws Exception\ExpiredAuthorization
     */
    protected function _fetchAuthorizationResponses() {

        if(!file_exists($this->_order->getKeyDirectoryPath() . 'private.pem')) {

            Utilities\Logger::getInstance()->add(
                Utilities\Logger::LEVEL_DEBUG,
                get_class() . '::' . __FUNCTION__ . ' result suppressed (Order has finished already)'
            );

            return;
        }

        $orderResponse = Cache\OrderResponse::getInstance()->get($this->_order);

        foreach($orderResponse->getAuthorizations() as $authorization) {

            $request = new Request\Authorization\Get($this->_account, $authorization);
            $this->_authorizationResponses[] = $request->getResponse();
        }
    }

    protected function _hasValidAuthorizationResponses() : bool {

        return count($this->_authorizationResponses) > 0;
    }

    public function shouldStartAuthorization() : bool {

        foreach($this->_authorizationResponses as $response) {

            $challenge = $response->getChallenge($this->_getChallengeType());
            if($challenge->status == Response\Authorization\Struct\Challenge::STATUS_PENDING) {

                Utilities\Logger::getInstance()->add(
                    Utilities\Logger::LEVEL_DEBUG,
                    get_class() . '::' . __FUNCTION__ . ' "Pending challenge found',
                    $challenge
                );

                return true;
            }
        }
        return false;
    }

    abstract protected function _getChallengeType() : string;

    /**
     * @throws Exception\AuthorizationInvalid
     * @throws Exception\InvalidResponse
     * @throws Exception\RateLimitReached
     * @throws Exception\ExpiredAuthorization
     */
    public function progress() {

        if(!$this->_hasValidAuthorizationResponses())
            return;

        $existsNotValidChallenges = false;

        foreach($this->_authorizationResponses as $authorizationResponse) {

            $challenge = $authorizationResponse->getChallenge($this->_getChallengeType());

            if($this->_existsNotValidChallenges($challenge, $authorizationResponse)) {
                $existsNotValidChallenges = true;
            }
        }

        $this->_finished = !$existsNotValidChallenges;
    }

    /**
     * @param Response\Authorization\Struct\Challenge $challenge
     * @param Response\Authorization\Get $authorizationResponse
     * @return bool
     *
     * @throws Exception\AuthorizationInvalid
     */
    protected function _existsNotValidChallenges(Response\Authorization\Struct\Challenge $challenge,
                                                 Response\Authorization\Get $authorizationResponse
    ) : bool {

        if($challenge->status == Response\Authorization\Struct\Challenge::STATUS_PENDING) {

            Utilities\Logger::getInstance()->add(
                Utilities\Logger::LEVEL_DEBUG,
                get_class() . '::' . __FUNCTION__ . ' "Non valid challenge found',
                $challenge
            );

            return true;
        }
        else if($challenge->status == Response\Authorization\Struct\Challenge::STATUS_PROGRESSING) {

            // Should come back later
            return true;
        }
        else if($challenge->status == Response\Authorization\Struct\Challenge::STATUS_VALID) {

        }
        else if($challenge->status == Response\Authorization\Struct\Challenge::STATUS_INVALID) {
            throw new Exception\AuthorizationInvalid(
                'Received status "' . Response\Authorization\Struct\Challenge::STATUS_INVALID . '" while challenge should be verified'
            );
        }
        else {

            throw new \RuntimeException('Challenge status "' . $challenge->status . '" is not implemented');
        }

        return false;
    }

    protected $_finished = false;

    public function hasFinished() : bool {

        Utilities\Logger::getInstance()->add(
            Utilities\Logger::LEVEL_DEBUG,
            get_called_class() . '::' . __FUNCTION__,
            $this->_finished
        );

        return $this->_finished;
    }
}

