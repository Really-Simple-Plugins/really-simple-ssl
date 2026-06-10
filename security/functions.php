<?php

use RSSSL\Security\RSSSL_Htaccess_File_Manager;

defined( 'ABSPATH' ) or die( );

if ( !function_exists('rsssl_admin_url')) {
    /**
     * Get admin url, adjusted for multisite
     * @param array $args //query args
     * @param string $path //hash slug for the settings pages (e.g. #dashboard)
     * @return string
     */
    function rsssl_admin_url(array $args = [], string $path = ''): string {
        $url = is_multisite() ? network_admin_url('admin.php') : admin_url('admin.php');
        $args = wp_parse_args($args, ['page' => 'really-simple-security']);
        return add_query_arg($args, $url) . $path;
    }
}

if ( !function_exists('rsssl_maybe_clear_transients')) {
    /**
     * If the corresponding setting has been changed, clear the test cache and re-run it.
     *
     * @return void
     */
    function rsssl_maybe_clear_transients( $field_id, $field_value, $prev_value, $field_type ) {
        if ( $field_id === 'mixed_content_fixer' && $field_value ) {
            delete_transient( 'rsssl_mixed_content_fixer_detected' );
            RSSSL()->admin->mixed_content_fixer_detected();
        }

        //expire in five minutes
        $headers = get_transient('rsssl_can_use_curl_headers_check');
        set_transient('rsssl_can_use_curl_headers_check', $headers, 5 * MINUTE_IN_SECONDS);

        //no change
        if ( $field_value === $prev_value ) {
            return;
        }

        /*
         * Intentional side effect: these helpers repopulate status caches/transients
         * immediately after a setting change. Their return values are not used here.
         */
        if ( $field_id === 'disable_http_methods' ) {
            delete_option( 'rsssl_http_methods_allowed' );
            rsssl_http_methods_allowed();
        }
        if ( $field_id === 'xmlrpc' ) {
            delete_transient( 'rsssl_xmlrpc_allowed' );
            rsssl_xmlrpc_allowed();
        }
        if ( $field_id === 'disable_indexing' ) {
            delete_transient( 'rsssl_directory_indexing_status' );
            // Prime transient immediately so settings responses reflect the latest post-save state.
            rsssl_directory_indexing_allowed();
        }
        if ( $field_id === 'block_code_execution_uploads' ) {
            delete_transient( 'rsssl_code_execution_allowed_status' );
            // Prime transient immediately so settings responses reflect the latest post-save state.
            rsssl_code_execution_allowed();
        }
        if ( $field_id === 'hide_wordpress_version' ) {
            delete_option( 'rsssl_wp_version_detected' );
            rsssl_src_contains_wp_version();
        }
        if ( $field_id === 'rename_admin_user' ) {
            delete_transient('rsssl_admin_user_count');
            rsssl_has_admin_user();
        }

    }

    add_action( "rsssl_after_save_field", 'rsssl_maybe_clear_transients', 100, 4 );
}

/**
 * Remove RSSSL-managed `.htaccess` rules during deactivation without touching unrelated content.
 *
 * Cleanup deliberately bypasses `do_not_edit_htaccess`: disabling future managed writes should not prevent
 * plugin-owned rules from being removed during uninstall or explicit revert-to-HTTP deactivation.
 * The cleanup itself still runs under the shared managed-htaccess serializer so it cannot interleave
 * with a concurrent queued rebuild from another request.
 */
function rsssl_run_managed_htaccess_cleanup( bool $clear_htaccess_redirect = false ): void {
    if ( ! rsssl_user_can_manage() ) {
        return;
    }

    if ( ! rsssl_uses_htaccess() ) {
        return;
    }

    $lock_handle = rsssl_acquire_htaccess_update_process_lock();
    if ( $lock_handle === false ) {
        RSSSL_Htaccess_File_Manager::get_instance()->log_error(
            'Failed to acquire the managed .htaccess serializer during deactivation cleanup.'
        );
        return;
    }

    $switched_to_main_site = false;
    try {
        rsssl_set_htaccess_deactivation_lockout();
        rsssl_clear_managed_htaccess_update_state();
        if ( rsssl_should_run_htaccess_updates_in_main_site_context() ) {
            $main_site_id = (int) get_main_site_id();
            if ( $main_site_id > 0 && get_current_blog_id() !== $main_site_id ) {
                switch_to_blog( $main_site_id );
                $switched_to_main_site = true;
            }
        }

        RSSSL_Htaccess_File_Manager::get_instance()->run_with_do_not_edit_htaccess_bypass(
            static function() use ( $clear_htaccess_redirect ): void {
                rsssl_htaccess_clear_errors();
                rsssl_remove_uploads_htaccess_security_edits();
                rsssl_remove_root_htaccess_security_edits( $clear_htaccess_redirect );
                rsssl_clear_root_htaccess_status();
                rsssl_clear_uploads_htaccess_status();
                rsssl_clear_managed_htaccess_update_state();
            }
        );
    } finally {
        if ( $switched_to_main_site ) {
            restore_current_blog();
        }
        rsssl_release_htaccess_update_process_lock( $lock_handle );
        rsssl_maybe_delete_htaccess_update_process_lock_file();
    }
}

/**
 * Remove the global managed-htaccess lock file during deactivation cleanup once the lock has been released.
 *
 * The file is only the advisory lock carrier. It is recreated automatically on the next executor run, so
 * best-effort cleanup here is safe and avoids leaving an unused lock file behind after plugin deactivation.
 */
function rsssl_maybe_delete_htaccess_update_process_lock_file(): void {
    $lock_path = rsssl_get_htaccess_update_process_lock_path();
    if ( $lock_path === '' || ! is_file( $lock_path ) ) {
        return;
    }

    @unlink( $lock_path );
}

/**
 * Clear the managed `.htaccess` retry cooldown regardless of which option scope currently stores it.
 *
 * Manual retries and activation-mode changes should never remain blocked by a stale timestamp in the
 * inactive storage backend, so we clear both the per-site and network variants together.
 */
function rsssl_clear_htaccess_retry_cooldown(): void {
    delete_option( 'rsssl_htaccess_retry_cooldown' );
    delete_site_option( 'rsssl_htaccess_retry_cooldown' );
}

/**
 * Clear queued rebuild state so deactivation cleanup wins over any stale scheduled write in this request.
 */
function rsssl_clear_managed_htaccess_update_state(): void {
    delete_option( 'rsssl_htaccess_needs_update' );
    delete_site_option( 'rsssl_htaccess_needs_update' );
    rsssl_clear_htaccess_retry_cooldown();
    rsssl_clear_pending_htaccess_branding_migration();
}

/**
 * Set a short-lived lockout so stale in-flight requests cannot requeue `.htaccess` writes after cleanup wins.
 */
function rsssl_set_htaccess_deactivation_lockout(): void {
    $duration = (int) apply_filters( 'rsssl_htaccess_deactivation_lockout_duration', 30 );
    if ( $duration < 1 ) {
        $duration = 30;
    }

    $expires_at = time() + $duration;
    if ( rsssl_should_use_network_htaccess_queue_flag() ) {
        update_site_option( 'rsssl_htaccess_deactivation_lockout_until', $expires_at );
        return;
    }

    update_option( 'rsssl_htaccess_deactivation_lockout_until', $expires_at, false );
}

/**
 * Auto-clear the short-lived deactivation lockout after it expires.
 */
function rsssl_is_htaccess_deactivation_lockout_active(): bool {
    if ( rsssl_should_use_network_htaccess_queue_flag() ) {
        $expires_at = (int) get_site_option( 'rsssl_htaccess_deactivation_lockout_until', 0 );
        if ( $expires_at <= time() ) {
            delete_site_option( 'rsssl_htaccess_deactivation_lockout_until' );
            return false;
        }

        return true;
    }

    $expires_at = (int) get_option( 'rsssl_htaccess_deactivation_lockout_until', 0 );
    if ( $expires_at <= time() ) {
        delete_option( 'rsssl_htaccess_deactivation_lockout_until' );
        return false;
    }

    return true;
}

/**
 * Clear root `.htaccess` markers from the current target and, when relevant, from the historical wp-content fallback.
 */
function rsssl_remove_root_htaccess_security_edits( bool $clear_htaccess_redirect = false ): void {
    $root_manager = RSSSL_Htaccess_File_Manager::get_instance();
    $operations = rsssl_build_root_htaccess_cleanup_operations( $clear_htaccess_redirect );
    $htaccess_file = $root_manager->determineExistingRootHtaccessFilePath();

    if ( $htaccess_file !== '' && $root_manager->is_valid_htaccess_file_path( $htaccess_file ) ) {
        $root_rules_cleared = $root_manager->write_rules_atomic_for_path(
            $htaccess_file,
            $operations
        );
        if ( ! $root_rules_cleared ) {
            $root_manager->log_error( 'Failed to clear root .htaccess security rules during deactivation cleanup.' );
        }
    }

    $legacy_fallback_path = $root_manager->get_wp_content_htaccess_fallback_path();
    if ( ! rsssl_should_cleanup_legacy_root_htaccess_fallback( $root_manager, $legacy_fallback_path, $htaccess_file, $clear_htaccess_redirect ) ) {
        return;
    }

    if ( ! $root_manager->write_rules_atomic_for_path(
        $legacy_fallback_path,
        $operations
    ) ) {
        $root_manager->log_error(
            'Failed to clear legacy wp-content .htaccess security rules during deactivation cleanup.'
        );
    }
}

/**
 * Only touch the historical wp-content fallback when it exists, is writable, and still contains RSSSL-owned markers.
 */
function rsssl_should_cleanup_legacy_root_htaccess_fallback(
    RSSSL_Htaccess_File_Manager $root_manager,
    string $fallback_path,
    string $current_root_path = '',
    bool $clear_htaccess_redirect = false
): bool {
    if ( $fallback_path === '' || $fallback_path === $current_root_path ) {
        return false;
    }

    if ( ! is_file( $fallback_path ) || ! $root_manager->is_valid_htaccess_file_path( $fallback_path ) ) {
        return false;
    }

    return rsssl_root_htaccess_file_contains_rsssl_markers(
        $root_manager,
        $fallback_path,
        $clear_htaccess_redirect
    );
}

/**
 * Detect whether a root `.htaccess` file still contains RSSSL-owned marker blocks that cleanup should remove.
 */
