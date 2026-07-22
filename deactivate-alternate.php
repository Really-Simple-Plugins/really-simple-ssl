<?php
defined( 'ABSPATH' ) or die();

if ( ! function_exists( 'rsssl_deactivate_alternate' ) ) {
    /**
     * Deactivate the alternate version if active. This function is included in
     * both the pro and free plugin and should be used to deactivate the
     * alternate version upon activation.
     * @param string $target The target plugin to deactivate
     */
    function rsssl_deactivate_alternate( string $target = 'free' ): void {

        $alternate_plugin_path = rsssl_alternate_plugin_path( $target );

        // If no valid target or alternate path, return early
        if ( empty( $alternate_plugin_path ) ) {
            return;
        }

        if ( rsssl_alternate_plugin_active( $target ) ) {
            // we use this to ensure the base function doesn't load, as the active
            // plugins function does not update yet. See RSSSL() in main plugin file
            if ( ! defined( 'RSSSL_DEACTIVATING_ALTERNATE' ) ) {
                define( 'RSSSL_DEACTIVATING_ALTERNATE', true );
            }

            # Get current options
            $is_network_active = is_multisite() && is_plugin_active_for_network( $alternate_plugin_path );
            if ( $is_network_active ) {
                $options = get_site_option( 'rsssl_options', [] );
            } else {
                $options = get_option( 'rsssl_options', [] );
            }

            # Store original values we need to preserve
            $ssl_enabled_was_active = isset( $options['ssl_enabled'] ) && $options['ssl_enabled'];
            $delete_data_on_uninstall_was_enabled = isset( $options['delete_data_on_uninstall'] ) && $options['delete_data_on_uninstall'];

            # Temporarily disable delete_data_on_uninstall to prevent data loss during deactivation
            if ( $delete_data_on_uninstall_was_enabled ) {
                $options['delete_data_on_uninstall'] = false;

                # Save this change before deactivation to prevent data loss
                if ( $is_network_active ) {
                    update_site_option( 'rsssl_options', $options );
                } else {
                    update_option( 'rsssl_options', $options );
                }
            }

            if ( $target === 'free' ) {
                update_option( 'rsssl_free_deactivated', true );
            }

            if ( function_exists( 'deactivate_plugins' ) ) {
                deactivate_plugins( $alternate_plugin_path );
            }

            // Ensure the function exists to prevent fatal errors in case of
            // direct access
            // Delete plugins based on environment and target
            if ( function_exists( 'delete_plugins' ) && function_exists( 'request_filesystem_credentials' ) ) {
                // Always delete free plugin
                if ( $target === 'free' ) {
                    delete_plugins( array( $alternate_plugin_path ) );
                }
                // Delete multisite plugin on non-multisite environments
                elseif ( $target === 'multisite' && ! is_multisite() ) {
                    delete_plugins( array( $alternate_plugin_path ) );
                }
                // Delete pro plugin on multisite environments
                elseif ( $target === 'pro' && is_multisite() ) {
                    delete_plugins( array( $alternate_plugin_path ) );
                }
            }

            # Re-read options after plugin operations to get current state
            if ( $is_network_active ) {
                $options = get_site_option( 'rsssl_options', [] );
            } else {
                $options = get_option( 'rsssl_options', [] );
            }

            # Restore preserved settings
            if ( $ssl_enabled_was_active ) {
                $options['ssl_enabled'] = true;
            }
            if ( $delete_data_on_uninstall_was_enabled ) {
                $options['delete_data_on_uninstall'] = true;
            }

            # Save all option changes at once
            if ( $is_network_active ) {
                update_site_option( 'rsssl_options', $options );
            } else {
                update_option( 'rsssl_options', $options );
            }

            rsssl_delete_free_translation_files( $target );
        }
    }
}

if ( ! function_exists( 'rsssl_alternate_plugin_path' ) ) {
    function rsssl_alternate_plugin_path( string $target ): string {
        switch ( $target ) {
            case 'free':
                return 'really-simple-ssl/rlrsssl-really-simple-ssl.php';
            case 'pro':
                return 'really-simple-ssl-pro/really-simple-ssl-pro.php';
            case 'multisite':
                return 'really-simple-ssl-pro-multisite/really-simple-ssl-pro-multisite.php';
        }

        return '';
    }
}

if ( ! function_exists( 'rsssl_alternate_plugin_active' ) ) {
    function rsssl_alternate_plugin_active( string $target ): bool {
        if ( $target === 'free' && function_exists( 'rsssl_activation_check' ) ) {
            return true;
        }

        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        $alternate_plugin_path = rsssl_alternate_plugin_path( $target );
        if ( empty( $alternate_plugin_path ) ) {
            return false;
        }

        return is_plugin_active( $alternate_plugin_path )
            || ( is_multisite() && is_plugin_active_for_network( $alternate_plugin_path ) );
    }
}

if ( ! function_exists( 'rsssl_free_active' ) ) {
    function rsssl_free_active(): bool {
        if ( function_exists( 'rsssl_activation_check' ) ) {
            return true;
        }

        return rsssl_alternate_plugin_active( 'free' );
    }
}

if ( ! function_exists( 'rsssl_delete_free_translation_files' ) ) {
    function rsssl_delete_free_translation_files( string $target ): void {
        // Delete free translations files from /wp-content/languages/plugins where files contain really-simple-ssl
        if ( $target !== 'free' || ! defined( 'WP_CONTENT_DIR' ) ) {
            return;
        }

        $languages_plugins_dir = WP_CONTENT_DIR . '/languages/plugins';
        if ( ! is_dir( $languages_plugins_dir ) || ! is_writable( $languages_plugins_dir ) ) {
            return;
        }

        $files = scandir( $languages_plugins_dir );
        if ( $files === false ) {
            return;
        }

        foreach ( $files as $file ) {
            if ( is_file( $languages_plugins_dir . '/' . $file ) && strpos( $file, 'really-simple-ssl' ) === 0 ) {
                @unlink( $languages_plugins_dir . '/' . $file );
            }
        }
    }
}
