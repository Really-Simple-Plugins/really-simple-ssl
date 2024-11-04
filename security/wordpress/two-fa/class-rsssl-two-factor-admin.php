<?php
/**
 * This file contains the Rsssl_Two_Factor_Admin class.
 *
 * The Rsssl_Two_Factor_Admin class is responsible for handling the administrative
 * aspects of the two-factor authentication feature in the Really Simple SSL plugin.
 * It includes methods for displaying the two-factor authentication settings in the
 * admin area, handling user input, and managing user roles and capabilities related
 * to two-factor authentication.
 *
 * PHP version 7.2
 *
 * @category   Security
 * @package Really_Simple_SSL
 * @author Really Simple SSL
 */

namespace RSSSL\Security\WordPress\Two_Fa;

use WP_User;

/**
 * The Rsssl_Two_Factor_Admin class is responsible for handling the administrative
 * aspects of the two-factor authentication feature in the Really Simple SSL plugin.
 * It includes methods for displaying the two-factor authentication settings in the
 * admin area, handling user input, and managing user roles and capabilities related
 * to two-factor authentication.
 *
 * @category   Security
 * @package Really_Simple_SSL
 * @subpackage Two_Factor
 */
class Rsssl_Two_Factor_Admin
{
    /**
     * The Rsssl_Two_Factor_Admin instance.
     *
     * @var Rsssl_Two_Factor_Settings $instance The settings object.
     */
    private static $instance;

    /**
     * The constructor.
     *
     * @return void
     */
    public function __construct()
    {
        // if the user is not logged in, it don't need to do anything.
        if (!rsssl_admin_logged_in()) {
            return;
        }
        if (isset(self::$instance)) {
            wp_die();
        }
        self::$instance = $this;
        add_filter('rsssl_do_action', array($this, 'two_fa_table'), 10, 3);
        add_filter('rsssl_after_save_field', array($this, 'maybe_reset_two_fa'), 20, 2);
        add_filter('rsssl_after_save_field', array($this, 'change_disabled_users_when_forced'), 20, 3);
	    add_action('process_user_batch_event', [$this, 'process_user_batch'], 10 , 5);

    }

	/**
	 * Handles server-side processing for two-factor authentication data.
	 *
	 * @param Rsssl_Two_FA_Data_Parameters $data_parameters The data parameters for the request.
	 *
	 * @return array The response array containing the request success status, data, total records, and executed query.
	 */
	private function server_side_handler(Rsssl_Two_FA_Data_Parameters $data_parameters): array {
		global $wpdb;

		$days_threshold = rsssl_get_option('two_fa_grace_period', 30);
		$filter_value = $data_parameters->filter_value;

		$enabled_roles = array_unique(array_merge(
			defined('rsssl_pro') ? rsssl_get_option('two_fa_enabled_roles_totp', array()) : array(),
			rsssl_get_option('two_fa_enabled_roles_email', array())
		));

		$forced_roles = rsssl_get_option('two_fa_forced_roles', array());

		$fields = ['id', 'user', 'status_for_user', 'rsssl_two_fa_providers', 'user_role']; // Example fields
		$enabled_roles_placeholders = implode(',', array_map(function($role) { return "'$role'"; }, $enabled_roles));
		$forced_roles_placeholder = implode(',', array_map(function($role) { return "'$role'"; }, $forced_roles));
		$query = self::generate_query($fields, $enabled_roles_placeholders, $forced_roles_placeholder, $forced_roles);

		if ($filter_value !== 'all') {
			$query .= $wpdb->prepare(" HAVING status_for_user = %s", $filter_value);
		}
		$prepared_query = $wpdb->prepare($query, array_merge(
		// Use array_map to generate the thresholds for each forced role
			array_fill(0, count($forced_roles), $days_threshold)
		));

		// only execute query if there are enabled roles to show
		if (empty($enabled_roles)) {
			return array(
				'request_success' => true,
				'data' => [],
				'totalRecords' => 0,
			);
		}

		$results = $wpdb->get_results($prepared_query);

		return array(
			'request_success' => true,
			'data' => is_array($results) ? array_values($results) : [],
			'totalRecords' => is_array($results) ? count($results) : 0,
//			'executed_query' => $prepared_query,
		);
	}

