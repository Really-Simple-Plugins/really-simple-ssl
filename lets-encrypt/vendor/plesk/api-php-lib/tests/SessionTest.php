<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskXTest;

class SessionTest extends TestCase
{
    public function testGet()
    {
        $sessionId = static::$_client->server()->createSession('admin', '127.0.0.1');
        $sessions = static::$_client->session()->get();
        $this->assertArrayHasKey($sessionId, $sessions);

        $sessionInfo = $sessions[$sessionId];
        $this->assertEquals('admin', $sessionInfo->login);
        $this->assertEquals('127.0.0.1', $sessionInfo->ipAddress);
        $this->assertEquals($sessionId, $sessionInfo->id);
    }

    public function testTerminate()
    {
        $sessionId = static::$_client->server()->createSession('admin', '127.0.0.1');
        static::$_client->session()->terminate($sessionId);
        $sessions = static::$_client->session()->get();
        $this->assertArrayNotHasKey($sessionId, $sessions);
    }
}
