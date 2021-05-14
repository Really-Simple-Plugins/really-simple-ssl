<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskXTest;

class DnsTemplateTest extends TestCase
{
    /**
     * @var bool
     */
    private static $_isDnsSupported;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $serviceStates = static::$_client->server()->getServiceStates();
        static::$_isDnsSupported = $serviceStates['dns'] && ('running' == $serviceStates['dns']['state']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (!static::$_isDnsSupported) {
            $this->markTestSkipped('DNS system is not supported.');
        }
    }

    public function testCreate()
    {
        $dns = static::$_client->dnsTemplate()->create([
            'type' => 'TXT',
            'host' => 'test.create',
            'value' => 'value',
        ]);
        $this->assertIsInt($dns->id);
        $this->assertGreaterThan(0, $dns->id);
        $this->assertEquals(0, $dns->siteId);
        $this->assertEquals(0, $dns->siteAliasId);
        static::$_client->dnsTemplate()->delete('id', $dns->id);
    }

    public function testGetById()
    {
        $dns = static::$_client->dnsTemplate()->create([
            'type' => 'TXT',
            'host' => 'test.get.by.id',
            'value' => 'value',
        ]);

        $dnsInfo = static::$_client->dnsTemplate()->get('id', $dns->id);
        $this->assertEquals('TXT', $dnsInfo->type);
        $this->assertEquals('value', $dnsInfo->value);

        static::$_client->dnsTemplate()->delete('id', $dns->id);
    }

    public function testGetAll()
    {
        $dns = static::$_client->dnsTemplate()->create([
            'type' => 'TXT',
            'host' => 'test.get.all',
            'value' => 'value',
        ]);
        $dns2 = static::$_client->dnsTemplate()->create([
            'type' => 'TXT',
            'host' => 'test.get.all',
            'value' => 'value2',
        ]);
        $dnsInfo = static::$_client->dnsTemplate()->getAll();
        $dsRecords = [];
        foreach ($dnsInfo as $dnsRec) {
            if ('TXT' === $dnsRec->type && 0 === strpos($dnsRec->host, 'test.get.all')) {
                $dsRecords[] = $dnsRec;
            }
        }
        $this->assertCount(2, $dsRecords);

        static::$_client->dnsTemplate()->delete('id', $dns->id);
        static::$_client->dnsTemplate()->delete('id', $dns2->id);
    }

    public function testDelete()
    {
        $dns = static::$_client->dnsTemplate()->create([
            'type' => 'TXT',
            'host' => 'test.delete',
            'value' => 'value',
        ]);
        $result = static::$_client->dnsTemplate()->delete('id', $dns->id);
        $this->assertTrue($result);
    }
}
