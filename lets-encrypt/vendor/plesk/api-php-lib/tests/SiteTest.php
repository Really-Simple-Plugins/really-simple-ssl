<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskXTest;

use PleskXTest\Utility\KeyLimitChecker;

class SiteTest extends TestCase
{
    /** @var \PleskX\Api\Struct\Webspace\Info */
    private static $webspace;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::$webspace = static::_createWebspace();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $keyInfo = static::$_client->server()->getKeyInfo();

        if (!KeyLimitChecker::checkByType($keyInfo, KeyLimitChecker::LIMIT_DOMAINS, 2)) {
            $this->markTestSkipped('License does not allow to create more than 1 domain.');
        }
    }

    private function _createSite($name, array $properties = [])
    {
        $properties = array_merge([
            'name' => $name,
            'webspace-id' => static::$webspace->id,
        ], $properties);

        return static::$_client->site()->create($properties);
    }

    public function testCreate()
    {
        $site = $this->_createSite('addon.dom');

        $this->assertIsNumeric($site->id);
        $this->assertGreaterThan(0, $site->id);

        static::$_client->site()->delete('id', $site->id);
    }

    public function testDelete()
    {
        $site = $this->_createSite('addon.dom');

        $result = static::$_client->site()->delete('id', $site->id);
        $this->assertTrue($result);
    }

    public function testGet()
    {
        $site = $this->_createSite('addon.dom');

        $siteInfo = static::$_client->site()->get('id', $site->id);
        $this->assertEquals('addon.dom', $siteInfo->name);

        static::$_client->site()->delete('id', $site->id);
    }

    public function testGetHostingWoHosting()
    {
        $site = $this->_createSite('addon.dom');

        $siteHosting = static::$_client->site()->getHosting('id', $site->id);
        $this->assertNull($siteHosting);

        static::$_client->site()->delete('id', $site->id);
    }

    public function testGetHostingWithHosting()
    {
        $properties = [
            'hosting' => [
                'www_root' => 'addon.dom',
            ],
        ];
        $site = $this->_createSite('addon.dom', $properties);

        $siteHosting = static::$_client->site()->getHosting('id', $site->id);
        $this->assertArrayHasKey('www_root', $siteHosting->properties);
        $this->assertStringEndsWith('addon.dom', $siteHosting->properties['www_root']);

        static::$_client->site()->delete('id', $site->id);
    }

    public function testGetAll()
    {
        $site = $this->_createSite('addon.dom');
        $site2 = $this->_createSite('addon2.dom');

        $sitesInfo = static::$_client->site()->getAll();
        $this->assertCount(2, $sitesInfo);
        $this->assertEquals('addon.dom', $sitesInfo[0]->name);
        $this->assertEquals('addon.dom', $sitesInfo[0]->asciiName);

        static::$_client->site()->delete('id', $site->id);
        static::$_client->site()->delete('id', $site2->id);
    }
}