function rsssl_root_htaccess_file_contains_rsssl_markers(
    RSSSL_Htaccess_File_Manager $root_manager,
    string $htaccess_file,
    bool $clear_htaccess_redirect = false
): bool {
    foreach ( rsssl_get_root_htaccess_cleanup_markers( $clear_htaccess_redirect ) as $marker ) {
        if ( $root_manager->get_rule_content_for_path( $htaccess_file, $marker ) !== null ) {
            return true;
        }
    }

    $content = $root_manager->get_htaccess_content_for_path( $htaccess_file );
    if ( ! is_string( $content ) ) {
        return false;
    }

    return preg_match(
               $root_manager->get_exact_legacy_generic_root_marker_block_pattern(),
               $content
           ) === 1;
}

/**
 * Remove uploads `.htaccess` rules for the active scope, fanning out across blogs when the plugin runs network-wide.
 */
function rsssl_remove_uploads_htaccess_security_edits(): void {
    // Networkwide mode stores one shared setting, but uploads .htaccess lives per blog.
    if ( ! rsssl_should_process_uploads_htaccess_network_wide() ) {
        rsssl_remove_current_blog_uploads_htaccess_security_edits();
        rsssl_clear_uploads_htaccess_status();
        return;
    }

    rsssl_for_each_network_site_for_uploads_htaccess(
        static function( int $site_id ): void {
            switch_to_blog( $site_id );
            try {
                rsssl_remove_current_blog_uploads_htaccess_security_edits();
            } finally {
                restore_current_blog();
            }
        }
    );

    rsssl_clear_uploads_htaccess_status();
}

/**
 * Remove the uploads `.htaccess` file for the current blog when it is plugin-owned and no longer needed.
 */
function rsssl_remove_current_blog_uploads_htaccess_security_edits(): void {
    $upload_dir = wp_get_upload_dir();
    $htaccess_file_uploads = trailingslashit( $upload_dir['basedir'] ) . '.htaccess';
    if ( ! is_file( $htaccess_file_uploads ) ) {
        return;
    }

    $upload_manager = RSSSL_Htaccess_File_Manager::get_instance();
    if ( $upload_manager->write_uploads_htaccess_atomic(
        $htaccess_file_uploads,
        ''
    ) ) {
        return;
    }

    $upload_manager->log_error(
        'Failed to clear uploads .htaccess rules during deactivation cleanup. Blog ID: '
        . get_current_blog_id()
        . '. Path: '
        . $htaccess_file_uploads
    );
}

/**
 * Managed `.htaccess` architecture overview:
 *
 * 1. Callers never write root/uploads/LE `.htaccess` files directly during normal runtime.
 *    They request a rebuild, which only marks the shared queue as dirty.
 * 2. The executor claims that dirty flag once, resolves the latest rule state, and then processes
 *    every managed target in a deterministic order: uploads -> Let's Encrypt auxiliary files -> root.
 * 3. Each target file is written at most once per executor pass. Root rule cleanup, exact legacy
 *    marker removal, and queued branding replacement are all folded into that single root write.
 * 4. The executor lock serializes RSSSL against itself. It does not prevent unrelated plugins from
 *    opening or writing `.htaccess` unless they also cooperate with the same advisory lock model.
 */
if ( ! function_exists( 'rsssl_request_managed_rule_rebuild' ) ) {
    /**
     * Request a full managed-rule rebuild while preserving the public `rsssl_update_rules` hook contract.
     * The legacy action remains the compatibility entrypoint and then fans out into the dedicated
     * `rsssl_update_advanced_headers` and `rsssl_update_htaccess` actions in that order.
     */
    function rsssl_request_managed_rule_rebuild(): void {
        do_action( 'rsssl_update_rules' );
    }

    /**
     * Request only the managed `.htaccess` rebuild flow.
     */
    function rsssl_request_managed_htaccess_rebuild(): void {
        do_action( 'rsssl_update_htaccess' );
    }

    /**
     * Clear legacy managed `.htaccess` state and request a rebuild during upgrades.
     *
     * @param string|false $prev_version Previously stored plugin version.
     */
    function rsssl_upgrade_migrate_htaccess_state( $prev_version ): void {
        if ( ! $prev_version ) {
            return;
        }

        delete_option( 'rsssl_htaccess_should_wrap' );
        delete_site_option( 'rsssl_htaccess_should_wrap' );
        delete_option( 'rsssl_updating_htaccess' );
        delete_site_option( 'rsssl_updating_htaccess' );
        rsssl_clear_htaccess_retry_cooldown();
        rsssl_request_managed_htaccess_rebuild();
    }

    /**
     * Request only the advanced-headers / firewall file rebuild flow.
     */
    function rsssl_request_advanced_headers_rebuild(): void {
        do_action( 'rsssl_update_advanced_headers' );
    }

    /*
     * Preserve the legacy `rsssl_update_rules` action as the public compatibility surface while
     * routing the actual work through the dedicated target-specific actions in the historical order:
     * advanced-headers first, managed `.htaccess` second.
     */
    add_action( 'rsssl_update_rules', 'rsssl_request_advanced_headers_rebuild', 10 );
    add_action( 'rsssl_update_rules', 'rsssl_request_managed_htaccess_rebuild', 30 );
}

/**
 * Mark the managed `.htaccess` set dirty and defer actual writes to the centralized executor.
 * This function never writes a file itself.
 */
function rsssl_schedule_htaccess_update(): void {
    if ( rsssl_is_htaccess_deactivation_lockout_active() ) {
        return;
    }

    // Multiple triggers can mark the same generation dirty; the executor collapses them into one pass.
    rsssl_set_htaccess_update_flag();
    rsssl_queue_htaccess_update_flush();
}

/**
 * Use site options for network-wide setups to avoid storing the queue flag in a single subsite context.
 */
function rsssl_should_use_network_htaccess_queue_flag(): bool {
    if ( ! function_exists( 'rsssl_is_networkwide_active' ) ) {
        return false;
    }

    return is_multisite() && rsssl_is_networkwide_active();
}

/**
 * Mark the shared queue as dirty so the next executor run rebuilds uploads, Let's Encrypt auxiliary files, and root rules.
 */
function rsssl_set_htaccess_update_flag(): void {
    // Per-site option for regular installs; network option for network-wide multisite activation.
    if ( rsssl_should_use_network_htaccess_queue_flag() ) {
        update_site_option( 'rsssl_htaccess_needs_update', true );
        return;
    }

    update_option( 'rsssl_htaccess_needs_update', true, false );
}

/**
 * Read the current queue flag from the correct option scope for this install.
 */
function rsssl_get_htaccess_update_flag(): bool {
    if ( rsssl_should_use_network_htaccess_queue_flag() ) {
        return (bool) get_site_option( 'rsssl_htaccess_needs_update', false );
    }

    return (bool) get_option( 'rsssl_htaccess_needs_update', false );
}

/**
 * Register one shutdown flush per request to prevent duplicate write attempts in the same request lifecycle.
 */
function rsssl_queue_htaccess_update_flush(): void {
    static $flush_registered = false;
    if ( $flush_registered ) {
        return;
    }

    $flush_registered = true;
    add_action( 'shutdown', 'rsssl_flush_scheduled_htaccess_update', 0 );
}

/**
 * Flush at shutdown so all option/filter mutations in the current request are finalized before building rules.
 */
function rsssl_flush_scheduled_htaccess_update(): void {
    if ( ! rsssl_get_htaccess_update_flag() ) {
        return;
    }

    // Both shutdown and explicit same-request flushes land here before the centralized executor runs.
    rsssl_process_scheduled_htaccess_update();
}

/**
 * Cooldown prevents tight retry loops when writes fail due to temporary FS/permission issues.
 */
function rsssl_set_htaccess_retry_cooldown(): void {
    $expires_at = time() + MINUTE_IN_SECONDS;
    if ( rsssl_should_use_network_htaccess_queue_flag() ) {
        update_site_option( 'rsssl_htaccess_retry_cooldown', $expires_at );
        return;
    }

    update_option( 'rsssl_htaccess_retry_cooldown', $expires_at, false );
}

/**
 * Auto-clears expired cooldown so retries resume without requiring a manual reset.
 */
function rsssl_is_htaccess_retry_cooldown_active(): bool {
    if ( rsssl_should_use_network_htaccess_queue_flag() ) {
        $expires_at = (int) get_site_option( 'rsssl_htaccess_retry_cooldown', 0 );
        if ( $expires_at <= time() ) {
            delete_site_option( 'rsssl_htaccess_retry_cooldown' );
            return false;
        }

        return true;
    }

    $expires_at = (int) get_option( 'rsssl_htaccess_retry_cooldown', 0 );
    if ( $expires_at <= time() ) {
        delete_option( 'rsssl_htaccess_retry_cooldown' );
        return false;
    }

    return true;
}

/**
 * Re-queue after failure to preserve eventual consistency without blocking the current admin request.
 */
function rsssl_reschedule_failed_htaccess_update(): void {
    rsssl_set_htaccess_retry_cooldown();
    rsssl_schedule_htaccess_update();
}

/**
 * Atomically claim the dirty flag by deleting it from the options table.
 *
 * This is the second layer of RSSSL self-serialization:
 * - the executor lock decides who may attempt a run
 * - the SQL delete decides which request owns the currently queued generation
 *
 * It does not coordinate with third-party plugins; it only makes sure two RSSSL requests do not
 * both believe they own the same queued generation.
 */
function rsssl_claim_queued_htaccess_generation(): bool {
    if ( rsssl_is_htaccess_retry_cooldown_active() ) {
        return false;
    }

    if ( ! rsssl_get_htaccess_update_flag() ) {
        return false;
    }

    global $wpdb;
    if ( rsssl_should_use_network_htaccess_queue_flag() ) {
        $network_id = function_exists( 'get_current_network_id' ) ? (int) get_current_network_id() : 1;
        // Delete is the atomic claim: any positive delete count means this request claimed the queued generation.
        $deleted = $wpdb->delete(
            $wpdb->sitemeta,
            [
                'site_id'  => $network_id,
                'meta_key' => 'rsssl_htaccess_needs_update',
            ],
            [
                '%d',
                '%s',
            ]
        );
        wp_cache_delete( $network_id . ':rsssl_htaccess_needs_update', 'site-options' );
        wp_cache_delete( $network_id . ':notoptions', 'site-options' );
        return $deleted > 0;
    }

    // Delete is the atomic claim: any positive delete count means this request claimed the queued generation.
    $deleted = $wpdb->delete(
        $wpdb->options,
        [ 'option_name' => 'rsssl_htaccess_needs_update' ],
        [ '%s' ]
    );
    wp_cache_delete( 'rsssl_htaccess_needs_update', 'options' );
    wp_cache_delete( 'alloptions', 'options' );
    return $deleted > 0;
}

