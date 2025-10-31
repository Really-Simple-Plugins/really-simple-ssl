<?php

declare(strict_types=1);

namespace ReallySimplePlugins\RSS\Core\Managers;

use ReallySimplePlugins\RSS\Core\Traits\HasNonces;
use ReallySimplePlugins\RSS\Core\Traits\HasAllowlistControl;
use ReallySimplePlugins\RSS\Core\Interfaces\ProviderInterface;
use ReallySimplePlugins\RSS\Core\Interfaces\SingleEndpointInterface;
use ReallySimplePlugins\RSS\Core\Interfaces\MultiEndpointInterface;

final class EndpointManager extends AbstractManager
{
    use HasNonces;
    use HasAllowlistControl;

    private string $version;
    private string $namespace;
    private array $routes = [];

    /**
     * @inheritDoc
     */
    public function isRegistrable(object $class): bool
    {
        return ($class instanceof SingleEndpointInterface
            || $class instanceof MultiEndpointInterface
        );
    }

    /**
     * @inheritDoc
     */
    public function registerClass(object $class): void
    {
        if ($class instanceof SingleEndpointInterface) {
            $this->registerSingleEndpointRoute($class);
        }

        $this->registerMultiEndpointRoute($class);
    }

    /**
     * @inheritDoc
     */
    public function afterRegister(): void
    {
        $this->registerWordPressRestRoutes();
        do_action('rss_core_endpoints_loaded');
    }

    /**
     * Register a plugin route for and endpoint instance that implements the
     * {@see SingleEndpointInterface}
     */
    private function registerSingleEndpointRoute(SingleEndpointInterface $endpoint): void
    {
        if ($endpoint->enabled() === false) {
            return;
        }

        $this->routes[$endpoint->registerRoute()] = $endpoint->registerArguments();
    }

    /**
     * Register plugin routes for an endpoint instance that implements the
     * {@see MultiEndpointInterface}
     */
    private function registerMultiEndpointRoute(MultiEndpointInterface $endpoint): void
    {
        if ($endpoint->enabled() === false) {
            return;
        }

        $routeEndpoints = $endpoint->registerRoutes();
        foreach ($routeEndpoints as $route => $arguments) {
            $this->routes[$route] = $arguments;
        }
    }

    /**
     * This method provides a way to register custom REST routes via the
     * rss_core_rest_routes filter. A controller of feature should be
     * instantiated before this manager is called and the controller should
     * hook into the rss_core_rest_routes filter to add its own routes.
     * @uses apply_filters rss_core_rest_routes
     */
    public function registerWordPressRestRoutes(): void
    {
        $routes = $this->getPluginRestRoutes();

        foreach ($routes as $route => $data) {
            $version = ($data['version'] ??  $this->app->config->getString('env.http.version'));
            $callback = ($data['callback'] ?? null);
            $middleware = ($data['middleware'] ?? null);

            $arguments = [
                'methods' => $this->normalizeMethods($data['methods'] ?? ''),
                'callback' => $this->callbackMiddleware($callback, $middleware),
                'permission_callback' => ($data['permission_callback'] ?? [$this, 'defaultPermissionCallback']),
            ];

            register_rest_route($this->app->config->getUrl('env.http.namespace') . '/' . $version, $route, $arguments);
        }
    }

    /**
     * Get the plugins REST routes
     * @uses apply_filters rss_core_rest_routes
     */
    private function getPluginRestRoutes(): array
    {
        /**
         * Filter: rss_core_rest_routes
         * Can be used to add or modify the REST routes
         *
         * @param array $routes
         * @return array
         * @example [
         *      'route' => [ // key is the route name
         *          'methods' => 'GET', // required
         *          'callback' => 'callback_function', // required
         *          'permission_callback' => 'permission_callback_function', // optional to override the default permission callback
         *          'version' => 'v1' // optional to override the default version
         *      ]
         * ]
         */
        return apply_filters('rss_core_rest_routes', $this->routes);
    }

    /**
     * This method is used to add middleware to the callback function. The
     * middleware should be a callable function that takes a request as an
     * argument and returns a response. The default middleware is to switch
     * the user locale to the current user locale.
     */
    public function callbackMiddleware(?callable $callback, ?callable $middleware): callable
    {
        return function ($request) use ($callback, $middleware) {
            if (is_callable($middleware)) {
                $middleware($request);
            } else {
                $this->defaultMiddlewareCallback();
            }

            return $callback($request);
        };
    }

    /**
     * This method is used to switch the user locale to the current user locale.
     * This is important because we will otherwise show the default site
     * language to the user for the Tasks and Notifications. Those
     * translations are created in PHP and not in JS.
     */
    private function defaultMiddlewareCallback(): void
    {
        switch_to_user_locale(get_current_user_id());
    }

    /**
     * The default permission callback, will check if the nonce is valid and if
     * the user has the required permissions to do a request.
     * @return bool|\WP_Error
     */
    public function defaultPermissionCallback(\WP_REST_Request $request)
    {
        $method = $request->get_method();
        $nonce = $request->get_param('nonce');
        if (($method === 'POST') && ($this->verifyNonce($nonce) === false)) {
            return new \WP_Error(
                'rest_forbidden',
                esc_html__('Forbidden.', 'really-simple-ssl'),
                ['status' => 403]
            );
        }

        return true;
    }

    /**
     * Process the given methods and compare them to the allowed
     * {@see \WP_REST_Server::ALLMETHODS} methods. Remove unwanted entries and
     * cleanup method usage from, for example, "get " to "GET".
     *
     * @return string From "get, POSt, fake" to "GET,POST"
     */
    private function normalizeMethods(string $methods): string
    {
        // Split into array, trim whitespace and uppercase entries
        $methodsArray = array_map('trim', explode(',', $methods));
        $methodsArray = array_map('strtoupper', $methodsArray);

        // Split allowed entries into array and trim whitespaces
        $allowedMethodsArray = array_map('trim', explode(',', \WP_REST_Server::ALLMETHODS));

        // Keep only allowed methods
        $methodsArray = array_intersect($methodsArray, $allowedMethodsArray);
        $methodsArray = array_values(array_unique($methodsArray));

        // Convert back to CSV format for register_rest_route usage
        return implode(',', $methodsArray);
    }

}