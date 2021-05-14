<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskXTest;

use PleskX\Api\Exception;

class SecretKeyTest extends TestCase
{
    public function testCreate()
    {
        $keyId = static::$_client->secretKey()->create('192.168.0.1');
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $keyId);
        static::$_client->secretKey()->delete($keyId);
    }

    public function testGet()
    {
        $keyId = static::$_client->secretKey()->create('192.168.0.1');
        $keyInfo = static::$_client->secretKey()->get($keyId);

        $this->assertNotEmpty($keyInfo->key);
        $this->assertEquals('192.168.0.1', $keyInfo->ipAddress);
        $this->assertEquals('admin', $keyInfo->login);

        static::$_client->secretKey()->delete($keyId);
    }

    public function testGetAll()
    {
        $keyIds = [];
        $keyIds[] = static::$_client->secretKey()->create('192.168.0.1');
        $keyIds[] = static::$_client->secretKey()->create('192.168.0.2');

        $keys = static::$_client->secretKey()->getAll();
        $this->assertGreaterThanOrEqual(2, count($keys));

        $keyIpAddresses = array_map(function ($key) {
            return $key->ipAddress;
        }, $keys);
        $this->assertContains('192.168.0.1', $keyIpAddresses);
        $this->assertContains('192.168.0.2', $keyIpAddresses);

        foreach ($keyIds as $keyId) {
            static::$_client->secretKey()->delete($keyId);
        }
    }

    public function testDelete()
    {
        $keyId = static::$_client->secretKey()->create('192.168.0.1');
        static::$_client->secretKey()->delete($keyId);

        try {
            static::$_client->secretKey()->get($keyId);
            $this->fail("Secret key $keyId was not deleted.");
        } catch (Exception $exception) {
            $this->assertEquals(1013, $exception->getCode());
        }
    }

    public function testListEmpty()
    {
        $keys = static::$_client->secretKey()->getAll();
        foreach ($keys as $key) {
            static::$_client->secretKey()->delete($key->key);
        }

        $keys = static::$_client->secretKey()->getAll();
        $this->assertEquals(0, count($keys));
    }
}
