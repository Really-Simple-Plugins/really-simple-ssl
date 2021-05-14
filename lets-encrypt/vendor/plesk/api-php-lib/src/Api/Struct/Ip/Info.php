<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Struct\Ip;

class Info extends \PleskX\Api\Struct
{
    /** @var string */
    public $ipAddress;

    /** @var string */
    public $netmask;

    /** @var string */
    public $type;

    /** @var string */
    public $interface;

    public function __construct($apiResponse)
    {
        $this->_initScalarProperties($apiResponse, [
            'ip_address',
            'netmask',
            'type',
            'interface',
        ]);
    }
}
