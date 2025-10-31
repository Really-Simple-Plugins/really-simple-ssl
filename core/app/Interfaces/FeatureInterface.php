<?php

declare(strict_types=1);

namespace ReallySimplePlugins\RSS\Core\Interfaces;

/**
 * This interface can be used to register a feature. Features will only
 * be accepted and registered by {@see FeatureManager} when they implement
 * this interface.
 */
interface FeatureInterface
{
    /**
     * This method should be used to register all hooks and filters. The
     * {@see FeatureManager} will make sure the method is called in the boot
     * process of the plugin.
     */
    public function register(): void;
}