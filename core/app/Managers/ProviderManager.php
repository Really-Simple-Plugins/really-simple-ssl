<?php

declare(strict_types=1);

namespace ReallySimplePlugins\RSS\Core\Managers;

use ReallySimplePlugins\RSS\Core\Interfaces\ProviderInterface;

final class ProviderManager extends AbstractManager
{
    /**
     * @inheritDoc
     */
    public function isRegistrable(object $class): bool
    {
        return $class instanceof ProviderInterface;
    }

    /**
     * @inheritDoc
     */
    public function registerClass(object $class): void
    {
        $class->provide();
    }

    /**
     * @inheritDoc
     */
    public function afterRegister(): void
    {
        do_action('rss_core_providers_loaded');
    }
}