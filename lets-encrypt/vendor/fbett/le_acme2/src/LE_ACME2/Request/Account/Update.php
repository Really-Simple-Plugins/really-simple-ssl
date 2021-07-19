<?php

namespace LE_ACME2\Request\Account;
defined('ABSPATH') or die();

use LE_ACME2\Response;

use LE_ACME2\Exception;

use LE_ACME2\Account;

class Update extends AbstractLocation {

    protected $_newEmail;

    public function __construct(Account $account, $newEmail) {

        parent::__construct($account);

        $this->_newEmail = $newEmail;
    }

    protected function _getPayload() : array {

        return [
            'contact' => $this->_buildContactPayload($this->_newEmail),
        ];
    }

    /**
     * @return Response\AbstractResponse|Response\Account\Update
     * @throws Exception\InvalidResponse
     * @throws Exception\RateLimitReached
     */
    public function getResponse() : Response\AbstractResponse {
        return new Response\Account\Update($this->_getRawResponse());
    }
}
