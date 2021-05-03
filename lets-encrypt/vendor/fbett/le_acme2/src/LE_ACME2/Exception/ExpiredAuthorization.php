<?php

namespace LE_ACME2\Exception;

class ExpiredAuthorization extends AbstractException {

    public function __construct() {
        parent::__construct("Expired authorization received");
    }
}