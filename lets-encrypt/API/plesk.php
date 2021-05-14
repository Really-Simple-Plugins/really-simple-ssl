<?php
/**
 *
 * @package Plesk
 * This PHP app issues and installs free SSL certificates in Plesk shared hosting with complete automation.
 *
 * @author rogier lankhorst
 * @copyright  Copyright (C) 2020-2021, Rogier Lankhorst
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
 *
 *
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */
require_once rsssl_le_path . 'vendor/autoload.php';

class rsssl_plesk
{
	private $cpanel_host;
	private $username;
	private $password;
	public $ssl_installation_url;

	/**
	 * Initiates the Plesk class.
	 *
	 * @param string $cpanel_host
	 * @param string $username
	 * @param string $password
	 */
	public function __construct($cpanel_host, $username='', $password='')
	{
		$this->cpanel_host =  str_replace(array('http://', 'https://', ':2083'), '', $cpanel_host);;
		$this->username = $username;
		$this->password = $password;
		$this->ssl_installation_url = $this->cpanel_host.":2083/frontend/paper_lantern/ssl/index.html";

		$client = new \PleskX\Api\Client($host);
		$client->setCredentials($login, $password);

		$client->certificate()->install([
			'cname' => 'Plesk',
			'pname' => 'John Smith',
			'login' => 'john',
			'passwd' => 'secret',
			'email' => 'john@smith.com',
		]);


	}

	public function install(){?>
		<packet>
<certificate>
<install>
   <name>common</name>
   <admin/>
   <content>
      <csr></csr>
      <pvt></pvt>
      <cert></cert>
        <ca></ca>
   </content>
</install>
</certificate>
</packet>
<?php
	}



}

