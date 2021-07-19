<?php

namespace LE_ACME2\Response\Order;
defined('ABSPATH') or die();

use LE_ACME2\Response\AbstractResponse;
use LE_ACME2\Exception;

abstract class AbstractOrder extends AbstractResponse {

    const STATUS_PENDING = 'pending';
    const STATUS_VALID = 'valid';
    const STATUS_READY = 'ready';
    const STATUS_INVALID = 'invalid';

    public function getLocation() : string {

        $matches = $this->_preg_match_headerLine($this->_pattern_header_location);
        return trim($matches[1]);
    }

    public function getStatus() : string {
        return $this->_raw->body['status'];
    }

    public function getExpires() : string {
        return $this->_raw->body['expires'];
    }

    public function getIdentifiers() : array {
        return $this->_raw->body['identifiers'];
    }

    public function getAuthorizations() : array {
        return $this->_raw->body['authorizations'];
    }

    public function getFinalize() : string {
        return $this->_raw->body['finalize'];
    }

    public function getCertificate() : string {
        return $this->_raw->body['certificate'];
    }

    /**
     * @return bool
     * @throws Exception\StatusInvalid
     */
    protected function _isValid(): bool {

        if(!parent::_isValid()) {
            return false;
        }

        if(
            $this->getStatus() == AbstractOrder::STATUS_INVALID
        ) {
            throw new Exception\StatusInvalid('Order has status "' . AbstractOrder::STATUS_INVALID . '"'.
                '. Probably all authorizations have failed. ' . PHP_EOL .
                'Please see: ' . $this->getLocation() . PHP_EOL .
                'Continue by using $order->clear() after getting rid of the problem'
            );
        }

        return true;
    }
}