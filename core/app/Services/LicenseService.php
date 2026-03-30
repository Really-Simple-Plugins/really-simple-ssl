<?php

declare(strict_types=1);

namespace ReallySimplePlugins\RSS\Core\Services;

/**
 * Business logic related to the plugin licensing.
 * @todo Move RSSSL()->licensing methods here after full refactor.
 */
final class LicenseService
{
    /**
     * Method returns true if the license is valid. False otherwise.
     */
    public function isValid(): bool
    {
        $pluginInstance = RSSSL();

        if (! isset($pluginInstance->licensing) || ! is_object($pluginInstance->licensing)) {
            return false;
        }

        return $pluginInstance->licensing->license_is_valid();
    }
}
