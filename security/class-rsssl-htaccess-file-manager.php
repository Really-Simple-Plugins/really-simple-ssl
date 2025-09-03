<?php
/**
 * class-rsssl-htaccess-file-manager.php
 *
 * Responsible for reading, writing and versioning .htaccess
 * rules via WordPress’s insert_with_markers API.
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
	 *  – recording history,
	 *  – cooperating with WP Rocket.
	 *  – will no longer auto-create a missing .htaccess (opt-in via `rsssl_allow_create_htaccess`).
	 */
class RSSSL_Htaccess_File_Manager {

    /**
     * Singleton instance.
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Return the shared instance of this class.
     *
     * @return self
     */
    public static function get_instance(): self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Is used for storing the path to the .htaccess file.
     */
    public string $htaccess_file_path;

    /**
     * Constructor.
     *
     */
    public function __construct()
    {
        $this->htaccess_file_path = $this->determineHtaccessFilePath();
        $this->registerRocketHooks();
    }

    /**
     * Determines the path to the .htaccess file based on various conditions.
     */
    private function determineHtaccessFilePath(): string
    {
        // Prefer a custom home .htaccess if it exists
        $homePath = apply_filters('rsssl_home_htaccess_path', get_home_path() . '.htaccess');
        if ($this->file_exists($homePath)) {
            return apply_filters('rsssl_htaccess_file_path', $homePath);
        }

        // Otherwise use the default .htaccess in ABSPATH
        $defaultPath = apply_filters('rsssl_default_htaccess_path', ABSPATH . '.htaccess');
        if ($this->file_exists($defaultPath)) {
            return apply_filters('rsssl_htaccess_file_path', $defaultPath);
        }

        // Fallback to WP_CONTENT_DIR/.htaccess (path only; file will not be auto-created)
        $contentPath = apply_filters('rsssl_wp_content_htaccess_path', WP_CONTENT_DIR . '/.htaccess');
        return apply_filters('rsssl_htaccess_file_path', $contentPath);
    }

	/**
	 * Registers hooks for WP Rocket activation and deactivation. So we can record the history of changes made by WP Rocket.
	 */
	private function registerRocketHooks(): void
	{
		// Register hooks for WP Rocket activation and deactivation
		add_action('rocket_activation', [ $this, 'record_history_from_rocket' ]);
		add_action('rocket_deactivation', [ $this, 'record_history_from_rocket' ]);
	}

    /**
     * Sets or updates the path to the .htaccess file to be managed.
     */
    public function set_htaccess_file_path(string $htaccess_file_path): void {
        $this->htaccess_file_path = $htaccess_file_path;
    }

    /**
     * Reads the content of the .htaccess file.
     */
    public function get_htaccess_content():? string
    {
        if ( is_file($this->htaccess_file_path) && is_readable($this->htaccess_file_path)) {
            return file_get_contents($this->htaccess_file_path);
        }
        return null;
    }

