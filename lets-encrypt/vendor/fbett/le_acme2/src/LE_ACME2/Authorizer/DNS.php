<?php

namespace LE_ACME2\Authorizer;

use LE_ACME2\Request;
use LE_ACME2\Response;
use LE_ACME2\Exception;

use LE_ACME2\Order;
use LE_ACME2\Struct\ChallengeAuthorizationKey;
use LE_ACME2\Utilities;

class DNS extends AbstractAuthorizer {

    protected function _getChallengeType(): string {
        return Order::CHALLENGE_TYPE_DNS;
    }

    /** @var AbstractDNSWriter $_dnsWriter */
    private static $_dnsWriter = null;

    public static function setWriter(AbstractDNSWriter $dnsWriter) : void {
        self::$_dnsWriter = $dnsWriter;
    }

    /**
     * @param Response\Authorization\Struct\Challenge $challenge
     * @param Response\Authorization\Get $authorizationResponse
     * @return bool
     *
     * @throws Exception\AuthorizationInvalid
     * @throws Exception\DNSAuthorizationInvalid
     * @throws Exception\ExpiredAuthorization
     * @throws Exception\InvalidResponse
     * @throws Exception\RateLimitReached
     */
    protected function _existsNotValidChallenges(Response\Authorization\Struct\Challenge $challenge,
                                                 Response\Authorization\Get $authorizationResponse
    ) : bool {

        if($challenge->status == Response\Authorization\Struct\Challenge::STATUS_PENDING) {

            if(self::$_dnsWriter === null) {
                throw new \RuntimeException('DNS writer is not set');
            }

            if( self::$_dnsWriter->write(
                    $this->_order,
                    $authorizationResponse->getIdentifier()->value,
                    (new ChallengeAuthorizationKey($this->_account))->getEncoded($challenge->token)
                )
            ) {
                $request = new Request\Authorization\Start($this->_account, $this->_order, $challenge);
                /* $response = */ $request->getResponse();
            } else {

                Utilities\Logger::getInstance()->add(Utilities\Logger::LEVEL_INFO, 'Pending challenge deferred');
            }
        }

        if($challenge->status == Response\Authorization\Struct\Challenge::STATUS_INVALID) {
            throw new Exception\DNSAuthorizationInvalid(
                'Received status "' . Response\Authorization\Struct\Challenge::STATUS_INVALID . '" while challenge should be verified'
            );
        }

        return parent::_existsNotValidChallenges($challenge, $authorizationResponse);
    }
}