/**
 * Use a dedicated lock file so only one RSSSL request can orchestrate managed `.htaccess` targets at a time.
 * The lock file is separate from the target `.htaccess` files because it protects the executor as a whole,
 * not just one concrete file commit.
 */
function rsssl_get_htaccess_update_process_lock_path(): string {
    return trailingslashit( WP_CONTENT_DIR ) . 'rsssl-managed-htaccess.lock';
}

/**
 * Build the advisory database lock name used when filesystem locking is unavailable.
 * This fallback still only serializes RSSSL against itself; unrelated plugins will not honor it.
 */
function rsssl_get_htaccess_update_process_db_lock_name(): string {
    $scope = rsssl_should_use_network_htaccess_queue_flag()
        ? 'network_' . ( function_exists( 'get_current_network_id' ) ? (int) get_current_network_id() : 1 )
        : 'site_' . get_current_blog_id();

    return 'rsssl_htaccess_' . md5( ABSPATH . '|' . $scope );
}

/**
 * Try the dedicated filesystem executor lock first.
 *
 * Return statuses:
 * - `acquired`: this request owns the executor via the lock file
 * - `contended`: another cooperative request currently owns the executor; do not bypass it
 * - `unavailable`: filesystem locking itself is not usable here, so the caller may fall back to DB locking
 *
 * This lock is advisory. It coordinates RSSSL requests and any other cooperative code, but it does not
 * stop unrelated plugins from opening or writing files unless they also use advisory locking.
 *
 * @return array{status:string,handle:resource|null}
 */
function rsssl_try_acquire_htaccess_update_process_file_lock(): array {
    $lock_path = rsssl_get_htaccess_update_process_lock_path();
    $handle    = @fopen( $lock_path, 'c+' );
    if ( $handle === false ) {
        return [
            'status' => 'unavailable',
            'handle' => null,
        ];
    }

    $max_attempts = (int) apply_filters( 'rsssl_htaccess_process_lock_max_attempts', 30 );
    $delay        = (int) apply_filters( 'rsssl_htaccess_process_lock_attempt_delay', 100000 );
    if ( $max_attempts < 1 ) {
        $max_attempts = 1;
    }
    if ( $delay < 0 ) {
        $delay = 0;
    }

    $attempt = 0;
    while ( $attempt < $max_attempts ) {
        $would_block = 0;
        if ( @flock( $handle, LOCK_EX | LOCK_NB, $would_block ) ) {
            return [
                'status' => 'acquired',
                'handle' => $handle,
            ];
        }

        // `would_block === 1` means the lock works and is simply held by someone else.
        // Any other outcome is treated as "locking unavailable", which triggers the DB fallback.
        if ( (int) $would_block !== 1 ) {
            fclose( $handle );
            return [
                'status' => 'unavailable',
                'handle' => null,
            ];
        }

        $attempt++;
        if ( $attempt >= $max_attempts ) {
            break;
        }

        usleep( $delay );
    }

    fclose( $handle );
    return [
        'status' => 'contended',
        'handle' => null,
    ];
}

/**
 * Try to serialize the executor with a MySQL/MariaDB advisory lock when filesystem locking is unavailable.
 *
 * This is deliberately a fallback, not the primary mechanism:
 * - filesystem locks remain the best coordination path when supported
 * - DB advisory locks keep RSSSL self-serialization intact on hosts where `flock()` is not usable
 *
 * Like `flock()`, this lock is advisory. It does not protect against non-cooperative third-party writers.
 *
 * @return bool|null True when acquired, false when contended, null when unavailable.
 */
function rsssl_try_acquire_htaccess_update_process_db_lock( string $lock_name ) {
    global $wpdb;

    $max_attempts = (int) apply_filters( 'rsssl_htaccess_process_lock_max_attempts', 30 );
    $delay        = (int) apply_filters( 'rsssl_htaccess_process_lock_attempt_delay', 100000 );
    if ( $max_attempts < 1 ) {
        $max_attempts = 1;
    }
    if ( $delay < 0 ) {
        $delay = 0;
    }

    $attempt = 0;
    while ( $attempt < $max_attempts ) {
        $result = $wpdb->get_var(
            $wpdb->prepare( 'SELECT GET_LOCK(%s, 0)', $lock_name )
        );
        if ( $result === '1' || $result === 1 ) {
            return true;
        }

        if ( $result === '0' || $result === 0 ) {
            $attempt++;
            if ( $attempt >= $max_attempts ) {
                return false;
            }

            usleep( $delay );
            continue;
        }

        return null;
    }

    return false;
}

/**
 * Return the shared option name used to persist degraded-lock warnings.
 */
function rsssl_get_htaccess_lock_warning_option_name(): string {
    return 'rsssl_htaccess_lock_warning';
}

/**
 * Return the shared degraded-lock notice text.
 */
function rsssl_get_htaccess_lock_warning_message(): string {
    return __(
        'Really Simple Security could not acquire a filesystem lock for a managed .htaccess update. The plugin fell back to atomic temporary-file replacement and best-effort serialization where available. This still protects against partial writes, but concurrent writers that do not share the same locking model may overwrite each other. Networked filesystems such as NFS can cause this behavior.',
        'really-simple-ssl'
    );
}

/**
 * Store the generic lock warning notice for one day so admins see degraded locking conditions.
 * The warning is shared by both the executor lock and the per-file writers.
 */
function rsssl_set_htaccess_lock_warning_notice(): void {
    $option_name   = rsssl_get_htaccess_lock_warning_option_name();
    $warning       = get_option( $option_name, [] );
    $last_shown_at = is_array( $warning ) ? (int) ( $warning['timestamp'] ?? 0 ) : 0;
    if ( $last_shown_at > ( time() - DAY_IN_SECONDS ) ) {
        return;
    }

    update_option(
        $option_name,
        [
            'timestamp' => time(),
            'message'   => rsssl_get_htaccess_lock_warning_message(),
        ],
        false
    );
}

/**
 * Acquire the global managed-htaccess executor lock with bounded retries.
 *
 * Lock hierarchy:
 * 1. Try the dedicated filesystem executor lock.
 * 2. If locking itself is unavailable, show the warning and fall back to a DB advisory lock.
 * 3. If the filesystem lock is merely contended, do not bypass it with the DB fallback.
 *
 * That distinction matters: contention means cooperative locking is working and another RSSSL request
 * is already in the critical section. Falling back in that situation would defeat the serializer.
 *
 * @return array{type:string,handle?:resource,name?:string}|false
 */
function rsssl_acquire_htaccess_update_process_lock() {
    $file_lock = rsssl_try_acquire_htaccess_update_process_file_lock();
    if ( $file_lock['status'] === 'acquired' ) {
        return [
            'type'   => 'file',
            'handle' => $file_lock['handle'],
        ];
    }

    if ( $file_lock['status'] === 'contended' ) {
        return false;
    }

    rsssl_set_htaccess_lock_warning_notice();
    $db_lock_name = rsssl_get_htaccess_update_process_db_lock_name();
    $db_lock      = rsssl_try_acquire_htaccess_update_process_db_lock( $db_lock_name );
    if ( $db_lock !== true ) {
        return false;
    }

    return [
        'type' => 'db',
        'name' => $db_lock_name,
    ];
}

/**
 * Release one advisory database lock if it was acquired.
 *
 * `RELEASE_LOCK()` returns:
 * - `1` when this connection released the lock
 * - `0` when this connection did not own the lock
 * - `NULL` on error
 *
 * We keep cleanup best-effort here, but log unexpected release outcomes for debugging.
 */
function rsssl_release_htaccess_update_process_db_lock( string $lock_name ): void {
    global $wpdb;
    if ( $lock_name === '' ) {
        return;
    }

    $result = $wpdb->get_var(
        $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name )
    );
    if ( $result === '1' || $result === 1 ) {
        return;
    }

    if ( class_exists( RSSSL_Htaccess_File_Manager::class ) ) {
        $status = is_scalar( $result ) ? (string) $result : 'null';
        RSSSL_Htaccess_File_Manager::get_instance()->log_error(
            'Failed to release managed .htaccess DB lock "' . esc_html( $lock_name ) . '" (result: ' . esc_html( $status ) . ').'
        );
    }
}

/**
 * Release one filesystem executor lock if it was acquired.
 *
 * This global helper is intentionally stricter than a generic
 * `flock()`/`fclose()` wrapper: it only releases the dedicated RSSSL
 * executor lock file so unrelated resources cannot be closed through
 * this function.
 *
 * @param resource $handle
 */
function rsssl_release_htaccess_update_process_file_lock( $handle ): void {
    if ( ! is_resource( $handle ) || get_resource_type( $handle ) !== 'stream' ) {
        return;
    }

    // Validate the underlying stream target before releasing it so this
    // public function cannot unlock/close arbitrary resources when
    // called with the wrong handle.
    $meta = stream_get_meta_data( $handle );
    $uri  = $meta['uri'] ?? '';
    if ( ! is_string( $uri ) || $uri === '' ) {
        return;
    }

    $normalized_uri = wp_normalize_path( $uri );
    $normalized_lock_path = wp_normalize_path( rsssl_get_htaccess_update_process_lock_path() );
    if ( $normalized_uri !== $normalized_lock_path ) {
        // Log and bail instead of touching a non-RSSSL stream; safety matters more than best-effort release here.
        if ( class_exists( RSSSL_Htaccess_File_Manager::class ) ) {
            RSSSL_Htaccess_File_Manager::get_instance()->log_error(
                'Refused to release a non-RSSSL filesystem lock resource through rsssl_release_htaccess_update_process_file_lock().'
            );
        }
        return;
    }

    flock( $handle, LOCK_UN );
    fclose( $handle );
}

/**
 * Release whichever executor lock variant was acquired.
 *
 * @param array{type:string,handle?:resource,name?:string}|resource $handle
 */
function rsssl_release_htaccess_update_process_lock( $handle ): void {
    if ( is_array( $handle ) ) {
        $type = $handle['type'] ?? '';
        if ( $type === 'db' ) {
            rsssl_release_htaccess_update_process_db_lock( (string) ( $handle['name'] ?? '' ) );
        } elseif ( $type === 'file' ) {
            rsssl_release_htaccess_update_process_file_lock( $handle['handle'] ?? null );
        }

        return;
    }

    rsssl_release_htaccess_update_process_file_lock( $handle );
}

