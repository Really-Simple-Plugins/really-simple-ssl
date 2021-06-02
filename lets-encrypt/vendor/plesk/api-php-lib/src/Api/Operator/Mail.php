<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Operator;

use PleskX\Api\Struct\Mail as Struct;

class Mail extends \PleskX\Api\Operator
{
    /**
     * @param string $name
     * @param int $siteId
     * @param bool $mailbox
     * @param string $password
     *
     * @return Struct\Info
     */
    public function create($name, $siteId, $mailbox = false, $password = '')
    {
        $packet = $this->_client->getPacket();
        $info = $packet->addChild($this->_wrapperTag)->addChild('create');

        $filter = $info->addChild('filter');
        $filter->addChild('site-id', $siteId);
        $mailname = $filter->addChild('mailname');
        $mailname->addChild('name', $name);
        if ($mailbox) {
            $mailname->addChild('mailbox')->addChild('enabled', 'true');
        }
        if (!empty($password)) {
            $mailname->addChild('password')->addChild('value', $password);
        }

        $response = $this->_client->request($packet);

        return new Struct\Info($response->mailname);
    }

    /**
     * @param string $field
     * @param int|string $value
     * @param int $siteId
     *
     * @return bool
     */
    public function delete($field, $value, $siteId)
    {
        $packet = $this->_client->getPacket();
        $filter = $packet->addChild($this->_wrapperTag)->addChild('remove')->addChild('filter');
        $filter->addChild('site-id', $siteId);
        $filter->addChild($field, $value);
        $response = $this->_client->request($packet);

        return 'ok' === (string) $response->status;
    }
}
