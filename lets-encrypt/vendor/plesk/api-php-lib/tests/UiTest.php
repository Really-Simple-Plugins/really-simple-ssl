<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskXTest;

class UiTest extends TestCase
{
    private $_customButtonProperties = [
        'place' => 'admin',
        'url' => 'http://example.com',
        'text' => 'Example site',
    ];

    public function testGetNavigation()
    {
        $navigation = static::$_client->ui()->getNavigation();
        $this->assertIsArray($navigation);
        $this->assertGreaterThan(0, count($navigation));
        $this->assertArrayHasKey('general', $navigation);
        $this->assertArrayHasKey('hosting', $navigation);

        $hostingSection = $navigation['hosting'];
        $this->assertArrayHasKey('name', $hostingSection);
        $this->assertArrayHasKey('nodes', $hostingSection);
        $this->assertGreaterThan(0, count($hostingSection['nodes']));
    }

    public function testCreateCustomButton()
    {
        $buttonId = static::$_client->ui()->createCustomButton('admin', $this->_customButtonProperties);
        $this->assertGreaterThan(0, $buttonId);

        static::$_client->ui()->deleteCustomButton($buttonId);
    }

    public function testGetCustomButton()
    {
        $buttonId = static::$_client->ui()->createCustomButton('admin', $this->_customButtonProperties);
        $customButtonInfo = static::$_client->ui()->getCustomButton($buttonId);
        $this->assertEquals('http://example.com', $customButtonInfo->url);
        $this->assertEquals('Example site', $customButtonInfo->text);

        static::$_client->ui()->deleteCustomButton($buttonId);
    }

    public function testDeleteCustomButton()
    {
        $buttonId = static::$_client->ui()->createCustomButton('admin', $this->_customButtonProperties);
        $result = static::$_client->ui()->deleteCustomButton($buttonId);
        $this->assertTrue($result);
    }
}
