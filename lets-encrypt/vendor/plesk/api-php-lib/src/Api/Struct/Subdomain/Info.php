<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Struct\Subdomain;

class Info extends \PleskX\Api\Struct
{
    /** @var int */
    public $id;

    /** @var string */
    public $parent;

    /** @var string */
    public $name;

    /** @var array */
    public $properties;

    public function __construct($apiResponse)
    {
        $this->properties = [];
        $this->_initScalarProperties($apiResponse, [
            'id',
            'parent',
            'name',
        ]);
        foreach ($apiResponse->property as $propertyInfo) {
            $this->properties[(string) $propertyInfo->name] = (string) $propertyInfo->value;
        }
    }
}
