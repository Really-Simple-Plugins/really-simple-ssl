<?php

namespace LE_ACME2\Response\Account;

use LE_ACME2\Response\AbstractResponse;

abstract class AbstractAccount extends AbstractResponse {

    const STATUS_VALID = 'valid';


    public function getLocation() : string {

        $matches = $this->_preg_match_headerLine($this->_pattern_header_location);
        return trim($matches[1]);
    }
}