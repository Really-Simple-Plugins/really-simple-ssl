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
        if ( ! function_exists( 'RSSSL' ) ) {
            return false;
        }

        $plugin = RSSSL();

        if ( ! isset( $plugin->licensing ) || ! method_exists( $plugin->licensing, 'license_is_valid' ) ) {
            return false;
        }

        return $plugin->licensing->license_is_valid();
    }
}
