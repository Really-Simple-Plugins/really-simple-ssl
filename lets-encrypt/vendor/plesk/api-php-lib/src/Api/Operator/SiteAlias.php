<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Operator;
defined('ABSPATH') or die();
use PleskX\Api\Struct\SiteAlias as Struct;

class SiteAlias extends \PleskX\Api\Operator
{
    /**
     * @param array $properties
     * @param array $preferences
     *
     * @return Struct\Info
     */
    public function create(array $properties, array $preferences = [])
    {
        $packet = $this->_client->getPacket();
        $info = $packet->addChild($this->_wrapperTag)->addChild('create');

        if (count($preferences) > 0) {
            $prefs = $info->addChild('pref');

            foreach ($preferences as $key => $value) {
                $prefs->addChild($key, is_bool($value) ? ($value ? 1 : 0) : $value);
            }
        }

        $info->addChild('site-id', $properties['site-id']);
        $info->addChild('name', $properties['name']);

        $response = $this->_client->request($packet);

        return new Struct\Info($response);
    }

    /**
     * @param string $field
     * @param int|string $value
     *
     * @return Struct\Info
     */
    public function get($field, $value)
    {
        $items = $this->getAll($field, $value);

        return reset($items);
    }

    /**
     * @param string $field
     * @param int|string $value
     *
     * @return Struct\Info[]
     */
    public function getAll($field = null, $value = null)
    {
        $packet = $this->_client->getPacket();
        $getTag = $packet->addChild($this->_wrapperTag)->addChild('get');

        $filterTag = $getTag->addChild('filter');
        if (!is_null($field)) {
            $filterTag->addChild($field, $value);
        }

        $response = $this->_client->request($packet, \PleskX\Api\Client::RESPONSE_FULL);
        $items = [];
        foreach ($response->xpath('//result') as $xmlResult) {
            $item = new Struct\GeneralInfo($xmlResult->info);
            $items[] = $item;
        }

        return $items;
    }
}
