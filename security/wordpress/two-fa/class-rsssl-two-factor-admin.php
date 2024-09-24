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

use RSSSL\Security\WordPress\Two_Fa\Rsssl_Two_Factor_Settings;
use WP_User;
use RSSSL\Security\WordPress\Two_Fa\Rsssl_Two_FA_Data_Parameters;

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
class Rsssl_Two_Factor_Admin {
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
	public function __construct() {
		// if the user is not logged in, it don't need to do anything.
		if ( ! rsssl_admin_logged_in() ) {
			return;
		}
		if ( isset( self::$instance ) ) {
			wp_die();
		}

		self::$instance = $this;
		add_filter( 'rsssl_do_action', array( $this, 'two_fa_table' ), 10, 3 );
		add_filter( 'rsssl_after_save_field', array( $this, 'maybe_reset_two_fa' ), 20, 2 );
		add_filter( 'rsssl_after_save_field', array( $this, 'change_disabled_users_when_forced' ), 20, 2 );
	}

    /**
     * Change the disabled status of users when forced.
     *
     * @param string $field_id The ID of the field being changed.
     * @param mixed $new_value The new value of the field.
     *
     * @return void
     */
    public function change_disabled_users_when_forced(string $field_id, $new_value): void
    {
        if ( 'two_fa_forced_roles' === $field_id && !empty($new_value)) {
            $forced_roles = $new_value;
            // Fetching the users that have the forced roles.
            $args = array(
                'role__in' => $forced_roles,
                'fields' => array( 'ID', 'user_login' ), // Only get necessary fields.
            );
            $users = self::get_users( $args, 'two_fa_forced_roles' );
            foreach ($users as $user) {
                $user = new WP_User($user);
                $status_per_methods = Rsssl_Two_Factor_Settings::get_user_status_per_method($user->ID);

                // if there is an active or open method, We do nothing.
                if (in_array('active', $status_per_methods) || in_array('open', $status_per_methods)) {
                    // I the method is open we check if the user has a last_login if not we set it to now.
//                    if (in_array('open', $status_per_methods)) {
//                        $last_login = get_user_meta($user->ID, 'rsssl_two_fa_last_login', true);
//                        if (empty($last_login)) {
//                            update_user_meta($user->ID, 'rsssl_two_fa_last_login', gmdate('Y-m-d H:i:s'));
//                        }
//                    }
                    continue;
                }
                // now we reset the user.
                Rsssl_Two_Fa_Status::delete_two_fa_meta($user);
                // Set the rsssl_two_fa_last_login to now, so the user will be forced to use 2fa.
                update_user_meta($user->ID, 'rsssl_two_fa_last_login', gmdate('Y-m-d H:i:s'));
            }
        }
    }

