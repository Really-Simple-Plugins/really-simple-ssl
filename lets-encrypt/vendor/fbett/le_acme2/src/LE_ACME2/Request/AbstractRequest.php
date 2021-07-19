<?php

namespace LE_ACME2\Request;
defined('ABSPATH') or die();

use LE_ACME2\Response\AbstractResponse;

use LE_ACME2\Exception;

abstract class AbstractRequest {

    /**
     * @return AbstractResponse
     * @throws Exception\InvalidResponse
     * @throws Exception\RateLimitReached
     */
    abstract public function getResponse() : AbstractResponse;

    protected function _buildContactPayload(string $email) : array {

        $result = [
            'mailto:' . $email
        ];
        return $result;
    }
}