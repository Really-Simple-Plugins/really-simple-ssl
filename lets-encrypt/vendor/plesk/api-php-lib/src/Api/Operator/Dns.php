<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Operator;
defined('ABSPATH') or die();

use PleskX\Api\Struct\Dns as Struct;

class Dns extends \PleskX\Api\Operator
{
    /**
     * @param array $properties
     *
     * @return Struct\Info
     */
    public function create($properties)
    {
        $packet = $this->_client->getPacket();
        $info = $packet->addChild($this->_wrapperTag)->addChild('add_rec');

        foreach ($properties as $name => $value) {
            $info->addChild($name, $value);
        }

        return new Struct\Info($this->_client->request($packet));
    }

    /**
     * Send multiply records by one request.
     *
     * @param array $records
     *
     * @return \PleskX\Api\XmlResponse[]
     */
    public function bulkCreate(array $records)
    {
        $packet = $this->_client->getPacket();

        foreach ($records as $properties) {
            $info = $packet->addChild($this->_wrapperTag)->addChild('add_rec');

            foreach ($properties as $name => $value) {
                $info->addChild($name, $value);
            }
        }

        $response = $this->_client->request($packet, \PleskX\Api\Client::RESPONSE_FULL);
        $items = [];
        foreach ($response->xpath('//result') as $xmlResult) {
            $items[] = $xmlResult;
        }

        return $items;
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
    public function getAll($field, $value)
    {
        $packet = $this->_client->getPacket();
        $getTag = $packet->addChild($this->_wrapperTag)->addChild('get_rec');

        $filterTag = $getTag->addChild('filter');
        if (!is_null($field)) {
            $filterTag->addChild($field, $value);
        }

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
        return $this->_delete($field, $value, 'del_rec');
    }

    /**
     * Delete multiply records by one request.
     *
     * @param array $recordIds
     *
     * @return \PleskX\Api\XmlResponse[]
     */
    public function bulkDelete(array $recordIds)
    {
        $packet = $this->_client->getPacket();

        foreach ($recordIds as $recordId) {
            $packet->addChild($this->_wrapperTag)->addChild('del_rec')
                ->addChild('filter')->addChild('id', $recordId);
        }

        $response = $this->_client->request($packet, \PleskX\Api\Client::RESPONSE_FULL);
        $items = [];
        foreach ($response->xpath('//result') as $xmlResult) {
            $items[] = $xmlResult;
        }

        return $items;
    }
}
