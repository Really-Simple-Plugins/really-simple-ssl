<?php

namespace RSSSL\Security\WordPress\Two_Fa\Providers;

use RSSSL\Pro\Security\WordPress\Two_Fa\Providers\Rsssl_Provider_Loader_Pro;
use WP_User;

abstract class Rsssl_Provider_Loader {

	public const TWO_FA_PROVIDERS = [ 'totp', 'email', 'passkey' ];

	/**
	 * Retrieves the list of available two-factor authentication providers.
	 *
	 * @return array The array of available provider class names indexed by method name.
	 */
	public static function available_providers(): array {
		$providers = static::get_providers();
		return apply_filters( 'rsssl_two_factor_providers', $providers );
	}

    public static function get_providers(): array {
        $providers = [];
        $directory = __DIR__ ;
	    foreach ( glob( $directory . '/*.php', GLOB_NOSORT ) as $file) {
            $base_name = str_replace('class-', '', basename($file, '.php'));
            $class_name = 'RSSSL\\Security\\WordPress\\Two_Fa\\Providers\\' . str_replace(' ', '_', ucwords(str_replace('-', ' ', $base_name)));
            if (class_exists($class_name) && is_subclass_of($class_name, Rsssl_Two_Factor_Provider_Interface::class)) {
                preg_match('/Rsssl_Two_Factor_(.+)/', $class_name, $matches);
                $method_name = strtolower($matches[1]);
                $providers[$method_name] = $class_name;
            }
        }
        return $providers;
    }

	/**
     * Fetches the enabled providers for the user.
     *
     * @return array
     */
    public static function get_enabled_providers_for_user( WP_User $user ): array {
		$enabled_providers = [];
		foreach ( static::available_providers() as $method => $class ) {
			if ( $class::is_enabled( $user ) ) {
				$enabled_providers[$method] = $class::get_instance( $user );
			}
		}
		return $enabled_providers;
	}

	/**
     * Get the configured providers for the user.
     *
     *
     * @return array
     */
    public static function get_configured_providers_for_user( WP_User $user ): array {
		$configured_providers = [];
		foreach ( static::get_enabled_providers_for_user( $user ) as $method => $provider ) {
			if ( $provider::is_configured( $user ) ) {
				$configured_providers[$method] = $provider::get_instance( $user );
			}
		}
		return $configured_providers;
	}

	/**
	 * Checks is the pro version is active.
	 * @return bool
	 */
	public static function is_pro_active(): bool {
		return defined( 'rsssl_pro' );
	}

	/**
     * Get the enabled providers for the user.
     *
     * @return array
     */
    public static function get_user_enabled_providers( WP_User $user ): array {
		$enabled_providers = [];
		foreach ( self::available_providers() as $method => $provider ) {
			if ( $provider::is_enabled( $user ) ) {
				$enabled_providers[$method] = $provider::get_instance( $user );
			}
		}
		return $enabled_providers;
	}

    /**
     * Loads the correct provider loader based on the active plugin.
     *
     * @return Rsssl_Provider_Loader_Pro|Rsssl_Provider_Loader_Free
     */
    public static function get_loader() {
        return Rsssl_Provider_Loader_Free::is_pro_active() ? new Rsssl_Provider_Loader_Pro() : new Rsssl_Provider_Loader_Free();
    }
}