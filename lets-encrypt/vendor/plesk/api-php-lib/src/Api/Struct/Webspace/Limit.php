<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Struct\Webspace;

class Limit extends \PleskX\Api\Struct
{
    /** @var string */
    public $name;

    /** @var string */
    public $value;

    public function __construct($apiResponse)
    {
        $this->_initScalarProperties($apiResponse, [
            'name',
            'value',
        ]);
    }
}
