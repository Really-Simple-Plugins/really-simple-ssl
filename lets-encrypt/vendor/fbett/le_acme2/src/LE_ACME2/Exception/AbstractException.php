<?php

namespace LE_ACME2\Exception;
defined('ABSPATH') or die();

use LE_ACME2\Utilities;

abstract class AbstractException extends \Exception {

    public function __construct(string $message) {

        Utilities\Logger::getInstance()->add(
            Utilities\Logger::LEVEL_DEBUG,
            'Exception "' . get_called_class() . '" thrown '
        );

        parent::__construct($message);
    }
}