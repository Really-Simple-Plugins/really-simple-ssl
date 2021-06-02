<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskXTest;

class ServerTest extends TestCase
{
    public function testGetProtos()
    {
        $protos = static::$_client->server()->getProtos();
        $this->assertIsArray($protos);
        $this->assertContains('1.6.3.0', $protos);
    }

    public function testGetGenInfo()
    {
        $generalInfo = static::$_client->server()->getGeneralInfo();
        $this->assertGreaterThan(0, strlen($generalInfo->serverName));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', strtolower($generalInfo->serverGuid));
        $this->assertEquals('standard', $generalInfo->mode);
    }

    public function testGetPreferences()
    {
        $preferences = static::$_client->server()->getPreferences();
        $this->assertIsNumeric($preferences->statTtl);
        $this->assertGreaterThan(0, $preferences->statTtl);
        $this->assertEquals(0, $preferences->restartApacheInterval);
    }

    public function testGetAdmin()
    {
        $admin = static::$_client->server()->getAdmin();
        $this->assertGreaterThan(0, strlen($admin->name));
        $this->assertStringContainsString('@', $admin->email);
    }

    public function testGetKeyInfo()
    {
        $keyInfo = static::$_client->server()->getKeyInfo();
        $this->assertIsArray($keyInfo);
        $this->assertGreaterThan(0, count($keyInfo));
        $this->assertArrayHasKey('plesk_key_id', $keyInfo);
        $this->assertArrayHasKey('lim_date', $keyInfo);
    }

    public function testGetComponents()
    {
        $components = static::$_client->server()->getComponents();
        $this->assertIsArray($components);
        $this->assertGreaterThan(0, count($components));
        $this->assertArrayHasKey('psa', $components);
    }

    public function testGetServiceStates()
    {
        $serviceStates = static::$_client->server()->getServiceStates();

        $this->assertIsArray($serviceStates);
        $this->assertGreaterThan(0, count($serviceStates));

        $service = current($serviceStates);
        $this->assertIsArray($service);
        $this->assertArrayHasKey('id', $service);
        $this->assertArrayHasKey('title', $service);
        $this->assertArrayHasKey('state', $service);
    }

    public function testGetSessionPreferences()
    {
        $preferences = static::$_client->server()->getSessionPreferences();
        $this->assertIsNumeric($preferences->loginTimeout);
        $this->assertGreaterThan(0, $preferences->loginTimeout);
    }

    public function testGetShells()
    {
        $shells = static::$_client->server()->getShells();

        $this->assertIsArray($shells);
        $this->assertGreaterThan(0, count($shells));
    }

    public function testGetNetworkInterfaces()
    {
        $netInterfaces = static::$_client->server()->getNetworkInterfaces();
        $this->assertIsArray($netInterfaces);
        $this->assertGreaterThan(0, count($netInterfaces));
    }

    public function testGetStatistics()
    {
        $stats = static::$_client->server()->getStatistics();
        $this->assertIsNumeric($stats->objects->clients);
        $this->assertEquals('psa', $stats->version->internalName);
    }

    public function testGetSiteIsolationConfig()
    {
        $config = static::$_client->server()->getSiteIsolationConfig();
        $this->assertIsArray($config);
        $this->assertGreaterThan(0, count($config));
        $this->assertArrayHasKey('php', $config);
    }

    public function testGetUpdatesInfo()
    {
        $updatesInfo = static::$_client->server()->getUpdatesInfo();
        $this->assertIsBool($updatesInfo->installUpdatesAutomatically);
    }
}
