<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Operator;

use PleskX\Api\Struct\SecretKey as Struct;

class SecretKey extends \PleskX\Api\Operator
{
    protected $_wrapperTag = 'secret_key';

    /**
     * @param string $ipAddress
     *
     * @return string
     */
    public function create($ipAddress)
    {
        $packet = $this->_client->getPacket();
        $packet->addChild($this->_wrapperTag)->addChild('create')->addChild('ip_address', $ipAddress);
        $response = $this->_client->request($packet);

        return (string) $response->key;
    }

    /**
     * @param string $keyId
     *
     * @return bool
     */
    public function delete($keyId)
    {
        return $this->_delete('key', $keyId, 'delete');
    }

    /**
     * @param string $keyId
     *
     * @return Struct\Info
     */
    public function get($keyId)
    {
        $items = $this->_get($keyId);

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
     * @param string|null $keyId
     *
     * @return Struct\Info[]
     */
    public function _get($keyId = null)
    {
        $packet = $this->_client->getPacket();
        $getTag = $packet->addChild($this->_wrapperTag)->addChild('get_info');

        $filterTag = $getTag->addChild('filter');
        if (!is_null($keyId)) {
            $filterTag->addChild('key', $keyId);
        }

        $response = $this->_client->request($packet, \PleskX\Api\Client::RESPONSE_FULL);

        $items = [];
        foreach ($response->xpath('//result/key_info') as $keyInfo) {
            $items[] = new Struct\Info($keyInfo);
        }

        return $items;
    }
}
