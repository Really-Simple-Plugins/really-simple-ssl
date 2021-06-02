<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskXTest;

class PhpHandlerTest extends TestCase
{
    public function testGet()
    {
        $handler = static::$_client->phpHandler()->get(null, null);

        $this->assertIsObject($handler);
        $this->assertObjectHasAttribute('type', $handler);
    }

    public function testGetAll()
    {
        $handlers = static::$_client->phpHandler()->getAll();

        $this->assertIsArray($handlers);
        $this->assertNotEmpty($handlers);

        $handler = current($handlers);

        $this->assertIsObject($handler);
        $this->assertObjectHasAttribute('type', $handler);
    }

    public function testGetUnknownHandlerThrowsException()
    {
        $this->expectException(\PleskX\Api\Exception::class);
        $this->expectExceptionMessage('Php handler does not exists');

        static::$_client->phpHandler()->get('id', 'this-handler-does-not-exist');
    }
}
