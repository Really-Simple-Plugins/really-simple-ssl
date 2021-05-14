<?php
namespace PleskX\Api\Operator;
require_once rsssl_le_path . 'vendor/autoload.php';

use PleskX\Api\Struct\CertificateInstaller as Struct;

class CertificateInstaller extends \PleskX\Api\Operator
{
	/**
	 * @param array $properties
	 *
	 * @return Struct\Info
	 */
	public function install($properties)
	{
		$packet = $this->_client->getPacket();
		$info = $packet->addChild($this->_wrapperTag)->addChild('add')->addChild('gen_info');

		foreach ($properties as $name => $value) {
			$info->addChild($name, $value);
		}

		$response = $this->_client->request($packet);

		return new Struct\Info($response);
	}
}