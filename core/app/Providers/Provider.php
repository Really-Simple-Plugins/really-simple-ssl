<?php
namespace ReallySimplePlugins\RSS\Core\Providers;

use ReallySimplePlugins\RSS\Core\Bootstrap\App;
use ReallySimplePlugins\RSS\Core\Interfaces\ProviderInterface;
use ReallySimplePlugins\RSS\Core\Support\Utility\StringUtility;

class Provider implements ProviderInterface
{
    /**
     * Register the provided services. Will be used to find and call the
     * provide{Service} methods. You can use lowercase for the service name.
     */
    protected array $provides = [];

    /**
     * Providers are classes that provide functionality to the container. Child
     * classes should never use the container instance themselves to prevent
     * recursion in the container registry. Therefor child Providers should
     * always return the provided functionality directly in the
     * provide{Function} method instead of setting it in the
     * container. This is why this property is private.
     */
    private App $app;

    final public function __construct(App $app)
    {
        $this->app = $app;
    }

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

            $providedFunctionality = $this->$method();
            $this->app->set($provide, static function() use ($providedFunctionality) {
                return $providedFunctionality;
            });
        }
    }
}