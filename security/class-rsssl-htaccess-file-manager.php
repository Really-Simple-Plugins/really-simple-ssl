<?php
/**
 * class-rsssl-htaccess-file-manager.php
 *
 * Responsible for locating, reading, and writing managed `.htaccess`
 * rules through the plugin's shared file manager.
 *
 * @package RSSSL\Pro\Security\WordPress\Firewall\Builders\Rules
 */
namespace {
    //Multiple requirements to support different WordPress versions and ensure the filesystem API is available.
    if ( ! function_exists( 'insert_with_markers' )) {
        require_once ABSPATH . 'wp-admin/includes/misc.php';
    }
    if ( ! function_exists( 'get_home_path' )) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
}
namespace RSSSL\Security {
    /**
     * Handles low-level .htaccess file operations:
     *  – locating the file,
     *  – reading/writing rules,
     *  – will no longer auto-create a missing .htaccess (opt-in via `rsssl_allow_create_htaccess`).
     */
    class RSSSL_Htaccess_File_Manager {

        /**
         * This error indicates that the .htaccess file exists but is not readable.
         */
        public const ERROR_NOT_READABLE = 'not-readable';

        /**
         * This error indicates that the .htaccess file exists but is not writable.
         */
        public const ERROR_NOT_WRITABLE = 'not-writable';

        /**
         * This error indicates that the .htaccess file does not exist and cannot be
         * created, or that the environment does not support .htaccess modifications.
         */
        public const ERROR_NOT_SUPPORTED = 'not-supported';

        /**
         * Replaces or inserts one managed marker block by marker name.
         */
        private const ATOMIC_OPERATION_MARKER = 'marker';

        /**
         * Inserts a managed marker only when the literal rule is still absent outside RSSSL blocks.
         */
        public const ATOMIC_OPERATION_MARKER_IF_LITERAL_ABSENT = 'marker_if_literal_absent';

        /**
         * Rewrites literal content only inside existing RSSSL-managed marker blocks.
         */
        public const ATOMIC_OPERATION_REPLACE_LITERAL_IN_RSSSL_BLOCKS = 'replace_literal_in_rsssl_blocks';

        /**
         * Replaces a literal string anywhere in the current `.htaccess` content.
         */
        public const ATOMIC_OPERATION_REPLACE_LITERAL = 'replace_literal';

        /**
         * Removes exact bare legacy `Really Simple Security` root blocks during migration cleanup.
         */
        public const ATOMIC_OPERATION_REMOVE_EXACT_LEGACY_GENERIC_ROOT_BLOCKS = 'remove_exact_legacy_generic_root_blocks';

        /**
         * Singleton instance.
         *
         * @var self|null
         */
        private static ?self $instance = null;

        /**
         * Is used for storing the path to the .htaccess file.
         */
        private string $htaccess_file_path;

        /**
         * Cached RSSSL marker names for the current request.
         *
         * @var array<int,string>|null
         */
        private ?array $cached_rsssl_markers = null;

        /**
         * Stores the most recent internal write or lock error for the current operation.
         */
        private string $last_error_message = '';

        /**
         * Tracks whether the last file-lock attempt failed because locking was unavailable, not contended.
         */
        private bool $last_lock_fallback_detected = false;

        /**
         * Allows explicit cleanup flows to bypass the global `do_not_edit_htaccess` opt-out.
         */
        private bool $bypass_do_not_edit_htaccess = false;

        /**
         * Constructor.
         *
         * @throws \Throwable
         */
        private function __construct()
        {
            $this->htaccess_file_path = $this->determineHtaccessFilePath();
        }

