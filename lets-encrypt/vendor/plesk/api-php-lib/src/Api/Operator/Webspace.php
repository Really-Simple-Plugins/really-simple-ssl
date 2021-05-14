<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Operator;

use PleskX\Api\Struct\Webspace as Struct;

class Webspace extends \PleskX\Api\Operator
{
    public function getPermissionDescriptor()
    {
        $response = $this->request('get-permission-descriptor.filter');

        return new Struct\PermissionDescriptor($response);
    }

    public function getLimitDescriptor()
    {
        $response = $this->request('get-limit-descriptor.filter');

        return new Struct\LimitDescriptor($response);
    }

    public function getPhysicalHostingDescriptor()
    {
        $response = $this->request('get-physical-hosting-descriptor.filter');

        return new Struct\PhysicalHostingDescriptor($response);
    }

    /**
     * @param string $field
     * @param int|string $value
     *
     * @return Struct\PhpSettings
     */
    public function getPhpSettings($field, $value)
    {
        $packet = $this->_client->getPacket();
        $getTag = $packet->addChild($this->_wrapperTag)->addChild('get');

        $getTag->addChild('filter')->addChild($field, $value);
        $getTag->addChild('dataset')->addChild('php-settings');

        $response = $this->_client->request($packet, \PleskX\Api\Client::RESPONSE_FULL);

        return new Struct\PhpSettings($response);
    }

    /**
     * @param string $field
     * @param int|string $value
     *
     * @return Struct\Limits
     */
    public function getLimits($field, $value)
    {
        $items = $this->_getItems(Struct\Limits::class, 'limits', $field, $value);

        return reset($items);
    }

    /**
     * @param array $properties
     * @param array|null $hostingProperties
     * @param $planName
     *
     * @return Struct\Info
     */
    public function create(array $properties, array $hostingProperties = null, $planName = null)
    {
        $packet = $this->_client->getPacket();
        $info = $packet->addChild($this->_wrapperTag)->addChild('add');

        $infoGeneral = $info->addChild('gen_setup');
        foreach ($properties as $name => $value) {
            $infoGeneral->addChild($name, $value);
        }

        if ($hostingProperties) {
            $infoHosting = $info->addChild('hosting')->addChild('vrt_hst');
            foreach ($hostingProperties as $name => $value) {
                $property = $infoHosting->addChild('property');
                $property->addChild('name', $name);
                $property->addChild('value', $value);
            }

            if (isset($properties['ip_address'])) {
                $infoHosting->addChild('ip_address', $properties['ip_address']);
            }
        }

        if ($planName) {
            $info->addChild('plan-name', $planName);
        }

        $response = $this->_client->request($packet);

        return new Struct\Info($response);
    }

    /**
     * @param string $field
     * @param int|string $value
     *
     * @return bool
     */
    public function delete($field, $value)
    {
        return $this->_delete($field, $value);
    }

    /**
     * @param string $field
     * @param int|string $value
     *
     * @return Struct\GeneralInfo
     */
    public function get($field, $value)
    {
        $items = $this->_getItems(Struct\GeneralInfo::class, 'gen_info', $field, $value);

        return reset($items);
    }

    /**
     * @return Struct\GeneralInfo[]
     */
    public function getAll()
    {
        return $this->_getItems(Struct\GeneralInfo::class, 'gen_info');
    }

    /**
     * @param string $field
     * @param int|string $value
     *
     * @return Struct\DiskUsage
     */
    public function getDiskUsage($field, $value)
    {
        $items = $this->_getItems(Struct\DiskUsage::class, 'disk_usage', $field, $value);

        return reset($items);
    }
}
