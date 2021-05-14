<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Struct\Server;

class Admin extends \PleskX\Api\Struct
{
    /** @var string */
    public $companyName;

    /** @var string */
    public $name;

    /** @var string */
    public $email;

    public function __construct($apiResponse)
    {
        $this->_initScalarProperties($apiResponse, [
            ['admin_cname' => 'companyName'],
            ['admin_pname' => 'name'],
            ['admin_email' => 'email'],
        ]);
    }
}