        /**
         * Return the shared instance of this class.
         *
         * @return self
         * @throws \Throwable
         */
        public static function get_instance(): self {
            if ( self::$instance === null ) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Run an explicit cleanup operation without honoring the global `do_not_edit_htaccess` opt-out.
         *
         * @param callable():mixed $operation
         * @throws \Throwable
         * @return mixed
         */
        public function run_with_do_not_edit_htaccess_bypass( callable $operation ) {
            $previous_bypass_state = $this->bypass_do_not_edit_htaccess;
            $this->bypass_do_not_edit_htaccess = true;

            try {
                return $operation();
            } finally {
                $this->bypass_do_not_edit_htaccess = $previous_bypass_state;
            }
        }

        /**
         * Determines the path to the .htaccess file based on various conditions.
         *
         * @throws \Throwable
         */
        private function determineHtaccessFilePath(): string
        {
            $rootPath = $this->determineExistingRootHtaccessFilePath();
            if ($rootPath !== '') {
                return $rootPath;
            }

            return $this->get_wp_content_htaccess_fallback_path();
        }

        /**
         * Return the historical `wp-content/.htaccess` fallback target after the shared path filter is applied.
         * This remains available for legacy cleanup and migration flows, but root reads/writes should prefer
         * the dedicated root-target helpers.
         *
         * @throws \Throwable
         */
        public function get_wp_content_htaccess_fallback_path(): string
        {
            $contentPath = $this->get_wp_content_htaccess_fallback_path_candidate();
            return $this->apply_htaccess_file_path_filter((string) $contentPath);
        }

        /**
         * Return the root-level write target, even when the file still needs to be created.
         * Root writes use this so new files target the home path instead of the historical `wp-content` fallback.
         *
         * @throws \Throwable
         */
        public function get_root_htaccess_target_path(): string
        {
            $existingRootPath = $this->determineExistingRootHtaccessFilePath();
            if ($existingRootPath !== '') {
                return $existingRootPath;
            }

            // When creating a new root file, follow WordPress and target the home path instead of wp-content.
            $homePath = apply_filters('rsssl_home_htaccess_path', get_home_path() . '.htaccess');
            return $this->apply_htaccess_file_path_filter((string) $homePath);
        }

        /**
         * Return the existing root-level `.htaccess` path without falling back to `wp-content`.
         * Read and cleanup flows use this so they never mutate a best-effort fallback path by accident.
         *
         * @throws \Throwable
         */
        public function determineExistingRootHtaccessFilePath(): string
        {
            // Match WordPress rewrite behavior first, then fall back to the default root target.
            $homePath = $this->apply_htaccess_file_path_filter(
                (string) apply_filters('rsssl_home_htaccess_path', get_home_path() . '.htaccess')
            );
            if (is_file($homePath)) {
                return $homePath;
            }

            $defaultPath = $this->apply_htaccess_file_path_filter(
                (string) apply_filters('rsssl_default_htaccess_path', $this->get_default_htaccess_path_candidate())
            );
            if (is_file($defaultPath)) {
                return $defaultPath;
            }

            return '';
        }

        /**
         * Apply the shared path override once so existence checks and returned targets stay aligned.
         *
         * @throws \Throwable
         */
        private function apply_htaccess_file_path_filter(string $path): string
        {
            return (string) apply_filters('rsssl_htaccess_file_path', $path);
        }

        /**
         * Return the unfiltered historical fallback candidate so migration cleanup can still locate old files.
         *
         * @throws \Throwable
         */
        private function get_wp_content_htaccess_fallback_path_candidate(): string
        {
            return (string) apply_filters('rsssl_wp_content_htaccess_path', WP_CONTENT_DIR . '/.htaccess');
        }

        /**
         * Returns the default root config path candidate for Apache-compatible rewrites.
         */
        private function get_default_htaccess_path_candidate(): string
        {
            $bitnamiPath = $this->get_bitnami_htaccess_conf_path();
            if ($bitnamiPath !== '') {
                return $bitnamiPath;
            }

            return ABSPATH . '.htaccess';
        }

        /**
         * Detects Bitnami-style htaccess.conf path when it is accessible.
         */
        private function get_bitnami_htaccess_conf_path(): string
        {
            $openBasedir = ini_get('open_basedir');
            if (! empty($openBasedir)) {
                return '';
            }

            $path = dirname(ABSPATH) . '/conf/htaccess.conf';
            if (! is_file($path)) {
                return '';
            }

            $resolvedPath = realpath($path);
            if ($resolvedPath === false) {
                return '';
            }

            return $resolvedPath;
        }

        /**
         * Run one operation against an explicit `.htaccess` path without leaking that path into later calls.
         *
         * @param string $htaccess_file_path
         * @param callable $operation
         *
         * @throws \Throwable
         * @return mixed
         */
        private function with_temporary_htaccess_path(string $htaccess_file_path, callable $operation)
        {
            $originalPath = $this->htaccess_file_path;
            $this->htaccess_file_path = $htaccess_file_path;

            try {
                return $operation();
            } finally {
                $this->htaccess_file_path = $originalPath;
            }
        }

        /**
         * Read one explicit `.htaccess` path without mutating the manager's active path.
         */
        public function get_htaccess_content_for_path(string $htaccess_file_path): ?string
        {
            if ( ! is_file($htaccess_file_path) || ! is_readable($htaccess_file_path)) {
                return null;
            }

            $content = file_get_contents($htaccess_file_path);
            if ($content === false) {
                return null;
            }

            return (string) $content;
        }

        /**
         * Read normalized root `.htaccess` contents for read-only rule detection.
         *
         * @throws \Throwable
         */
        public function get_root_htaccess_content_for_detection(): string
        {
            $htaccessPath = $this->determineExistingRootHtaccessFilePath();
            if ($htaccessPath === '') {
                return '';
            }

            return $this->get_normalized_htaccess_content_for_detection($htaccessPath);
        }

        /**
         * Read normalized uploads `.htaccess` contents for read-only rule detection.
         */
        public function get_current_blog_uploads_htaccess_content_for_detection(): string
        {
            $uploadDir = wp_get_upload_dir();
            $baseDir = (string) ($uploadDir['basedir'] ?? '');
            if ($baseDir === '') {
                return '';
            }

            return $this->get_normalized_htaccess_content_for_detection(
                trailingslashit($baseDir) . '.htaccess'
            );
        }

        /**
         * Normalize line endings so read-only rule detection stays stable across environments.
         */
        private function get_normalized_htaccess_content_for_detection(string $htaccess_file_path): string
        {
            $content = $this->get_htaccess_content_for_path($htaccess_file_path);
            if (! is_string($content)) {
                return '';
            }

            $normalized = preg_replace("/\r\n?|\r/", "\n", $content);
            if (! is_string($normalized)) {
                return '';
            }

            return $normalized;
        }

        /**
         * Write one marker block through the shared atomic writer.
         * This is a convenience wrapper for callers that only need a single marker update.
         *
         * @throws \Throwable
         */
        public function write_rule(array $rule_definition): bool
        {
            if (! $this->validateRuleDefinition($rule_definition)) {
                return false;
            }

            $markerName = $this->extract_name_from_marker((string) $rule_definition['marker']);
            $lines      = $this->prepareLines($rule_definition);
            return $this->write_rule_atomic($markerName, $lines);
        }

        /**
         * Write one marker block to an explicit `.htaccess` path without leaving that path active afterward.
         *
         * @throws \Throwable
         */
        public function write_rule_for_path(string $htaccess_file_path, array $rule_definition): bool
        {
            return $this->with_temporary_htaccess_path(
                $htaccess_file_path,
                function () use ( $rule_definition ): bool {
                    return $this->write_rule($rule_definition);
                }
            );
        }

        /**
         * Writes a single marker block with an atomic read-modify-write cycle.
         *
         * @throws \Throwable
         */
        public function write_rule_atomic(string $markerName, array $lines): bool
        {
            return $this->write_rules_atomic(
                [
                    [
                        'marker' => $markerName,
                        'lines'  => $lines,
                    ],
                ]
            );
        }

        /**
         * Writes multiple marker operations in one read-modify-write cycle for the current target path.
         *
         * Preferred path:
         * - acquire an advisory `flock()` on the target file
         * - read current content while that lock is held
         * - apply every requested operation in memory
         * - commit once with temp-file swap + rename
         *
         * Important lock semantics:
         * - `flock()` is advisory, not mandatory; it only coordinates with cooperative code
         * - a third-party plugin can still open or write the file if it ignores advisory locks
         * - RSSSL still uses the lock because it prevents cooperative writers, including concurrent RSSSL requests,
         *   from interleaving their own read/modify/write cycles
         *
         * Fallback behavior is intentionally strict:
         * - if locking is unavailable, we may fall back to a lockless atomic temp-swap write
         * - if the lock is merely contended, we do not bypass that contention with the lockless path
         *
         * @param array<int,array{marker:string,lines?:array,type?:string}> $operations
         * @throws \Throwable
         */
        public function write_rules_atomic(array $operations): bool
        {
            $this->reset_last_error_message();
            if (empty($operations)) {
                return true;
            }

            if ($this->should_skip_current_htaccess_write()) {
                return true;
            }

            // Keep one lock across the full read-modify-write cycle so concurrent marker updates cannot interleave.
            $allowEmptyOriginal = ! is_file($this->htaccess_file_path);
            if (! (bool) apply_filters('rsssl_use_atomic_htaccess_writes', true)) {
                return $this->write_rules_lockless_atomic_fallback($operations, $allowEmptyOriginal);
            }

            if (! $this->can_start_atomic_write($operations)) {
                return $this->fail_write_operation(
                    $this->htaccess_file_path,
                    $this->get_last_error_message('Atomic .htaccess write preconditions failed.')
                );
            }

            $targetMissingBeforeWrite = ! is_file($this->htaccess_file_path);
            $handle = $this->open_and_lock_htaccess_handle();
            if ($handle === false) {
                if ($this->did_last_lock_fallback()) {
                    return $this->write_rules_lockless_atomic_fallback($operations, $allowEmptyOriginal);
                }

                return $this->fail_write_operation(
                    $this->htaccess_file_path,
                    $this->get_last_error_message('Unable to open and lock .htaccess for atomic write.')
                );
            }

            $writePerformed = false;
            $result = $this->run_atomic_write_cycle($handle, $operations, $allowEmptyOriginal, $writePerformed);
            if ($targetMissingBeforeWrite && ! $writePerformed) {
                $this->cleanup_empty_placeholder_file($this->htaccess_file_path);
            }
            if (! $result) {
                return false;
            }

            return true;
        }

        /**
         * Write multiple marker operations to one explicit `.htaccess` path without leaking that path into later calls.
         *
         * @param array<int,array{marker:string,lines?:array,type?:string}> $operations
         * @throws \Throwable
         */
        public function write_rules_atomic_for_path(string $htaccess_file_path, array $operations): bool
        {
            return $this->with_temporary_htaccess_path(
                $htaccess_file_path,
                function () use ( $operations ): bool {
                    return $this->write_rules_atomic($operations);
                }
            );
        }

        /**
         * Lockless atomic fallback when advisory locking is unavailable or intentionally disabled.
         *
         * This path still preserves atomic replacement of the target file via temp-file swap + rename, but it does
         * not serialize the read/modify/write cycle against other cooperative writers. For that reason we only use
         * it when `flock()` itself is unavailable, not when another request merely holds the advisory lock.
         *
         * @param array<int,array{marker:string,lines?:array,type?:string}> $operations
         * @throws \Throwable
         */
        private function write_rules_lockless_atomic_fallback(array $operations, bool $allowEmptyOriginal = false): bool
        {
            // Some hosts do not support reliable advisory locking; keep the same safety gates even when we degrade the write path.
            if (! $this->can_start_atomic_write()) {
                return $this->fail_write_operation($this->htaccess_file_path, 'Lockless atomic .htaccess write preconditions failed.');
            }

            $originalHtaccess = $this->get_htaccess_content_for_path($this->htaccess_file_path);
            if ($this->is_effectively_empty($originalHtaccess) && ! $allowEmptyOriginal) {
                return $this->fail_write_operation($this->htaccess_file_path, 'Refusing lockless atomic write because .htaccess is effectively empty.');
            }

            $updatedHtaccess = $this->apply_atomic_operations((string) $originalHtaccess, $operations);
            if ($updatedHtaccess === null) {
                return $this->fail_write_operation($this->htaccess_file_path, 'Failed to apply lockless atomic .htaccess operations.');
            }
            if ($updatedHtaccess === $originalHtaccess) {
                return true;
            }

            if ($this->is_effectively_empty($updatedHtaccess)) {
                return $this->fail_write_operation($this->htaccess_file_path, 'Refusing lockless atomic write because resulting .htaccess would be empty.');
            }
            if ($this->should_block_root_only_rsssl_result($updatedHtaccess)) {
                return $this->fail_write_operation($this->htaccess_file_path, 'Refusing lockless atomic write because resulting .htaccess would only contain RSSSL markers.');
            }

            if (! $this->write_content_with_temp_swap($this->htaccess_file_path, $updatedHtaccess)) {
                return $this->fail_write_operation($this->htaccess_file_path, 'Lockless atomic .htaccess write failed.');
            }

            return true;
        }

        /**
         * Validates if atomic write preconditions are met.
         *
         * @param array<int,array{marker:string,lines?:array,type?:string}> $operations
         * @throws \Throwable
         */
        private function can_start_atomic_write(array $operations = []): bool
        {
            if (! $this->ensure_htaccess_is_writable()) {
                return false;
            }

            if (is_link($this->htaccess_file_path)) {
                $this->log_error('.htaccess path is a symlink. Refusing atomic write: ' . esc_html($this->htaccess_file_path));
                return false;
            }

            if (! $this->should_block_root_only_rsssl_creation($operations)) {
                return true;
            }

            $this->log_error('Refusing root .htaccess creation because resulting file would only contain RSSSL markers.');
            return false;
        }

        /**
         * Executes the full atomic write lifecycle on a locked file handle.
         *
         * @param resource $handle
         * @param array<int,array{marker:string,lines?:array,type?:string}> $operations
         * @throws \Throwable
         */
        private function run_atomic_write_cycle(
            $handle,
            array $operations,
            bool $allowEmptyOriginal = false,
            bool &$writePerformed = false
        ): bool
        {
            try {
                // All reads, transforms and commit checks stay inside the locked section.
                $originalHtaccess = $this->read_locked_htaccess_content($handle, $allowEmptyOriginal);
                if ($originalHtaccess === null) {
                    return false;
                }

                $updatedHtaccess = $this->apply_atomic_operations($originalHtaccess, $operations);
                if ($updatedHtaccess === null) {
                    return false;
                }

                if ($updatedHtaccess === $originalHtaccess) {
                    return true;
                }

                if (! $this->persist_atomic_update($handle, $updatedHtaccess)) {
                    return false;
                }

                $writePerformed = true;
                return true;
            } finally {
                flock($handle, LOCK_UN);
                fclose($handle);
            }
        }

        /**
         * Validates and persists updated `.htaccess` content on a locked handle.
         *
         * Even with the advisory file lock held we still commit via temp-file swap rather than truncating the
         * locked handle directly. That keeps the final replace atomic and avoids leaving a partially written file
         * behind if PHP crashes mid-write.
         *
         * @param resource $handle
         * @throws \Throwable
         */
        private function persist_atomic_update(
            $handle,
            string $updatedHtaccess
        ): bool
        {
            // Treat empty output as corruption everywhere, and block RSSSL-only output only for the real root target.
            if ($this->is_effectively_empty($updatedHtaccess)) {
                $this->log_error('Refusing atomic write because resulting .htaccess would be empty.');
                return false;
            }

            if ($this->should_block_root_only_rsssl_result($updatedHtaccess)) {
                $this->log_error('Refusing atomic write because resulting .htaccess would only contain RSSSL markers.');
                return false;
            }

            return $this->write_locked_htaccess_content($handle, $updatedHtaccess);
        }

        /**
         * Root .htaccess needs non-RSSSL content like WordPress rewrites to stay reachable, but
         * dedicated plugin-managed files such as ACME challenge htaccess files can legitimately
         * contain only our own marker block.
         *
         * @throws \Throwable
         */
        private function should_block_root_only_rsssl_result(string $content): bool
        {
            if (! $this->is_current_canonical_root_htaccess_target()) {
                return false;
            }

            return $this->contains_only_rsssl_blocks($content);
        }

        /**
         * Block root-file creation before touching the filesystem so a failed safety check
         * cannot leave behind an empty .htaccess file.
         *
         * @param array<int,array{marker:string,lines?:array,type?:string}> $operations
         *
         * @throws \Throwable
         */
        private function should_block_root_only_rsssl_creation(array $operations): bool
        {
            // Only protect creation of the canonical root target; plugin-owned auxiliary files may be created from scratch.
            if (! $this->is_current_canonical_root_htaccess_target()) {
                return false;
            }

            if (is_file($this->htaccess_file_path)) {
                return false;
            }

            $predictedHtaccess = $this->apply_atomic_operations('', $operations);
            if ($predictedHtaccess === null) {
                return false;
            }

            return $this->contains_only_rsssl_blocks($predictedHtaccess);
        }

        /**
         * Check whether the active path matches the canonical root `.htaccess` target.
         *
         * @throws \Throwable
         */
        private function is_current_canonical_root_htaccess_target(): bool
        {
            return $this->normalize_htaccess_path_for_compare($this->htaccess_file_path)
                   === $this->normalize_htaccess_path_for_compare($this->get_root_htaccess_target_path());
        }

        /**
         * Normalize compared paths so filter-adjusted targets still match reliably.
         */
        private function normalize_htaccess_path_for_compare(string $path): string
        {
            $normalizedPath = function_exists('wp_normalize_path') ? wp_normalize_path($path) : str_replace('\\', '/', $path);
            return rtrim($normalizedPath, '/');
        }

        /**
         * Opens and advisory-locks the current `.htaccess` target for an atomic write cycle.
         *
         * @return resource|false
         * @throws \Throwable
         */
        private function open_and_lock_htaccess_handle()
        {
            return $this->open_and_lock_handle_for_path($this->htaccess_file_path, '.htaccess atomic write');
        }

        /**
         * Reads current .htaccess content from a locked file handle.
         *
         * @param resource $handle
         */
        private function read_locked_htaccess_content($handle, bool $allowEmptyContent = false): ?string
        {
            if (rewind($handle) === false) {
                $this->log_error('Failed to rewind .htaccess handle before atomic read.');
                return null;
            }

            $content = stream_get_contents($handle);
            if ($content === false) {
                $this->log_error('Failed to read .htaccess during atomic write.');
                return null;
            }

            if ($this->is_effectively_empty($content) && ! $allowEmptyContent) {
                $this->log_error('Refusing atomic write because .htaccess is effectively empty.');
                return null;
            }

            return $content;
        }

        /**
         * Applies marker operations to .htaccess content in memory.
         *
         * @param array<int,array{marker:string,lines?:array,type?:string,literal?:string,search?:string,replace?:string}> $operations
         * @throws \Throwable
         */
        private function apply_atomic_operations(string $content, array $operations): ?string
        {
            $lineEnding = $this->detect_line_ending($content);
            $hasBom = $this->has_utf8_bom($content);
            $updatedHtaccess = $this->normalize_content_for_processing($content);

            foreach ($operations as $operation) {
                $normalizedOperation = $this->normalize_atomic_operation($operation);
                if ($normalizedOperation === null) {
                    return null;
                }

                $operationType   = $normalizedOperation['type'];
                $markerName      = $normalizedOperation['marker'];
                $lines           = $normalizedOperation['lines'];
                if ($operationType === self::ATOMIC_OPERATION_REMOVE_EXACT_LEGACY_GENERIC_ROOT_BLOCKS) {
                    $updatedHtaccess = $this->remove_exact_legacy_generic_root_marker_blocks($updatedHtaccess);
                    continue;
                }

                if ($operationType === self::ATOMIC_OPERATION_REPLACE_LITERAL) {
                    $updatedHtaccess = str_replace(
                        (string) $normalizedOperation['search'],
                        (string) $normalizedOperation['replace'],
                        $updatedHtaccess
                    );
                    continue;
                }

                if ($operationType === self::ATOMIC_OPERATION_REPLACE_LITERAL_IN_RSSSL_BLOCKS) {
                    $updatedHtaccess = $this->replace_literal_in_rsssl_blocks(
                        $updatedHtaccess,
                        (string) $normalizedOperation['search'],
                        (string) $normalizedOperation['replace']
                    );
                    continue;
                }

                $updatedHtaccess = $this->remove_marker_block($updatedHtaccess, $markerName);
                if (empty($lines)) {
                    continue;
                }

                if (
                    $operationType === self::ATOMIC_OPERATION_MARKER_IF_LITERAL_ABSENT
                    && $this->content_contains_literal($updatedHtaccess, (string) ($normalizedOperation['literal'] ?? ''))
                ) {
                    continue;
                }

                $markerBlock     = $this->build_marker_block($markerName, $lines);
                $updatedHtaccess = $this->insert_marker_block($updatedHtaccess, $markerName, $markerBlock);
            }

            $updatedHtaccess = $this->cleanupEmptyLines($updatedHtaccess);
            return $this->restore_content_format($updatedHtaccess, $lineEnding, $hasBom);
        }

        /**
         * Normalizes and validates one atomic marker operation.
         *
         * @param mixed $operation
         * @return array{marker:string,lines:array,type:string,literal?:string,search?:string,replace?:string}|null
         */
        private function normalize_atomic_operation($operation): ?array
        {
            if (! is_array($operation) || ! isset($operation['marker'])) {
                $this->log_error('Invalid operation supplied to write_rules_atomic().');
                return null;
            }

            $operationType = (string) ($operation['type'] ?? self::ATOMIC_OPERATION_MARKER);
            if ($operationType === '') {
                $this->log_error('Operation type is empty in write_rules_atomic().');
                return null;
            }

            $markerName = $this->extract_name_from_marker((string) $operation['marker']);
            if ($markerName === '') {
                $this->log_error('Operation marker is empty in write_rules_atomic().');
                return null;
            }

            if ($operationType === self::ATOMIC_OPERATION_REMOVE_EXACT_LEGACY_GENERIC_ROOT_BLOCKS) {
                return [
                    'marker' => $markerName,
                    'lines'  => [],
                    'type'   => $operationType,
                ];
            }

            if (
                $operationType === self::ATOMIC_OPERATION_REPLACE_LITERAL
                || $operationType === self::ATOMIC_OPERATION_REPLACE_LITERAL_IN_RSSSL_BLOCKS
            ) {
                $search = isset($operation['search']) ? (string) $operation['search'] : '';
                if ($search === '') {
                    $this->log_error('Replace-literal operation search string is empty in write_rules_atomic().');
                    return null;
                }

                return [
                    'marker'  => $markerName,
                    'lines'   => [],
                    'type'    => $operationType,
                    'search'  => $search,
                    'replace' => isset($operation['replace']) ? (string) $operation['replace'] : '',
                ];
            }

            if (
                $operationType !== self::ATOMIC_OPERATION_MARKER
                && $operationType !== self::ATOMIC_OPERATION_MARKER_IF_LITERAL_ABSENT
            ) {
                $this->log_error('Unsupported operation type supplied to write_rules_atomic().');
                return null;
            }

            $lines = $operation['lines'] ?? [];
            if (! is_array($lines)) {
                $lines = [];
            }

            $normalizedOperation = [
                'marker' => $markerName,
                'lines'  => $lines,
                'type'   => $operationType,
            ];

            if ($operationType === self::ATOMIC_OPERATION_MARKER_IF_LITERAL_ABSENT) {
                $literal = isset($operation['literal']) ? trim((string) $operation['literal']) : '';
                if ($literal === '') {
                    $this->log_error('Conditional marker operation literal is empty in write_rules_atomic().');
                    return null;
                }

                $normalizedOperation['literal'] = $literal;
            }

            return $normalizedOperation;
        }

        /**
         * Detect whether the normalized `.htaccess` content already contains a literal directive outside the target marker.
         */
        private function content_contains_literal(string $content, string $literal): bool
        {
            if ($literal === '') {
                return false;
            }

            $normalizedContent = preg_replace("/\r\n?|\r/", "\n", $content);
            if (! is_string($normalizedContent)) {
                $normalizedContent = $content;
            }

            if (preg_match('/^\s*' . preg_quote($literal, '/') . '\s*$/im', $normalizedContent) === 1) {
                return true;
            }

            $normalizedLiteral = $this->normalize_literal_directive_for_compare($literal);
            if ($normalizedLiteral === '') {
                return false;
            }

            $lines = explode("\n", $normalizedContent);
            foreach ($lines as $line) {
                if ($this->normalize_literal_directive_for_compare($line) === $normalizedLiteral) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Normalize single-line directives for duplicate detection.
         *
         * This keeps dedupe tolerant of whitespace/comment variations for known directives such as
         * `Options -Indexes` without changing the broader literal replacement behavior.
         */
        private function normalize_literal_directive_for_compare(string $value): string
        {
            $trimmedValue = trim($value);
            if ($trimmedValue === '') {
                return '';
            }

            $commentStrippedValue = preg_replace('/\s+#.*$/', '', $trimmedValue);
            if (! is_string($commentStrippedValue)) {
                $commentStrippedValue = $trimmedValue;
            }

            $normalizedValue = preg_replace('/\s+/', ' ', trim($commentStrippedValue));
            if (! is_string($normalizedValue)) {
                return trim($commentStrippedValue);
            }

            return $normalizedValue;
        }

        /**
         * Detect whether any RSSSL-managed marker block contains the given literal.
         *
         * @throws \Throwable
         */
        private function content_contains_literal_in_rsssl_blocks(string $content, string $literal): bool
        {
            if ($literal === '') {
                return false;
            }

            $normalizedContent = $this->normalize_content_for_processing($content);
            foreach ($this->get_all_rsssl_markers() as $markerName) {
                $pattern = $this->generate_marker_pattern($markerName);
                $matched = preg_match_all($pattern, $normalizedContent, $matches);
                if ($matched === false || empty($matches[0])) {
                    continue;
                }

                foreach ($matches[0] as $block) {
                    if (strpos($block, $literal) !== false) {
                        return true;
                    }
                }
            }

            return false;
        }

        /**
         * Replace a literal only inside RSSSL-managed marker blocks, leaving unrelated file content untouched.
         * @throws \Throwable
         */
        private function replace_literal_in_rsssl_blocks(string $content, string $search, string $replace): string
        {
            if ($search === '' || $search === $replace) {
                return $content;
            }

            $updatedContent = $content;
            foreach ($this->get_all_rsssl_markers() as $markerName) {
                $pattern = $this->generate_marker_pattern($markerName);
                $candidate = preg_replace_callback(
                    $pattern,
                    static function(array $matches) use ($search, $replace): string {
                        // If there is no match, this callback is not called
                        return str_replace($search, $replace, $matches[0]);
                    },
                    $updatedContent
                );
                if (is_string($candidate)) {
                    $updatedContent = $candidate;
                }
            }

            return $updatedContent;
        }

        /**
         * Strip only exact bare `#Begin Really Simple Security` blocks, leaving named markers untouched.
         */
        private function remove_exact_legacy_generic_root_marker_blocks(string $htaccess): string
        {
            $updatedHtaccess = preg_replace(
                $this->get_exact_legacy_generic_root_marker_block_pattern(),
                '',
                $htaccess
            );
            if (! is_string($updatedHtaccess)) {
                return $htaccess;
            }

            return $updatedHtaccess;
        }

        /**
         * Match only the exact bare legacy `Really Simple Security` marker block.
         */
        public function get_exact_legacy_generic_root_marker_block_pattern(): string
        {
            return '/^\s*#+\s*Begin\s+Really Simple Security\s*#*\s*$\R?(.*?)^\s*#+\s*End\s+Really Simple Security\s*#*\s*(?:\R|$)/ims';
        }

        /**
         * Persist updated `.htaccess` content while the handle is still open and verified.
         *
         * The handle remains valuable even though the actual bytes are written through a temp file:
         * it proves we still own the same advisory-locked inode/path relationship at commit time.
         *
         * @param resource $handle
         */
        private function write_locked_htaccess_content($handle, string $updatedHtaccess, ?string $targetPath = null): bool
        {
            $path = $targetPath ?? $this->htaccess_file_path;
            if (! $this->validate_open_handle_path($handle, $path)) {
                $this->log_error('Atomic write aborted because file path changed before commit: ' . esc_html($path));
                return false;
            }

            if (! $this->write_content_with_temp_swap($path, $updatedHtaccess)) {
                return false;
            }

            clearstatcache(true, $path);
            return true;
        }

        /**
         * Persist content through a temp-file swap so the target path is replaced atomically.
         *
         * This avoids leaving a partially written `.htaccess` behind if PHP crashes mid-write.
         * The temp-swap guarantees atomic replacement of the target path, but it is not a lock on its own.
         */
        private function write_content_with_temp_swap(string $targetPath, string $content): bool
        {
            $tempPath = $this->generate_temp_swap_path($targetPath);
            $tempHandle = @fopen($tempPath, 'wb');
            if ($tempHandle === false) {
                $this->log_error('Unable to open temporary file for write swap: ' . esc_html($tempPath));
                return false;
            }

            if (! $this->write_all_bytes($tempHandle, $content)) {
                fclose($tempHandle);
                @unlink($tempPath);
                $this->log_error('Failed writing temporary file during swap write: ' . esc_html($tempPath));
                return false;
            }

            if (fflush($tempHandle) === false) {
                fclose($tempHandle);
                @unlink($tempPath);
                $this->log_error('Failed flushing temporary file during swap write: ' . esc_html($tempPath));
                return false;
            }

            if (function_exists('fsync') && @fsync($tempHandle) === false) {
                fclose($tempHandle);
                @unlink($tempPath);
                $this->log_error('Failed syncing temporary file during swap write: ' . esc_html($tempPath));
                return false;
            }

            fclose($tempHandle);
            $this->apply_source_file_permissions_to_target($targetPath, $tempPath);
            if (! @rename($tempPath, $targetPath)) {
                @unlink($tempPath);
                $this->log_error('Failed replacing target file with temporary swap file: ' . esc_html($targetPath));
                return false;
            }

            return true;
        }

        /**
         * Drain the full payload into the temp handle so short writes cannot slip through.
         *
         * @param resource $handle
         */
        private function write_all_bytes($handle, string $content): bool
        {
            $offset = 0;
            $length = strlen($content);
            while ($offset < $length) {
                $chunk = substr($content, $offset);
                $written = fwrite($handle, $chunk);
                if ($written === false || $written === 0) {
                    return false;
                }

                $offset += $written;
            }

            return true;
        }

        /**
         * Build a unique temporary swap path next to the target file.
         */
        private function generate_temp_swap_path(string $targetPath): string
        {
            $suffix = str_replace('.', '', uniqid('rsssl_htaccess_', true));
            return $targetPath . '.' . $suffix . '.tmp';
        }

        /**
         * Apply the source file permissions to the temporary swap file when available.
         */
        private function apply_source_file_permissions_to_target(string $sourcePath, string $targetPath): void
        {
            $mode = @fileperms($sourcePath);
            if ($mode === false) {
                return;
            }

            @chmod($targetPath, $mode & 0777);
        }

        /**
         * Reads file content or returns empty content for missing or empty files.
         */
        private function read_file_content_or_empty(string $filePath): ?string
        {
            if ( ! is_file($filePath)) {
                return '';
            }

            $content = file_get_contents($filePath);
            if ($content === false) {
                $this->log_error('Unable to read file content: ' . esc_html($filePath));
                return null;
            }

            return (string) $content;
        }

        /**
         * Atomically derive and update the uploads `.htaccess` RSSSL marker block from rule providers.
         *
         * @param array<int,mixed> $rulesUploads
         * @throws \Throwable
         * @return array{success:bool,rules_content:string}
         */
        public function resolve_and_write_uploads_htaccess_rules_atomic(string $htaccessPath, array $rulesUploads): array
        {
            $result = [
                'success'       => false,
                'rules_content' => '',
            ];

            $this->reset_last_error_message();
            if ($htaccessPath === '') {
                $this->fail_write_operation($htaccessPath, 'Uploads .htaccess path is empty.');
                return $result;
            }

            if ($this->should_skip_current_htaccess_write()) {
                $result['success'] = true;
                return $result;
            }

            // Uploads rules are plugin-owned, so this path may be created from scratch when the file is missing.
            if (! (bool) apply_filters('rsssl_use_atomic_htaccess_writes', true)) {
                return $this->resolve_and_write_uploads_htaccess_rules_lockless_atomic_fallback($htaccessPath, $rulesUploads);
            }

            if (is_link($htaccessPath)) {
                $this->fail_write_operation($htaccessPath, 'Uploads .htaccess path is a symlink. Refusing write.');
                return $result;
            }

            if (! $this->ensure_uploads_htaccess_is_ready($htaccessPath)) {
                $this->fail_write_operation(
                    $htaccessPath,
                    $this->get_last_error_message('Uploads .htaccess file is not ready for atomic write.')
                );
                return $result;
            }

            $targetMissingBeforeWrite = ! is_file($htaccessPath);
            $handle = $this->open_and_lock_custom_handle($htaccessPath);
            if ($handle === false) {
                if ($this->did_last_lock_fallback()) {
                    return $this->resolve_and_write_uploads_htaccess_rules_lockless_atomic_fallback($htaccessPath, $rulesUploads);
                }

                $this->fail_write_operation(
                    $htaccessPath,
                    $this->get_last_error_message('Unable to open and lock uploads .htaccess for atomic write.')
                );
                return $result;
            }

            $originalContent = $this->read_locked_content_allow_empty($handle);
            if ($originalContent === null) {
                $this->fail_write_operation(
                    $htaccessPath,
                    $this->get_last_error_message('Failed to read uploads .htaccess content while lock was held.')
                );
            } else {
                $result['rules_content'] = $this->build_uploads_rules_content_from_rules($rulesUploads, $originalContent);
                $updatedContent = $this->build_uploads_htaccess_content($originalContent, $result['rules_content']);
                if ($updatedContent === $originalContent) {
                    $result['success'] = true;
                } elseif (! $this->write_locked_htaccess_content($handle, $updatedContent, $htaccessPath)) {
                    $this->fail_write_operation(
                        $htaccessPath,
                        $this->get_last_error_message('Uploads .htaccess atomic write failed.')
                    );
                } else {
                    $result['success'] = true;
                }
            }

            flock($handle, LOCK_UN);
            fclose($handle);
            if ($targetMissingBeforeWrite) {
                $this->cleanup_empty_placeholder_file($htaccessPath);
            }

            return $result;
        }

        /**
         * Atomically update the uploads `.htaccess` RSSSL marker block for one explicit uploads target.
         *
         * Uploads differs from root in one important way: the file is plugin-owned, so creating it from scratch
         * is valid. The lock and fallback semantics stay the same as root, though:
         * - try the advisory file lock first
         * - fall back to lockless temp-swap only when locking is unavailable
         * - do not bypass a merely contended lock
         *
         * @throws \Throwable
         */
        public function write_uploads_htaccess_atomic(string $htaccessPath, string $rulesContent): bool
        {
            $this->reset_last_error_message();
            if ($htaccessPath === '') {
                return $this->fail_write_operation($htaccessPath, 'Uploads .htaccess path is empty.');
            }

            if ($this->should_skip_current_htaccess_write()) {
                return true;
            }

            // Uploads rules are plugin-owned, so this path may be created from scratch when the file is missing.
            if (! (bool) apply_filters('rsssl_use_atomic_htaccess_writes', true)) {
                return $this->write_uploads_htaccess_lockless_atomic_fallback($htaccessPath, $rulesContent);
            }

            if (is_link($htaccessPath)) {
                return $this->fail_write_operation($htaccessPath, 'Uploads .htaccess path is a symlink. Refusing write.');
            }

            if (! $this->ensure_uploads_htaccess_is_ready($htaccessPath)) {
                return $this->fail_write_operation(
                    $htaccessPath,
                    $this->get_last_error_message('Uploads .htaccess file is not ready for atomic write.')
                );
            }

            $targetMissingBeforeWrite = ! is_file($htaccessPath);
            $handle = $this->open_and_lock_custom_handle($htaccessPath);
            if ($handle === false) {
                if ($this->did_last_lock_fallback()) {
                    return $this->write_uploads_htaccess_lockless_atomic_fallback($htaccessPath, $rulesContent);
                }

                return $this->fail_write_operation(
                    $htaccessPath,
                    $this->get_last_error_message('Unable to open and lock uploads .htaccess for atomic write.')
                );
            }

            $success = true;
            $originalContent = $this->read_locked_content_allow_empty($handle);
            if ($originalContent === null) {
                $success = $this->fail_write_operation(
                    $htaccessPath,
                    $this->get_last_error_message('Failed to read uploads .htaccess content while lock was held.')
                );
            } else {
                $updatedContent = $this->build_uploads_htaccess_content($originalContent, $rulesContent);
                if ($updatedContent !== $originalContent && ! $this->write_locked_htaccess_content($handle, $updatedContent, $htaccessPath)) {
                    $success = $this->fail_write_operation(
                        $htaccessPath,
                        $this->get_last_error_message('Uploads .htaccess atomic write failed.')
                    );
                }
            }

            flock($handle, LOCK_UN);
            fclose($handle);
            if ($targetMissingBeforeWrite) {
                $this->cleanup_empty_placeholder_file($htaccessPath);
            }

            return $success;
        }

        /**
         * Lockless atomic uploads fallback when advisory locking is unavailable or intentionally disabled.
         *
         * @param array<int,mixed> $rulesUploads
         * @throws \Throwable
         * @return array{success:bool,rules_content:string}
         */
        private function resolve_and_write_uploads_htaccess_rules_lockless_atomic_fallback(string $htaccessPath, array $rulesUploads): array
        {
            $result = [
                'success'       => false,
                'rules_content' => '',
            ];

            if ($htaccessPath === '' || is_link($htaccessPath)) {
                $this->fail_write_operation($htaccessPath, 'Uploads lockless atomic write aborted because the path is invalid or a symlink.');
                return $result;
            }

            if (! $this->ensure_uploads_htaccess_is_ready($htaccessPath)) {
                $this->fail_write_operation($htaccessPath, 'Uploads .htaccess file is not ready for lockless atomic write.');
                return $result;
            }

            $originalContent = $this->read_file_content_or_empty($htaccessPath);
            if ($originalContent === null) {
                $this->fail_write_operation($htaccessPath, 'Failed to read uploads .htaccess content before lockless atomic write.');
                return $result;
            }

            $result['rules_content'] = $this->build_uploads_rules_content_from_rules($rulesUploads, $originalContent);
            $updatedContent = $this->build_uploads_htaccess_content($originalContent, $result['rules_content']);
            if ($updatedContent === $originalContent) {
                $result['success'] = true;
                return $result;
            }

            if (! $this->write_content_with_temp_swap($htaccessPath, $updatedContent)) {
                $this->fail_write_operation($htaccessPath, 'Lockless atomic uploads .htaccess write failed.');
                return $result;
            }

            $result['success'] = true;
            return $result;
        }

        /**
         * Lockless atomic uploads fallback when advisory locking is unavailable or intentionally disabled.
         */
        private function write_uploads_htaccess_lockless_atomic_fallback(string $htaccessPath, string $rulesContent): bool
        {
            // Mirror the atomic uploads behavior for hosts where flock() is not usable.
            if ($htaccessPath === '' || is_link($htaccessPath)) {
                return $this->fail_write_operation($htaccessPath, 'Uploads lockless atomic write aborted because the path is invalid or a symlink.');
            }

            if (! $this->ensure_uploads_htaccess_is_ready($htaccessPath)) {
                return $this->fail_write_operation($htaccessPath, 'Uploads .htaccess file is not ready for lockless atomic write.');
            }

            $originalContent = $this->read_file_content_or_empty($htaccessPath);
            if ($originalContent === null) {
                return $this->fail_write_operation($htaccessPath, 'Failed to read uploads .htaccess content before lockless atomic write.');
            }

            $updatedContent = $this->build_uploads_htaccess_content($originalContent, $rulesContent);
            if ($updatedContent === $originalContent) {
                return true;
            }

            if (! $this->write_content_with_temp_swap($htaccessPath, $updatedContent)) {
                return $this->fail_write_operation($htaccessPath, 'Lockless atomic uploads .htaccess write failed.');
            }

            return true;
        }

        /**
         * Ensures uploads .htaccess path can be created or updated without pre-writing the target file.
         */
        private function ensure_uploads_htaccess_is_ready(string $htaccessPath): bool
        {
            $directory = dirname($htaccessPath);
            if (! is_dir($directory) && ! wp_mkdir_p($directory)) {
                $this->log_error('Could not create uploads directory for .htaccess: ' . esc_html($directory));
                return false;
            }

            if (! is_file($htaccessPath)) {
                if (! is_writable($directory)) {
                    $this->log_error('Uploads directory is not writable for .htaccess creation: ' . esc_html($directory));
                    return false;
                }

                return true;
            }

            if (is_writable($htaccessPath)) {
                return true;
            }

            $this->log_error('Uploads .htaccess file is not writable: ' . esc_html($htaccessPath));
            return false;
        }

        /**
         * Opens and locks a custom file path for atomic updates.
         *
         * @return resource|false
         * @throws \Throwable
         */
        private function open_and_lock_custom_handle(string $filePath)
        {
            return $this->open_and_lock_handle_for_path($filePath, 'custom atomic write');
        }

        /**
         * Open the target file and acquire a bounded advisory exclusive lock.
         *
         * This method may create an empty file via `fopen('c+')` when the target does not exist yet.
         * If we never reach a real commit, the caller cleans up that placeholder again so a failed or
         * interrupted create-on-open cycle does not leave a stray empty `.htaccess` behind.
         *
         * @return resource|false
         * @throws \Throwable
         */
        private function open_and_lock_handle_for_path(string $filePath, string $context, int $attempt = 0)
        {
            $this->last_lock_fallback_detected = false;
            $targetMissingBeforeOpen           = ! is_file($filePath);
            $handle                            = @fopen($filePath, 'c+');
            if ($handle === false) {
                $this->log_error('Unable to open file for atomic write: ' . esc_html($filePath));
                return false;
            }

            if (! $this->validate_open_handle_path($handle, $filePath)) {
                fclose($handle);
                if ($targetMissingBeforeOpen) {
                    $this->cleanup_empty_placeholder_file($filePath);
                }
                return false;
            }

            if (! $this->acquire_exclusive_lock_with_timeout($handle, $filePath, $context)) {
                fclose($handle);
                if ($targetMissingBeforeOpen) {
                    $this->cleanup_empty_placeholder_file($filePath);
                }
                return false;
            }

            if ($this->validate_open_handle_path($handle, $filePath)) {
                return $handle;
            }

            flock($handle, LOCK_UN);
            fclose($handle);
            if ($targetMissingBeforeOpen) {
                $this->cleanup_empty_placeholder_file($filePath);
            }
            if ($attempt >= 1) {
                return false;
            }

            return $this->open_and_lock_handle_for_path($filePath, $context, $attempt + 1);
        }

        /**
         * Acquire a bounded advisory exclusive lock for the already-open file handle.
         *
         * Outcome split:
         * - if `flock()` reports ordinary contention, return false without enabling fallback
         * - if `flock()` itself is unavailable or broken here, mark the lockless fallback as allowed
         *
         * That distinction prevents RSSSL from bypassing a working cooperative lock just because another
         * request currently owns it.
         *
         * @param resource $handle
         * @throws \Throwable
         */
        private function acquire_exclusive_lock_with_timeout($handle, string $filePath, string $context): bool
        {
            // Fail fast after bounded retries so admin requests do not hang indefinitely behind a stale lock.
            $maxAttempts = (int) apply_filters('rsssl_htaccess_lock_max_attempts', 50);
            $delay       = (int) apply_filters('rsssl_htaccess_lock_attempt_delay', 100000);
            if ($maxAttempts < 1) {
                $maxAttempts = 1;
            }
            if ($delay < 0) {
                $delay = 0;
            }

            $attempt = 0;
            while ($attempt < $maxAttempts) {
                $wouldBlock = 0;
                if (@flock($handle, LOCK_EX | LOCK_NB, $wouldBlock)) {
                    return true;
                }

                if ((int) $wouldBlock !== 1) {
                    $this->last_lock_fallback_detected = true;
                    $this->log_error(
                        'Unable to acquire file lock for ' . esc_html($context) .
                        '. Path: ' . esc_html($filePath) .
                        '. Continuing with lockless atomic temp-swap writes for this request.'
                    );
                    $this->set_lock_warning_notice();
                    return false;
                }

                $attempt++;
                if ($attempt >= $maxAttempts) {
                    break;
                }

                usleep($delay);
            }

            $this->log_error(
                'Timed out waiting for file lock for ' . esc_html($context) .
                '. Path: ' . esc_html($filePath)
            );
            return false;
        }

        /**
         * Whether the last lock attempt failed because locking was unavailable, not merely contended.
         */
        private function did_last_lock_fallback(): bool
        {
            return $this->last_lock_fallback_detected;
        }

        /**
         * Validates that the opened handle still points to the expected non-symlink path.
         *
         * @param resource $handle
         */
        private function validate_open_handle_path($handle, string $filePath): bool
        {
            // Re-check the opened handle against the path to catch symlink or path swaps before commit.
            clearstatcache(true, $filePath);
            if (is_link($filePath)) {
                $this->log_error('Atomic write blocked because path is a symlink: ' . esc_html($filePath));
                return false;
            }

            $handleStat = fstat($handle);
            if (! is_array($handleStat)) {
                $this->log_error('Atomic write blocked because file handle stat failed: ' . esc_html($filePath));
                return false;
            }

            $pathStat = @lstat($filePath);
            if (! is_array($pathStat)) {
                $this->log_error('Atomic write blocked because path stat failed: ' . esc_html($filePath));
                return false;
            }

            if ($this->is_stat_symlink($pathStat)) {
                $this->log_error('Atomic write blocked because path resolved to symlink: ' . esc_html($filePath));
                return false;
            }

            if ($this->is_same_file_stat($handleStat, $pathStat)) {
                return true;
            }

            $this->log_error('Atomic write blocked because file changed during open/lock cycle: ' . esc_html($filePath));
            return false;
        }

        /**
         * Compares file stats by inode/device.
         *
         * @param array $handleStat
         * @param array $pathStat
         *
         * @return bool
         */
        private function is_same_file_stat(array $handleStat, array $pathStat): bool
        {
            if (! isset($handleStat['dev'], $handleStat['ino'], $pathStat['dev'], $pathStat['ino'])) {
                return false;
            }

            return (int) $handleStat['dev'] === (int) $pathStat['dev']
                   && (int) $handleStat['ino'] === (int) $pathStat['ino'];
        }

        /**
         * Detects symlink mode from stat array.
         *
         * @param array $stat
         *
         * @return bool
         */
        private function is_stat_symlink(array $stat): bool
        {
            if (! isset($stat['mode'])) {
                return false;
            }

            return (((int) $stat['mode']) & 0120000) === 0120000;
        }

        /**
         * Reads from a locked file handle and allows empty content.
         *
         * @param resource $handle
         */
        private function read_locked_content_allow_empty($handle): ?string
        {
            if (rewind($handle) === false) {
                $this->log_error('Failed to rewind locked file handle before read.');
                return null;
            }

            $content = stream_get_contents($handle);
            if ($content === false) {
                $this->log_error('Failed reading locked file content.');
                return null;
            }

            return (string) $content;
        }

        /**
         * Builds the final uploads .htaccess content around the RSSSL marker block.
         */
        private function build_uploads_htaccess_content(string $originalContent, string $rulesContent): string
        {
            // Uploads uses its own #Begin/#End marker format, separate from root .htaccess markers.
            $lineEnding = $this->detect_line_ending($originalContent);
            $hasBom = $this->has_utf8_bom($originalContent);
            $workingContent = $this->normalize_content_for_processing($originalContent);
            $contentWithoutBlock = $this->remove_uploads_marker_block($workingContent);
            $markerBlock         = $this->build_uploads_marker_block($rulesContent);
            if ($markerBlock === '') {
                $updatedContent = $this->cleanupEmptyLines(rtrim($contentWithoutBlock));
                return $this->restore_content_format($updatedContent, $lineEnding, $hasBom);
            }

            if (trim($contentWithoutBlock) === '') {
                return $this->restore_content_format($markerBlock, $lineEnding, $hasBom);
            }

            $updatedContent = rtrim($contentWithoutBlock) . "\n\n" . $markerBlock;
            $updatedContent = $this->cleanupEmptyLines($updatedContent);
            return $this->restore_content_format($updatedContent, $lineEnding, $hasBom);
        }

        /**
         * Compile uploads rules from the same content snapshot used for the write decision.
         *
         * @param array<int,mixed> $rulesUploads
         */
        private function build_uploads_rules_content_from_rules(array $rulesUploads, string $existingContent): string
        {
            $rulesContent = '';
            foreach ($rulesUploads as $rule) {
                if (! is_array($rule)) {
                    continue;
                }

                if (! $this->should_include_uploads_rule_for_content($rule, $existingContent)) {
                    continue;
                }

                $rulesContent .= (string) ($rule['rules'] ?? '');
            }

            $normalizedRules = preg_replace("/\r\n?/", "\n", $rulesContent);
            return trim((string) $normalizedRules, "\n");
        }

        /**
         * Skip uploads rules whose identifier already exists outside the managed uploads marker block.
         */
        private function should_include_uploads_rule_for_content(array $rule, string $existingContent): bool
        {
            $identifier = (string) ($rule['identifier'] ?? '');
            if ($identifier === '') {
                return true;
            }

            $normalizedContent = $this->normalize_content_for_processing($existingContent);
            $contentWithoutManagedBlock = $this->remove_uploads_marker_block($normalizedContent);
            return strpos($contentWithoutManagedBlock, $identifier) === false;
        }

        /**
         * Removes the uploads RSSSL marker block from existing content.
         */
        private function remove_uploads_marker_block(string $content): string
        {
            $pattern = '/#Begin Really Simple Security.*?#End Really Simple Security\s*/is';
            $updated = preg_replace($pattern, '', $content);
            if ($updated === null) {
                return $content;
            }

            return $updated;
        }

        /**
         * Builds uploads marker block content from provided rules.
         */
        private function build_uploads_marker_block(string $rulesContent): string
        {
            $normalizedRules = preg_replace("/\r\n?/", "\n", $rulesContent);
            if ($normalizedRules === null) {
                $normalizedRules = $rulesContent;
            }

            $trimmedRules = trim($normalizedRules, "\n");
            if ($trimmedRules === '') {
                return '';
            }

            return '#Begin Really Simple Security' . "\n" .
                   $trimmedRules . "\n" .
                   '#End Really Simple Security' . "\n";
        }

        /**
         * Validates the rule definition before writing.
         *
         * @param array $ruleDefinition
         * @return bool True if valid, false otherwise.
         */
        private function validateRuleDefinition(array $ruleDefinition): bool
        {
            if (empty($ruleDefinition['marker'])) {
                $this->log_error('No marker provided for write_rule.');
                return false;
            }
            return true;
        }

        /**
         * Normalizes write_rule() input so empty lines mean "remove or skip this marker".
         *
         * @param array $ruleDefinition
         * @return string[] Array of lines to write.
         */
        private function prepareLines(array $ruleDefinition): array
        {
            $lines = $ruleDefinition['lines'] ?? [];
            if (! is_array($lines)) {
                return [];
            }

            return $lines;
        }

        /**
         * Ensures that the .htaccess path can be created or updated without pre-writing the target file.
         *
         * @throws \Throwable
         */
        private function ensure_htaccess_is_writable(): bool
        {
            $dir = dirname($this->htaccess_file_path);

            // Ensure the directory exists.
            if (! is_dir($dir) && ! wp_mkdir_p($dir)) {
                $this->log_error('Cannot create directory for .htaccess at: ' . esc_html($dir));
                return false;
            }

            // Do not pre-create the file here. The locked fopen('c+') path creates it only when the
            // atomic cycle actually starts, which avoids extra writes and placeholder truncation races.
            if (! is_file($this->htaccess_file_path)) {
                $allow_create = apply_filters('rsssl_allow_create_htaccess', false, $this->htaccess_file_path);
                if (! $allow_create) {
                    $this->log_error('.htaccess file does not exist and automatic creation is disabled. Path: ' . esc_html($this->htaccess_file_path));
                    return false;
                }

                if (! is_writable($dir)) {
                    $this->log_error('Directory for .htaccess is not writable at: ' . esc_html($dir));
                    return false;
                }

                return true;
            }

            if (! is_writable($this->htaccess_file_path)) {
                $this->log_error('.htaccess file is not writable at: ' . esc_html($this->htaccess_file_path));
                return false;
            }

            return true;
        }

        /**
         * Inserts a marker block either at the top (for top markers) or at file end.
         *
         * @throws \Throwable
         */
        private function insert_marker_block(string $htaccess, string $markerName, string $markerBlock): string
        {
            $top_markers = apply_filters(
                'rsssl_htaccess_top_markers',
                [ 'Really Simple Auto Prepend File', 'Really Simple Security Redirect' ]
            );

            if ( in_array( $markerName, $top_markers, true ) ) {
                return $this->insert_marker_in_correct_position( $htaccess, $markerName, $markerBlock );
            }

            if ( $this->is_effectively_empty( $htaccess ) ) {
                return $markerBlock;
            }

            return rtrim( $htaccess ) . PHP_EOL . PHP_EOL . $markerBlock;
        }

        /**
         * Inserts a marker block in the correct position in the .htaccess file.
         */
        private function insert_marker_in_correct_position(string $htaccess, string $markerName, string $markerBlock): string
        {
            $autoPrependName = 'Really Simple Auto Prepend File';

            if ( strcasecmp( $markerName, $autoPrependName ) === 0 ) {
                return $markerBlock . $htaccess;
            }

            $autoPrependPattern = $this->generate_marker_pattern( $autoPrependName );

            if (
                preg_match( $autoPrependPattern, $htaccess, $match, PREG_OFFSET_CAPTURE ) === 1
                && isset( $match[0][0], $match[0][1] )
                && is_string( $match[0][0] )
                && is_int( $match[0][1] )
            ) {
                $insertPosition = $match[0][1] + strlen( $match[0][0] );
                $prefix = substr( $htaccess, 0, $insertPosition );
                $needsLeadingNewLine = $prefix !== '' && substr( $prefix, -1 ) !== "\n";
                if ( $needsLeadingNewLine ) {
                    $markerBlock = "\n" . ltrim( $markerBlock, "\n" );
                }

                return $prefix . $markerBlock . substr( $htaccess, $insertPosition );
            }

            return $markerBlock . $htaccess;
        }

        /**
         * Generates a regex pattern to match a marker block in the .htaccess file.
         *
         * This pattern matches both # and ### markers, case-insensitive, and captures
         * the entire block including the BEGIN and END lines.
         */
        public function generate_marker_pattern(string $markerName): string
        {
            $escaped = preg_quote( $markerName, '/' );

            return '/(^\s*#+\s*BEGIN\s+' . $escaped . '\s*#*\s*$\n.*?^\s*#+\s*END\s+' . $escaped . '\s*#*\s*(?:\n|$))/ims';
        }

        /**
         * Removes a marker block from the .htaccess file.
         */
        private function remove_marker_block(string $htaccess, string $markerName): string
        {
            $normalized = preg_replace("/\r\n?|\r/", "\n", $htaccess);
            if ($normalized !== null) {
                $htaccess = $normalized;
            }
            $htaccess = $this->normalize_marker_boundaries($htaccess);

            $pattern = $this->generate_marker_pattern($markerName);
            $updated = preg_replace($pattern, '', $htaccess);
            if ($updated !== null) {
                $htaccess = $updated;
            }

            return ltrim($htaccess, "\n");
        }

        /**
         * Returns all RSSSL marker names used in .htaccess safety checks.
         *
         * @throws \Throwable
         * @return array<int,string>
         */
        public function get_all_rsssl_markers(): array
        {
            // Keep the safety-check marker list centralized so every managed block is accounted for.
            if ($this->cached_rsssl_markers !== null) {
                return $this->cached_rsssl_markers;
            }

            $defaultMarkers = [
                'Really Simple Auto Prepend File',
                'Really Simple Security Redirect',
                'Really Simple Security Disable directory indexing',
                'Really Simple Security LETS ENCRYPT',
                'Really Simple Security No Index',
                'Really_Simple_SSL_SECURITY_HEADERS',
                'Really_Simple_SSL_CSP_Report_Only',
                'Really_Simple_SSL_Content_Security_Policy',
                'Really Simple SSL',
                'rlrssslReallySimpleSSL',
                'Really Simple Security',
            ];
            $markers = apply_filters('rsssl_htaccess_all_markers', $defaultMarkers);
            if (! is_array($markers)) {
                $markers = $defaultMarkers;
            }

            $normalized = [];
            foreach ($markers as $marker) {
                $markerName = $this->extract_name_from_marker((string) $marker);
                if ($markerName === '') {
                    continue;
                }

                $normalized[] = $markerName;
            }

            $this->cached_rsssl_markers = array_values(array_unique($normalized));
            return $this->cached_rsssl_markers;
        }

        /**
         * Detects whether content only contains RSSSL-managed marker blocks.
         *
         * @throws \Throwable
         */
        private function contains_only_rsssl_blocks(string $content): bool
        {
            if ((bool) apply_filters('rsssl_force_write_despite_safety_check', false)) {
                return false;
            }

            // If stripping our blocks leaves nothing meaningful, assume we are about to overwrite non-RSSSL content.
            $remainingContent = $this->normalize_content_for_processing($content);
            foreach ($this->get_all_rsssl_markers() as $markerName) {
                $remainingContent = $this->remove_marker_block($remainingContent, $markerName);
            }

            $remainingContent = $this->cleanupEmptyLines($remainingContent);
            return trim($remainingContent) === '';
        }

        /**
         * Build one canonical marker block so every managed insert uses the same shape.
         */
        private function build_marker_block(string $markerName, array $lines): string
        {
            return implode(PHP_EOL, array_merge(
                    ["# BEGIN {$markerName}"],
                    $lines,
                    ["# END {$markerName}"]
                )) . PHP_EOL;
        }



        /**
         * Extracts a usable name from the BEGIN marker for insert_with_markers.
         * E.g., "#BEGIN My Rule" becomes "My Rule".
         */
        public function extract_name_from_marker(string $begin_marker): string
        {
            // Remove #, BEGIN, Begin, begin and then trim
            // also remove trailing ###
            $name = preg_replace( array( '/^#+\s*(BEGIN|Begin|begin)\s*/i', '/\s*#+$/' ), '', $begin_marker );
            return trim($name);
        }

        /**
         * Clears a specific marker block from the .htaccess file.
         *
         * @param string|array $marker The marker name (string) or marker array (['#Begin ...', '#End ...']).
         *
         * @return bool True on success, false on failure.
         * @throws \Throwable
         */
        public function clear_rule($marker): bool
        {
            // Accept either a string (marker name) or an array (markers)
            if (is_array($marker)) {
                $begin_marker = $marker[0] ?? '';
            } else {
                $begin_marker = (string) $marker;
            }

            $markerName = $this->extract_name_from_marker( $begin_marker );
            if ( $markerName === '' ) {
                $this->log_error( 'No marker provided for clear_rule().' );
                return false;
            }

            return $this->write_rule_atomic( $markerName, [] );
        }

        /**
         * Clears a specific legacy marker block from the `.htaccess` file.
         *
         * @deprecated 9.5.10 Use clear_rule() instead.
         * @throws \Throwable
         */
        public function clear_legacy_rule(string $marker): bool
        {
            if (function_exists('_deprecated_function')) {
                _deprecated_function(__METHOD__, '9.5.10', __CLASS__ . '::clear_rule');
            }

            return $this->clear_rule($marker);
        }

        /**
         * The plugin-wide opt-out should suppress every .htaccess mutation, regardless of caller.
         */
        private function should_skip_current_htaccess_write(): bool
        {
            if ($this->bypass_do_not_edit_htaccess) {
                return false;
            }

            if (! function_exists('rsssl_should_skip_managed_htaccess_writes')) {
                return false;
            }

            return rsssl_should_skip_managed_htaccess_writes();
        }

        /**
         * Reads the content between a marker block in the .htaccess file and returns it as a string, including the marker lines.
         */
        public function get_rule_content(string $markerName):? string
        {
            $content = $this->get_htaccess_content_for_path($this->htaccess_file_path);
            if ($content === null) {
                return null;
            }

            $content = $this->normalize_content_for_processing($content);
            $content = $this->normalize_marker_boundaries($content);

            $pattern = $this->generate_marker_pattern($markerName);
            if (preg_match($pattern, $content, $matches)) {
                return trim($matches[1]);
            }
            return null;
        }

        /**
         * Read one marker block from an explicit `.htaccess` path without leaving that path active afterward.
         *
         * @throws \Throwable
         */
        public function get_rule_content_for_path(string $htaccess_file_path, string $markerName): ?string
        {
            return $this->with_temporary_htaccess_path(
                $htaccess_file_path,
                function () use ( $markerName ): ?string {
                    return $this->get_rule_content($markerName);
                }
            );
        }

        /**
         * Reads the lines inside a marker block (without BEGIN/END lines).
         *
         * @return array<int,string>
         */
        public function get_rule_lines(string $markerName): array
        {
            $block = $this->get_rule_content($markerName);
            if ($block === null) {
                return [];
            }

            $normalized = preg_replace("/\r\n?/", "\n", $block);
            $lines = explode("\n", trim((string) $normalized, "\n"));
            if (count($lines) < 3) {
                return [];
            }

            array_shift($lines);
            array_pop($lines);
            return array_values($lines);
        }

        /**
         * Read one marker block's lines from an explicit `.htaccess` path without leaving that path active afterward.
         *
         * @return array<int,string>
         * @throws \Throwable
         */
        public function get_rule_lines_for_path(string $htaccess_file_path, string $markerName): array
        {
            return $this->with_temporary_htaccess_path(
                $htaccess_file_path,
                function () use ( $markerName ): array {
                    return $this->get_rule_lines($markerName);
                }
            );
        }

        /**
         * Detect whether one explicit `.htaccess` path still contains a literal inside RSSSL-managed blocks.
         *
         * @throws \Throwable
         */
        public function contains_literal_in_rsssl_blocks_for_path(string $htaccess_file_path, string $literal): bool
        {
            return $this->with_temporary_htaccess_path(
                $htaccess_file_path,
                function () use ( $literal ): bool {
                    $content = $this->get_htaccess_content_for_path($this->htaccess_file_path);
                    if (! is_string($content)) {
                        return false;
                    }

                    return $this->content_contains_literal_in_rsssl_blocks($content, $literal);
                }
            );
        }

        /**
         * Delegate lock-warning storage to the shared helper so executor and per-file fallbacks present one notice.
         */
        private function set_lock_warning_notice(): void
        {
            if (function_exists('\\rsssl_set_htaccess_lock_warning_notice')) {
                \rsssl_set_htaccess_lock_warning_notice();
            }
        }

        /**
         * Keep failure exits consistent so callers only need one log-and-return path.
         */
        private function fail_write_operation(string $path, string $errorMessage): bool
        {
            $this->log_error($errorMessage);
            return false;
        }

        /**
         * Reset the per-operation error state before a new managed write attempt starts.
         */
        private function reset_last_error_message(): void
        {
            $this->last_error_message = '';
        }

        /**
         * Prefer the captured internal error, but let callers provide a context fallback.
         */
        private function get_last_error_message(string $fallback): string
        {
            if ($this->last_error_message !== '') {
                return $this->last_error_message;
            }

            return $fallback;
        }

        /**
         * Writes an error message to the error log.
         */
        public function log_error(string $message): void
        {
            $this->last_error_message = $message;
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'RSSSL_Htaccess_File_Manager: ' . $message );
            }
        }

        /**
         * Check whether an explicit `.htaccess` path exists and is writable.
         */
        public function is_valid_htaccess_file_path( string $htaccess_file_path ): bool {
            if ( empty( $htaccess_file_path ) ) {
                return false;
            }

            return is_file( $htaccess_file_path ) && is_writable( $htaccess_file_path );
        }

        /**
         * Cleans up extra empty lines in .htaccess content.
         *
         * @param string $content The raw .htaccess content.
         * @return string The content with consecutive blank lines reduced.
         */
        private function cleanupEmptyLines(string $content): string
        {
            $normalized = preg_replace("/\r\n?/", "\n", $content);
            if ($normalized !== null) {
                $content = $normalized;
            }
            $content = $this->normalize_marker_boundaries($content);
            $collapsed = preg_replace("/\n{3,}/", "\n\n", $content);
            if ($collapsed !== null) {
                $content = $collapsed;
            }
            return $content;
        }

        /**
         * Splits malformed marker boundaries like:
         * "# END Marker# BEGIN Other Marker".
         */
        private function normalize_marker_boundaries(string $content): string
        {
            $normalized = preg_replace('/(^\s*#+\s*END[^\n]*?)([ \t]*#+\s*BEGIN\s+)/im', '$1' . "\n" . '$2', $content);
            if ($normalized === null) {
                return $content;
            }

            return $normalized;
        }

        /**
         * Checks if the given content is effectively empty (only whitespace).
         */
        private function is_effectively_empty( $content ): bool {
            if ( $content === null || $content === false ) {
                return true;
            }
            return trim( (string) $content ) === '';
        }

        /**
         * Remove an empty file that was created only to start an atomic cycle but never received a real commit.
         */
        private function cleanup_empty_placeholder_file(string $filePath): void
        {
            clearstatcache(true, $filePath);
            if (! is_file($filePath) || is_link($filePath)) {
                return;
            }

            $content = @file_get_contents($filePath);
            if ($content === false || trim($content) !== '') {
                return;
            }

            if (! @unlink($filePath)) {
                $this->log_error('Failed to remove empty .htaccess placeholder file: ' . esc_html($filePath));
            }
        }

        /**
         * Preserve an existing BOM so rewrites do not silently change file encoding.
         */
        private function has_utf8_bom(string $content): bool
        {
            return substr($content, 0, 3) === "\xEF\xBB\xBF";
        }

        /**
         * Reuse the existing line-ending style so managed writes stay diff-friendly.
         */
        private function detect_line_ending(string $content): string
        {
            if (strpos($content, "\r\n") !== false) {
                return "\r\n";
            }

            if (strpos($content, "\r") !== false) {
                return "\r";
            }

            return "\n";
        }

        /**
         * Normalize content for regex work so matching logic is not formatting-dependent.
         */
        private function normalize_content_for_processing(string $content): string
        {
            if ($this->has_utf8_bom($content)) {
                $content = substr($content, 3);
            }

            $normalized = preg_replace("/\r\n?|\r/", "\n", $content);
            if ($normalized === null) {
                return $content;
            }

            return $normalized;
        }

        /**
         * Restore the original line ending and BOM after in-memory normalization.
         */
        private function restore_content_format(string $content, string $lineEnding, bool $hasBom): string
        {
            if ($lineEnding !== "\n") {
                $content = str_replace("\n", $lineEnding, $content);
            }

            if (! $hasBom || $content === '') {
                return $content;
            }

            if ($this->has_utf8_bom($content)) {
                return $content;
            }

            return "\xEF\xBB\xBF" . $content;
        }
    }
}