	/**
	 * Generates the SELECT clause for the SQL query.
	 *
	 * @param array $fields The fields to include in the SELECT clause.
	 * @return string The generated SELECT clause.
	 */
	public static function generate_select_clause(array $fields, array $forced_roles): string
	{
		$select_parts = [];

		if ( in_array( 'id', $fields, true ) ) {
			$select_parts[] = 'DISTINCT (u.ID) as id';
		}

		if ( in_array( 'user', $fields, true ) ) {
			$select_parts[] = 'u.user_login as user';
		}

		// Status for User Field
		if (in_array('status_for_user', $fields, true)) {
			// Create placeholders for forced roles
			$forced_roles_placeholders = implode(',', array_fill(0, count($forced_roles), '%s'));

			// Check if forced_roles is empty or not
			if (empty($forced_roles_placeholders)) {
				// No forced roles, basic status handling
				$select_parts[] = "
                CASE
                    WHEN COALESCE(um_totp.meta_value, 'open') = 'open' OR COALESCE(um_email.meta_value, 'open') = 'open' THEN 'open'
                    WHEN COALESCE(um_totp.meta_value, 'disabled') = 'active' OR COALESCE(um_email.meta_value, 'disabled') = 'active' THEN 'active'
                    ELSE COALESCE(um_totp.meta_value, um_email.meta_value)
                END AS status_for_user
            ";
			} else {
				// Initialize the CASE statement parts for status_for_user
				$status_cases = [];

				// First condition: Check if TOTP or Email is active (this is common for all roles)
				$status_cases[] = "WHEN COALESCE(um_totp.meta_value, 'disabled') = 'active' OR COALESCE(um_email.meta_value, 'disabled') = 'active' THEN 'active'";

				// Loop through forced roles and apply expiration logic
				foreach ($forced_roles as $role) {
					// Check if an expiration threshold is defined for the current role
						$status_cases[] = "WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(ur.meta_value, '\"', 2), '\"', -1) = '$role'
                                   AND DATEDIFF(NOW(), um_last_login.meta_value) > %d THEN 'expired'";
				}

				// Fallback: If no other conditions match, default to 'open'
				$status_cases[] = "ELSE COALESCE(um_totp.meta_value, um_email.meta_value, 'open')";

				// Combine the conditions into a CASE clause
				$select_parts[] = "CASE " . implode(' ', $status_cases) . " END AS status_for_user";
			}
		}

		if ( in_array( 'user_role', $fields, true ) ) {
			$select_parts[] = "SUBSTRING_INDEX(SUBSTRING_INDEX(ur.meta_value, '\"', 2), '\"', -1) AS user_role";
		}

		if ( in_array( 'rsssl_two_fa_providers', $fields, true ) ) {
			$select_parts[] = "
            CASE
                WHEN COALESCE(um_totp.meta_value, um_email.meta_value, 'open') = 'open' THEN ''
                WHEN um_totp.meta_value = 'active' THEN 'totp'
                WHEN um_email.meta_value = 'active' THEN 'email'
                ELSE 'none'
            END AS rsssl_two_fa_providers
        ";
		}

		return implode(', ', $select_parts);
	}

	/**
	 * Generates the full SQL query.
	 *
	 * @param array $fields The fields to include in the SELECT clause.
	 * @param string $enabled_roles_placeholders The placeholders for enabled roles.
	 * @param string|null $forced_roles_placeholder The placeholders for forced roles.
	 * @return string The generated SQL query.
	 */
	public static function generate_query(array $fields, string $enabled_roles_placeholders, string $forced_roles_placeholder = '', $forced_roles = array() ): string
	{
	    global $wpdb;

	    $select_clause = self::generate_select_clause($fields, $forced_roles);

	    $where_clause = "SUBSTRING_INDEX(SUBSTRING_INDEX(ur.meta_value, '\"', 2), '\"', -1) in ($enabled_roles_placeholders)";
//	    if (!empty($forced_roles_placeholder)) {
//	        $where_clause = "SUBSTRING_INDEX(SUBSTRING_INDEX(ur.meta_value, '\"', 2), '\"', -1) in ($forced_roles_placeholder)";
//	    }

	    $sql = "
	        SELECT $select_clause
	        FROM {$wpdb->users} u
	        LEFT JOIN {$wpdb->usermeta} um_totp ON u.ID = um_totp.user_id AND um_totp.meta_key = 'rsssl_two_fa_status_totp'
	        LEFT JOIN {$wpdb->usermeta} um_email ON u.ID = um_email.user_id AND um_email.meta_key = 'rsssl_two_fa_status_email'
	            ";
		if (is_multisite()) {
			$sites = get_sites();
			$conditions = [];
			foreach ($sites as $site) {
				$conditions[] = "ur.meta_key = '{$wpdb->get_blog_prefix($site->blog_id)}capabilities'";
			}
			$sql .= "LEFT JOIN {$wpdb->usermeta} ur ON u.ID = ur.user_id AND (" . implode(' OR ', $conditions) . ")";
		} else {
			$sql .= "LEFT JOIN {$wpdb->usermeta} ur ON u.ID = ur.user_id AND ur.meta_key = '{$wpdb->base_prefix}capabilities'";
		}


	    $sql .="LEFT JOIN {$wpdb->usermeta} la ON u.ID = la.user_id AND la.meta_key = 'rsssl_two_fa_login_action'
	        LEFT JOIN {$wpdb->usermeta} um_last_login ON u.ID = um_last_login.user_id AND um_last_login.meta_key = 'rsssl_two_fa_last_login'
	        WHERE $where_clause
	    ";

			return $sql;
	}

