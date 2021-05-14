<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Operator;

use PleskX\Api\Struct\Ui as Struct;

class Ui extends \PleskX\Api\Operator
{
    /**
     * @return array
     */
    public function getNavigation()
    {
        $response = $this->request('get-navigation');

        return unserialize(base64_decode($response->navigation));
    }

    /**
     * @param string $owner
     * @param array $properties
     *
     * @return int
     */
    public function createCustomButton($owner, $properties)
    {
        $packet = $this->_client->getPacket();
        $buttonNode = $packet->addChild($this->_wrapperTag)->addChild('create-custombutton');
        $buttonNode->addChild('owner')->addChild($owner);
        $propertiesNode = $buttonNode->addChild('properties');

        foreach ($properties as $name => $value) {
            $propertiesNode->addChild($name, $value);
        }

        $response = $this->_client->request($packet);

        return (int) $response->id;
    }

    /**
     * @param int $id
     *
     * @return Struct\CustomButton
     */
    public function getCustomButton($id)
    {
        $response = $this->request("get-custombutton.filter.custombutton-id=$id");

        return new Struct\CustomButton($response);
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public function deleteCustomButton($id)
    {
        return $this->_delete('custombutton-id', $id, 'delete-custombutton');
    }
}
