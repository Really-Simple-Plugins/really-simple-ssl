<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskXTest;

class IpTest extends TestCase
{
    public function testGet()
    {
        $ips = static::$_client->ip()->get();
        $this->assertGreaterThan(0, count($ips));

        $ip = reset($ips);
        $this->assertMatchesRegularExpression('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $ip->ipAddress);
    }
}
