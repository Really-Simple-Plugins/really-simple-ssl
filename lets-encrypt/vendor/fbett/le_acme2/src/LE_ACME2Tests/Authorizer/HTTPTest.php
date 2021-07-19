<?php
namespace LE_ACME2Tests\Authorizer;
defined('ABSPATH') or die();

use LE_ACME2Tests\AbstractTest;
use LE_ACME2Tests\TestHelper;

/**
 * @covers \LE_ACME2\Authorizer\HTTP
 */
class HTTPTest extends AbstractTest {

    private $_directoryPath;

    public function __construct() {
        parent::__construct();

        $this->_directoryPath = TestHelper::getInstance()->getTempPath() . 'acme-challenges/';
    }

    public function testNonExistingDirectoryPath() {

        $this->assertTrue(\LE_ACME2\Authorizer\HTTP::getDirectoryPath() === null);

        $this->expectException(\RuntimeException::class);
        \LE_ACME2\Authorizer\HTTP::setDirectoryPath(TestHelper::getInstance()->getNonExistingPath());
    }

    public function testDirectoryPath() {

        if(!file_exists($this->_directoryPath)) {
            mkdir($this->_directoryPath);
        }

        \LE_ACME2\Authorizer\HTTP::setDirectoryPath($this->_directoryPath);

        $this->assertTrue(
            \LE_ACME2\Authorizer\HTTP::getDirectoryPath() === $this->_directoryPath
        );
    }
}