<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Struct\Site;
defined('ABSPATH') or die();
class GeneralInfo extends \PleskX\Api\Struct
{
    /** @var string */
    public $name;

    /** @var string */
    public $asciiName;

    /** @var string */
    public $guid;

    /** @var string */
    public $status;

    /** @var string */
    public $description;

    public function __construct($apiResponse)
    {
        $this->_initScalarProperties($apiResponse, [
            'name',
            'ascii-name',
            'status',
            'guid',
            'description',
        ]);
    }
}
