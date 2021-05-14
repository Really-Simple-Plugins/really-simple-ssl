<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Struct\Server;

class SessionPreferences extends \PleskX\Api\Struct
{
    /** @var int */
    public $loginTimeout;

    public function __construct($apiResponse)
    {
        $this->_initScalarProperties($apiResponse, [
            'login_timeout',
        ]);
    }
}
