<?php

declare(strict_types=1);

namespace ReallySimplePlugins\RSS\Core\Services;

/**
 * Global onboarding service for managing onboarding state and visibility.
 * This service provides globally accessible onboarding functionality that can
 * be used throughout the plugin.
 */
class GlobalOnboardingService
{
    /**
     * Reset the onboarding to allow the onboarding modal to be shown again.
     * This called when:
     * - The license is deactivated
     * - The free plugin is deactivated after Pro installation
     * - The user clicks "Activate SSL" after previously dismissing onboarding
     *
     * @return void
     */
    public function resetOnboarding(): void
    {
        update_option('rsssl_show_onboarding', true, false);
        update_option('rsssl_onboarding_dismissed', false, false);
    }
}