/**
 * Sync plugin-owned auxiliary `.htaccess` targets through the shared executor.
 * These files are separate managed targets, so "single write" here means one atomic write per target file,
 * not one transaction across every `.htaccess` file in the install.
 */
function rsssl_sync_letsencrypt_directory_htaccess_rules(): bool {
    if ( ! function_exists( 'RSSSL_LE' ) ) {
        return true;
    }

    $letsencrypt = RSSSL_LE();
    if ( ! isset( $letsencrypt->letsencrypt_handler ) || ! is_object( $letsencrypt->letsencrypt_handler ) ) {
        return true;
    }

    if ( ! method_exists( $letsencrypt->letsencrypt_handler, 'sync_letsencrypt_directory_htaccess_files' ) ) {
        return true;
    }

    return (bool) $letsencrypt->letsencrypt_handler->sync_letsencrypt_directory_htaccess_files();
}

/**
 * Claim the queued update and run the managed `.htaccess` executor.
 *
 * This is the only normal runtime path that may orchestrate managed `.htaccess` writes.
 * Target order is deterministic:
 * 1. uploads `.htaccess`
 * 2. Let's Encrypt auxiliary `.htaccess` files
 * 3. root `.htaccess`
 *
 * After each stage we re-check the dirty flag. If another request queued fresher state while we were
 * working, we stop early and let the next executor pass rebuild from that newer snapshot instead of
 * continuing to commit older derived state to later targets.
 */
function rsssl_process_scheduled_htaccess_update(): void {
    if ( ! rsssl_should_process_scheduled_htaccess_update() ) {
        return;
    }

    static $processing = false;
    if ( $processing ) {
        return;
    }

    // Serialize the full uploads -> Let's Encrypt -> root pipeline before attempting a queued run.
    $lock_handle = rsssl_acquire_htaccess_update_process_lock();
    if ( $lock_handle === false ) {
        return;
    }

    $processing            = true;
    $switched_to_main_site = false;

    try {
        // The lock only gates executor entry; the queue row delete below decides whether this pass owns the queued generation.
        if ( ! rsssl_claim_queued_htaccess_generation() ) {
            return;
        }

        /*
         * The queue flag is shared network-wide. Running the executor from the main-site context keeps
         * root path resolution deterministic even if a retry is claimed during a subsite admin request.
         */
        if ( rsssl_should_run_htaccess_updates_in_main_site_context() ) {
            $main_site_id = (int) get_main_site_id();
            if ( $main_site_id > 0 && get_current_blog_id() !== $main_site_id ) {
                switch_to_blog( $main_site_id );
                $switched_to_main_site = true;
            }
        }

        rsssl_htaccess_clear_errors();
        $uploads_updated = rsssl_sync_uploads_htaccess_rules();
        if ( rsssl_get_htaccess_update_flag() ) {
            // A newer generation was queued mid-run, so stop before committing later targets from stale state.
            return;
        }

        $letsencrypt_updated = rsssl_sync_letsencrypt_directory_htaccess_rules();
        if ( rsssl_get_htaccess_update_flag() ) {
            // A newer generation was queued mid-run, so let the next pass rebuild root from fresher inputs.
            return;
        }

        $root_updated = rsssl_sync_root_htaccess_rules();
        if ( ( ! $uploads_updated || ! $letsencrypt_updated || ! $root_updated ) && ! rsssl_get_htaccess_update_flag() ) {
            rsssl_reschedule_failed_htaccess_update();
        }
    } catch ( Throwable $throwable ) {
        if ( ! rsssl_get_htaccess_update_flag() ) {
            rsssl_reschedule_failed_htaccess_update();
        }
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'RSSSL scheduled htaccess update failed: ' . $throwable->getMessage() );
        }
    } finally {
        if ( $switched_to_main_site ) {
            restore_current_blog();
        }
        rsssl_release_htaccess_update_process_lock( $lock_handle );
        $processing = false;
    }
}

add_action( 'admin_init', 'rsssl_process_scheduled_htaccess_update' );
add_action( 'rsssl_after_saved_fields', 'rsssl_schedule_htaccess_update', 30 );
add_action( 'rocket_activation', 'rsssl_schedule_htaccess_update', 5 );
add_action( 'rocket_deactivation', 'rsssl_schedule_htaccess_update', 5 );
add_action( 'rsssl_firewall_settings_changed', 'rsssl_schedule_htaccess_update' );
add_action( 'rsssl_update_htaccess', 'rsssl_schedule_htaccess_update', 30 );

/**
 * Reuse the dashboard notice Re-check action to rerun the managed `.htaccess` executor immediately.
 */
function rsssl_recheck_managed_htaccess( $data ): void {
    if ( ! rsssl_user_can_manage() ) {
        return;
    }

    $cache_id = sanitize_title( $data['cache_id'] ?? '' );
    if ( $cache_id !== 'managed_htaccess' ) {
        return;
    }

    rsssl_clear_htaccess_retry_cooldown();

    rsssl_request_managed_htaccess_rebuild();
    rsssl_process_scheduled_htaccess_update();

    if ( function_exists( 'RSSSL' ) && isset( RSSSL()->admin ) && method_exists( RSSSL()->admin, 'clear_admin_notices_cache' ) ) {
        RSSSL()->admin->clear_admin_notices_cache();
    }
}
add_action( 'rsssl_clear_test_caches', 'rsssl_recheck_managed_htaccess', 30, 1 );

function rsssl_show_htaccess_lock_warning_notice(): void {
    if ( ! rsssl_user_can_manage() ) {
        return;
    }

    $option_name = rsssl_get_htaccess_lock_warning_option_name();
    $warning = get_option( $option_name, [] );
    if ( ! is_array( $warning ) ) {
        return;
    }

    $timestamp = (int) ( $warning['timestamp'] ?? 0 );
    if ( $timestamp > 0 && $timestamp <= ( time() - DAY_IN_SECONDS ) ) {
        delete_option( $option_name );
        return;
    }

    $message = (string) ( $warning['message'] ?? '' );
    if ( $message === '' ) {
        return;
    }

    echo '<div class="notice notice-warning"><p>' . esc_html( $message ) . '</p></div>';
}
add_action( 'admin_notices', 'rsssl_show_htaccess_lock_warning_notice' );

/**
 * Check whether the global `do_not_edit_htaccess` setting currently disables managed `.htaccess` writes.
 */
function rsssl_should_skip_managed_htaccess_writes(): bool {
    if ( ! function_exists( 'rsssl_get_option' ) ) {
        return false;
    }

    return (bool) rsssl_get_option( 'do_not_edit_htaccess' );
}

/**
 * Decide whether the queued executor should run in the current request.
 */
function rsssl_should_process_scheduled_htaccess_update(): bool {
    if ( ! rsssl_user_can_manage() || ! rsssl_uses_htaccess() ) {
        return false;
    }
    if ( ! rsssl_get_htaccess_update_flag() ) {
        return false;
    }
    if ( rsssl_is_htaccess_deactivation_lockout_active() ) {
        rsssl_clear_managed_htaccess_update_state();
        return false;
    }
    if ( rsssl_should_skip_managed_htaccess_writes() ) {
        rsssl_htaccess_clear_errors();
        return false;
    }

    return true;
}

/**
 * Run the queued write in main-site context when the network shares root-level managed targets.
 */
function rsssl_should_run_htaccess_updates_in_main_site_context(): bool {
    if ( ! is_multisite() ) {
        return false;
    }

    if ( ! function_exists( 'get_main_site_id' ) ) {
        return false;
    }

    if ( ! function_exists( 'rsssl_is_networkwide_active' ) ) {
        return false;
    }

    return rsssl_is_networkwide_active();
}

/**
 * Build and persist the desired root `.htaccess` state in one atomic manager write.
 *
 * Every root mutation is assembled in memory first:
 * - legacy marker removals
 * - exact bare legacy generic block cleanup
 * - queued branding replacement
 * - current desired RSSSL marker blocks
 *
 * Only after that full operation list is ready do we call the file manager once for the root target.
 */
function rsssl_sync_root_htaccess_rules(): bool {
    $rules = apply_filters( 'rsssl_htaccess_security_rules', [] );
    $manager = RSSSL_Htaccess_File_Manager::get_instance();
    $htaccess_file = $manager->get_root_htaccess_target_path();
    if ( rsssl_should_skip_root_htaccess_write( $rules, $htaccess_file ) ) {
        rsssl_clear_pending_htaccess_branding_migration();
        return true;
    }

    if ( $htaccess_file === '' ) {
        rsssl_store_root_htaccess_error( RSSSL_Htaccess_File_Manager::ERROR_NOT_SUPPORTED, $rules );
        return true;
    }

    if ( ! rsssl_root_htaccess_preconditions_met( $htaccess_file, $rules ) ) {
        return true;
    }

    $operations = rsssl_build_root_htaccess_operations( $rules );
    if ( empty( $operations ) ) {
        rsssl_clear_root_htaccess_status();
        rsssl_clear_pending_htaccess_branding_migration();
        return true;
    }

    if ( rsssl_get_htaccess_update_flag() ) {
        return true;
    }

    // Root may need removals and additions together, so build the full operation list first and commit once.
    if ( ! $manager->write_rules_atomic_for_path( $htaccess_file, $operations ) ) {
        return rsssl_handle_root_htaccess_write_failure( $htaccess_file, $rules );
    }

    rsssl_clear_root_htaccess_status();
    rsssl_clear_pending_htaccess_branding_migration();
    return true;
}

/**
 * Build the dedicated atomic cleanup operation for the exact bare legacy root marker block.
 *
 * This exists because plain marker-name removal would be too broad: the historical bare
 * `Really Simple Security` marker shares its prefix with current named blocks such as
 * `Really Simple Security Redirect`. Root cleanup therefore removes that one legacy block
 * only by exact bare begin/end-line matching inside the atomic root write.
 *
 * @return array<int,array{marker:string,type:string}>
 */
function rsssl_get_exact_legacy_generic_root_cleanup_operations(): array {
    return [
        [
            'marker' => 'Really Simple Security',
            'type'   => RSSSL_Htaccess_File_Manager::ATOMIC_OPERATION_REMOVE_EXACT_LEGACY_GENERIC_ROOT_BLOCKS,
        ],
    ];
}

/**
 * Skip root writes only when there is nothing to apply and no root .htaccess exists.
 * We intentionally continue when the file exists (even with empty incoming rules),
 * because cleanup operations can still remove stale RSSSL markers from that file.
 * When skipping entirely, stored error/rules options are cleared to avoid stale notices.
 */
