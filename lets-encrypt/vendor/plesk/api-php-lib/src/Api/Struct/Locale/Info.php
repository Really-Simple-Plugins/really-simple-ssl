<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Struct\Locale;
defined('ABSPATH') or die();
class Info extends \PleskX\Api\Struct
{
    /** @var string */
    public $id;

    /** @var string */
    public $language;

    /** @var string */
    public $country;

    public function __construct($apiResponse)
    {
        $this->_initScalarProperties($apiResponse, [
            'id',
            ['lang' => 'language'],
            'country',
        ]);
    }
}
