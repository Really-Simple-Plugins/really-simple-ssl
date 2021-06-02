<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Struct\Webspace;

class Limits extends \PleskX\Api\Struct
{
    /** @var string */
    public $overuse;

    /** @var array */
    public $limits;

    public function __construct($apiResponse)
    {
        $this->_initScalarProperties($apiResponse, ['overuse']);
        $this->limits = [];

        foreach ($apiResponse->limit as $limit) {
            $this->limits[(string) $limit->name] = new Limit($limit);
        }
    }
}
