<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Struct\Database;

class Info extends \PleskX\Api\Struct
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var string */
    public $type;

    /** @var int */
    public $webspaceId;

    /** @var int */
    public $dbServerId;

    /** @var int */
    public $defaultUserId;

    public function __construct($apiResponse)
    {
        $this->_initScalarProperties($apiResponse, [
            'id',
            'name',
            'type',
            'webspace-id',
            'db-server-id',
            'default-user-id',
        ]);
    }
}