    private static function user_count(): ?string {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->users");
    }

	/**
	 * Change the disabled status of users when forced.
	 *
	 * @param string $field_id The ID of the field being changed.
	 * @param mixed $new_value The new value of the field.
	 *
	 * @return void
	 */
	public function change_disabled_users_when_forced( string $field_id, $new_value, $prev_value = [] ): void
	{
		if ('two_fa_forced_roles' === $field_id && !empty($new_value)) {
			global $wpdb;
			$forced_roles = $new_value;
			if (empty($prev_value)) {
				$prev_value = [];
			}
			$added_roles = array_diff($forced_roles, $prev_value);

			$forced_roles = $added_roles;
			if(empty($forced_roles)) {
				return;
			}

			// Fetching the users that have the forced roles.
			$fields = ['id', 'status_for_user'];
			$enabled_roles = array_unique(array_merge(
				defined('rsssl_pro') ? rsssl_get_option('two_fa_enabled_roles_totp', array()) : array(),
				rsssl_get_option('two_fa_enabled_roles_email', array())
			));
			//This line is forcefully setting the forced roles to the enabled roles. Because we only impact enforced users with this action.
			$enabled_roles_placeholders = implode(',', array_map(function($role) { return "'$role'"; }, $forced_roles));
			$forced_roles_placeholder = implode(',', array_map(function($role) { return "'$role'"; }, $forced_roles));
			$query = self::generate_query($fields, $enabled_roles_placeholders, $forced_roles_placeholder, $forced_roles);

			$batch_size = 1000;
			$offset = 0;

			$this->process_user_batch($query, $enabled_roles, $forced_roles, $batch_size, $offset);
		}
	}


	/**
	 * Process a batch of users.
	 *
	 * @param string $query The base query to fetch users.
	 * @param array $enabled_roles The enabled roles.
	 * @param array $forced_roles The forced roles.
	 * @param int $batch_size The size of each batch.
	 * @param int $offset The offset for the current batch.
	 *
	 * @return void
	 */
	public function process_user_batch(string $query, array $enabled_roles, array $forced_roles, int $batch_size, int $offset): void
	{
		global $wpdb;
		$paged_query = $query . " LIMIT %d OFFSET %d";
		$forced_roles_placeholder = implode(',', $forced_roles);
		$enabled_roles_placeholders = implode(',', $enabled_roles);
		$prepared_query = $wpdb->prepare($paged_query, $forced_roles_placeholder, $batch_size, $offset);
		$users = $wpdb->get_results($prepared_query);

		if (empty($users)) {
			return;
		}

		foreach ($users as $user) {
			// if there is an active or open method, We do nothing.
			if ('active' === $user->status_for_user ) {
				continue;
			}
			if ('open' === $user->status_for_user) {
				// if the user has no meta_key rsssl_two_fa_last_login, we set it to now.
				if (!get_user_meta((int)$user->id, 'rsssl_two_fa_last_login', true)) {
					update_user_meta((int)$user->id, 'rsssl_two_fa_last_login', gmdate('Y-m-d H:i:s'));
				}
				continue;
			}
			// now we reset the user.
			Rsssl_Two_Fa_Status::delete_two_fa_meta((int)$user->id);
			// Set the rsssl_two_fa_last_login to now, so the user will be forced to use 2fa.
			update_user_meta((int)$user->id, 'rsssl_two_fa_last_login', gmdate('Y-m-d H:i:s'));
		}

		// Schedule the next batch
		wp_schedule_single_event(time() + 60, 'process_user_batch_event', [$query, $enabled_roles, $forced_roles, $batch_size, $offset + $batch_size]);
	}

    /**
     * Checks if the user can use two-factor authentication (2FA).
     *
     * @return bool Returns true if the user can use 2FA, false otherwise.
     */
    public function can_i_use_2fa(): bool
    {
        return rsssl_get_option('login_protection_enabled');
    }


