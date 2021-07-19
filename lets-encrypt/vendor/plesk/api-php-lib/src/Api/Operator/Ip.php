<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Operator;
defined('ABSPATH') or die();
use PleskX\Api\Struct\Ip as Struct;

class Ip extends \PleskX\Api\Operator
{
    /**
     * @return Struct\Info[]
     */
    public function get()
    {
        $ips = [];
        $packet = $this->_client->getPacket();
        $packet->addChild($this->_wrapperTag)->addChild('get');
        $response = $this->_client->request($packet);

        foreach ($response->addresses->ip_info as $ipInfo) {
            $ips[] = new Struct\Info($ipInfo);
        }

        return $ips;
    }
}
