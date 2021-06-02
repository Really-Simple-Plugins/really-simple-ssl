<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Struct\ServicePlan;

class Info extends \PleskX\Api\Struct
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var string */
    public $guid;

    /** @var string */
    public $externalId;

    public function __construct($apiResponse)
    {
        $this->_initScalarProperties($apiResponse, [
            'id',
            'name',
            'guid',
            'external-id',
        ]);
    }
}
