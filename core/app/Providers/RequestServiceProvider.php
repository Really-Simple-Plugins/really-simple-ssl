<?php
namespace ReallySimplePlugins\RSS\Core\Providers;

use ReallySimplePlugins\RSS\Core\Support\Helpers\Storage;

class RequestServiceProvider extends Provider
{
    protected array $provides = [
        'request',
        'files',
        'requestBody',
    ];

    /**
     * Provides the global request object for the application to use
     * @example $this->app->request->get('key.key', 'default');
     */
    public function provideRequest(): Storage
    {
        return new Storage($_REQUEST);
    }

    /**
     * Provides the global files object for the application to use
     * @example $this->app->files->get('key.key', 'default');
     */
    public function provideFiles(): Storage
    {
        return new Storage($_FILES);
    }

    /**
     * Provides the JSON request body as a Storage object.
     * Reads and decodes the raw request body from php://input.
     * Returns empty array if body is empty or invalid JSON.
     *
     * @example $this->app->requestBody->get('activateSSLClicked', false);
     * @return Storage
     */
    public function provideRequestBody(): Storage
    {
        $rawBody = file_get_contents('php://input');
        $decoded = json_decode($rawBody, true);

        // Return empty array if decode fails or result is not an array
        $decodedBody = is_array($decoded) ? $decoded : [];

        return new Storage($decodedBody);
    }
}