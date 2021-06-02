<?php

namespace LE_ACME2\Response\Authorization\Struct;

class Identifier {

    public $type;
    public $value;

    public function __construct(string $type, string $value) {

        $this->type = $type;
        $this->value = $value;
    }
}