function rsssl_should_skip_root_htaccess_write( array $rules, string $htaccess_file ): bool {
    if ( rsssl_has_pending_htaccess_branding_migration() ) {
        return false;
    }

    if ( ! empty( $rules ) ) {
        return false;
    }

    if ( $htaccess_file !== '' && is_file( $htaccess_file ) ) {
        return false;
    }

    rsssl_clear_root_htaccess_status();
    return true;
}

/**
 * Clear stored root error state once the real file no longer needs manual attention.
 */
function rsssl_clear_root_htaccess_status(): void {
    delete_site_option( 'rsssl_htaccess_error' );
    delete_site_option( 'rsssl_htaccess_rules' );
}

/**
 * Queue a one-time root `.htaccess` branding migration so it runs inside the shared atomic root writer.
 */
function rsssl_queue_htaccess_branding_migration(): void {
    update_site_option( 'rsssl_htaccess_branding_migration_pending', true );
    if ( function_exists( 'rsssl_schedule_htaccess_update' ) ) {
        rsssl_schedule_htaccess_update();
    }
}

/**
 * Check whether a queued root `.htaccess` branding migration is still pending.
 */
function rsssl_has_pending_htaccess_branding_migration(): bool {
    return (bool) get_site_option( 'rsssl_htaccess_branding_migration_pending', false );
}

/**
 * Clear the queued root `.htaccess` branding migration flag after a successful orchestrated write.
 */
function rsssl_clear_pending_htaccess_branding_migration(): void {
    delete_site_option( 'rsssl_htaccess_branding_migration_pending' );
}

/**
 * Derive the pre-write root error state so the UI can show the actionable failure before we write.
 */
function rsssl_root_htaccess_preconditions_met( string $htaccess_file, array $rules ): bool {
    $allow_create = (bool) apply_filters( 'rsssl_allow_create_htaccess', false, $htaccess_file );
    $error = rsssl_get_root_htaccess_error_state( $htaccess_file, $allow_create, false );
    if ( $error !== '' ) {
        rsssl_store_root_htaccess_error( $error, $rules );
        return false;
    }

    return true;
}

/**
 * Translate a failed root write back into the existing admin notice payload.
 */
function rsssl_handle_root_htaccess_write_failure( string $htaccess_file, array $rules ): bool {
    $allow_create = (bool) apply_filters( 'rsssl_allow_create_htaccess', false, $htaccess_file );
    $error = rsssl_get_root_htaccess_error_state( $htaccess_file, $allow_create, true );
    if ( $error !== '' ) {
        rsssl_store_root_htaccess_error( $error, $rules );
        return true;
    }

    return false;
}

/**
 * Determine the most relevant root .htaccess error state for pre-check and write-failure handling.
 */
function rsssl_get_root_htaccess_error_state( string $htaccess_file, bool $allow_create, bool $write_failed ): string {
    if ( $htaccess_file === '' ) {
        return RSSSL_Htaccess_File_Manager::ERROR_NOT_SUPPORTED;
    }

    if ( is_file( $htaccess_file ) ) {
        if ( ! is_readable( $htaccess_file ) ) {
            return RSSSL_Htaccess_File_Manager::ERROR_NOT_READABLE;
        }

        return is_writable( $htaccess_file ) ? '' : RSSSL_Htaccess_File_Manager::ERROR_NOT_WRITABLE;
    }

    $directory_writable = is_writable( dirname( $htaccess_file ) );
    if ( $write_failed ) {
        if ( ! $allow_create ) {
            return RSSSL_Htaccess_File_Manager::ERROR_NOT_SUPPORTED;
        }

        return $directory_writable ? '' : RSSSL_Htaccess_File_Manager::ERROR_NOT_WRITABLE;
    }

    if ( ! $allow_create ) {
        return RSSSL_Htaccess_File_Manager::ERROR_NOT_SUPPORTED;
    }

    return $directory_writable ? '' : RSSSL_Htaccess_File_Manager::ERROR_NOT_WRITABLE;
}

/**
 * Persist root .htaccess failure details for notices and manual recovery guidance.
 */
function rsssl_store_root_htaccess_error( ?string $error, array $rules ): void {
    $is_permission_error = in_array(
        $error,
        [
            RSSSL_Htaccess_File_Manager::ERROR_NOT_WRITABLE,
            RSSSL_Htaccess_File_Manager::ERROR_NOT_READABLE,
        ],
        true
    );

    $compiled_rules = rsssl_compile_rules_for_storage( rsssl_filter_missing_root_rules_for_storage( $rules ) );
    if ( ! $is_permission_error || $compiled_rules === '' ) {
        rsssl_clear_root_htaccess_status();
        return;
    }

    update_site_option( 'rsssl_htaccess_error', $error );
    update_site_option( 'rsssl_htaccess_rules', $compiled_rules );
}

/**
 * Persist compiled rule text for admin diagnostics/copy-paste recovery when automatic writes fail.
 * The stored value is used by security notices (`rsssl_htaccess_rules`) and is not used as write input.
 */
function rsssl_compile_rules_for_storage( array $rules ): string {
    $compiled_rules = [];
    foreach ( $rules as $rule ) {
        if ( ! is_array( $rule ) ) {
            continue;
        }

        $normalized_rules = preg_replace( "/\r\n?/", "\n", (string) ( $rule['rules'] ?? '' ) );
        $trimmed_rules = trim( (string) $normalized_rules, "\n" );
        if ( $trimmed_rules === '' ) {
            continue;
        }

        $compiled_rules[] = $trimmed_rules;
    }

    return implode( "\n", $compiled_rules );
}

/**
 * Reduce manual root `.htaccess` guidance to only the rules still absent from the current readable file.
 *
 * If the file cannot be read, we intentionally fall back to the full desired rule set because we cannot
 * safely diff the live file state. When it is readable, both manually pasted rules and existing RSS-managed
 * blocks count as already present because this notice is about the live end state, not marker ownership.
 *
 * @param array<int,mixed> $rules
 * @return array<int,mixed>
 */
function rsssl_filter_missing_root_rules_for_storage( array $rules ): array {
    if ( empty( $rules ) || ! class_exists( RSSSL_Htaccess_File_Manager::class ) ) {
        return $rules;
    }

    $manager = RSSSL_Htaccess_File_Manager::get_instance();
    $htaccess_path = $manager->determineExistingRootHtaccessFilePath();
    if ( $htaccess_path === '' ) {
        return $rules;
    }

    $htaccess_content = $manager->get_htaccess_content_for_path( $htaccess_path );
    if ( ! is_string( $htaccess_content ) ) {
        return $rules;
    }

    $identifier_marker_map = rsssl_get_root_identifier_marker_map();
    $missing_rules = [];

    foreach ( $rules as $rule ) {
        if ( ! is_array( $rule ) ) {
            continue;
        }

        $marker = rsssl_get_root_rule_marker( $rule, $identifier_marker_map );
        if ( $marker === '' ) {
            $missing_rules[] = $rule;
            continue;
        }

        $lines = rsssl_get_root_rule_lines( $rule, $marker );
        if ( empty( $lines ) ) {
            continue;
        }

        if ( rsssl_root_htaccess_contains_effective_rule( $htaccess_content, $marker, $lines ) ) {
            continue;
        }

        $missing_rules[] = $rule;
    }

    return $missing_rules;
}

if ( ! function_exists( 'rsssl_get_disable_directory_indexing_marker' ) ) {
    /**
     * Return the managed marker name for the root disable-indexing block.
     */
    function rsssl_get_disable_directory_indexing_marker(): string {
        return 'Really Simple Security Disable directory indexing';
    }
}

/**
 * Return the root-rule identifier-to-marker overrides shared by write and detection paths.
 *
 * Most root rule providers already use the managed marker name as their identifier, so they do not
 * need an entry here. This map only exists for providers whose identifier is a literal directive
 * (for example the redirect rule or bare `Options -Indexes`) while the writer manages them under a
 * human-readable marker name.
 */
function rsssl_get_root_identifier_marker_map(): array {
    return (array) apply_filters(
        'rsssl_identifier_marker_map',
        [
            'RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1' => 'Really Simple Security Redirect',
            'Options -Indexes'                           => rsssl_get_disable_directory_indexing_marker(),
        ]
    );
}

/**
 * Remove one managed root marker block from content before duplicate detection.
 *
 * We deliberately strip the current marker first so RSS does not treat its own managed block as an
 * "external/manual" rule. Without this, any existing RSS block would always satisfy the duplicate
 * check and future updates to that marker would be skipped incorrectly.
 */
function rsssl_strip_root_marker_block_for_detection( string $content, string $marker ): string {
    if ( ! class_exists( RSSSL_Htaccess_File_Manager::class ) ) {
        return $content;
    }

    $manager = RSSSL_Htaccess_File_Manager::get_instance();
    $pattern = $manager->generate_marker_pattern( $marker );
    $stripped = preg_replace( $pattern, '', $content );
    if ( ! is_string( $stripped ) ) {
        return $content;
    }

    return $stripped;
}

/**
 * Normalize multi-line root rules for duplicate detection.
 *
 * @param array<int,mixed> $lines
 * @return array<int,string>
 */
function rsssl_normalize_root_rule_lines_for_detection( array $lines ): array {
    $normalized_lines = [];
    foreach ( $lines as $line ) {
        if ( ! is_string( $line ) && ! is_numeric( $line ) ) {
            continue;
        }

        $normalized_line = preg_replace( '/\s+/', ' ', trim( (string) $line ) );
        if ( ! is_string( $normalized_line ) ) {
            $normalized_line = trim( (string) $line );
        }

        if ( $normalized_line === '' ) {
            continue;
        }

        $normalized_lines[] = $normalized_line;
    }

    return $normalized_lines;
}

/**
 * Detect whether root `.htaccess` content contains the provided normalized rule block.
 *
 * Matching is tolerant of indentation and line-ending differences, but preserves line order.
 *
 * @param array<int,string> $lines
 */
function rsssl_root_htaccess_contains_rule_block( string $content, array $lines ): bool {
    $normalized_block = rsssl_normalize_root_rule_lines_for_detection( $lines );
    if ( empty( $normalized_block ) ) {
        return false;
    }

    $normalized_content = preg_replace( "/\r\n?|\r/", "\n", $content );
    if ( ! is_string( $normalized_content ) ) {
        $normalized_content = $content;
    }

    $content_lines = rsssl_normalize_root_rule_lines_for_detection( explode( "\n", $normalized_content ) );
    $block_length = count( $normalized_block );
    $content_length = count( $content_lines );
    if ( $content_length < $block_length ) {
        return false;
    }

    $last_start = $content_length - $block_length;
    for ( $start = 0; $start <= $last_start; $start++ ) {
        if ( array_slice( $content_lines, $start, $block_length ) === $normalized_block ) {
            return true;
        }
    }

    return false;
}

