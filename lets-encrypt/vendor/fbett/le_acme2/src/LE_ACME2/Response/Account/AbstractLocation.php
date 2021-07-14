<?php

namespace LE_ACME2\Response\Account;
defined('ABSPATH') or die();

use LE_ACME2\Response\AbstractResponse;

abstract class AbstractLocation extends AbstractResponse {

    public function getKey() : string {
        return $this->_raw->body['key'];
    }

    public function getContact() : string {
        return $this->_raw->body['contact'];
    }

    public function getAgreement() : string {
        return $this->_raw->body['agreement'];
    }

    public function getInitialIP() : string {
        return $this->_raw->body['initialIp'];
    }

    public function getCreatedAt() : string {
        return $this->_raw->body['createdAt'];
    }

    public function getStatus() : string {
        return $this->_raw->body['status'];
    }
}