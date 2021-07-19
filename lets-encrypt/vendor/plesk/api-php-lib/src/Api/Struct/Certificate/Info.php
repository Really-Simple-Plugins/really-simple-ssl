<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Struct\Certificate;
defined('ABSPATH') or die();
class Info extends \PleskX\Api\Struct
{
    /** @var string */
    public $request;

    /** @var string */
    public $privateKey;

    public function __construct($apiResponse)
    {
        $this->_initScalarProperties($apiResponse, [
            ['csr' => 'request'],
            ['pvt' => 'privateKey'],
        ]);
    }
}
