<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskXTest;

class SubdomainTest extends TestCase
{
    /** @var \PleskX\Api\Struct\Webspace\Info */
    private static $webspace;

    /** @var string */
    private static $webspaceName;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::$webspace = static::_createWebspace();
        $webspaceInfo = static::$_client->webspace()->get('id', static::$webspace->id);
        static::$webspaceName = $webspaceInfo->name;
    }

    /**
     * @param string $name
     *
     * @return \PleskX\Api\Struct\Subdomain\Info
     */
    private function _createSubdomain($name)
    {
        return static::$_client->subdomain()->create([
            'parent' => static::$webspaceName,
            'name' => $name,
            'property' => [
                'www_root' => $name,
            ],
        ]);
    }

    public function testCreate()
    {
        $subdomain = $this->_createSubdomain('sub');

        $this->assertIsInt($subdomain->id);
        $this->assertGreaterThan(0, $subdomain->id);

        static::$_client->subdomain()->delete('id', $subdomain->id);
    }

    public function testDelete()
    {
        $subdomain = $this->_createSubdomain('sub');

        $result = static::$_client->subdomain()->delete('id', $subdomain->id);
        $this->assertTrue($result);
    }

    public function testGet()
    {
        $name = 'sub';
        $subdomain = $this->_createSubdomain($name);

        $subdomainInfo = static::$_client->subdomain()->get('id', $subdomain->id);
        $this->assertEquals($name.'.'.$subdomainInfo->parent, $subdomainInfo->name);
        $this->assertTrue(false !== strpos($subdomainInfo->properties['www_root'], $name));

        static::$_client->subdomain()->delete('id', $subdomain->id);
    }

    public function testGetAll()
    {
        $name = 'sub';
        $name2 = 'sub2';
        $subdomain = $this->_createSubdomain($name);
        $subdomain2 = $this->_createSubdomain($name2);

        $subdomainsInfo = static::$_client->subdomain()->getAll();
        $this->assertCount(2, $subdomainsInfo);
        $this->assertEquals($name.'.'.$subdomainsInfo[0]->parent, $subdomainsInfo[0]->name);
        $this->assertTrue(false !== strpos($subdomainsInfo[0]->properties['www_root'], $name));
        $this->assertEquals($name2.'.'.$subdomainsInfo[1]->parent, $subdomainsInfo[1]->name);
        $this->assertTrue(false !== strpos($subdomainsInfo[1]->properties['www_root'], $name2));

        static::$_client->subdomain()->delete('id', $subdomain->id);
        static::$_client->subdomain()->delete('id', $subdomain2->id);

        $subdomainsInfo = static::$_client->subdomain()->getAll();
        $this->assertEmpty($subdomainsInfo);
    }
}
