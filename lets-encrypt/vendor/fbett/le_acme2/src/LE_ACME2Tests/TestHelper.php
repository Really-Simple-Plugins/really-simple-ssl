<?php
namespace LE_ACME2Tests;
defined('ABSPATH') or die();

use LE_ACME2\SingletonTrait;

class TestHelper {

    private $_tempPath;
    private $_nonExistingPath;

    use SingletonTrait;

    private function __construct() {

        $projectPath = realpath($_SERVER[ 'PWD' ]) . '/';
        $this->_tempPath = $projectPath . 'temp/';
        if( !file_exists($this->_tempPath) ) {
            mkdir($this->_tempPath);
        }

        $this->_nonExistingPath = $this->getTempPath() . 'should-not-exist/';
    }

    public function getTempPath() : string {
        return $this->_tempPath;
    }

    public function getNonExistingPath() : string {
        return $this->_nonExistingPath;
    }

    private $_skipAccountModificationTests = false;

    public function setSkipAccountModificationTests(bool $value) : void {
        $this->_skipAccountModificationTests = $value;
    }
    
    public function shouldSkipAccountModificationTests() : bool {
        return $this->_skipAccountModificationTests;
    }
}