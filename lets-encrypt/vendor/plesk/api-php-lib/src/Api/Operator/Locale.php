<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Operator;
defined('ABSPATH') or die();
use PleskX\Api\Struct\Locale as Struct;

class Locale extends \PleskX\Api\Operator
{
    /**
     * @param string|null $id
     *
     * @return Struct\Info|Struct\Info[]
     */
    public function get($id = null)
    {
        $locales = [];
        $packet = $this->_client->getPacket();
        $filter = $packet->addChild($this->_wrapperTag)->addChild('get')->addChild('filter');

        if (!is_null($id)) {
            $filter->addChild('id', $id);
        }

        $response = $this->_client->request($packet, \PleskX\Api\Client::RESPONSE_FULL);

        foreach ($response->locale->get->result as $localeInfo) {
            $locales[(string) $localeInfo->info->id] = new Struct\Info($localeInfo->info);
        }

        return !is_null($id) ? reset($locales) : $locales;
    }
}