	/**
	 * Creates a captcha notice array.
	 *
	 * This method creates and returns an array representing a captcha notice.
	 *
	 * @param  string $title  The title of the notice.
	 * @param  string $msg  The message of the notice.
	 *
	 * @return array The captcha notice array.
	 */
	private function create_2fa_notice( string $title, string $msg ): array {
		return array(
			'callback'          => '_true_',
			'score'             => 1,
			'show_with_options' => array( 'login_protection_enabled' ),
			'output'            => array(
				'true' => array(
					'title'              => $title,
					'msg'                => $msg,
					'icon'               => 'warning',
					'type'               => 'open',
					'dismissible'        => true,
					'admin_notice'       => false,
					'plusone'            => true,
					'highlight_field_id' => 'two_fa_enabled_roles',
				),
			),
		);
	}

	/**
	 * If a user role is removed, it needs to reset this role for all users
	 *
	 * @param string $field_id The field ID.
	 * @param mixed  $new_value The new value.
	 *
	 * @return void
	 */
	public static function maybe_reset_two_fa( string $field_id, $new_value ): void {
		if ( ! rsssl_user_can_manage() ) {
			return;
		}
	}

	/**
	 * Reset the two-factor authentication for a user.
	 *
	 * @param array  $response The response array.
	 * @param string $action The action being performed.
	 * @param array  $data The data array.
	 *
	 * @return array The updated response array.
	 */
	public static function reset_user_two_fa( array $response, string $action, array $data ): array {
		if ( ! rsssl_user_can_manage() ) {
			return $response;
		}
		if ( 'two_fa_table' === $action ) {
			// if the user has been disabled, it needs to reset the two-factor authentication.
			$user = get_user_by( 'id', $data['user_id'] );
			if ( $user ) {
				// Delete all 2fa related user meta.
				self::delete_two_fa_meta( $user );
				// Set the last login to now, so the user will be forced to use 2fa.
				update_user_meta( $user->ID, 'rsssl_two_fa_last_login', gmdate( 'Y-m-d H:i:s' ) );
			}
		}
		return $response;
	}

	/**
	 * Get users based on arguments and method.
	 *
	 * @param array  $args The arguments to retrieve users.
	 * @param string $method The method to retrieve users.
	 *
	 * @return array The list of users matching the arguments and method.
	 */
	protected static function get_users( array $args, string $method ): array {
		if ( ! is_multisite() ) {
			return get_users( $args );
		}

		$users = self::get_multisite_users( $args );
        if( $method !== 'two_fa_forced_roles' ) {
            $users = self::filter_users_by_role( $users, $args, $method );
        }

		return self::slice_users_by_offset_and_number( $users, $args );
	}

	/**
	 * Get all multisite users from all sites.
	 *
	 * @param array $args {
	 *     Optional. Arguments for filtering the users.
	 *
	 * @type int $offset Offset for pagination. Default is 0.
	 * @type int $number Maximum number of users to retrieve. Default is 0 (retrieve all users).
	 *     ... Additional arguments for filtering the user query.
	 * }
	 *
	 * @return array Array of users.
	 */
	private static function get_multisite_users( array $args ): array {
		$sites = get_sites();
		$users = array();

		unset( $args['offset'], $args['number'] );

		foreach ( $sites as $site ) {
			switch_to_blog( $site->blog_id );
			$site_users = get_users( $args );
			foreach ( $site_users as $user ) {
				$user_roles = get_userdata( $user->ID )->roles;
				if ( ! isset( $users[ $user->ID ] ) ) {
					$users[ $user->ID ] = $user;
				}
				$users_roles[ $user->ID ] = array_unique( $users_roles[ $user->ID ] ?? array() + $user_roles );
			}
			restore_current_blog();
		}

		return $users;
	}

	/**
	 * Filter users by role.
	 *
	 * @param array  $users The array of users.
	 * @param array  $args The array of filter arguments.
	 * @param string $method The method name.
	 *
	 * @return array The filtered array of users.
	 */
	private static function filter_users_by_role( array $users, array $args, string $method ): array {
		if ( ! isset( $args['role'] ) ) {
			return $users;
		}

		$filter_role           = $args['role'];
		$filter_role_is_forced = Rsssl_Two_Factor_Settings::role_is_of_type( $method, $filter_role, 'forced' );

		return array_filter(
			$users,
			static function ( $user_id, $user_roles ) use ( $filter_role_is_forced, $method ) {
				return ! ( ! $filter_role_is_forced && Rsssl_Two_Factor_Settings::contains_role_of_type( $method, (array) $user_roles, 'forced' ) );
			},
			ARRAY_FILTER_USE_BOTH
		);
	}

