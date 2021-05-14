<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskXTest;

use PleskX\Api\Client\Exception;

class ApiClientTest extends TestCase
{
    public function testWrongProtocol()
    {
        $this->expectException(\PleskX\Api\Exception::class);
        $this->expectExceptionCode(1005);

        $packet = static::$_client->getPacket('100.0.0');
        $packet->addChild('server')->addChild('get_protos');
        static::$_client->request($packet);
    }

    public function testUnknownOperator()
    {
        $this->expectException(\PleskX\Api\Exception::class);
        $this->expectExceptionCode(1014);

        $packet = static::$_client->getPacket();
        $packet->addChild('unknown');
        static::$_client->request($packet);
    }

    public function testInvalidXmlRequest()
    {
        $this->expectException(\PleskX\Api\Exception::class);
        $this->expectExceptionCode(1014);

        static::$_client->request('<packet><wrongly formatted xml</packet>');
    }

    public function testInvalidCredentials()
    {
        $this->expectException(\PleskX\Api\Exception::class);
        $this->expectExceptionCode(1001);

        $host = static::$_client->getHost();
        $port = static::$_client->getPort();
        $protocol = static::$_client->getProtocol();
        $client = new \PleskX\Api\Client($host, $port, $protocol);
        $client->setCredentials('bad-login', 'bad-password');
        $packet = static::$_client->getPacket();
        $packet->addChild('server')->addChild('get_protos');
        $client->request($packet);
    }

    public function testInvalidSecretKey()
    {
        $this->expectException(\PleskX\Api\Exception::class);
        $this->expectExceptionCode(11003);

        $host = static::$_client->getHost();
        $port = static::$_client->getPort();
        $protocol = static::$_client->getProtocol();
        $client = new \PleskX\Api\Client($host, $port, $protocol);
        $client->setSecretKey('bad-key');
        $packet = static::$_client->getPacket();
        $packet->addChild('server')->addChild('get_protos');
        $client->request($packet);
    }

    public function testLatestMajorProtocol()
    {
        $packet = static::$_client->getPacket('1.6');
        $packet->addChild('server')->addChild('get_protos');
        $result = static::$_client->request($packet);
        $this->assertEquals('ok', $result->status);
    }

    public function testLatestMinorProtocol()
    {
        $packet = static::$_client->getPacket('1.6.5');
        $packet->addChild('server')->addChild('get_protos');
        $result = static::$_client->request($packet);
        $this->assertEquals('ok', $result->status);
    }

    public function testRequestShortSyntax()
    {
        $response = static::$_client->request('server.get.gen_info');
        $this->assertGreaterThan(0, strlen($response->gen_info->server_name));
    }

    public function testOperatorPlainRequest()
    {
        $response = static::$_client->server()->request('get.gen_info');
        $this->assertGreaterThan(0, strlen($response->gen_info->server_name));
        $this->assertEquals(36, strlen($response->getValue('server_guid')));
    }

    public function testRequestArraySyntax()
    {
        $response = static::$_client->request([
            'server' => [
                'get' => [
                    'gen_info' => '',
                ],
            ],
        ]);
        $this->assertGreaterThan(0, strlen($response->gen_info->server_name));
    }

    public function testOperatorArraySyntax()
    {
        $response = static::$_client->server()->request(['get' => ['gen_info' => '']]);
        $this->assertGreaterThan(0, strlen($response->gen_info->server_name));
    }

    public function testMultiRequest()
    {
        $responses = static::$_client->multiRequest([
            'server.get_protos',
            'server.get.gen_info',
        ]);

        $this->assertCount(2, $responses);

        $protos = (array) $responses[0]->protos->proto;
        $generalInfo = $responses[1];

        $this->assertContains('1.6.6.0', $protos);
        $this->assertGreaterThan(0, strlen($generalInfo->gen_info->server_name));
    }

    public function testConnectionError()
    {
        $this->expectException(\PleskX\Api\Client\Exception::class);

        $client = new \PleskX\Api\Client('invalid-host.dom');
        $client->server()->getProtos();
    }

    public function testGetHost()
    {
        $client = new \PleskX\Api\Client('example.dom');
        $this->assertEquals('example.dom', $client->getHost());
    }

    public function testGetPort()
    {
        $client = new \PleskX\Api\Client('example.dom', 12345);
        $this->assertEquals(12345, $client->getPort());
    }

    public function testGetProtocol()
    {
        $client = new \PleskX\Api\Client('example.dom', 8880, 'http');
        $this->assertEquals('http', $client->getProtocol());
    }

    public function testSetVerifyResponse()
    {
        static::$_client->setVerifyResponse(function ($xml) {
            if ($xml->xpath('//proto')) {
                throw new Exception('proto');
            }
        });

        try {
            static::$_client->server()->getProtos();
        } catch (Exception $e) {
            $this->assertEquals('proto', $e->getMessage());
        } finally {
            static::$_client->setVerifyResponse();
        }
    }
}
