<?php

declare(strict_types=1);

namespace ReallySimplePlugins\RSS\Core\Support\Helpers\Storages;

use ReallySimplePlugins\RSS\Core\Support\Helpers\Storage;

/**
 * Typed configuration wrapper for config/related.php
 */
final class RelatedConfig extends Storage
{
    public function __construct()
    {
        parent::__construct(
            require dirname(__FILE__, 5) . '/config/related.php'
        );
    }
}
