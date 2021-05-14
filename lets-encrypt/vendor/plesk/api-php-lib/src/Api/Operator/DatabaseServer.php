<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Operator;

use PleskX\Api\Struct\DatabaseServer as Struct;

class DatabaseServer extends \PleskX\Api\Operator
{
    protected $_wrapperTag = 'db_server';

    /**
     * @return array
     */
    public function getSupportedTypes()
    {
        $response = $this->request('get-supported-types');

        return (array) $response->type;
    }

    /**
     * @param string $field
     * @param int|string $value
     *
     * @return Struct\Info
     */
    public function get($field, $value)
    {
        $items = $this->_get($field, $value);

        return reset($items);
    }

    /**
     * @return Struct\Info[]
     */
    public function getAll()
    {
        return $this->_get();
    }

    /**
     * @param string|null $field
     * @param int|string|null $value
     *
     * @return Struct\Info|Struct\Info[]
     */
    private function _get($field = null, $value = null)
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
            $item = new Struct\Info($xmlResult->data);
            $item->id = (int) $xmlResult->id;
            $items[] = $item;
        }

        return $items;
    }
}
