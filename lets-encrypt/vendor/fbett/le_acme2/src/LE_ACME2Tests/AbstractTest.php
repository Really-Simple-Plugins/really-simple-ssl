<?php
namespace LE_ACME2Tests;
defined('ABSPATH') or die();

use PHPUnit\Framework\TestCase;
use LE_ACME2;

abstract class AbstractTest extends TestCase {

    public function __construct() {
        parent::__construct();

        LE_ACME2\Connector\Connector::getInstance()->useStagingServer(true);
    }
}