	/**
	 * Checks if the user can use two-factor authentication (2FA).
	 *
	 * @return bool Returns true if the user can use 2FA, false otherwise.
	 */
	public function can_i_use_2fa(): bool {
		return rsssl_get_option( 'login_protection_enabled' );
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
	 * @param array  $response The initial response data.
	 * @param string $action The action to perform.
	 * @param array  $data The data needed for the action.
	 *
	 * @return array The updated response data.
	 */
	public function two_fa_table( array $response, string $action, array $data ): array {
		$new_response = $response;
		if ( rsssl_user_can_manage() ) {
			$data_parameters = new \RSSSL\Security\WordPress\Two_Fa\Rsssl_Two_FA_Data_Parameters($data);

			switch ( $action ) {
				case 'two_fa_table':
					$args = array(
						'fields' => array( 'ID', 'user_login' ), // Only get necessary fields.
					);

					$args['orderby'] = 'user' === $data_parameters->sort_column ? 'user_login' : $data_parameters->sort_column;
					$args['order']   = $data_parameters->sort_direction;

					if ( '' !== $data_parameters->search_term ) {
						$args['search'] = '*' . $data_parameters->search_term . '*';
					}

					$total_data = self::get_users( $args, $data_parameters->method );

                   // Filtering out users that have roles that are enabled.
                    $total_data = array_filter($total_data, function($data) {
                        $user = new WP_User($data); // Replace this with your actual objects
                        $enabled_roles = Rsssl_Two_Factor_Settings::get_enabled_roles($user->ID);
                        return !empty($enabled_roles);
                    });

					// now limit to one page only.
					$args['number'] = $data_parameters->page_size;
					$args['offset'] = $data_parameters->page - 1;


					$formatted_data = array();
					foreach ( $total_data as $user ) {
						// Convert the user object to WP_User.
						$user          = new WP_User( $user );
						$status_method = $this->get_status_by_method( $user->ID );

						// Get the user role.
						$user_role = Rsssl_Two_Factor_Settings::get_user_roles( $user->ID );
						// Format user data.
						$login_action = Rsssl_Two_Factor_Settings::get_login_action( $user->ID );

                        $user_status = $status_method[1];

                        if ($login_action === 'onboarding' || $login_action === 'login') {
                            $login_action = '';
                        }

                        if ($login_action === 'expired') {
                            $user_status = 'expired';
                        }

                        if ($login_action === 'totp') {
                            $login_action = strtoupper($login_action);
                        } else {
                            $login_action = ucfirst($login_action);
                        }

                        $role = $user_role[0];
						$formatted_data[] = array(
							'id'                     => $user->ID,
							'user'                   => ucfirst( $user->user_login ),
							'rsssl_two_fa_providers' => $login_action,
							'user_role'              => ucfirst( $role ),
                            'status_for_user'        => ucfirst($user_status),
						);
					}

					$formatted_data = array_values( $formatted_data );
					// Define the callback function for array_filter.
					$filter_callback = static function ( $item ) use ( $data_parameters ) {
						if ( 'all' !== $data_parameters->filter_value ) {
							return ucfirst( $data_parameters->filter_value ) === $item['status_for_user'];
						}
						return $item;
					};

					// Use array_filter to filter the array.
					$formatted_data = array_filter( $formatted_data, $filter_callback );

					$new_response = array(
						'request_success' => true,
						'data'            => array_values( $formatted_data ),
						'args'            => $args,
						'totalRecords'    => count( $total_data ),
					);
					break;

				case 'two_fa_reset_user':
					// if the user has been disabled, it needs to reset the two-factor authentication.
					$user = get_user_by( 'id', $data['id'] );

					if ( $user ) {
						// Delete all 2fa related user meta.
						Rsssl_Two_Fa_Status::delete_two_fa_meta( $user );
						// Set the rsssl_two_fa_last_login to now, so the user will be forced to use 2fa.
						update_user_meta( $user->ID, 'rsssl_two_fa_last_login', gmdate( 'Y-m-d H:i:s' ) );
					}
					if ( ! $user ) {
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
	 * @param int    $user_id The user ID.
	 *
	 * @return string[]
	 */
	private function check_status_and_return( string $method, int $user_id ): ?array {
		$status = Rsssl_Two_Factor_Settings::get_user_status( $method, $user_id );
		if ( in_array( $status, array( 'active', 'open', 'disabled' ), true ) ) {
			return array( $method, $status, true );
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
	public function get_status_by_method( int $user_id ): array {
		$user_id = absint( $user_id );
        if(defined('rsssl_pro') && rsssl_pro ) {
            $result  = $this->get_status_for_method( 'totp', $user_id );
        }

        if(!isset($result)) {
            $result = $this->get_status_for_method( 'email', $user_id );
        } else {
            if ( $result[0] === 'empty' || 'disabled' === $result[1] ) {
                $result = $this->get_status_for_method( 'email', $user_id );
            }

        }

		if ( empty( $result ) || 'disabled' === $result[1] ) {
			$result = array( 'disabled', 'disabled' );
		}

		if ( empty( $result ) ) {
			$enabled_roles  = Rsssl_Two_Factor_Settings::get_enabled_roles( $user_id ) ?? array();
			$enabled_method = Rsssl_Two_Factor_Settings::get_enabled_method( $user_id );

			$result = empty( $enabled_roles )
				? array( $enabled_method, 'disabled' )
				: array( $enabled_method, 'open' );
		}
		return $result;
	}

	/**
	 * Get the status for a given method and user ID.
	 *
	 * @param string $method The method to get the status for.
	 * @param int    $user_id The user ID to get the status for.
	 *
	 * @return array|null The status for the given method and user ID, or null if not found.
	 */
	public function get_status_for_method( string $method, int $user_id ): ?array {
		$role_status = Rsssl_Two_Factor_Settings::get_role_status( $method, $user_id );
		$user_status = Rsssl_Two_Factor_Settings::get_user_status( $method, $user_id );

		if ( 'empty' !== $role_status && 'open' === $user_status ) {
			$result = $this->check_status_and_return( $method, $user_id );
			if ( 'active' === $user_status ) {
				return $result;
			}
		}
		return array( $role_status, $user_status );
	}
}
