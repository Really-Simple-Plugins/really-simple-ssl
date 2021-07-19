<?php

namespace LE_ACME2\Response\Authorization\Struct;
defined('ABSPATH') or die();

class Identifier {

    public $type;
    public $value;

    public function __construct(string $type, string $value) {

        $this->type = $type;
        $this->value = $value;
    }
}