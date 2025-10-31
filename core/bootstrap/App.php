<?php

declare(strict_types=1);

namespace ReallySimplePlugins\RSS\Core\Bootstrap;

use ReallySimplePlugins\RSS\Core\Support\Helpers\Storage;

/**
 * Container class that provides dependency injection capabilities to manage
 * object creation and resolution. Class is used for retrieving and injecting
 * dependencies in a structured and reusable way. This is important because it
 * decouples classes from concrete implementations (new..) and makes the
 * codebase easier to test and maintain.
 *
 * @property-read Storage $config Provided by {@see ConfigServiceProvider}
 * @property-read Storage $request Provided by {@see RequestServiceProvider}
 * @property-read Storage $files Provided by {@see RequestServiceProvider}
 * @property-read Storage $requestBody Provided by {@see RequestServiceProvider}
 *
 * @method static Storage config() Provided by {@see RequestServiceProvider}
 * @method static Storage request() Provided by {@see RequestServiceProvider}
 * @method static Storage files() Provided by {@see RequestServiceProvider}
 * @method static Storage requestBody() Provided by {@see RequestServiceProvider}
 */
class App
{
    /**
     * Singleton instance holder. Ensures a single container is shared across
     * the plugin without globals.
     */
    private static ?App $instance = null;

    /**
     * Registry of service factories indexed by identifier. Allows registering
     * lazy factory closures for services.
     *
     * @var array<string, \Closure>
     */
    private array $registry = [];

    /**
     * Private constructor to enforce the singleton pattern. Prevents direct
     * instantiation; use getInstance() instead.
     */
    private function __construct() {}

    /**
     * Retrieve the shared container instance. Provides a central access point
     * to the container for the plugin.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Register a service factory. Defers service creation until first use. The
     * provided Closure will be called with no arguments when the service is
     * resolved.
     */
    public function set(string $name, \Closure $value): void
    {
        $this->registry[$name] ??= $value;
    }

    /**
     * Resolve an identifier to an object instance. It first checks the registry
     * for a factory. If none is found, it calls {@see make} to perform
     * constructor autowiring.
     *
     * @throws \Exception If the target is not instantiable or cannot resolve a dependency.
     * @throws \ReflectionException If reflection fails.
     */
    public function get(string $class): object
    {
        if (array_key_exists($class, $this->registry)) {
            return ($this->registry[$class])();
        }

        return $this->make($class);
    }

    /**
     * Method is used for on-demand construction of an object using constructor
     * autowiring without touching the registry. When a constructor parameter
     * asks for the Container itself, the current Container instance is injected
     * instead of creating a new Container. Class-typed dependencies are
     * resolved via {@see get} so factories from the registry are honored, while
     * scalars or unresolved parameters require defaults or will result in an
     * exception. This keeps "make" safe for ad-hoc instances you may later
     * choose to register manually.
     *
     * @param string $class The class to make. Dependencies are injected.
     * @param bool $register Made classes are registered in the container on
     * true. Useful for optimization on multi-used classes.
     * @param bool $registerDependencies Made dependency classes are registered
     * in the container on true. Useful for optimization on multi-used classes.
     *
     * @throws \Exception If the target is not instantiable or a dependency cannot be resolved.
     * @throws \ReflectionException If reflection fails.
     */
    public function make(string $class, bool $register = true, bool $registerDependencies = true): object
    {
        $reflector = new \ReflectionClass($class);

        if ($reflector->isInstantiable() === false) {
            throw new \Exception("Target [{$class}] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $arguments = [];
        $parameters = $constructor->getParameters();

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            // No type hinted: allow default value, otherwise we cannot resolve.
            if ($type === null) {
                if ($parameter->isDefaultValueAvailable()) {
                    $arguments[] = $parameter->getDefaultValue();
                    continue;
                }

                throw new \Exception(sprintf(
                    'Cannot resolve untyped parameter $%s for [%s] without a default value.',
                    $parameter->getName(),
                    $class
                ));
            }

            // For PHP 7.4 only ReflectionNamedType exists (no unions).
            if ($type instanceof \ReflectionNamedType === false) {
                throw new \Exception(sprintf(
                    'Unsupported parameter type for $%s in [%s].',
                    $parameter->getName(),
                    $class
                ));
            }

            // If nullable and no default, we still must supply something;
            if ($type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $arguments[] = $parameter->getDefaultValue();
                    continue;
                }

                throw new \Exception(sprintf(
                    'Cannot autowire builtin parameter $%s (%s) for [%s]. Provide a default or register a factory.',
                    $parameter->getName(),
                    $type->getName(),
                    $class
                ));
            }

            $dependencyClass = $type->getName();

            // Inject the current container, never a new one.
            if ($dependencyClass === self::class) {
                $arguments[] = $this;
                continue;
            }

            // Using get() will also resolve dependencies of dependencies
            $dependency = $this->get($dependencyClass);

            // Dependencies are often for multi-use and therefor adding them
            // to the registry is beneficial for speed
            if ($registerDependencies === true) {
                $this->set($dependencyClass, static function() use ($dependency) {
                    return $dependency;
                });
            }

            $arguments[] = $this->get($dependencyClass);
        }

        $made = new $class(...$arguments);

        if ($register) {
            $this->set($class, static function() use ($made) {
                return $made;
            });
        }

        return $made;
    }

    /**
     * Calls {@see get} immediately for unknown properties.
     *
     * @throws \Exception If the target is not instantiable.
     * @throws \ReflectionException If reflection fails.
     */
    public function __get(string $name): object
    {
        return $this->get($name);
    }

    /**
     * Helper method to be able to do static calls like so:
     * Container::config->getString('env.plugin.name');
     *
     * Instead of:
     * Container::getInstance()->config->getString('env.plugin.name');
     */
    public static function __callStatic(string $name, array $arguments = [])
    {
        $instance = self::getInstance();
        return call_user_func([$instance, 'get'], $name);
    }
}