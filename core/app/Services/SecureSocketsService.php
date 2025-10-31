<?php
namespace ReallySimplePlugins\RSS\Core\Services;

final class SecureSocketsService
{
    /**
     * Method to activate SSL for the current site.
     * @return array|bool Array when the current request is a REST request
     *
     * @todo Move admin method here after full refactor.
     */
    public function activateSSL(array $data = [])
    {
        return RSSSL()->admin->activate_ssl($data);
    }
}