/**
 * Detect whether the live root `.htaccess` already contains this effective rule anywhere.
 *
 * Manual recovery guidance and stale-notice cleanup care about whether the directive is already active in
 * the real file, regardless of whether it lives inside an RSS-managed marker or was pasted manually.
 *
 * @param array<int,string> $lines
 */
function rsssl_root_htaccess_contains_effective_rule( string $content, string $marker, array $lines ): bool {
    if ( $marker === rsssl_get_disable_directory_indexing_marker() ) {
        return preg_match( '/^\s*Options\s+-Indexes\b/im', $content ) === 1;
    }

    return rsssl_root_htaccess_contains_rule_block( $content, $lines );
}

/**
 * Detect whether the current multi-line root rule already exists outside its managed marker block.
 *
 * This prevents duplicate insertion when a user pasted the suggested multi-line rules manually.
 * Single-line literals such as `Options -Indexes` already have a dedicated atomic writer path.
 *
 * @param array<int,string> $lines
 */
function rsssl_root_htaccess_rule_exists_outside_marker( string $content, string $marker, array $lines ): bool {
    $content_without_marker = rsssl_strip_root_marker_block_for_detection( $content, $marker );
    return rsssl_root_htaccess_contains_rule_block( $content_without_marker, $lines );
}

/**
 * Normalize every desired root marker mutation into one batched manager operation list for one explicit root path.
 *
 * Operation order is intentional and deterministic:
 * 1. remove legacy marker variants
 * 2. remove the exact bare legacy generic block
 * 3. apply one-time migrations such as branding replacement
 * 4. write the current desired marker set
 *
 * The file manager then applies those operations in the same order inside one in-memory transform and one commit.
 */
function rsssl_build_root_htaccess_operations( array $rules ): array {
    $operations = [];
    $disable_directory_indexing_marker = rsssl_get_disable_directory_indexing_marker();
    $manager = RSSSL_Htaccess_File_Manager::get_instance();
    $existing_root_content = $manager->get_root_htaccess_content_for_detection();
    foreach ( rsssl_get_legacy_markers() as $legacy_marker ) {
        $operations[] = [
            'marker' => $legacy_marker,
            'lines'  => [],
        ];
    }
    $operations = array_merge( $operations, rsssl_get_exact_legacy_generic_root_cleanup_operations() );
    if ( rsssl_has_pending_htaccess_branding_migration() ) {
        $operations[] = [
            'marker'  => 'branding_replace',
            'type'    => RSSSL_Htaccess_File_Manager::ATOMIC_OPERATION_REPLACE_LITERAL_IN_RSSSL_BLOCKS,
            'search'  => 'Really Simple SSL',
            'replace' => 'Really Simple Security',
        ];
    }

    $identifier_marker_map = rsssl_get_root_identifier_marker_map();
    $marker_operations = [];
    foreach ( $rules as $rule ) {
        $marker = rsssl_get_root_rule_marker( $rule, $identifier_marker_map );
        if ( $marker === '' ) {
            continue;
        }

        $lines = rsssl_get_root_rule_lines( $rule, $marker );
        if (
            ! empty( $lines )
            && $marker !== $disable_directory_indexing_marker
            && $existing_root_content !== ''
            && rsssl_root_htaccess_rule_exists_outside_marker( $existing_root_content, $marker, $lines )
        ) {
            // Only do custom block-level dedupe for multi-line rules. `Options -Indexes` already uses the
            // file manager's literal-aware conditional marker write, so keeping it out of this path avoids
            // duplicating that logic in two places.
            $lines = [];
        }

        $marker_operations[ $marker ] = $lines;
    }

    if ( rsssl_get_option( 'redirect' ) !== 'htaccess' ) {
        $marker_operations['Really Simple Security Redirect'] = [];
    }

    if ( ! rsssl_get_option( 'disable_indexing', false ) ) {
        $marker_operations[ $disable_directory_indexing_marker ] = [];
    }

    /*
     * Prepend rules are now supplied through the shared rule filter. When that provider stops emitting
     * the marker, we still need an explicit empty operation here so the old block is actually removed.
     */
    if ( ! array_key_exists( 'Really Simple Auto Prepend File', $marker_operations ) ) {
        $marker_operations['Really Simple Auto Prepend File'] = [];
    }

    foreach ( $marker_operations as $marker => $lines ) {
        $operation = [
            'marker' => $marker,
            'lines'  => $lines,
        ];
        if ( $marker === $disable_directory_indexing_marker && ! empty( $lines ) ) {
            $operation['type'] = RSSSL_Htaccess_File_Manager::ATOMIC_OPERATION_MARKER_IF_LITERAL_ABSENT;
            $operation['literal'] = 'Options -Indexes';
        }

        $operations[] = $operation;
    }

    return $operations;
}

/**
 * Build the clear operations used when deactivation or cleanup needs to remove RSSSL-owned root markers.
 */
function rsssl_build_root_htaccess_cleanup_operations( bool $clear_htaccess_redirect = true ): array {
    $operations = [];
    foreach ( rsssl_get_root_htaccess_cleanup_markers( $clear_htaccess_redirect ) as $marker ) {
        $operations[] = [
            'marker' => $marker,
            'lines'  => [],
        ];
    }
    $operations = array_merge( $operations, rsssl_get_exact_legacy_generic_root_cleanup_operations() );

    return $operations;
}

/**
 * List every root marker that cleanup may need to remove, including legacy names.
 */
function rsssl_get_root_htaccess_cleanup_markers( bool $clear_htaccess_redirect = true ): array {
    $markers = array_merge(
        [
            'Really Simple Auto Prepend File',
            rsssl_get_disable_directory_indexing_marker(),
            'Really Simple Security LETS ENCRYPT',
        ],
        rsssl_get_legacy_markers()
    );

    if ( $clear_htaccess_redirect ) {
        $markers[] = 'Really Simple Security Redirect';
    }

    $normalized_markers = array_map( 'rsssl_normalize_root_rule_marker', $markers );
    $normalized_markers = array_filter(
        $normalized_markers,
        static function( string $marker ): bool {
            return $marker !== '';
        }
    );

    return array_values( array_unique( $normalized_markers ) );
}

/**
 * Resolve a rule array into the marker name that the root writer should own.
 */
function rsssl_get_root_rule_marker( $rule, array $identifier_marker_map ): string {
    if ( ! is_array( $rule ) ) {
        return '';
    }

    $identifier = (string) ( $rule['identifier'] ?? '' );
    if ( $identifier === '' ) {
        return '';
    }

    $mapped_marker = $identifier_marker_map[ $identifier ] ?? $identifier;
    return rsssl_normalize_root_rule_marker( $mapped_marker );
}

/**
 * Normalize marker strings so providers can use the same identifiers without whitespace drift.
 */
function rsssl_normalize_root_rule_marker( $marker ): string {
    if ( ! is_string( $marker ) && ! is_numeric( $marker ) ) {
        return '';
    }

    $normalized = preg_replace( '/\s+/', ' ', trim( (string) $marker ) );
    if ( $normalized === null ) {
        return '';
    }

    return trim( $normalized );
}

/**
 * Convert a normalized root rule into the line array expected by the atomic writer for one explicit root path.
 */
function rsssl_get_root_rule_lines( array $rule, string $marker ): array {
    if ( $marker === rsssl_get_disable_directory_indexing_marker() ) {
        return rsssl_get_disable_indexing_rule_lines();
    }

    if ( $marker === 'Really Simple Security Redirect' ) {
        $definition = rsssl_build_redirect_block( $rule );
        return $definition['lines'] ?? [];
    }

    return rsssl_extract_rule_lines( $rule );
}

/**
 * Split provider rule text into normalized line arrays for marker blocks.
 */
function rsssl_extract_rule_lines( array $rule ): array {
    $raw_rules = (string) ( $rule['rules'] ?? '' );
    if ( $raw_rules === '' ) {
        return [];
    }

    $normalized = preg_replace( "/\r\n?/", "\n", $raw_rules );
    $trimmed    = trim( (string) $normalized, "\n" );
    if ( $trimmed === '' ) {
        return [];
    }

    return explode( "\n", $trimmed );
}

/**
 * Build the redirect block for the .htaccess file.
 *
 * @param array $rule The provider rule array for the redirect block.
 *
 * @return array
 */
function rsssl_build_redirect_block( array $rule = [] ): array
{
    return [
        'marker' => 'Really Simple Security Redirect',
        'lines'  => (array) rsssl_extract_rule_lines( $rule ),
    ];
}

/**
 * Return the managed disable-indexing lines for the root `.htaccess` writer.
 */
function rsssl_get_disable_indexing_rule_lines(): array {
    if ( ! rsssl_get_option( 'disable_indexing', false ) ) {
        return [];
    }

    return [
        '# Disable directory indexing to prevent listing of directory contents',
        'Options -Indexes',
    ];
}

/**
 * Sync the uploads `.htaccess` targets for the current install scope.
 */
function rsssl_sync_uploads_htaccess_rules(): bool {
    if ( rsssl_should_process_uploads_htaccess_network_wide() ) {
        return rsssl_sync_networkwide_uploads_htaccess_rules();
    }

    $result = rsssl_sync_current_blog_uploads_htaccess_rules();
    $retry_required = (bool) ( $result['retry_required'] ?? false );

    rsssl_store_uploads_htaccess_status( $result );
    return $retry_required === false;
}

/**
 * Iterate every site in the network and sync each uploads `.htaccess` file once.
 */
function rsssl_sync_networkwide_uploads_htaccess_rules(): bool {
    $first_error = [];
    $retry_required = false;

    rsssl_for_each_network_site_for_uploads_htaccess(
        static function( int $site_id ) use ( &$first_error, &$retry_required ) {
            switch_to_blog( $site_id );
            try {
                $result = rsssl_sync_current_blog_uploads_htaccess_rules();
            } finally {
                restore_current_blog();
            }

            $site_retry_required = (bool) ( $result['retry_required'] ?? false );

            if ( empty( $first_error ) && ( $result['error'] ?? '' ) !== '' ) {
                $first_error = $result;
            }

            if ( $site_retry_required === false ) {
                if ( rsssl_get_htaccess_update_flag() ) {
                    $retry_required = true;
                    return false;
                }

                return null;
            }

            $retry_required = true;
            return false;
        }
    );

    if ( empty( $first_error ) ) {
        rsssl_clear_uploads_htaccess_status();
    } else {
        rsssl_store_uploads_htaccess_status( $first_error );
    }

    return ! $retry_required;
}

