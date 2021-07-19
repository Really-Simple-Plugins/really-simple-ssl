<?php

namespace LE_ACME2\Request;
defined('ABSPATH') or die();

use LE_ACME2\Response;

use LE_ACME2\Connector\Connector;
use LE_ACME2\Exception;

class GetDirectory extends AbstractRequest {

    /**
     * @return Response\AbstractResponse|Response\GetDirectory
     * @throws Exception\InvalidResponse
     * @throws Exception\RateLimitReached
     */
    public function getResponse() : Response\AbstractResponse {

        $connector = Connector::getInstance();

        $result = $connector->request(
            Connector::METHOD_GET,
             $connector->getBaseURL() . '/directory'
        );
        return new Response\GetDirectory($result);
    }
}