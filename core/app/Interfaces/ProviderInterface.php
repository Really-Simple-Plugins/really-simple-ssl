<?php

declare(strict_types=1);

namespace ReallySimplePlugins\RSS\Core\Interfaces;

interface ProviderInterface
{
    /**
     * The method that gets called by the ProviderManager to serve the provided
     * functionality.
     */
    public function provide(): void;
}