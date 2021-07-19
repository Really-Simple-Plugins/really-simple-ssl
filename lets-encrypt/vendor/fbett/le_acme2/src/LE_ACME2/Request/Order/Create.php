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

class Create extends AbstractRequest {

    protected $_order;

    public function __construct(Order $order) {

        $this->_order = $order;
    }

    /**
     * @return Response\AbstractResponse|Response\Order\Create
     * @throws Exception\InvalidResponse
     * @throws Exception\RateLimitReached
     */
    public function getResponse() : Response\AbstractResponse {

        $identifiers = [];
        foreach($this->_order->getSubjects() as $subject) {

            $identifiers[] = [
                'type' => 'dns',
                'value' => $subject
            ];
        }

        $payload = [
            'identifiers' => $identifiers,
            'notBefore' => '',
            'notAfter' => '',
        ];

        $kid = Utilities\RequestSigner::KID(
            $payload,
            Cache\AccountResponse::getInstance()->get($this->_order->getAccount())->getLocation(),
            Cache\DirectoryResponse::getInstance()->get()->getNewOrder(),
            Cache\NewNonceResponse::getInstance()->get()->getNonce(),
            $this->_order->getAccount()->getKeyDirectoryPath()
        );
        $result = Connector\Connector::getInstance()->request(
            Connector\Connector::METHOD_POST,
            Cache\DirectoryResponse::getInstance()->get()->getNewOrder(),
            $kid
        );

        return new Response\Order\Create($result);
    }
}