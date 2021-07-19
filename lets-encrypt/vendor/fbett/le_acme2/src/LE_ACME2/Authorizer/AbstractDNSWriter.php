<?php

namespace LE_ACME2\Authorizer;
defined('ABSPATH') or die();
use LE_ACME2\Order;

abstract class AbstractDNSWriter {

    /**
     * @param Order $order
     * @param string $identifier
     * @param string $digest
     *
     * @return bool return true, if the dns configuration is usable and the process should be progressed
     */
    abstract public function write(Order $order, string $identifier, string $digest) : bool;
}