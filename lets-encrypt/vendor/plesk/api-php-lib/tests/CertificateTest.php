<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskXTest;

class CertificateTest extends TestCase
{
    public function testGenerate()
    {
        $certificate = static::$_client->certificate()->generate([
            'bits' => 2048,
            'country' => 'RU',
            'state' => 'NSO',
            'location' => 'Novosibirsk',
            'company' => 'Plesk',
            'email' => 'info@plesk.com',
            'name' => 'plesk.com',
        ]);
        $this->assertGreaterThan(0, strlen($certificate->request));
        $this->assertStringStartsWith('-----BEGIN CERTIFICATE REQUEST-----', $certificate->request);
        $this->assertGreaterThan(0, strlen($certificate->privateKey));
        $this->assertStringStartsWith('-----BEGIN PRIVATE KEY-----', $certificate->privateKey);
    }
}
