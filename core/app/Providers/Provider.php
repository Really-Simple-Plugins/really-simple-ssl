<?php

namespace ReallySimplePlugins\RSS\Core\Providers;

use ReallySimplePlugins\RSS\Core\Bootstrap\App;
use ReallySimplePlugins\RSS\Core\Interfaces\ProviderInterface;
use ReallySimplePlugins\RSS\Core\Support\Utility\StringUtility;

/**
 * Providers are classes that provide functionality to the container. Child
 * classes should never use the container instance themselves to prevent
 * recursion in the container registry. Therefor child Providers should
 * always return the provided functionality directly in the
 * provide{Function} method instead of setting it in the
 * container {@see App}
 */
class Provider implements ProviderInterface
{
    /**
     * Register the provided services. Will be used to find and call the
     * provide{Service} methods. You can use lowercase for the service name.
     * @var string[]
     */
    protected array $provides = [];

    /**
     * Register the provided singleton services. The key is the name of the
     * service and is used to find and call the provide{Service}Singleton
     * method. The value is the class string that will be used to register
     * and retrieve the singleton in the container.
     * @var array<string, class-string>
     */
    protected array $singletons = [];

    /**
     * Method will be called by the ProviderManager to serve the provided
     * services.
     */
    final public function provide(): void
    {
        foreach ($this->provides as $provide) {
            $method = 'provide' . StringUtility::snakeToPascalCase($provide);
            if (method_exists($this, $method) === false) {
                continue;
            }

            App::getInstance()->set($provide, static function() use ($method) {
                return static::$method();
            });
        }

        foreach ($this->singletons as $key => $classString) {
            $method = 'provide' . StringUtility::snakeToPascalCase($key) . 'Singleton';
            if (method_exists($this, $method) === false) {
                continue;
            }

            App::getInstance()->set($classString, static function() use ($method) {
                return static::$method();
            });
        }
    }
}
