<?php

namespace LE_ACME2\Request;

use LE_ACME2\Response;

use LE_ACME2\Connector;
use LE_ACME2\Cache;
use LE_ACME2\Exception;

class GetNewNonce extends AbstractRequest {

    /**
     * @return Response\AbstractResponse|Response\GetNewNonce
     * @throws Exception\InvalidResponse
     * @throws Exception\RateLimitReached
     */
    public function getResponse() : Response\AbstractResponse {

        $result = Connector\Connector::getInstance()->request(
            Connector\Connector::METHOD_HEAD,
            Cache\DirectoryResponse::getInstance()->get()->getNewNonce()
        );

        return new Response\GetNewNonce($result);
    }
}