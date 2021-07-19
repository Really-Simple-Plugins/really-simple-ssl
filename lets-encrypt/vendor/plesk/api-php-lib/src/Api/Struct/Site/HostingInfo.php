<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Struct\Site;
defined('ABSPATH') or die();
class HostingInfo extends \PleskX\Api\Struct
{
    /** @var array */
    public $properties = [];

    /** @var string */
    public $ipAddress;

    public function __construct($apiResponse)
    {
        foreach ($apiResponse->vrt_hst->property as $property) {
            $this->properties[(string) $property->name] = (string) $property->value;
        }
        $this->_initScalarProperties($apiResponse->vrt_hst, [
            'ip_address',
        ]);
    }
}
