<?php
namespace PleskX\Api\Operator;
require_once rsssl_le_path . 'vendor/autoload.php';

use PleskX\Api\Struct\CertificateInstaller as Struct;

class CertificateInstaller extends \PleskX\Api\Operator\Certificate
{
	/**
	 * @param array $properties
	 *
	 * @return Struct\Info
	 */
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