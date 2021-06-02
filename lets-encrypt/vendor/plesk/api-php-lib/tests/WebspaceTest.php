<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskXTest;

use PleskXTest\Utility\PasswordProvider;

class WebspaceTest extends TestCase
{
    public function testGetPermissionDescriptor()
    {
        $descriptor = static::$_client->webspace()->getPermissionDescriptor();
        $this->assertIsArray($descriptor->permissions);
        $this->assertNotEmpty($descriptor->permissions);
    }

    public function testGetLimitDescriptor()
    {
        $descriptor = static::$_client->webspace()->getLimitDescriptor();
        $this->assertIsArray($descriptor->limits);
        $this->assertNotEmpty($descriptor->limits);
    }

    public function testGetDiskUsage()
    {
        $webspace = static::_createWebspace();
        $diskusage = static::$_client->webspace()->getDiskUsage('id', $webspace->id);

        $this->assertObjectHasAttribute('httpdocs', $diskusage);

        static::$_client->webspace()->delete('id', $webspace->id);
    }

    public function testGetPhysicalHostingDescriptor()
    {
        $descriptor = static::$_client->webspace()->getPhysicalHostingDescriptor();
        $this->assertIsArray($descriptor->properties);
        $this->assertNotEmpty($descriptor->properties);

        $ftpLoginProperty = $descriptor->properties['ftp_login'];
        $this->assertEquals('ftp_login', $ftpLoginProperty->name);
        $this->assertEquals('string', $ftpLoginProperty->type);
    }

    public function testGetPhpSettings()
    {
        $webspace = static::_createWebspace();
        $info = static::$_client->webspace()->getPhpSettings('id', $webspace->id);

        $this->assertArrayHasKey('open_basedir', $info->properties);

        static::$_client->webspace()->delete('id', $webspace->id);
    }

    public function testGetLimits()
    {
        $webspace = static::_createWebspace();
        $limits = static::$_client->webspace()->getLimits('id', $webspace->id);

        $this->assertIsArray($limits->limits);
        $this->assertNotEmpty($limits->limits);

        static::$_client->webspace()->delete('id', $webspace->id);
    }

    public function testCreateWebspace()
    {
        $webspace = static::_createWebspace();

        $this->assertGreaterThan(0, $webspace->id);

        static::$_client->webspace()->delete('id', $webspace->id);
    }

    public function testDelete()
    {
        $webspace = static::_createWebspace();
        $result = static::$_client->webspace()->delete('id', $webspace->id);

        $this->assertTrue($result);
    }

    public function testRequestCreateWebspace()
    {
        $handlers = static::$_client->phpHandler()->getAll();
        $enabledHandlers = array_filter($handlers, function ($handler) {
            return $handler->handlerStatus !== 'disabled';
        });
        $this->assertGreaterThan(0, count($enabledHandlers));
        $handler = current($enabledHandlers);

        $request = [
            'add' => [
                'gen_setup' => [
                    'name' => 'webspace-test-full.test',
                    'htype' => 'vrt_hst',
                    'status' => '0',
                    'ip_address' => [static::_getIpAddress()],
                ],
                'hosting' => [
                    'vrt_hst' => [
                        'property' => [
                            [
                                'name' => 'php_handler_id',
                                'value' => $handler->id,
                            ],
                            [
                                'name' => 'ftp_login',
                                'value' => 'testuser',
                            ],
                            [
                                'name' => 'ftp_password',
                                'value' => PasswordProvider::STRONG_PASSWORD,
                            ],
                        ],
                        'ip_address' => static::_getIpAddress(),
                    ],
                ],
                'limits' => [
                    'overuse' => 'block',
                    'limit' => [
                        [
                            'name' => 'mbox_quota',
                            'value' => 100,
                        ],
                    ],
                ],
                'prefs' => [
                    'www' => 'false',
                    'stat_ttl' => 6,
                ],
                'performance' => [
                    'bandwidth' => 120,
                    'max_connections' => 10000,
                ],
                'permissions' => [
                    'permission' => [
                        [
                            'name' => 'manage_sh_access',
                            'value' => 'true',
                        ],
                    ],
                ],
                'php-settings' => [
                    'setting' => [
                        [
                            'name' => 'memory_limit',
                            'value' => '128M',
                        ],
                        [
                            'name' => 'safe_mode',
                            'value' => 'false',
                        ],
                    ],
                ],
                'plan-name' => 'Unlimited',
            ],
        ];

        $webspace = static::$_client->webspace()->request($request);

        $this->assertGreaterThan(0, $webspace->id);

        static::$_client->webspace()->delete('id', $webspace->id);
    }

    public function testGet()
    {
        $webspace = static::_createWebspace();
        $webspaceInfo = static::$_client->webspace()->get('id', $webspace->id);

        $this->assertNotEmpty($webspaceInfo->name);
        $this->assertEquals(0, $webspaceInfo->realSize);

        static::$_client->webspace()->delete('id', $webspace->id);
    }
}
