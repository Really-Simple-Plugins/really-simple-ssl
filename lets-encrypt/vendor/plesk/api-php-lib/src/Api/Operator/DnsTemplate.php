<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Operator;

use PleskX\Api\Struct\Dns as Struct;

class DnsTemplate extends \PleskX\Api\Operator
{
    protected $_wrapperTag = 'dns';

    /**
     * @param array $properties
     *
     * @return Struct\Info
     */
    public function create(array $properties)
    {
        $packet = $this->_client->getPacket();
        $info = $packet->addChild($this->_wrapperTag)->addChild('add_rec');

        unset($properties['site-id'], $properties['site-alias-id']);
        foreach ($properties as $name => $value) {
            $info->addChild($name, $value);
        }

        return new Struct\Info($this->_client->request($packet));
    }

    /**
     * @param string $field
     * @param int|string $value
     *
     * @return Struct\Info|null
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
        $getTag = $packet->addChild($this->_wrapperTag)->addChild('get_rec');

        $filterTag = $getTag->addChild('filter');
        if (!is_null($field)) {
            $filterTag->addChild($field, $value);
        }
        $getTag->addChild('template');

        $response = $this->_client->request($packet, \PleskX\Api\Client::RESPONSE_FULL);
        $items = [];
        foreach ($response->xpath('//result') as $xmlResult) {
            $item = new Struct\Info($xmlResult->data);
            $item->id = (int) $xmlResult->id;
            $items[] = $item;
        }

        return $items;
    }

    /**
     * @param string $field
     * @param int|string $value
     *
     * @return bool
     */
    public function delete($field, $value)
    {
        $packet = $this->_client->getPacket();
        $delTag = $packet->addChild($this->_wrapperTag)->addChild('del_rec');
        $delTag->addChild('filter')->addChild($field, $value);
        $delTag->addChild('template');

        $response = $this->_client->request($packet);

        return 'ok' === (string) $response->status;
    }
}
