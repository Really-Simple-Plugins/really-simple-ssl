<?php

declare(strict_types=1);

namespace ReallySimplePlugins\RSS\Core\Interfaces;

/**
 * This interface can be used instead of {@see SingleEndpointInterface} to register
 * multiple routes at once. This is useful when a single route has multiple
 * endpoints.
 */
interface MultiEndpointInterface
{
    /**
     * The routes to register. For each array in the array, the key is the route
     * and the value is an array of arguments to pass to the register_rest_route
     * function: {@see EndpointManager::registerWordPressRestRoutes}.
     *
     * Arguments you can use are documented with filter: rss_core_rest_routes
     * in method: {@see EndpointManager::getPluginRoutes}
     */
    public function registerRoutes(): array;

    /**
     * This method should return true if the endpoint is enabled, false
     * otherwise. Endpoint will not be registered if this method returns false:
     * {@see EndpointManager::registerEndpoints}
     */
    public function enabled(): bool;
}