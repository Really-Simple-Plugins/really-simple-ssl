<?php
namespace LE_ACME2\Cache;

use LE_ACME2\AbstractKeyValuable;

abstract class AbstractKeyValuableCache {

    protected function __construct() {}

    protected function _getObjectIdentifier(AbstractKeyValuable $object) : string {
        return $object->getKeyDirectoryPath();
    }
}