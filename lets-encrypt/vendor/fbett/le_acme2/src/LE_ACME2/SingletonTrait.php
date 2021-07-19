<?php
namespace LE_ACME2;
defined('ABSPATH') or die();

trait SingletonTrait {

    private static $_instance = NULL;

    /**
     * @return static
     */
    final public static function getInstance(): self {

        if( self::$_instance === NULL ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
}