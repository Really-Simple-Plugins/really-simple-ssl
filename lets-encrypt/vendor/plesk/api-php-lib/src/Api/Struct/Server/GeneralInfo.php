<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Struct\Server;

class GeneralInfo extends \PleskX\Api\Struct
{
    /** @var string */
    public $serverName;

    /** @var string */
    public $serverGuid;

    /** @var string */
    public $mode;

    public function __construct($apiResponse)
    {
        $this->_initScalarProperties($apiResponse, [
            'server_name',
            'server_guid',
            'mode',
        ]);
    }
}
