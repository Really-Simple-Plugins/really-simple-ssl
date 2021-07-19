<?php

namespace LE_ACME2\Request\Order;
defined('ABSPATH') or die();

use LE_ACME2\Order;
use LE_ACME2\Request\AbstractRequest;
use LE_ACME2\Response;

use LE_ACME2\Connector;
use LE_ACME2\Cache;
use LE_ACME2\Exception;
use LE_ACME2\Utilities;

class GetCertificate extends AbstractRequest {

    protected $_order;
    protected $_orderResponse;

    private $_alternativeUrl = null;

    public function __construct(Order $order, Response\Order\AbstractOrder $orderResponse,
                                string $alternativeUrl = null
    ) {
        $this->_order = $order;
        $this->_orderResponse = $orderResponse;

        if($alternativeUrl !== null) {
            $this->_alternativeUrl = $alternativeUrl;
        }
    }

    /**
     * @return Response\AbstractResponse|Response\Order\GetCertificate
     * @throws Exception\InvalidResponse
     * @throws Exception\RateLimitReached
     */
    public function getResponse() : Response\AbstractResponse {

        $url = $this->_alternativeUrl === null ?
            $this->_orderResponse->getCertificate() :
            $this->_alternativeUrl;

        $kid = Utilities\RequestSigner::KID(
            null,
            Cache\AccountResponse::getInstance()->get($this->_order->getAccount())->getLocation(),
            $url,
            Cache\NewNonceResponse::getInstance()->get()->getNonce(),
            $this->_order->getAccount()->getKeyDirectoryPath()
        );

        $result = Connector\Connector::getInstance()->request(
            Connector\Connector::METHOD_POST,
            $url,
            $kid
        );

        return new Response\Order\GetCertificate($result);
    }
}