<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Struct\Database;

class UserInfo extends \PleskX\Api\Struct
{
    /** @var int */
    public $id;

    /** @var string */
    public $login;

    /** @var int */
    public $dbId;

    public function __construct($apiResponse)
    {
        $this->_initScalarProperties($apiResponse, [
            'id',
            'login',
            'db-id',
        ]);
    }
}