/**
 * Uploads `.htaccess` lives per site, so only networkwide activation should fan out across blogs.
 */
function rsssl_should_process_uploads_htaccess_network_wide(): bool {
    if ( ! is_multisite() ) {
        return false;
    }

    if ( ! function_exists( 'rsssl_is_networkwide_active' ) ) {
        return false;
    }

    return rsssl_is_networkwide_active();
}

/**
 * Batch through network sites so uploads sync scales without loading the entire site list at once.
 */
function rsssl_for_each_network_site_for_uploads_htaccess( callable $callback ): void {
    $offset = 0;
    $batch_size = (int) apply_filters( 'rsssl_htaccess_network_site_batch_size', 100 );
    if ( $batch_size < 1 ) {
        $batch_size = 1;
    }

    while ( true ) {
        $site_ids = get_sites(
            [
                'fields' => 'ids',
                'number' => $batch_size,
                'offset' => $offset,
            ]
        );
        if ( empty( $site_ids ) ) {
            return;
        }

        foreach ( $site_ids as $site_id ) {
            if ( $callback( (int) $site_id ) === false ) {
                return;
            }
        }

        $processed = count( $site_ids );
        $offset += $processed;
        if ( $processed < $batch_size ) {
            return;
        }
    }
}

/**
 * Build and write the uploads `.htaccess` state for the current blog, returning retry metadata.
 *
 * Like the root flow, uploads gathers every provider first and then resolves the effective marker payload
 * from the same file snapshot used for the eventual atomic write.
 */
function rsssl_sync_current_blog_uploads_htaccess_rules(): array {
    $rules_uploads = apply_filters( 'rsssl_htaccess_security_rules_uploads', [] );
    $upload_dir = wp_get_upload_dir();
    $htaccess_uploads = trailingslashit( $upload_dir['basedir'] ) . '.htaccess';
    $requested_rules_content = rsssl_compile_uploads_requested_rules( $rules_uploads );

    if ( rsssl_get_htaccess_update_flag() ) {
        return rsssl_get_uploads_htaccess_result( $htaccess_uploads, '' );
    }

    if ( $requested_rules_content === '' && ! is_file( $htaccess_uploads ) ) {
        return rsssl_get_uploads_htaccess_result( $htaccess_uploads, '' );
    }

    $manager = RSSSL_Htaccess_File_Manager::get_instance();
    // Uploads resolves one plugin-owned marker block from the current file snapshot and the active rule providers.
    $write_result = $manager->resolve_and_write_uploads_htaccess_rules_atomic(
        $htaccess_uploads,
        $rules_uploads
    );
    $rules_content = (string) ( $write_result['rules_content'] ?? '' );
    if ( ! empty( $write_result['success'] ) ) {
        return rsssl_get_uploads_htaccess_result( $htaccess_uploads, $rules_content );
    }

    $uploads_dir_writable = is_writable( trailingslashit( $upload_dir['basedir'] ) );
    if ( ! is_file( $htaccess_uploads ) && ! $uploads_dir_writable ) {
        return rsssl_get_uploads_htaccess_result( $htaccess_uploads, $rules_content, 'not-writable' );
    }

    if ( is_file( $htaccess_uploads ) && ! is_writable( $htaccess_uploads ) ) {
        return rsssl_get_uploads_htaccess_result( $htaccess_uploads, $rules_content, 'not-writable' );
    }

    return rsssl_get_uploads_htaccess_result( $htaccess_uploads, $rules_content, '', true );
}

/**
 * Standardize the uploads sync result shape for status storage and retry handling.
 */
function rsssl_get_uploads_htaccess_result(
    string $path,
    string $rules_content,
    string $error = '',
    bool $retry_required = false
): array {
    return [
        'blog_id'        => get_current_blog_id(),
        'error'          => $error,
        'path'           => $path,
        'retry_required' => $retry_required,
        'rules_content'  => $rules_content,
    ];
}

/**
 * Persist only the first actionable uploads error so the UI can show a single recovery target.
 */
function rsssl_store_uploads_htaccess_status( array $result ): void {
    if ( $result['error'] === '' ) {
        rsssl_clear_uploads_htaccess_status();
        return;
    }

    update_site_option( 'rsssl_uploads_htaccess_error', $result['error'] );
    update_site_option( 'rsssl_uploads_htaccess_rules', $result['rules_content'] );
}

/**
 * Clear stored uploads status once the current desired state no longer reports an actionable error.
 */
function rsssl_clear_uploads_htaccess_status(): void {
    delete_site_option( 'rsssl_uploads_htaccess_error' );
    delete_site_option( 'rsssl_uploads_htaccess_rules' );
}

/**
 * Compile the raw uploads rule payload before duplicate detection so we can skip obvious no-op creates.
 */
function rsssl_compile_uploads_requested_rules( array $rules_uploads ): string {
    $rules_content = '';
    foreach ( $rules_uploads as $rule ) {
        if ( ! is_array( $rule ) ) {
            continue;
        }

        $rules_content .= (string) ( $rule['rules'] ?? '' );
    }

    $normalized = preg_replace( "/\r\n?/", "\n", $rules_content );
    return trim( (string) $normalized, "\n" );
}

/**
 * Clear all stored root/uploads notice state before rebuilding fresh status for this request.
 */
function rsssl_htaccess_clear_errors(): void {
    delete_site_option('rsssl_htaccess_error');
    delete_site_option('rsssl_htaccess_rules');
    rsssl_clear_uploads_htaccess_status();
}

/**
 * Return every legacy root marker name that the centralized writer should still clean up.
 * The bare `Really Simple Security` marker is intentionally excluded here. Older root writers used that exact generic
 * block name, but newer root markers share the same prefix (`Really Simple Security Redirect`, etc.), so normal
 * marker-name cleanup would be too broad. That legacy root block is removed separately with exact bare-line matching.
 */
function rsssl_get_legacy_markers(): array {
    return [
        'Really Simple Security No Index',
        'rlrssslReallySimpleSSL',
        'Really Simple SSL',
        'Really_Simple_SSL_SECURITY_HEADERS',
        'Really_Simple_SSL_CSP_Report_Only',
        'Really_Simple_SSL_Content_Security_Policy',
    ];
}

/**
 * Store warning blocks for later use in the mailer
 *
 * @param array $changed_fields
 *
 * @return void
 */
function rsssl_gather_warning_blocks_for_mail( array $changed_fields ){
    if (!rsssl_user_can_manage() ) {
        return;
    }

    if ( !rsssl_get_option('send_notifications_email') ) {
        return;
    }

    $fields = array_filter($changed_fields, static function($field) {
        // Check if email_condition exists and call the function, else assume true
        if ( !isset($field['email']['condition']) ) {
            $email_condition_result = true;
        } else if (is_array($field['email']['condition'])) {
            //rsssl option check
            $fieldname = array_key_first($field['email']['condition']);
            $value = $field['email']['condition'][$fieldname];
            $email_condition_result = rsssl_get_option($fieldname) === $value;
        } else {
            //function check
            $function  = $field['email']['condition'];
            $email_condition_result = function_exists($function) && $function();
        }
        return isset($field['email']['message']) && $field['value'] && $email_condition_result;
    });

    if ( count($fields)===0 ) {
        return;
    }
    $current_fields = get_option('rsssl_email_warning_fields', []);
    //if it's empty, we start counting time. 30 mins later we send a mail.
    update_option('rsssl_email_warning_fields_saved', time(), false );

    $current_ids = array_column($current_fields, 'id');
    foreach ($fields as $field){
        if ( !in_array( $field['id'], $current_ids, true ) ) {
            $current_fields[] = $field;
        }
    }
    update_option('rsssl_email_warning_fields', $current_fields, false);
}
add_action('rsssl_after_saved_fields', 'rsssl_gather_warning_blocks_for_mail', 40);

/**
 * Check if server uses .htaccess
 * @return bool
 */
function rsssl_uses_htaccess() {
    //when using WP CLI, the get_server check does not work, so we assume .htaccess is being used
    //and rely on the file exists check to catch if not.
    if ( defined( 'WP_CLI' ) && WP_CLI ) {
        return true;
    }
    return rsssl_get_server() === 'apache' || rsssl_get_server() === 'litespeed';
}

/**
 * Check whether the current root `.htaccess` already contains every rule the plugin currently wants to enforce.
 *
 * This is used to clear stale manual-action notices after a user pastes the suggested rules by hand.
 */
function rsssl_root_htaccess_contains_current_rules(): bool {
    if ( ! class_exists( RSSSL_Htaccess_File_Manager::class ) ) {
        return false;
    }

    $rules = apply_filters( 'rsssl_htaccess_security_rules', [] );
    if ( empty( $rules ) ) {
        return true;
    }

    $manager = RSSSL_Htaccess_File_Manager::get_instance();
    $htaccess_path = $manager->determineExistingRootHtaccessFilePath();
    if ( $htaccess_path === '' ) {
        return false;
    }

    $htaccess_content = $manager->get_htaccess_content_for_path( $htaccess_path );
    if ( ! is_string( $htaccess_content ) ) {
        return false;
    }

    $identifier_marker_map = rsssl_get_root_identifier_marker_map();
    foreach ( $rules as $rule ) {
        $marker = rsssl_get_root_rule_marker( $rule, $identifier_marker_map );
        if ( $marker === '' ) {
            continue;
        }

        $lines = rsssl_get_root_rule_lines( (array) $rule, $marker );
        if ( empty( $lines ) ) {
            continue;
        }

        if ( ! rsssl_root_htaccess_contains_effective_rule( $htaccess_content, $marker, $lines ) ) {
            return false;
        }
    }

    return true;
}

/**
 * Get htaccess status
 * @return string | bool
 */
function rsssl_htaccess_status(){
    if ( empty(get_site_option('rsssl_htaccess_rules','')) ) {
        return false;
    }

    if ( rsssl_root_htaccess_contains_current_rules() ) {
        rsssl_clear_root_htaccess_status();
        return false;
    }

    return get_site_option('rsssl_htaccess_error');
}

/**
 * Get htaccess status
 * @return string | bool
 */

