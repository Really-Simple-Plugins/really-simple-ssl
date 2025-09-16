<?php
/**
 * This file contains the Rsssl_Two_Factor_Admin class.
 *
 * The Rsssl_Two_Factor_Admin class is responsible for handling the administrative
 * aspects of the two-factor authentication feature in the Really Simple SSL plugin.
 * It includes two_fa_provider for displaying the two-factor authentication settings in the
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

use RSSSL\Security\WordPress\Two_Fa\Controllers\Rsssl_Two_Fa_User_Controller;
use RSSSL\Security\WordPress\Two_Fa\Models\Rsssl_Two_FA_Data_Parameters;
use RSSSL\Security\WordPress\Two_Fa\Models\Rsssl_Two_FA_user;
use RSSSL\Security\WordPress\Two_Fa\Repositories\Rsssl_Two_Fa_User_Repository;
use RSSSL\Security\WordPress\Two_Fa\Services\Rsssl_Two_Fa_Forced_Role_Service;
use RSSSL\Security\WordPress\Two_Fa\Services\Rsssl_Callback_Queue;

use RSSSL\Pro\Security\WordPress\Passkey\Models\Rsssl_Webauthn;
use WP_User;

/**
 * The Rsssl_Two_Factor_Admin class is responsible for handling the administrative
 * aspects of the two-factor authentication feature in the Really Simple SSL plugin.
 * It includes two_fa_provider for displaying the two-factor authentication settings in the
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

    private Rsssl_Callback_Queue $queue;

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
        add_filter('rsssl_do_action', [$this, 'two_fa_table'], 10, 3);
        add_filter('rsssl_after_save_field', [$this, 'change_disabled_users_when_forced'], 20, 3);
        add_filter('rsssl_after_save_field', [$this, 'process_added_removed_enabled_roles'], 20, 3);
		add_filter('rsssl_after_save_field', [$this, 'set_passkey_table'], 20, 3);
        $this->queue = new Rsssl_Callback_Queue();
        $this->queue->process_tasks(1);
    }


	/**
	 * Sets the passkey table.
	 */
	public function set_passkey_table(string $field_id, $new_value, $prev_value ): void
	{
		// checking if the field is the passkey enabled field
		if ('enable_passkey_login' === $field_id) {
			// if the passkey is enabled, it needs to set the passkey table.
			if ($new_value) {
				new Rsssl_Webauthn(); // Initialize the Webauthn class. It will install everything needed.
				do_action('rsssl_install_tables');
			}
			//TODO think of what needs to be done when the passkey is disabled.
		}
	}

    /**
     * Change the disabled status of users when forced.
     *
     * @param string $field_id  The ID of the field being changed.
     * @param mixed  $new_value The new value of the field.
     * @param array  $prev_value The previous value of the field.
     * @return void
     */
    public function change_disabled_users_when_forced( string $field_id, $new_value, $prev_value = [] ): void {
        if ( 'two_fa_forced_roles' !== $field_id
            || empty($new_value)
        ) {
            return;
        }

        //making sure that the new value is an array as well as the old value
        if (!is_array($new_value)) {
            $new_value = [];
        }

        if (!is_array($prev_value)) {
            $prev_value = [];
        }

        $changedRoles = Rsssl_Two_Fa_Forced_Role_Service::getForForcedRolesChange($prev_value, $new_value);

        // If no roles have changed, return early.
        if(empty($changedRoles)) {
            return;
        }

        // Set up initial batch parameters.
        $batch_size = 500;
        $offset     = 0;
        $params = new Rsssl_Two_FA_Data_Parameters([
            'filter_column' => 'user_role',
            'filter_value'  => 'all',
            'number'        => $batch_size,
            'offset'        => $offset,
        ]);
        // Add the first processing task to the queue.
        $this->queue->add_task([$this, 'process_users_batch'], [ $changedRoles, $params, $batch_size, $offset, 'open' ]);
        $this->queue->add_task([$this, 'process_users_batch'], [ $changedRoles, $params, $batch_size, $offset, 'disabled']);
    }

    /**
     * Process a batch of forced two-factor users with disabled status.
     *
     * @return void
     */
    public function process_users_batch(array $changedRoles, Rsssl_Two_FA_Data_Parameters $params, int $batch_size, int $offset, string $status): void {
        $collection = (new Rsssl_Two_Fa_Forced_Role_Service($params))->processBatch($changedRoles, $status);

        foreach ($collection->getUsers() as $user) {
            $statusForUser = $user->getStatus();
            if (in_array($statusForUser, ['open', 'disabled','expired'])) {
                // Check if the user has a role that has been changed.
                $rolesForUser = $user->getRoles();
                $matchingRoles = array_intersect($rolesForUser, $changedRoles);
                if (!empty($matchingRoles)) {
                    // Reset the user's status.
                    $user->resetStatus();
                    //temp meta key for testing
                    update_user_meta($user->getId(), 'rsssl_two_fa_status_reset', true);
                }
            }
        }
        // Check if there are more users to process.
        // The collection contains the total number of records (set in the repository).
        $total = $collection->getTotalRecords();
        if (($params->offset + $params->number) < $total) {
            // Update the offset for the next batch.
            $newOffset = $offset + $batch_size;
            // Queue the next task with the correct new offset.
            $this->queue->add_task([$this, 'process_users_batch'], [$changedRoles, $params, $batch_size, $newOffset, $status]);
        }
    }

    public function process_added_removed_enabled_roles(string $field_id, $new_value, $prev_value = [])
    {
        if ( 'two_fa_enabled_roles_email' !== $field_id
            || empty($new_value)
        ) {
            return;
        }

        if ( 'two_fa_enabled_roles_totp' !== $field_id
            || empty($new_value)
        ) {
            return;
        }
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
	 * @param string $title The title of the notice.
	 * @param string $msg The message of the notice.
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
				Rsssl_Two_Fa_Status::delete_two_fa_meta( $user->ID );
				// Set the last login to now, so the user will be forced to use 2fa.
				update_user_meta( $user->ID, 'rsssl_two_fa_last_login', gmdate( 'Y-m-d H:i:s' ) );
			}
		}
		return $response;
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
            switch ($action) {
                case 'two_fa_table':
                    $data_parameters = new Rsssl_Two_FA_Data_Parameters($data);
                    $userRepository = new Rsssl_Two_Fa_User_Repository();
                    // Create the controller.
                    return (new Rsssl_Two_Fa_User_Controller($userRepository))->getUsersForAdminOverview($data_parameters);
                case 'two_fa_reset_user':
                    // if the user has been disabled, it needs to reset the two-factor authentication.
                    $user = get_user_by('id', $data['id']);

                    if ($user) {
                        // Delete all 2fa related user meta.
                        Rsssl_Two_Fa_Status::delete_two_fa_meta($user->ID);
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
}
