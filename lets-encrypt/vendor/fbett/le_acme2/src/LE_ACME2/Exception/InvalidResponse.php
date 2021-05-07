<?php

namespace LE_ACME2\Exception;
defined( 'ABSPATH' ) or die();
use LE_ACME2\Connector\RawResponse;

class InvalidResponse extends AbstractException {

    public function __construct(RawResponse $raw) {
        parent::__construct(json_encode(var_export($raw, true)));
    }
}