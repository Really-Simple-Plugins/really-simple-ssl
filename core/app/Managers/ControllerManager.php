<?php

declare(strict_types=1);

namespace ReallySimplePlugins\RSS\Core\Managers;

use ReallySimplePlugins\RSS\Core\Interfaces\ControllerInterface;

final class ControllerManager extends AbstractManager
{
    /**
     * @inheritDoc
     */
    public function isRegistrable(object $class): bool
    {
        return $class instanceof ControllerInterface;
    }

    /**
     * @inheritDoc
     */
    public function registerClass(object $class): void
    {
        $class->register();
    }

    /**
     * @inheritDoc
     */
    public function afterRegister(): void
    {
        do_action('rss_core_controllers_loaded');
    }
}