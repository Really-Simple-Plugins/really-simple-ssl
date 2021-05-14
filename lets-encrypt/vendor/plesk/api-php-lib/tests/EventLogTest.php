<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskXTest;

class EventLogTest extends TestCase
{
    public function testGet()
    {
        $events = static::$_client->eventLog()->get();
        $this->assertGreaterThan(0, $events);

        $event = reset($events);
        $this->assertGreaterThan(0, $event->time);
    }

    public function testGetDetailedLog()
    {
        $events = static::$_client->eventLog()->getDetailedLog();
        $this->assertGreaterThan(0, $events);

        $event = reset($events);
        $this->assertGreaterThan(0, $event->time);
    }

    public function testGetLastId()
    {
        $lastId = static::$_client->eventLog()->getLastId();
        $this->assertGreaterThan(0, $lastId);
    }
}
