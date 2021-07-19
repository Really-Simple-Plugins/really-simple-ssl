<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Struct\Site;
defined('ABSPATH') or die();
class Info extends \PleskX\Api\Struct
{
    /** @var int */
    public $id;

    /** @var string */
    public $guid;

    public function __construct($apiResponse)
    {
        $this->_initScalarProperties($apiResponse, [
            'id',
            'guid',
        ]);
    }
}