function rsssl_uploads_htaccess_status(){
    if ( empty(get_site_option('rsssl_uploads_htaccess_rules','')) ) {
        return false;
    }
    return get_site_option('rsssl_uploads_htaccess_error');
}

/**
 * @return string|null
 * Get the wp-config.php path
 */
function rsssl_find_wp_config_path() {
    if ( ! rsssl_user_can_manage() ) {
        return null;
    }

    // Allow the wp-config.php path to be overridden via a filter.
    $filtered_path = apply_filters( 'rsssl_wpconfig_path', '' );

    // If a filtered path is provided, validate it.
    if ( ! empty( $filtered_path ) ) {
        $directory = dirname( $filtered_path );

        // Ensure the directory exists before checking for the file.
        if ( is_dir( $directory ) && file_exists( $filtered_path ) ) {
            return $filtered_path;
        }
    }

    // Limit number of iterations to 10
    $i   = 0;
    $dir = __DIR__;
    do {
        $i ++;
        if ( file_exists( $dir . "/wp-config.php" ) ) {
            return $dir . "/wp-config.php";
        }
    } while ( ( $dir = realpath( "$dir/.." ) ) && ( $i < 10 ) );

    return null;
}

/**
 * Returns the server type of the plugin user.
 *
 * @return string|bool server type the user is using of false if undetectable.
 */

function rsssl_get_server() {
    //Allows to override server authentication for testing or other reasons.
    if ( defined( 'RSSSL_SERVER_OVERRIDE' ) ) {
        return RSSSL_SERVER_OVERRIDE;
    }

    $server_raw = strtolower( htmlspecialchars( $_SERVER['SERVER_SOFTWARE'], ENT_QUOTES | ENT_HTML5 ) );

    //figure out what server they're using
    if ( strpos( $server_raw, 'apache' ) !== false ) {
        return 'apache';
    } elseif ( strpos( $server_raw, 'nginx' ) !== false ) {
        return 'nginx';
    } elseif ( strpos( $server_raw, 'litespeed' ) !== false ) {
        return 'litespeed';
    } else { //unsupported server
        return false;
    }
}

/**
 * @return string
 * Generate a random prefix
 */

function rsssl_generate_random_string($length) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';

    for ( $i = 0; $i < $length; $i++ ) {
        $index = rand(0, strlen($characters) - 1);
        $randomString .= $characters[$index];
    }

    return $randomString;
}

/**
 * @return string
 *
 * Get users as string to display
 */
function rsssl_list_users_where_display_name_is_login_name() {

    if ( !rsssl_user_can_manage() ) {
        return '';
    }
    $users = rsssl_get_users_where_display_name_is_login( true );
    if ( is_array( $users ) ) {
        $ext  = count($users)>=10 ? '...' : '';
        $users = array_slice($users, 0, 10);
        return implode( ', ', $users ).$ext;
    }

    return '';
}

/**
 * Check if user e-mail is verified
 * @return bool
 */
function rsssl_is_email_verified() {
    $verificationStatus = get_option('rsssl_email_verification_status');
    if (rsssl_user_can_manage() && $verificationStatus == 'completed' ) {
        return true;
    }

	// User cannot manage or status is ['started', 'email_changed']
	return false;
}

/**
 * Return the blocker message when email notifications cannot be enabled.
 */
function rsssl_get_send_notifications_email_block_reason(): string {
	if ( rsssl_is_email_verified() ) {
		return '';
	}

	return __( "Please verify your email address first before enabling email notifications.", "really-simple-ssl" );
}

function rsssl_remove_prefix_from_version($version) {
    return preg_replace('/^[^\d]*(?=\d)/', '', $version);
}
function rsssl_version_compare($version, $compare_to, $operator = null) {
    $version = rsssl_remove_prefix_from_version($version);
    $compare_to = rsssl_remove_prefix_from_version($compare_to);
    return version_compare($version, $compare_to, $operator);
}

function rsssl_maybe_disable_404_blocking() {
    $option_value = get_option( 'rsssl_homepage_contains_404_resources', false );
    // Explicitly check for boolean true or string "true"
    return $option_value === true || $option_value === "true";
}

function rsssl_lock_file_exists() {
    if ( file_exists( trailingslashit( WP_CONTENT_DIR ) . 'rsssl-safe-mode.lock' ) ) {
        return true;
    }
    return false;
}

/**
 * Return the blocker message when the firewall cannot be enabled.
 *
 * The firewall needs a writable cache/storage location in wp-content and at
 * least one early-loading bootstrap method so requests can be intercepted
 * before WordPress fully loads.
 */
function rsssl_get_enable_firewall_block_reason(): string {
	if ( is_writable( WP_CONTENT_DIR ) && rsssl_firewall_has_available_early_loader() ) {
		return '';
	}

	return __( "The firewall requires wp-content to be writable, and at least one writable early-loading method: wp-config.php, .htaccess, or user.ini.", "really-simple-ssl" );
}

/**
 * Check if at least one firewall bootstrap can be written, or is already present.
 */
function rsssl_firewall_has_available_early_loader(): bool {
	return rsssl_firewall_loader_file_available( RSSSL()->admin->wpconfig_path() )
		|| rsssl_firewall_htaccess_loader_available()
		|| rsssl_firewall_user_ini_loader_available();
}

/**
 * Check if .htaccess can be used for the firewall bootstrap.
 */
function rsssl_firewall_htaccess_loader_available(): bool {
	if ( defined( 'RSSSL_HTACCESS_SKIP_AUTO_PREPEND' ) && RSSSL_HTACCESS_SKIP_AUTO_PREPEND ) {
		return false;
	}

	if ( ! RSSSL()->server->uses_htaccess() ) {
		return false;
	}

	return rsssl_firewall_loader_file_available( RSSSL()->admin->htaccess_file() );
}

/**
 * Check if user.ini can be used for the firewall bootstrap.
 */
function rsssl_firewall_user_ini_loader_available(): bool {
	if ( defined( 'RSSSL_HTACCESS_SKIP_AUTO_PREPEND' ) && RSSSL_HTACCESS_SKIP_AUTO_PREPEND ) {
		return false;
	}

	$user_ini_filename = ini_get( 'user_ini.filename' );
	if ( empty( $user_ini_filename ) ) {
		return false;
	}

	if ( ! function_exists( 'get_home_path' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	return rsssl_firewall_loader_file_available( trailingslashit( get_home_path() ) . $user_ini_filename, true );
}

/**
 * Check if a loader file can be updated or already contains the firewall bootstrap.
 */
function rsssl_firewall_loader_file_available( string $file_path, bool $allow_create = false ): bool {
	if ( empty( $file_path ) ) {
		return false;
	}

	if ( is_file( $file_path ) ) {
		return is_writable( $file_path ) || rsssl_firewall_file_contains_loader( $file_path );
	}

	return $allow_create && is_writable( dirname( $file_path ) );
}

/**
 * Check if a file already references the firewall bootstrap.
 */
function rsssl_firewall_file_contains_loader( string $file_path ): bool {
	if ( ! is_readable( $file_path ) ) {
		return false;
	}

	$contents = file_get_contents( $file_path );
	return is_string( $contents ) && strpos( $contents, 'advanced-headers.php' ) !== false;
}

/**
 * Return the blocker message when HSTS cannot be enabled.
 */
function rsssl_get_hsts_enable_block_reason(): string {
	if ( ! is_writable( WP_CONTENT_DIR ) ) {
		return __( "Security headers require the wp-content folder to be writable.", "really-simple-ssl" );
	}

	if ( ! rsssl_get_option( 'site_has_ssl' ) ) {
		return __( "HSTS requires your site to have SSL enabled. Please enable SSL first.", "really-simple-ssl" );
	}

	return '';
}

/**
 * Check if there are active application passwords for any user
 *
 * @return bool
 */
function rsssl_has_active_application_passwords() {
	if ( ! function_exists( 'wp_is_application_passwords_available' ) ) {
		return false;
	}

	static $has_passwords_for_request = null;
	if ( $has_passwords_for_request !== null ) {
		return $has_passwords_for_request;
	}

	global $wpdb;

	// Check if any user has application passwords in user meta.
	// Using LIMIT 1 instead of COUNT(*) for performance.
	// We just need to know if any exist.
	$exists = $wpdb->get_var(
		"SELECT 1 FROM {$wpdb->usermeta} WHERE meta_key = '_application_passwords' AND meta_value != '' AND meta_value != 'a:0:{}' LIMIT 1"
	);

	$has_passwords_for_request = $exists !== null;
	return $has_passwords_for_request;
}

/**
 * Return the blocker message when application passwords cannot be disabled safely.
 */
function rsssl_get_disable_application_passwords_block_reason(): string {
	if ( ! rsssl_has_active_application_passwords() ) {
		return '';
	}

	return __( "Warning: Active application passwords were detected. Disabling this feature may break existing integrations.", 'really-simple-ssl' );
}
/**
 * Get a list of plugins that may use XML-RPC
 *
 * @return array
 */
function rsssl_get_xmlrpc_dependent_plugins() {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$dependent_plugins = [];

	// Known plugins that use XML-RPC
	$xmlrpc_plugins = apply_filters( 'rsssl_xmlrpc_plugins', [
		'jetpack/jetpack.php',
	] );

	foreach ( $xmlrpc_plugins as $plugin_path ) {
		if ( is_plugin_active( $plugin_path ) ) {
			$plugin_file = WP_PLUGIN_DIR . '/' . $plugin_path;
			$plugin_data = get_plugin_data( $plugin_file );
			$plugin_name = ! empty( $plugin_data['Name'] ) ? $plugin_data['Name'] : $plugin_path;

			$dependent_plugins[] = $plugin_name;
		}
	}

	return $dependent_plugins;
}

/**
 * Check if there are plugins that depend on XML-RPC
 *
 * @return bool
 */
function rsssl_has_xmlrpc_dependent_plugins() {
	return count( rsssl_get_xmlrpc_dependent_plugins() ) > 0;
}

/**
 * Return the blocker message when XML-RPC cannot be disabled safely.
 */
function rsssl_get_disable_xmlrpc_block_reason(): string {
	$xmlrpcDependentPlugins = rsssl_get_xmlrpc_dependent_plugins();
	if ( empty( $xmlrpcDependentPlugins ) ) {
		return '';
	}

	return sprintf(
		__( "Warning: The following plugins may use XML-RPC: %s. Disabling XML-RPC could break their functionality.", "really-simple-ssl" ),
		implode( ', ', $xmlrpcDependentPlugins )
	);
}
