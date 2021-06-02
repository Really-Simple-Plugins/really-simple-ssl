<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Struct\Webspace;

class PhpSettings extends \PleskX\Api\Struct
{
    /** @var array */
    public $properties;

    public function __construct($apiResponse)
    {
        $this->properties = [];

        foreach ($apiResponse->webspace->get->result->data->{'php-settings'}->setting as $setting) {
            $this->properties[(string) $setting->name] = (string) $setting->value;
        }
    }
}
