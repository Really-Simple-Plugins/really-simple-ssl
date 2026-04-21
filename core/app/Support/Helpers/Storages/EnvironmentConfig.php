<?php

declare(strict_types=1);

namespace ReallySimplePlugins\RSS\Core\Support\Helpers\Storages;

use ReallySimplePlugins\RSS\Core\Support\Helpers\Storage;

/**
 * Environment configuration helper used in DI container.
 */
final class EnvironmentConfig extends Storage
{
    public function __construct()
    {
        parent::__construct(
            require dirname(__FILE__, 5) . '/config/env.php'
        );
    }
}
