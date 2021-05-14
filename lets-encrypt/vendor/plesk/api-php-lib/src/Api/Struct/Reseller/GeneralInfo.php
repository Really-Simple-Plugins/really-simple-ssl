<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Struct\Reseller;

class GeneralInfo extends \PleskX\Api\Struct
{
    /** @var int */
    public $id;

    /** @var string */
    public $personalName;

    /** @var string */
    public $login;

    /** @var array */
    public $permissions;

    public function __construct($apiResponse)
    {
        $this->_initScalarProperties($apiResponse->{'gen-info'}, [
            ['pname' => 'personalName'],
            'login',
        ]);

        $this->permissions = [];
        foreach ($apiResponse->permissions->permission as $permissionInfo) {
            $this->permissions[(string) $permissionInfo->name] = (string) $permissionInfo->value;
        }
    }
}
