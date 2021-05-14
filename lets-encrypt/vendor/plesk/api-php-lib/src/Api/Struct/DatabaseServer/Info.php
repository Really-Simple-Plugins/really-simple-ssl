<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Struct\DatabaseServer;

class Info extends \PleskX\Api\Struct
{
    /** @var int */
    public $id;

    /** @var string */
    public $host;

    /** @var int */
    public $port;

    /** @var string */
    public $type;

    public function __construct($apiResponse)
    {
        $this->_initScalarProperties($apiResponse, [
            'id',
            'host',
            'port',
            'type',
        ]);
    }
}
