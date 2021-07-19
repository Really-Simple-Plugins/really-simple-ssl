<?php

namespace LE_ACME2\Request\Order;
defined('ABSPATH') or die();

use LE_ACME2\Request\AbstractRequest;
use LE_ACME2\Response;

use LE_ACME2\Connector;
use LE_ACME2\Cache;
use LE_ACME2\Exception;
use LE_ACME2\Utilities;

use LE_ACME2\Order;

class Get extends AbstractRequest {

    protected $_order;
    protected $_orderResponse;

    public function __construct(Order $order, Response\Order\AbstractOrder $orderResponse) {

        $this->_order = $order;
        $this->_orderResponse = $orderResponse;
    }

    /**
     * @return Response\AbstractResponse|Response\Order\Get
     * @throws Exception\InvalidResponse
     * @throws Exception\RateLimitReached
     */
    public function getResponse() : Response\AbstractResponse {

        $kid = Utilities\RequestSigner::KID(
            null,
            Cache\AccountResponse::getInstance()->get($this->_order->getAccount())->getLocation(),
            $this->_orderResponse->getLocation(),
            Cache\NewNonceResponse::getInstance()->get()->getNonce(),
            $this->_order->getAccount()->getKeyDirectoryPath()
        );

        $result = Connector\Connector::getInstance()->request(
            Connector\Connector::METHOD_POST,
            $this->_orderResponse->getLocation(),
            $kid
        );

        return new Response\Order\Get($result, $this->_orderResponse->getLocation());
    }
}