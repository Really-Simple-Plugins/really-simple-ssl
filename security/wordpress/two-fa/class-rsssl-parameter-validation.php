<?php
/**
 * Holds the request parameters for a specific action.
 * This class holds the request parameters for a specific action.
 * It is used to store the parameters and pass them to the functions.
 *
 * @package REALLY_SIMPLE_SSL
 */

namespace RSSSL\Security\WordPress\Two_Fa;

use WP_User;

/**
 * Holds the request parameters for a specific action.
 * This class holds the request parameters for a specific action.
 * It is used to store the parameters and pass them to the functions.
 *
 * @package REALLY_SIMPLE_SSL
 */
class Rsssl_Parameter_Validation
{

    /**
     * Validates a user ID.
     *
     * @param int $user_id The user ID to be validated.
     *
     * @return void
     */
    public static function validate_user_id(int $user_id): void
    {
        if (!is_numeric($user_id)) {
            // Create an error message for the profile page.
            add_settings_error(
                'two-factor-authentication',
                'rsssl-two-factor-authentication-error',
                __('The user ID is not valid.', 'really-simple-ssl')
            );
        }
    }

    /**
     * Validates post data.
     *
     * @param array $post_data The post data to validate.
     *
     * @return void
     */
    public static function validate_post_data(array $post_data): void
    {
        if (!isset($post_data['preferred_method'])) {
            // Create an error message for the profile page.
            add_settings_error(
                'two-factor-authentication',
                'rsssl-two-factor-authentication-error',
                __('The preferred method is not set.', 'really-simple-ssl')
            );
        }
    }

    /**
     * Validate user object.
     *
     * @param mixed $user The user object to validate.
     *
     * @return void
     */
    public static function validate_user($user): void
    {
        if (!$user instanceof WP_User) {
            // Create an error message for the profile page.
            add_settings_error(
                'two-factor-authentication',
                'rsssl-two-factor-authentication-error',
                __('The user object is not valid.', 'really-simple-ssl')
            );
        }
    }

    /**
     * Validates the selected provider.
     *
     * @param string $selected_provider The selected provider to validate.
     *
     * @return void
     */
    public static function validate_selected_provider(string $selected_provider): void
    {
        if (!in_array($selected_provider, array('totp', 'email', 'none'), true)) {
            // Create an error message for the profile page.
            add_settings_error(
                'two-factor-authentication',
                'rsssl-two-factor-authentication-error',
                __('The selected provider is not valid.', 'really-simple-ssl')
            );
        }
    }

    /**
     * Validates an authentication code.
     *
     * @param mixed $auth_code The authentication code to validate.
     *
     * @return void
     */
    public static function validate_auth_code($auth_code): void
    {
        if (!is_numeric($auth_code)) {
            // Create an error message for the profile page.
            add_settings_error(
                'two-factor-authentication',
                'rsssl-two-factor-authentication-error',
                __('The authentication code is not valid.', 'really-simple-ssl')
            );
        }
    }

    /**
     * Validates a given key.
     *
     * @param mixed $key The key to validate.
     *
     * @return void
     */
    public static function validate_key($key): void
    {
        if (!is_string($key)) {
            // Create an error message for the profile page.
            add_settings_error(
                'two-factor-authentication',
                'rsssl-two-factor-authentication-error',
                __('The key is not valid.', 'really-simple-ssl')
            );
        }
    }

    /**
     * Cache the current errors for a user in a transient.
     *
     * @param int $user_id The ID of the user.
     *
     * @return void
     */
    public static function cache_errors(int $user_id): void
    {
        // Put the current errors in a transient.
        set_transient('rsssl_two_factor_auth_error_' . $user_id, get_settings_errors(), 60);
    }

    /**
     * Retrieves cached errors for a specific user.
     *
     * @param int $user_id The ID of the user to retrieve the errors for.
     *
     * @return mixed|null An array of errors if found, null otherwise.
     */
    public static function get_cached_errors(int $user_id)
    {
        // Get the errors from the transient.
        $errors = get_transient('rsssl_two_factor_auth_error_' . $user_id);
        // Delete the transient.
        delete_transient('rsssl_two_factor_auth_error_' . $user_id);
        return $errors;
    }

    /**
     * Deletes cached errors for a specific user.
     *
     * @param int $user_id The ID of the user to delete the errors for.
     *
     * @return void
     */
    public static function delete_cached_errors(int $user_id): void
    {
        delete_transient('rsssl_two_factor_auth_error_' . $user_id);
    }
}
