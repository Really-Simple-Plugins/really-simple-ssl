<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Operator;

use PleskX\Api\Struct\ProtectedDirectory as Struct;

class ProtectedDirectory extends \PleskX\Api\Operator
{
    protected $_wrapperTag = 'protected-dir';

    /**
     * @param string $name
     * @param int $siteId
     * @param string $header
     *
     * @return Struct\Info
     */
    public function add($name, $siteId, $header = '')
    {
        $packet = $this->_client->getPacket();
        $info = $packet->addChild($this->_wrapperTag)->addChild('add');

        $info->addChild('site-id', $siteId);
        $info->addChild('name', $name);
        $info->addChild('header', $header);

        return new Struct\Info($this->_client->request($packet));
    }

    /**
     * @param string $field
     * @param int|string $value
     *
     * @return bool
     */
    public function delete($field, $value)
    {
        return $this->_delete($field, $value, 'delete');
    }

    /**
     * @param string $field
     * @param int|string $value
     *
     * @return Struct\DataInfo|false
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
     * @return Struct\DataInfo[]
     */
    public function getAll($field, $value)
    {
        $response = $this->_get('get', $field, $value);
        $items = [];
        foreach ($response->xpath('//result/data') as $xmlResult) {
            $items[] = new Struct\DataInfo($xmlResult);
        }

        return $items;
    }

    /**
     * @param Struct\Info $protectedDirectory
     * @param string $login
     * @param string $password
     *
     * @return Struct\UserInfo
     */
    public function addUser($protectedDirectory, $login, $password)
    {
        $packet = $this->_client->getPacket();
        $info = $packet->addChild($this->_wrapperTag)->addChild('add-user');

        $info->addChild('pd-id', $protectedDirectory->id);
        $info->addChild('login', $login);
        $info->addChild('password', $password);

        return new Struct\UserInfo($this->_client->request($packet));
    }

    /**
     * @param string $field
     * @param int|string $value
     *
     * @return bool
     */
    public function deleteUser($field, $value)
    {
        return $this->_delete($field, $value, 'delete-user');
    }

    /**
     * @param $command
     * @param $field
     * @param $value
     *
     * @return \PleskX\Api\XmlResponse
     */
    private function _get($command, $field, $value)
    {
        $packet = $this->_client->getPacket();
        $getTag = $packet->addChild($this->_wrapperTag)->addChild($command);

        $filterTag = $getTag->addChild('filter');
        if (!is_null($field)) {
            $filterTag->addChild($field, $value);
        }

        $response = $this->_client->request($packet, \PleskX\Api\Client::RESPONSE_FULL);

        return $response;
    }
}
