<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Operator;

use PleskX\Api\Struct\Certificate as Struct;

class Certificate extends \PleskX\Api\Operator
{
    /**
     * @param array $properties
     *
     * @return Struct\Info
     */
    public function generate($properties)
    {
        $packet = $this->_client->getPacket();
        $info = $packet->addChild($this->_wrapperTag)->addChild('generate')->addChild('info');

        foreach ($properties as $name => $value) {
            $info->addChild($name, $value);
        }

        $response = $this->_client->request($packet);

        return new Struct\Info($response);
    }

	public function create($properties)
	{
		$packet = $this->_client->getPacket();
		$info = $packet->addChild($this->_wrapperTag)->addChild('add')->addChild('gen_info');

		foreach ($properties as $name => $value) {
			$info->addChild($name, $value);
		}

		$response = $this->_client->request($packet);

		return new Struct\Info($response);
	}

	public function install($domains, $properties)
	{
		$packet = $this->_client->getPacket();
		foreach ($domains as $domain) {
			$install = $packet->addChild($this->_wrapperTag)->addChild('install');
			$install->addChild('name', $domain);
			$install->addChild('webspace', $domain);
			$content = $install->addChild('content');

			foreach ($properties as $name => $value) {
				$content->addChild($name, $value);
			}

		}


		$response = $this->_client->request($packet);

		return new Struct\Info($response);
	}

}
