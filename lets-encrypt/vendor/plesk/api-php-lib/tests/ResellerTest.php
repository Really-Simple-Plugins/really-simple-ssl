<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskXTest;

use PleskXTest\Utility\KeyLimitChecker;
use PleskXTest\Utility\PasswordProvider;

class ResellerTest extends TestCase
{
    private $_resellerProperties;

    public function setUp(): void
    {
        $this->_resellerProperties = [
            'pname' => 'John Reseller',
            'login' => 'reseller-unit-test',
            'passwd' => PasswordProvider::STRONG_PASSWORD,
        ];
    }

    public function testCreate()
    {
        $reseller = static::$_client->reseller()->create($this->_resellerProperties);
        $this->assertIsInt($reseller->id);
        $this->assertGreaterThan(0, $reseller->id);

        static::$_client->reseller()->delete('id', $reseller->id);
    }

    public function testDelete()
    {
        $reseller = static::$_client->reseller()->create($this->_resellerProperties);
        $result = static::$_client->reseller()->delete('id', $reseller->id);
        $this->assertTrue($result);
    }

    public function testGet()
    {
        $reseller = static::$_client->reseller()->create($this->_resellerProperties);
        $resellerInfo = static::$_client->reseller()->get('id', $reseller->id);
        $this->assertEquals('John Reseller', $resellerInfo->personalName);
        $this->assertEquals('reseller-unit-test', $resellerInfo->login);
        $this->assertGreaterThan(0, count($resellerInfo->permissions));

        static::$_client->reseller()->delete('id', $reseller->id);
    }

    public function testGetAll()
    {
        $keyInfo = static::$_client->server()->getKeyInfo();

        if (!KeyLimitChecker::checkByType($keyInfo, KeyLimitChecker::LIMIT_RESELLERS, 2)) {
            $this->markTestSkipped('License does not allow to create more than 1 reseller.');
        }

        static::$_client->reseller()->create([
            'pname' => 'John Reseller',
            'login' => 'reseller-a',
            'passwd' => PasswordProvider::STRONG_PASSWORD,
        ]);
        static::$_client->reseller()->create([
            'pname' => 'Mike Reseller',
            'login' => 'reseller-b',
            'passwd' => PasswordProvider::STRONG_PASSWORD,
        ]);

        $resellersInfo = static::$_client->reseller()->getAll();
        $this->assertCount(2, $resellersInfo);
        $this->assertEquals('John Reseller', $resellersInfo[0]->personalName);
        $this->assertEquals('reseller-a', $resellersInfo[0]->login);

        static::$_client->reseller()->delete('login', 'reseller-a');
        static::$_client->reseller()->delete('login', 'reseller-b');
    }
}
