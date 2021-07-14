<?php

namespace LE_ACME2\Response\Authorization;
defined('ABSPATH') or die();

use LE_ACME2\Response\AbstractResponse;

use LE_ACME2\Connector\RawResponse;
use LE_ACME2\Exception;

class AbstractAuthorization extends AbstractResponse {

    /**
     * AbstractAuthorization constructor.
     * @param RawResponse $raw
     * @throws Exception\InvalidResponse
     * @throws Exception\RateLimitReached
     * @throws Exception\ExpiredAuthorization
     */
    public function __construct(RawResponse $raw) {
        parent::__construct($raw);
    }

    /**
     * @return bool
     * @throws Exception\ExpiredAuthorization
     */
    protected function _isValid() : bool {

        if($this->_preg_match_headerLine('/^HTTP\/.* 404/i') !== null) {
            throw new Exception\ExpiredAuthorization();
        }

        return parent::_isValid();
    }
}