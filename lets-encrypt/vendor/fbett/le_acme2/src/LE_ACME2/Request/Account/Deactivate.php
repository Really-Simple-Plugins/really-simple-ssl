<?php

namespace LE_ACME2\Request\Account;

use LE_ACME2\Response;

use LE_ACME2\Exception;

class Deactivate extends AbstractLocation {

    protected function _getPayload() : array {

        return [
            'status' => 'deactivated',
        ];
    }

    /**
     * @return Response\AbstractResponse|Response\Account\Deactivate
     * @throws Exception\InvalidResponse
     * @throws Exception\RateLimitReached
     */
    public function getResponse() : Response\AbstractResponse {

        return new Response\Account\Deactivate($this->_getRawResponse());
    }
}