	/**
	 * Slice users by offset and number.
	 *
	 * This function takes an array of users and an array of arguments
	 * and applies the offset and number values to the users array.
	 * It returns a new array with the specified offset and number of users.
	 *
	 * @param array $users The array of users.
	 * @param array $args The array of arguments containing the offset and number values.
	 *
	 * @return array The new array of users with the specified offset and number.
	 */
	private static function slice_users_by_offset_and_number( array $users, array $args ): array {
		// Apply the 'offset' to the combined result.
		if ( 0 !== ( $args['offset'] ?? 0 ) ) {
			$users = array_slice( $users, $args['offset'] );
		}
		// Ensure the final result does not exceed the specified 'number'.
		if ( 0 !== ( $args['number'] ?? 0 ) ) {
			$users = array_slice( $users, 0, $args['number'] );
		}

		// To reset array keys.
		return array_values( $users );
	}


    /**
     * Generates the two-factor authentication table data based on the action and data parameters.
     *
     * @param array $response The initial response data.
     * @param string $action The action to perform.
     * @param array $data The data needed for the action.
     *
     * @return array The updated response data.
     */
    public function two_fa_table(array $response, string $action, array $data): array
    {
        $new_response = $response;
        if (rsssl_user_can_manage()) {
            $data_parameters = new Rsssl_Two_FA_Data_Parameters($data);

            switch ($action) {
                case 'two_fa_table':
	                return $this->server_side_handler($data_parameters);
                case 'two_fa_reset_user':
                    // if the user has been disabled, it needs to reset the two-factor authentication.
                    $user = get_user_by('id', $data['id']);

                    if ($user) {
                        // Delete all 2fa related user meta.
                        Rsssl_Two_Fa_Status::delete_two_fa_meta($user);
                        // Set the rsssl_two_fa_last_login to now, so the user will be forced to use 2fa.
                        update_user_meta($user->ID, 'rsssl_two_fa_last_login', gmdate('Y-m-d H:i:s'));
                    }
                    if (!$user) {
                        $new_response['request_success'] = false;
                    }
                    break;

                default:
                    // Default case if no action matches.
                    break;
            }
        }
        return $new_response;
    }

    /**
     * Reset two-factor authentication for a user if the user has been disabled.
     *
     * @param string $method The method to reset.
     * @param int $user_id The user ID.
     *
     * @return string[]
     */
    private function check_status_and_return(string $method, int $user_id): ?array
    {
        $status = Rsssl_Two_Factor_Settings::get_user_status($method, $user_id);
        if (in_array($status, array('active', 'open', 'disabled'), true)) {
            return array($method, $status, true);
        }
        return null;
    }

    /**
     * Get the status for a given user ID, by method.
     *
     * @param int $user_id The user ID to get the status for.
     *
     * @return array The status for the given user ID, by method.
     */
    public function get_status_by_method(int $user_id): array
    {
        $user_id = absint($user_id);
        if (defined('rsssl_pro') && rsssl_pro) {
            $result = $this->get_status_for_method('totp', $user_id);
        }

        if (!isset($result)) {
            $result = $this->get_status_for_method('email', $user_id);
        } else {
            if ($result[0] === 'empty' || 'disabled' === $result[1]) {
                $result = $this->get_status_for_method('email', $user_id);
            }

        }

        if (empty($result) || 'disabled' === $result[1]) {
            $result = array('disabled', 'disabled');
        }

        if (empty($result)) {
            $enabled_roles = Rsssl_Two_Factor_Settings::get_enabled_roles($user_id) ?? array();
            $enabled_method = Rsssl_Two_Factor_Settings::get_enabled_method($user_id);

            $result = empty($enabled_roles)
                ? array($enabled_method, 'disabled')
                : array($enabled_method, 'open');
        }
        return $result;
    }

    /**
     * Get the status for a given method and user ID.
     *
     * @param string $method The method to get the status for.
     * @param int $user_id The user ID to get the status for.
     *
     * @return array|null The status for the given method and user ID, or null if not found.
     */
    public function get_status_for_method(string $method, int $user_id): ?array
    {
        $role_status = Rsssl_Two_Factor_Settings::get_role_status($method, $user_id);
        $user_status = Rsssl_Two_Factor_Settings::get_user_status($method, $user_id);

        if ('empty' !== $role_status && 'open' === $user_status) {
            $result = $this->check_status_and_return($method, $user_id);
            if ('active' === $user_status) {
                return $result;
            }
        }
        return array($role_status, $user_status);
    }
}
