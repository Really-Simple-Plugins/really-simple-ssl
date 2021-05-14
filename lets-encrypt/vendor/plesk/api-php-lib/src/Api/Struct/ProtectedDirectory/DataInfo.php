<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Struct\ProtectedDirectory;

use PleskX\Api\Struct;

class DataInfo extends Struct
{
    /** @var string */
    public $name;

    /** @var string */
    public $header;

    public function __construct($apiResponse)
    {
        $this->_initScalarProperties($apiResponse, [
            'name',
            'header',
        ]);
    }
}