	/**
	 * Writes a rule block to the .htaccess file.
	 */
    public function write_rule(array $rule_definition, string $debugTest = 'unknown'): bool
    {
        if (! $this->validateRuleDefinition($rule_definition)) {
            return false;
        }

        if (! $this->ensure_htaccess_is_writable()) {
            return false;
        }

	    return $this->applyMarkerBlock(
		    $this->extract_name_from_marker($rule_definition['marker']),
		    $this->prepareLines($rule_definition),
		    $debugTest
	    );
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
     * Prepares the lines to write, inserting a placeholder if needed.
     *
     * @param array $ruleDefinition
     * @return string[] Array of lines to write.
     */
    private function prepareLines(array $ruleDefinition): array
    {
        $lines = $ruleDefinition['lines'] ?? [];
        $isBeingCleared = ! empty($ruleDefinition['clear_rule']);

        if (empty($lines) && ! $isBeingCleared) {
            return [
                '',
                '# This feature has not been activated.',
                '',
            ];
        }

        return $lines;
    }

	/**
	 * Applies a marker block to the .htaccess file, supporting configurable top-priority markers.
	 */
	private function applyMarkerBlock(string $markerName, array $lines, string $debugTest = 'unknown'): bool
	{
	    $oldContent = $this->get_htaccess_content() ?: '';

	    // Allow certain markers to be forced to the very top of .htaccess (right under any existing top block)
	    $top_markers = apply_filters(
	        'rsssl_htaccess_top_markers',
	        [ 'Really Simple Auto Prepend File', 'Really Simple Security Redirect' ]
	    );

	    if ( in_array( $markerName, $top_markers, true ) ) {
			// first remove any existing marker block with the same name
	        $result = $this->write_top_marker_block( $markerName, $lines );
	    } else {
	        // WP core will preserve everything outside of your marker
		    $probe = $this->get_htaccess_content();
		    if ( $this->is_effectively_empty( $probe ) ) {
			    $result = false;
		    } else {
			    // WP core will preserve everything outside of your marker
			    $result = insert_with_markers( $this->htaccess_file_path, $markerName, $lines );
		    }
	    }

	    if ( $result ) {
	        $newContent = $this->get_htaccess_content() ?: '';
	        $this->record_history( $oldContent, $newContent, $markerName, $debugTest );
	    }

	    return $result;
	}

	/**
	 * Ensures that the .htaccess file exists and is writable.
	 */
	private function ensure_htaccess_is_writable(): bool
	{
	    $dir = dirname( $this->htaccess_file_path );

	    // Ensure the directory exists (same as before)
	    if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
	        $this->log_error( 'Cannot create directory for .htaccess at: ' . esc_html( $dir ) );
	        return false;
	    }

	    // Do **not** create a new .htaccess automatically anymore.
	    // This previously led to empty files overwriting existing rewrite rules in some environments.
	    // If a site really wants us to create the file, they must opt in via the filter below.
	    if ( ! is_file( $this->htaccess_file_path ) ) {
	        $allow_create = apply_filters( 'rsssl_allow_create_htaccess', false, $this->htaccess_file_path );
	        if ( $allow_create ) {
	            if ( @file_put_contents( $this->htaccess_file_path, '' ) === false ) {
	                $this->log_error( 'Could not create .htaccess file at: ' . esc_html( $this->htaccess_file_path ) );
	                return false;
	            } else {
	                $this->log_error( 'Created new .htaccess file at: ' . esc_html( $this->htaccess_file_path ) );
	            }
	        } else {
	            $this->log_error( '.htaccess file does not exist and automatic creation is disabled. Path: ' . esc_html( $this->htaccess_file_path ) );
	            return false;
	        }
	    }

	    if ( ! is_writable( $this->htaccess_file_path ) ) {
	        $this->log_error( '.htaccess file is not writable at: ' . esc_html( $this->htaccess_file_path ) );
	        return false;
	    }

	    return true;
	}

    /**
     * Writes a marker block that must live at the very top of .htaccess.
     *
     * Used for markers that must run before WordPress rewrite rules – e.g.
     *  - "Really Simple Auto Prepend File"
     *  - "Really Simple Security Redirect" (HTTP→HTTPS redirect)
     */
    private function write_top_marker_block( string $markerName, array $linesToWrite ): bool
    {
        // Preserve original content for history
        $originalHtaccess = $this->get_htaccess_content() ?: '';
	    // SAFETY: if .htaccess is (effectively) empty or unreadable, do not write our markers
	    if ( $this->is_effectively_empty( $originalHtaccess ) ) {
		    return false;
	    }
		// we remove the redirect marker block if it exists, so we can write a new one
	    // this is needed because the redirect marker block is not removed by insert_with_markers
	    // We added this function because not on every save we can determine when to remove options when the rule is not present.
	    if ( $markerName !== 'Really Simple Security Redirect' && 'htaccess' !== rsssl_get_option('redirect')) {
		    $originalHtaccess = $this->remove_marker_block( $originalHtaccess, 'Really Simple Security Redirect' );
	    }
		$htaccessWithoutMarker = $this->remove_marker_block( $originalHtaccess, $markerName );

	    if (empty($linesToWrite)) {
		    return $this->save_htaccess_if_changed($originalHtaccess, $htaccessWithoutMarker, $markerName);
	    }

		$newMarkerBlock = $this->build_marker_block( $markerName, $linesToWrite );
		$updatedHtaccess = $this->insert_marker_in_correct_position($htaccessWithoutMarker, $markerName, $newMarkerBlock);

		$updatedHtaccess = $this->cleanupEmptyLines($updatedHtaccess);

        @file_put_contents( $this->htaccess_file_path, $updatedHtaccess, LOCK_EX );
        $this->record_history( $originalHtaccess, $updatedHtaccess, $markerName );
        return true;
    }

