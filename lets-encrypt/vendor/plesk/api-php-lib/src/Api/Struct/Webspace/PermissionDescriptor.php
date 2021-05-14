<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Struct\Webspace;

class PermissionDescriptor extends \PleskX\Api\Struct
{
    /** @var array */
    public $permissions;

    public function __construct($apiResponse)
    {
        $this->permissions = [];

        foreach ($apiResponse->descriptor->property as $propertyInfo) {
            $this->permissions[(string) $propertyInfo->name] = new PermissionInfo($propertyInfo);
        }
    }
}