	/**
	 * Inserts a marker block in the correct position in the .htaccess file.
	 */
	private function insert_marker_in_correct_position(string $htaccess, string $markerName, string $markerBlock): string
	{
		$autoPrependName = 'Really Simple Auto Prepend File';

		if (strcasecmp($markerName, $autoPrependName) === 0) {
			return $markerBlock . $htaccess;
		}

		$escapedAutoPrependName = preg_quote($autoPrependName, '/');

		$autoPrependPattern = $this->generate_marker_pattern($autoPrependName);

		if (preg_match($autoPrependPattern, $htaccess, $match, PREG_OFFSET_CAPTURE)) {
			$insertPosition = $match[1][1] + strlen($match[1][0]);
			return substr($htaccess, 0, $insertPosition) . $markerBlock . substr($htaccess, $insertPosition);
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
		$escaped = preg_quote($markerName, '/');
		//return '/(^#+\s*BEGIN\s+' . $escaped . '[^\n]*\n.*?^#+\s*END\s+' . $escaped . '[^\n]*\n?)/ims';
		return '/(^\s*#+\s*BEGIN\s+' . $escaped . '[^\n]*\n.*?^\s*#+\s*END\s+' . $escaped . '[^\n]*\n?)/ims';
	}

	/**
	 * Removes a marker block from the .htaccess file.
	 */
	private function remove_marker_block(string $htaccess, string $markerName): string
	{
        // Normalize line endings so regex behaves consistently
       // $htaccess = preg_replace("/\r\n? /", "\n", $htaccess);
		$htaccess = preg_replace("/\r\n?/", "\n", $htaccess);

        // Build a single, tolerant pattern matching any number of leading '#', optional trailing text on BEGIN/END lines,
        // and capturing across multiple lines.
        $pattern = $this->generate_marker_pattern($markerName);

        // Apply the replacement and capture match count for debugging
        $before = $htaccess;
        $htaccess = preg_replace($pattern, '', $htaccess, -1, $count);

        return ltrim($htaccess, "\n");
	}

	/**
	 * Saves the .htaccess file if it has changed, and record the history.
	 */
	private function save_htaccess_if_changed(string $original, string $modified, string $markerName): bool
	{
		if ( $modified === $original ) {
			return true;
		}

		// SAFETY: do not write when the current .htaccess is effectively empty
		if ( $this->is_effectively_empty( $original ) ) {
			return false;
		}

		$cleaned = $this->cleanupEmptyLines( $modified );

		// Avoid writing an empty result
		if ( $this->is_effectively_empty( $cleaned ) ) {
			return true;
		}

		@file_put_contents( $this->htaccess_file_path, $cleaned, LOCK_EX );
		$this->record_history( $original, $cleaned, $markerName );

		return true;
	}

	private function build_marker_block(string $markerName, array $lines): string
	{
		return implode(PHP_EOL, array_merge(
				["# BEGIN {$markerName}"],
				$lines,
				["# END {$markerName}"]
			)) . PHP_EOL;
	}



	/**
     * Checks if a specific marker block exists in the .htaccess file.
     *
     * @param array $markers The start and end markers (e.g., ['#BEGIN rule', '#END rule']).
     * @return bool True if the block exists, false otherwise.
     */
    public function are_markers_present(array $markers): bool
    {
        if (count($markers) !== 2) {
            return false;
        }
        $content = $this->get_htaccess_content();
        if ($content === null) {
            return false;
        }
        $start_marker_escaped = preg_quote($markers[0], '/');
        $end_marker_escaped = preg_quote($markers[1], '/');
        return preg_match('/^\s*' . $start_marker_escaped . '.*?^\s*' . $end_marker_escaped . '/ms', $content) === 1;
    }

    /**
     * Extracts a usable name from the BEGIN marker for insert_with_markers.
     * E.g., "#BEGIN My Rule" becomes "My Rule".
     */
    private function extract_name_from_marker(string $begin_marker): string
    {
        // Remove #, BEGIN, Begin, begin and then trim
	    // also remove trailing ###
        $name = preg_replace( array( '/^#+\s*(BEGIN|Begin|begin)\s*/i', '/\s*#+$/' ), '', $begin_marker );
        return trim($name);
    }

    /**
     * Records a change to the .htaccess history.
     *
     * @param string $old_content The previous content.
     * @param string $new_content The new content.
     */
    private function record_history( string $old_content, string $new_content , string $marker = 'unknown', string $debugTest = 'unknown'): void
    {
        if ( ! $this->is_htaccess_tracking_enabled() ) {
            // we remove the option if the constant is not defined.
            delete_option( 'rsssl_htaccess_history' );
            return;
        }
        if ( $old_content === $new_content ) {
            return;
        }
        $history = get_option( 'rsssl_htaccess_history', [] );
        $history[] = [
            'timestamp'   => time(),
            'file_path'   => $this->htaccess_file_path,
            'old_content' => $old_content,
            'new_content' => $new_content,
            'user_id'     => function_exists( 'get_current_user_id' ) ? get_current_user_id() : 0,
	        'marker'      => $marker,
	        // logging the current hook name for debugging purposes
	        'hook'        => current_filter() ?: 'unknown',
	        // logging the current action for debugging purposes
	        'action'      => current_action()? : 'unknown',
	        'debug_test'  => $debugTest,
        ];
        if ( count( $history ) > 20 ) {
            $history = array_slice( $history, -20 );
        }
        update_option( 'rsssl_htaccess_history', $history, false );
    }

    /**
     * Clears a specific marker block from the .htaccess file.
     *
     * @param string|array $marker The marker name (string) or marker array (['#Begin ...', '#End ...']).
     * @return bool True on success, false on failure.
     */
    public function clear_rule($marker, string $debugTest = 'unknown'): bool
    {
        // Accept either a string (marker name) or an array (markers)
        if (is_array($marker)) {
            $begin_marker = $marker[0] ?? '';
        } else {
            $begin_marker = $marker;
        }
        $rule_definition = [
            'marker' => $begin_marker,
            'lines' => [],
            'clear_rule' => true,
        ];
        return $this->write_rule($rule_definition, $debugTest);
    }

    /**
     * Clears a specific marker block from the .htaccess file without using
     * insert_with_markers. This method directly removes the block using raw
     * regex matching. This is needed for old markings that had capitalized
     * Begin and End markers.
     */
public function clear_legacy_rule(string $marker): bool
{
    $content = $this->get_htaccess_content();
    if ($content === null) {
        return false;
    }

	// SAFETY: if the file is effectively empty, do not attempt to rewrite it
	if ( $this->is_effectively_empty( $content ) ) {
		return false;
	}

    // Match and remove the block with the exact marker name
    $escaped = preg_quote($marker, '/');
    $pattern = '/^#+\s*Begin\s+' . $escaped . '.*?^#+\s*End\s+' . $escaped . '.*?$/ms';

    $new_content = trim(preg_replace($pattern, '', $content));

    // Regex error
    if ($new_content === null) {
        return false;
    }

    // Write the updated content back to the .htaccess file
    if ( $new_content !== $content && ! $this->is_effectively_empty( $new_content ) ) {
        return file_put_contents($this->htaccess_file_path, $new_content) !== false;
    }

    return true; // No changes needed
}

	/**
	 * Records the history of changes made by WP Rocket to the .htaccess file.
	 */
	public function record_history_from_rocket(): void
	{
		// We get the previous content from the history, if it exists.
		$history = get_option( 'rsssl_htaccess_history', [] );
		$old_content = '';
		if ( ! empty( $history ) ) {
			$last_entry = end( $history );
			if ( isset( $last_entry['new_content'] ) ) {
				$old_content = $last_entry['new_content'];
			}
		}
		$new_content = file_get_contents( $this->htaccess_file_path );
		if ( $new_content === false ) {
			return;
		}
		$this->record_history( $old_content, $new_content, 'wp-rocket' );
	}

    /**
     * Checks if .htaccess history tracking is enabled via constant.
     *
     * @since 5.x.x
     *
     * @return bool True if .htaccess history tracking is enabled, false otherwise.
     */
    private function is_htaccess_tracking_enabled(): bool
    {
        return defined( 'RSSSL_RECORDS_HISTORY_VERSION' );
    }

    /**
     * Reads the content between a marker block in the .htaccess file and returns it as a string, including the marker lines.
     */
    public function get_rule_content(string $markerName):? string
    {
        $content = $this->get_htaccess_content();
        if ($content === null) {
            return null;
        }
        // Match both # and ### marker styles, case-insensitive, including the marker lines
        $escaped = preg_quote($markerName, '/');
        $pattern = '/(#+\s*BEGIN\s+' . $escaped . '[^\n]*\n.*?#+\s*END\s+' . $escaped . '[^\n]*\n?)/is';
        if (preg_match($pattern, $content, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

	/**
	 * Writes an error message to the error log.
	 */
	public function log_error(string $message): void
	{
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'RSSSL_Htaccess_File_Manager: ' . $message );
		}
	}

	/**
	 * Validates the .htaccess file path. If exists, writable and a valid string.
	 */
	public function validate_htaccess_file_path(): bool {
		// Check if the file path is a valid string and not empty
		if (empty( $this->htaccess_file_path ) ) {
			return false;
		}

		// Check if the file exists and is writable
		if ( ! is_file( $this->htaccess_file_path ) || ! is_writable( $this->htaccess_file_path ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Checks if the .htaccess file exists.
	 */
	public function file_exists( string $file_path ): bool {
		return is_file( $file_path );
	}

    /**
     * Cleans up extra empty lines in .htaccess content.
     *
     * @param string $content The raw .htaccess content.
     * @return string The content with consecutive blank lines reduced.
     */
    private function cleanupEmptyLines(string $content): string
    {
        // Normalize all line endings to "\n"
	    // Collapse three or more consecutive newlines into two
        $content = preg_replace( array( "/\r\n?/", "/\n{3,}/" ), array( "\n", "\n\n" ), $content );
        return $content;
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
}
}