<?php
/**
 * This package is based on the WordPress feature plugin https://wordpress.org/plugins/two-factor/
 *
 * Class for creating two-factor authorization.
 *
 * @since 7.0.6
 * @noinspection OffsetOperationsInspection
 * @noinspection UnknownInspectionInspection
 * @package RSSSL\Pro\Security\WordPress\Two_Fa
 */

namespace RSSSL\Security\WordPress\Two_Fa;

use Exception;
use RSSSL\Security\WordPress\Two_Fa\Repositories\Rsssl_Two_Fa_User_Repository;
use RSSSL\Security\WordPress\Two_Fa\Services\Rsssl_Two_Fa_Reminder_Service;
use RSSSL\Security\WordPress\Two_Fa\Services\Rsssl_Two_Factor_Reset_Service;
use RSSSL\Security\WordPress\Two_Fa\Providers\Rsssl_Provider_Loader;
use RSSSL\Security\WordPress\Two_Fa\Providers\Rsssl_Two_Factor_Provider;
use RSSSL\Security\WordPress\Two_Fa\Providers\Rsssl_Two_Factor_Provider_Interface;
use RSSSL\Security\WordPress\Two_Fa\Traits\Rsssl_Email_Trait;
use WP_Error;
use WP_Session_Tokens;
use WP_User;

/**
 * Class Rsssl_Two_Factor.
 *
 * The Rsssl_Two_Factor class provides methods for managing two-factor authentication for users.
 *
 * @package Rsssl
 */
class Rsssl_Two_Factor
{
    use Rsssl_Email_Trait;

    /**
     * The user meta key to store the last failed timestamp.
     *
     * @type string
     */
    public const RSSSL_USER_RATE_LIMIT_KEY = '_rsssl_two_factor_last_login_failure';

    /**
     * The user meta key to store the number of failed login attempts.
     *
     * @var string
     */
    public const RSSSL_USER_FAILED_LOGIN_ATTEMPTS_KEY = '_rsssl_two_factor_failed_login_attempts';

    /**
     * The user meta key to store whether the password was reset.
     *
     * @var string
     */
    public const RSSSL_USER_PASSWORD_WAS_RESET_KEY = '_rsssl_two_factor_password_was_reset';

    /**
     * URL query parameter used for our custom actions.
     *
     * @var string
     */
    public const RSSSL_USER_SETTINGS_ACTION_QUERY_VAR = 'rsssl_two_factor_action';

    /**
     * Nonce key for user settings.
     *
     * @var string
     */
    public const RSSSL_USER_SETTINGS_ACTION_NONCE_QUERY_ARG = '_rsssl_two_factor_action_nonce';
    public const RSSSL_USER_META_ONBOARDING_COMPLETE = 'rsssl_two_fa_onboarding_complete';


    /**
     * Namespace for plugin rest api endpoints.
     *
     * @var string
     */
    public const REST_NAMESPACE = 'really-simple-security/v1/two-fa/v2';

    /**
     * Keep track of all the password-based authentication sessions that
     * need to invalidated before the second factor authentication.
     *
     * @var array
     */
    private static array $password_auth_tokens = array();

    /**
     * Set up filters and actions.
     *
     * @param object $compat A compatibility layer for plugins.
     *
     * @since 0.1-dev
     */
    public static function add_hooks(object $compat): void
    {
	    if ( ( defined( 'RSSSL_DISABLE_2FA' ) && RSSSL_DISABLE_2FA )
            || ( defined( 'RSSSL_SAFE_MODE' ) && RSSSL_SAFE_MODE )
        ) {
		    if ( rsssl_admin_logged_in() ) {
			    ( new Rsssl_Two_Factor_Admin() );
		    }
		    ( new Rsssl_Two_Factor_On_Board_Api() );
		    if ( is_user_logged_in() ) {
			    (Rsssl_Two_Factor_Profile_Settings::get_instance());
		    }
		    return;
	    }

        /**
         * Runs the fix for the reset error in 9.1.1
         */
	    if (filter_var(get_option('rsssl_reset_fix', false), FILTER_VALIDATE_BOOLEAN)) {
            $repository = new Rsssl_Two_Fa_User_Repository();
            (new Rsssl_Two_Factor_Reset_Service($repository))->resetFix();
	    }

//		add_action( 'login_enqueue_scripts', array( __CLASS__, 'twofa_scripts' ) );
        add_action('init', array(Rsssl_Provider_Loader::class, 'get_providers'));
        add_action('wp_login', array(__CLASS__, 'rsssl_wp_login'), 10, 2);
        add_action('wp_login_errors', array(__CLASS__, 'show_expired_onboarding_error'));
        add_filter('wp_login_errors', array(__CLASS__, 'rsssl_maybe_show_reset_password_notice'));
        add_action('after_password_reset', array(__CLASS__, 'rsssl_clear_password_reset_notice'));
        add_action('login_form_validate_2fa', array(__CLASS__, 'rsssl_login_form_validate_2fa'));
        // Loading the styles.
        add_action('login_enqueue_scripts', array(__CLASS__, 'enqueue_onboarding_styles'));
        if (rsssl_admin_logged_in()) {
            (new Rsssl_Two_Factor_Admin());
        }
	    ( new Rsssl_Two_Factor_On_Board_Api() );
        if(is_user_logged_in()) {
	        Rsssl_Two_Factor_Profile_Settings::get_instance();
        }

        //add_action('rsssl_upgrade',  array(__CLASS__, 'upgrade'));
        self::upgrade();
        // Add the localized script for WP_REST.

        /**
         * Keep track of all the user sessions for which we need to invalidate the
         * authentication cookies set during the initial password check.
         *
         * Is there a better way of doing this?
         */
        add_action('set_auth_cookie', array(__CLASS__, 'rsssl_collect_auth_cookie_tokens'));
        add_action('set_logged_in_cookie', array(__CLASS__, 'rsssl_collect_auth_cookie_tokens'));
        if ( isset( $_GET['rsssl_one_time_login'], $_GET['_wpnonce'] ) ) {
            $nonce = sanitize_text_field(wp_unslash($_GET['_wpnonce']));

            if (wp_verify_nonce($nonce)) {
                add_action('init', array(__CLASS__, 'maybe_skip_auth'));
            }
	        self::maybe_skip_auth();
        }

        add_action('init', array(__CLASS__, 'rsssl_collect_auth_cookie_tokens'));

        // Run only after the core wp_authenticate_username_password() check.
        add_filter('authenticate', array(__CLASS__, 'rsssl_filter_authenticate'));

        // Run as late as possible to prevent other plugins from unintentionally bypassing.
        add_filter('authenticate', array(__CLASS__, 'rsssl_filter_authenticate_block_cookies'), PHP_INT_MAX);
        add_action('admin_init', array(__CLASS__, 'rsssl_enable_dummy_method_for_debug'));
        add_filter('rsssl_two_factor_providers', array(__CLASS__, 'enable_dummy_method_for_debug'));
	    add_action( 'rsssl_daily_cron', array( __CLASS__, 'maybe_send_reminder_email' ) );
        add_action( 'user_register', [__CLASS__, 'set_2fa_activation_date'], 10, 1 );

        $compat->init();
    }

	/**
	 * @return void
	 *
	 * Send a reminder e-mail if Two FA has not been configured within 3 days.
	 */
	public static function maybe_send_reminder_email():void {
        $forcedRoles = rsssl_get_option('two_fa_forced_roles', []);
        if(empty($forcedRoles)) {
            return;
        }
        (new Rsssl_Two_Fa_Reminder_Service())->maybeSendReminderEmails($forcedRoles);
	}

    /**
     * Simple Date setter for Two Factor Forced roles.
     * @param $user_id
     * @return void
     */
    public static function set_2fa_activation_date($user_id): void {
        // Get the user data; if not found, return early.
        $user_data = get_userdata($user_id);
        if (!$user_data) {
            return;
        }
        $user_roles = $user_data->roles;

        // Ensure forced roles is an array (empty if not set).
        $forcedRoles = rsssl_get_option('two_fa_forced_roles') ?: [];

        // If there is no intersection between forced roles and user's roles, do nothing.
        if (!array_intersect($forcedRoles, $user_roles)) {
            return;
        }

        // TODO: I really regret the meta_key name here. It should be rsssl_two_fa_activation_date. Need to fix this in the future.
        update_user_meta($user_id, 'rsssl_two_fa_last_login', gmdate('Y-m-d H:i:s'));
    }

    /**
     * Upgrade the two-factor login configuration.
     *
     * This method updates the configuration of two-factor login if necessary.
     * It checks if the login protection is enabled, if the plugin has been upgraded,
     * and if the enabled roles for email and TOTP need to be updated.
     *
     * @return void
     */
    public static function upgrade(): void
    {
        if (rsssl_get_option('login_protection_enabled') && get_option('rsssl_two_fa_upgrade', false) === false) {
            // The way roles configuration was has now been changed. This means the forced roles and enabled roles need to change.
            $forced_roles = rsssl_get_option('two_fa_forced_roles');
            $optional_roles = rsssl_get_option('two_fa_optional_roles');

            $forced_roles = ($forced_roles !== false) ? $forced_roles : [];
            $optional_roles = ($optional_roles !== false) ? $optional_roles : [];

            // Merge the forced and optional roles into one array with unique values.
            $enabled_roles = array_unique(array_merge($forced_roles, $optional_roles));

            if (empty($optional_roles)) {
                // no roles were set so ending the upgrade.
                return;
            }

            if (function_exists('rsssl_update_option')) {
                // Update the enabled roles for only email.
                rsssl_update_option('two_fa_enabled_roles_email', $enabled_roles);
                rsssl_update_option('two_fa_enabled_roles_totp', ['administrator']);
                // update the forced roles.
                rsssl_update_option('two_fa_forced_roles', $forced_roles);
            }

            // fetching the users that have active 2FA enabled.
            $users = get_users(array('meta_key' => 'rsssl_two_fa_status_email', 'meta_value' => 'active'));
            foreach ($users as $user) {
                Rsssl_Two_Fa_Status::set_active_provider($user->ID, 'email');
            }
            update_option('rsssl_two_fa_upgrade', rsssl_version, false);
        }
    }

    /**
     * Enqueue the two-factor authentication scripts.
     *
     * @return void
     *
     * Allow 2FA bypass if status is open.
     */
    public static function maybe_skip_auth(): void
    {

        if (isset($_GET['rsssl_one_time_login'], $_GET['token'], $_GET['_wpnonce'])) {

            // Unslash and sanitize.
            $rsssl_one_time_login = sanitize_text_field(wp_unslash($_GET['rsssl_one_time_login']));
            $user_id = (int)Rsssl_Two_Factor_Settings::deobfuscate_user_id($rsssl_one_time_login);
            $user = get_user_by('id', $user_id);

            // Verify the nonce.
            $nonce = sanitize_text_field(wp_unslash($_GET['_wpnonce']));
            if (!wp_verify_nonce($nonce, 'one_time_login_' . $user_id)) {
                wp_safe_redirect(wp_login_url() . '?login_error=nonce_invalid');
                exit;
            }

            // Retrieve the stored token from the transient.
            $stored_token = get_transient('skip_two_fa_token_' . $user_id);

            // Check if the token is valid and not expired.
            $token = sanitize_text_field(wp_unslash($_GET['token']));
            if ($user && $stored_token && hash_equals($stored_token, $token)) {

                // Delete the transient to invalidate the token.
                delete_transient('skip_two_fa_token_' . $user_id);

                $status = get_user_meta($user->ID, 'rsssl_two_fa_status_email', true);

                // Only allow skipping for users which have 2FA value open.
                if (isset($_GET['rsssl_two_fa_disable']) && 'open' === $status) {
                    update_user_meta($user_id, 'rsssl_two_fa_status_email', 'disabled');
                }

                if ('open' === Rsssl_Two_Factor_Settings::get_user_status('email', $user_id)) {
                    update_user_meta($user_id, 'rsssl_two_fa_status_email', 'active');
                    update_user_meta($user_id, 'rsssl_two_fa_status_totp', 'disabled');

                }
	            delete_user_meta( $user_id, '_rsssl_factor_email_token' );
                delete_user_meta( $user_id, '_rsssl_two_factor_backup_codes' );
                wp_set_auth_cookie($user_id);
                wp_safe_redirect(admin_url());
                exit;
            }

            // The token is invalid or expired.
            // Redirect to the login page with an error message or handle it as needed.
            wp_safe_redirect(wp_login_url() . '?login_error=token_invalid');
            exit;
        }
    }

    /**
     * Enable the dummy method only during debugging.
     *
     * @param array $methods List of enabled methods.
     *
     * @return array
     */
    public static function enable_dummy_method_for_debug(array $methods): array
    {
        if (!self::is_wp_debug()) {
            unset($methods['Two_Factor_Dummy']);
        }

        return $methods;
    }

    /**
     * Check if the debug mode is enabled.
     *
     * @return boolean
     */
    protected static function is_wp_debug(): bool
    {
        return (defined('WP_DEBUG') && WP_DEBUG);
    }

    /**
     * Check if a user action is valid.
     *
     * @param integer $user_id User ID.
     * @param string $action User action ID.
     *
     * @return boolean
     */
    public static function is_valid_user_action(int $user_id, string $action): bool
    {
        $request_nonce = isset($_REQUEST[self::RSSSL_USER_SETTINGS_ACTION_NONCE_QUERY_ARG]) ? sanitize_text_field(wp_unslash($_REQUEST[self::RSSSL_USER_SETTINGS_ACTION_NONCE_QUERY_ARG])) : '';

        if (!$user_id || !$action || !$request_nonce) {
            return false;
        }

        return wp_verify_nonce(
            $request_nonce,
            sprintf('%d-%s', $user_id, $action)
        );
    }

    /**
     * Get the ID of the user being edited.
     *
     * @return integer
     */
    public static function current_user_being_edited(): int
    {
        // Try to resolve the user ID from the request first.
        if (!empty($_REQUEST['rsssl_user_id']) && !empty($_REQUEST['rsssl-action-nonce'])) {
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['rsssl-action-nonce'])), 'rsssl-user-action')) {
                wp_die('Invalid nonce');
            }

            $user_id = (int)$_REQUEST['rsssl_user_id'];
            if (current_user_can('edit_user', $user_id)) {
                return $user_id;
            }
        }
        return get_current_user_id();
    }

    /**
     * Trigger our custom update action if a valid
     * action request is detected and passes the nonce check.
     *
     * @return void
     */
    public static function rsssl_enable_dummy_method_for_debug(): void
    {
        $nonce = isset($_POST['nonce_field']) ? sanitize_text_field(wp_unslash($_POST['nonce_field'])) : '';
        // Verify the nonce.
        if (!wp_verify_nonce($nonce, 'rsssl_user_action')) {
            return;
        }
        $action = isset($_REQUEST[self::RSSSL_USER_SETTINGS_ACTION_QUERY_VAR]) ? sanitize_text_field(wp_unslash($_REQUEST[self::RSSSL_USER_SETTINGS_ACTION_QUERY_VAR])) : '';
        $user_id = self::current_user_being_edited();

        if (self::is_valid_user_action($user_id, $action)) {
            /**
             * This action is triggered when a valid Two Factor settings
             * action is detected, and it passes the nonce validation.
             *
             * @param integer $user_id User ID.
             * @param string $action Settings action.
             */
            do_action('rsssl_two_factor_user_settings_action', $user_id, $action);
        }
    }

    /**
     * Keep track of all the authentication cookies that need to be
     * invalidated before the second factor authentication.
     *
     * @param string $cookie Cookie string.
     *
     * @return void
     */
    public static function rsssl_collect_auth_cookie_tokens(string $cookie): void
    {
        $parsed = wp_parse_auth_cookie($cookie);

        if (!empty($parsed['token'])) {
            self::$password_auth_tokens[] = $parsed['token'];
        }
    }

    /**
     * Get all Two-Factor Auth providers that are both enabled and configured for the specified|current user.
     *
     * @param WP_User $user Optional. User ID, or WP_User object of the user. Defaults to current user.
     *
     * @return array
     */
    public static function get_available_providers_for_user(WP_User $user): array
    {
	    $loader = Rsssl_Provider_Loader::get_loader();
        return $loader::available_providers();
    }

    /**
     * Gets the Two-Factor Auth provider for the specified|current user.
     *
     * @param WP_User $user Optional. User ID, or WP_User object of the user. Defaults to current user.
     *
     * @return string
     * @since 0.1-dev
     */
    public static function get_primary_provider_for_user(WP_User $user): string
    {
        $loader = Rsssl_Provider_Loader::get_loader();
        $available_providers = $loader::get_configured_providers_for_user($user);
        // If there's only one available provider, force that to be the primary.
        if (empty($available_providers)) {
            return '';
        }

        if (1 === count($available_providers)) {
            $provider = key($available_providers);
        } else {
            $provider = Rsssl_Provider_Loader::get_user_enabled_providers($user);
            // Check if already a provider is active.

            // If the provider specified isn't enabled, just grab the first one that is based on the Weight.
            $best_valued_provider = 'totp';
            if (isset($available_providers[$best_valued_provider]) && $available_providers[$best_valued_provider]::is_enabled($user)) {
                $provider = $best_valued_provider;
            } else {
                $provider = key($available_providers);
            }
        }

        return get_class($available_providers[$provider]) ??  '';
    }

    /**
     * Quick boolean check for whether a given user is using two-step.
     * TODO: No longer needed?
     *
     * @param WP_User $user Optional. User ID, or WP_User object of the user. Defaults to current user.
     *
     * @return bool
     * @since 0.1-dev
     */
    public static function is_user_using_two_factor(WP_User $user): bool
    {
        $provider = self::get_primary_provider_for_user($user);

        $enabled_providers_meta = Rsssl_Provider_Loader::get_user_enabled_providers($user);
        // Initialize as empty arrays if they are empty.
        $two_fa_forced_roles = rsssl_get_option('two_fa_forced_roles');
        $two_fa_optional_roles = rsssl_get_option('two_fa_enabled_roles_email');
        $two_fa_optional_roles_totp = rsssl_get_option('two_fa_enabled_roles_totp');

        //ensure an array for all.
        if (!is_array($two_fa_forced_roles)) {
	        $two_fa_forced_roles = [];
        }
        if (!is_array($two_fa_optional_roles)) {
	        $two_fa_optional_roles = [];
        }
        if (!is_array($two_fa_optional_roles_totp)) {
	        $two_fa_optional_roles_totp = [];
        }
        $two_fa_optional_roles = array_unique(array_merge($two_fa_optional_roles, $two_fa_optional_roles_totp));

        foreach ($enabled_providers_meta as $enabled_provider) {
            $status = $enabled_provider::get_status($user);
            if ( ( 'disabled' === $status ) && is_object( $provider ) && get_class( $provider ) === $enabled_provider ) {
                $provider = [];
            }
	        if ('active' === $status ) {
		        return true;
	        }

	        if ('open' === $status) {
		        return true;
	        }
        }

        foreach ($user->roles as $role) {
            // If not forced, and not optional, or disabled, or provider not enabled.
            if (!in_array($role, $two_fa_forced_roles, true)
                && !in_array($role, $two_fa_optional_roles, true)
            ) {
                // Skip 2FA.
                return false;
            }
        }

        return !empty($provider);
    }

    /**
     * Show an expired onboarding error message.
     *
     * @param WP_Error $errors Error object to add the error to.
     *
     * @return WP_Error The updated error object.
     */
    public static function show_expired_onboarding_error(WP_Error $errors): WP_Error
    {
        if ( isset( $_GET['nonce'], $_GET['errors'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'rsssl_expired' ) && $_GET['errors'] === 'expired' ) {
            $errors->add('expired', __('Your 2FA grace period expired. Please contact your site administrator to regain access and to configure 2FA.', 'really-simple-ssl'));
        }
        return $errors;
    }

    /**
     * Handle the browser-based login.
     *
     * @param string $user_login Username.
     * @param WP_User $user WP_User object of the logged-in user.
     *
     * @throws Exception If the onboarding process fails.
     * @since 0.1-dev
     */
    public static function rsssl_wp_login(string $user_login, WP_User $user): void
    {
        switch (Rsssl_Two_Factor_Settings::get_login_action($user->ID)) {
            case 'onboarding':
                wp_clear_auth_cookie();
                self::is_onboarding_complete($user);
                exit;
            case 'expired':
                // Destroy the current session for the user.
                self::destroy_current_session_for_user($user);
                wp_clear_auth_cookie();
                self::display_expired_onboarding_error();
                exit;
            case 'totp':
            case 'email':
            case 'passkey':
                wp_clear_auth_cookie();
                self::show_two_factor_login($user);
                exit;
            case 'login':
            default:
                break;
        }
    }

    /**
     * Destroy the known password-based authentication sessions for the current user.
     *
     * Is there a better way of finding the current session token without
     * having access to the authentication cookies which are just being set
     * on the first password-based authentication request.
     *
     * @param WP_User $user User object.
     *
     * @return void
     */
    public static function destroy_current_session_for_user(WP_User $user): void
    {
        $session_manager = WP_Session_Tokens::get_instance($user->ID);

        foreach (self::$password_auth_tokens as $auth_token) {
            $session_manager->destroy($auth_token);
        }
    }

    /**
     * Prevent login through XML-RPC and REST API for users with at least one
     * two-factor method enabled.
     *
     * @param WP_User|WP_Error $user Valid WP_User only if the previous filters
     *                                have verified and confirmed the
     *                                authentication credentials.
     *
     * @return WP_User|WP_Error
     */
    public static function rsssl_filter_authenticate($user)
    {
        if ($user instanceof WP_User && self::is_api_request() && self::is_user_using_two_factor($user) && !self::is_user_api_login_enabled($user->ID)) {
            return new WP_Error(
                'invalid_application_credentials',
                __('API login for user disabled.', 'really-simple-ssl')
            );
        }

        return $user;
    }

    /**
     * Prevent login cookies being set on login for Two Factor users.
     *
     * This makes it so that Core never sends the auth cookies. `login_form_validate_2fa()` will send them manually once the 2nd factor has been verified.
     *
     * @param WP_User|WP_Error $user Valid WP_User only if the previous filters
     *                                have verified and confirmed the
     *                                authentication credentials.
     *
     * @return WP_User|WP_Error
     */
    public static function rsssl_filter_authenticate_block_cookies($user)
    {
        /*
         * NOTE: The `login_init` action is checked for here to ensure we're within the regular login flow,
         * rather than through an unsupported 3rd-party login process which this plugin doesn't support.
         */
        if ($user instanceof WP_User && self::is_user_using_two_factor($user) && did_action('login_init')) {
            add_filter('send_auth_cookies', '__return_false', PHP_INT_MAX);
        }

        return $user;
    }

    /**
     * If the current user can log in via API requests such as XML-RPC and REST.
     *
     * @param integer $user_id User ID.
     *
     * @return boolean
     */
    public static function is_user_api_login_enabled(int $user_id): bool
    {
        return (bool)apply_filters('rsssl_two_factor_user_api_login_enable', false, $user_id);
    }

    /**
     * Is the current request an XML-RPC or REST request.
     *
     * @return boolean
     */
    public static function is_api_request(): bool
    {
        if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
            return true;
        }

        if (defined('REST_REQUEST') && REST_REQUEST) {
            return true;
        }

        return false;
    }

    /**
     * Display the login form.
     *
     * @param WP_User $user WP_User object of the logged-in user.
     *
     * @throws Exception If the login nonce creation fails.
     * @since 0.1-dev
     */
    public static function show_two_factor_login(WP_User $user): void
    {
	    $redirect_to = isset($_REQUEST['redirect_to']) ? wp_validate_redirect(wp_unslash($_REQUEST['redirect_to']), admin_url()) : admin_url();
        $provider = Rsssl_Two_Factor_Settings::get_login_action($user->ID);
		$login_nonce = Rsssl_Two_Fa_Authentication::create_login_nonce($user->ID)['rsssl_key'];

        self::login_html($user, $login_nonce ,$redirect_to);
    }

    /**
     * Displays a message informing the user that their account has had failed login attempts.
     *
     * @param WP_User $user WP_User object of the logged-in user.
     */
    public static function maybe_show_last_login_failure_notice(WP_User $user): void
    {
        $last_failed_two_factor_login = (int)get_user_meta($user->ID, self::RSSSL_USER_RATE_LIMIT_KEY, true);
        $failed_login_count = (int)get_user_meta(
            $user->ID,
            self::RSSSL_USER_FAILED_LOGIN_ATTEMPTS_KEY,
            true
        );

        if ($last_failed_two_factor_login) {
            echo '<div id="login_notice" class="message"><strong>';
            // translators: %1$s is the number of failed login attempts, %2$s is the time since the last failed login.
            printf(
                esc_html(
                    _n(
                        'Warning: There has been %1$s failed login attempt on your account without providing a valid two-factor token. The last failed login occurred %2$s ago. If this wasn\'t you, you should reset your password.',
                        'Warning: %1$s failed login attempts have been detected on your account without providing a valid two-factor token. The last failed login occurred %2$s ago. If this wasn\'t you, you should reset your password.',
                        $failed_login_count,
                        'really-simple-ssl'
                    )
                ),
                esc_html(number_format_i18n($failed_login_count)),
                esc_html(human_time_diff($last_failed_two_factor_login, time()))
            );
            echo '</strong></div>';
        }
    }

    /**
     * Show the password reset notice if the user's password was reset.
     *
     * They were also sent an email notification in `send_password_reset_email()`, but email sent from a typical
     * web server is not reliable enough to trust completely.
     *
     * @param WP_Error $errors The error object.
     *
     * @return WP_Error
     */
    public static function rsssl_maybe_show_reset_password_notice(WP_Error $errors): WP_Error
    {
        if ('incorrect_password' !== $errors->get_error_code()) {
            return $errors;
        }

        if (!isset($_POST['log'])) {
            return $errors;
        }

        $user_name = sanitize_user(wp_unslash($_POST['log']));
        $attempted_user = get_user_by('login', $user_name);
        if ( $user_name && ! $attempted_user && strpos( $user_name, '@') !== false ) {
            $attempted_user = get_user_by('email', $user_name);
        }

        if (!$attempted_user) {
            return $errors;
        }

        $password_was_reset = get_user_meta($attempted_user->ID, self::RSSSL_USER_PASSWORD_WAS_RESET_KEY, true);

        if (!$password_was_reset) {
            return $errors;
        }

        $errors->remove('incorrect_password');
        $errors->add(
            'rsssl_two_factor_password_reset',
            sprintf(
            /* translators: %s: URL to reset password */
                __(
                    'Your password was reset because of too many failed Two Factor attempts. You will need to <a href="%s">create a new password</a> to regain access. Please check your email for more information.',
                    'really-simple-ssl'
                ),
                esc_url(add_query_arg('action', 'lostpassword', rsssl_wp_login_url()))
            )
        );

        return $errors;
    }

    /**
     * Clear the password reset notice after the user resets their password.
     *
     * @param WP_User $user WP_User object of the logged-in user.
     */
    public static function rsssl_clear_password_reset_notice(WP_User $user): void
    {
        delete_user_meta($user->ID, self::RSSSL_USER_PASSWORD_WAS_RESET_KEY);
    }

    /**
     * Generates the html form for the second step of the authentication process.
     *
     * @param WP_User $user WP_User object of the logged-in user.
     * @param string $login_nonce A string nonce stored in usermeta.
     * @param string $redirect_to The URL to which the user would like to be redirected.
     * @param string $error_msg Optional. Login error message.
     * @param string|object $provider An override to the provider.
     *
     * @throws Exception If the login nonce creation fails.
     * @since 0.1-dev
     */
    public static function login_html(
        WP_User $user,
        string  $login_nonce,
        string  $redirect_to,
        string  $error_msg = '',
                $provider = null
    ): void
    {

        if (empty($provider)) {
            $provider = self::get_primary_provider_for_user($user);
        } elseif (is_string($provider) && method_exists($provider, 'get_instance')) {
            $provider = call_user_func(array($provider, 'get_instance'));
        }

        if (!$provider) {
            return;
        }


        $provider_class = $provider::get_instance();


        $available_providers = self::get_available_providers_for_user($user);
//        $backup_providers = array_diff_key($available_providers, array($provider => null));
        $interim_login = isset($_REQUEST['interim-login']); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        $rememberme = (int)self::rememberme();

        if (!function_exists('login_header')) {
            // We really should migrate login_header() out of `wp-login.php` so it can be called from an includes file.
            include_once __DIR__ . '/function-login-header.php';
        }

        // Enqueue two-fa JavaScript assets
        $uri = trailingslashit(rsssl_url) . 'assets/features/two-fa/assets.min.js';
        $uri_file = trailingslashit(rsssl_path) . 'assets/features/two-fa/assets.min.js';
        add_filter('wp_script_attributes', [self::class, 'handle_script_attributes'], 10, 2);
        wp_enqueue_script('rsssl-frontend-settings', $uri, array(), filemtime($uri_file), true);

        wp_localize_script('rsssl-frontend-settings', 'rsssl_validate', array(
            'nonce' => wp_create_nonce('wp_rest'),
            'root' => esc_url_raw(rest_url(self::REST_NAMESPACE)),
            'login_nonce' => $login_nonce,
            'redirect_to' => $redirect_to,
            'user_id' => $user->ID,
            'origin' => 'validation',
            'translatables' => apply_filters('rsssl_two_factor_translatables', []),
        ));

        // Load the login template.
        rsssl_load_template(
            'login.php',
            compact(
                'login_nonce',
                'redirect_to',
                'error_msg',
                'provider',
//                'backup_providers',
                'interim_login',
                'rememberme',
                'provider_class',
                'user'
            ),
            rsssl_path . 'assets/templates/two_fa/'
        );

        if (!function_exists('login_footer')) {
            include_once __DIR__ . '/function-login-footer.php';
        }
        login_footer();
    }

    /**
     * Generate the two-factor login form URL.
     *
     * @param array $params List of query argument pairs to add to the URL.
     * @param string $scheme URL scheme context.
     *
     * @return string
     */
    public static function login_url(array $params = array(), string $scheme = 'login'): string
    {
        $params = urlencode_deep($params);
        return add_query_arg($params, site_url('wp-login.php', $scheme));
    }

    /**
     * Determine the minimum wait between two factor attempts for a user.
     *
     * This implements an increasing backoff, requiring an attacker to wait longer
     * each time to attempt to brute-force the login.
     *
     * @param WP_User $user The user being operated upon.
     *
     * @return int Time delay in seconds between login attempts.
     */
    public static function get_user_time_delay(WP_User $user): int
    {
        /**
         * Filter the minimum time duration between two factor attempts.
         *
         * @param int $rate_limit The number of seconds between two factor attempts.
         */
        $rate_limit = apply_filters('rsssl_two_factor_rate_limit', 1);

        $user_failed_logins = get_user_meta($user->ID, self::RSSSL_USER_FAILED_LOGIN_ATTEMPTS_KEY, true);
        if ($user_failed_logins) {
            $rate_limit = (2 ** $user_failed_logins) * $rate_limit;

            /**
             * Filter the maximum time duration a user may be locked out from retrying two-factor authentications.
             *
             * @param int $max_rate_limit The maximum number of seconds a user might be locked out for. Default 15 minutes.
             */
            $max_rate_limit = apply_filters('rsssl_two_factor_max_rate_limit', 15 * MINUTE_IN_SECONDS);

            $rate_limit = min($max_rate_limit, $rate_limit);
        }

        /**
         * Filters the per-user time duration between two-factor login attempts.
         *
         * @param int $rate_limit The number of seconds between two factor attempts.
         * @param WP_User $user The user attempting to log in.
         */
        return apply_filters('rsssl_two_factor_user_rate_limit', $rate_limit, $user);
    }

    /**
     * Determine if a time delay between user two-factor login attempts should be triggered.
     *
     * @param WP_User $user The User.
     *
     * @return bool True if rate limit is okay, false if not.
     * @since 0.8.0
     */
    public static function is_user_rate_limited(WP_User $user): bool
    {
        $rate_limit = self::get_user_time_delay($user);
        $last_failed = get_user_meta($user->ID, self::RSSSL_USER_RATE_LIMIT_KEY, true);

        $rate_limited = false;
        if ($last_failed && $last_failed + $rate_limit > time()) {
            $rate_limited = true;
        }

        /**
         * Filter whether this login attempt is rate limited or not.
         *
         * This allows for dedicated plugins to rate limit two-factor login attempts
         * based on their own rules.
         *
         * @param bool $rate_limited Whether the user login is rate limited.
         * @param WP_User $user The user attempting to log in.
         */
        return apply_filters('rsssl_two_factor_is_user_rate_limited', $rate_limited, $user);
    }

	/**
	 * Validates the two-factor authentication code. for all providers.
	 *
	 * @return void
	 * @throws Exception
	 */
	public static function rsssl_login_form_validate_2fa(): void {
		[$wp_auth_id, $nonce, $provider_key, $redirect_to] = self::get_request_data();

		if (isset($_SERVER['REQUEST_METHOD']) && 'POST' === strtoupper((sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD']))))) {
			$is_post_request = true;
		} else {
			$is_post_request = false;
		}

		if (!$wp_auth_id || !$nonce) {
			return;
		}

		$user = get_userdata($wp_auth_id);
		if (!$user) {
			return;
		}

		// Verify the nonce
		if (true !== Rsssl_Two_Fa_Authentication::verify_login_nonce($user->ID, $nonce)) {
			wp_safe_redirect(home_url());
			exit;
		}

		$loader = Rsssl_Provider_Loader::get_loader();
		// Get the provider
		$providers = $loader::get_enabled_providers_for_user($user);
		if ($provider_key && isset($providers[$provider_key])) {
			$provider_class = get_class($providers[$provider_key]);
		} else {
			wp_die(esc_html__('Authentication provider not specified or invalid.', 'really-simple-ssl'), 403);
		}

		/** @var Rsssl_Two_Factor_Provider $provider_instance */
		$provider_instance = $provider_class::get_instance();

		// Check for corrupted/empty TOTP key before attempting authentication
		self::validate_totp_key_exists( $user, $provider_key );

		// Allow the provider to re-send codes, etc.
		if ( ( 'email' === $provider_key ) && true === $provider_instance->pre_process_authentication( $user ) ) {
			// Always generate a new nonce.
			$new_nonce = self::generate_login_nonce_for_user($user->ID);
			self::login_html($user, $new_nonce, $redirect_to, '', $provider_class);
			exit;
		}

		// If the form hasn't been submitted, just display the auth form.
		if (!$is_post_request) {
			self::handle_not_post_request($user, $provider_class);
			exit;
		}
		if (self::is_user_rate_limited($user)) {
			$time_delay = self::get_user_time_delay($user);
			$last_failed = get_user_meta($user->ID, self::RSSSL_USER_RATE_LIMIT_KEY, true);

			$error = new WP_Error(
				'rsssl_two_factor_too_fast',
				sprintf(
				/* translators: %s: time delay between login attempts */
					__(
						'Too many invalid verification codes, you can try again in %s. This limit protects your account against automated attacks.',
						'really-simple-ssl'
					),
					human_time_diff($last_failed + $time_delay)
				)
			);

			do_action('rsssl_wp_login_failed', $user->user_login, $error);

			// Display the login form with an error message
			self::login_html(
				$user,
				$redirect_to,
				esc_html($error->get_error_message()),
				$provider_key
			);
			exit;
		}
		// Validate authentication
		if (!$provider_instance->validate_authentication($user)) {
			// Handle rate limiting and failed attempts
			self::handle_failed_attempt($user, $provider_class, $redirect_to, $nonce);
			exit;
		}

		// Successful authentication
		self::complete_authentication($user, $redirect_to);
	}

	/**
     * Handles the case when a two-factor authentication attempt fails.
     *
     *
     * @return void
     * @throws Exception
     */
    protected static function handle_failed_attempt(WP_User $user, string $provider_class, string $redirect_to, string $login_nonce): void {
		// Store the last time a failed login occurred.
		update_user_meta($user->ID, self::RSSSL_USER_RATE_LIMIT_KEY, time());

		// Store the number of failed login attempts.
		update_user_meta(
			$user->ID,
			self::RSSSL_USER_FAILED_LOGIN_ATTEMPTS_KEY,
			1 + (int)get_user_meta($user->ID, self::RSSSL_USER_FAILED_LOGIN_ATTEMPTS_KEY, true)
		);

		if (self::should_reset_password($user->ID)) {
			self::reset_compromised_password($user);
			self::send_password_reset_emails($user);
			self::show_password_reset_error();
			exit;
		}

		/** @var Rsssl_Two_Factor_Provider_Interface $provider_class */
        $provider_class::get_instance();

		self::login_html(
			$user,
			$login_nonce,
			$redirect_to,
			esc_html__('Invalid verification code.', 'really-simple-ssl'),
			$provider_class
		);
	}

	/**
     * Completes the two-factor authentication process. After a successful authentication, the user is redirected to the appropriate page.
     *
     * @return void
     */
    protected static function complete_authentication(WP_User $user, string $redirect_to): void {
		$rememberme = false;
		if (isset($_REQUEST['rememberme']) && filter_var(wp_unslash($_REQUEST['rememberme']), FILTER_VALIDATE_BOOLEAN)) {
			$rememberme = true;
		}
		// Authenticate the user.
		wp_set_auth_cookie($user->ID, $rememberme);

		do_action('rsssl_two_factor_user_authenticated', $user);

		$redirect_to = apply_filters('login_redirect', $redirect_to, $redirect_to, $user);
		// cleaning up the user meta.
	    delete_user_meta( $user->ID, self::RSSSL_USER_FAILED_LOGIN_ATTEMPTS_KEY);
	    delete_user_meta( $user->ID, self::RSSSL_USER_RATE_LIMIT_KEY);
		wp_safe_redirect($redirect_to);
		exit;
	}

    /**
     * Handle the case when the request method is not POST.
     *
     * @param WP_User $user The user object.
     * @param string $provider The provider name.
     *
     * @return void
     * @throws Exception If the login nonce cannot be created.
     */
    private static function handle_not_post_request(WP_User $user, string $provider): void
    {
        $login_nonce = self::generate_login_nonce_for_user($user->ID);

        self::login_html(
            $user,
            $login_nonce,
            isset($_REQUEST['redirect_to']) ? wp_validate_redirect(wp_unslash($_REQUEST['redirect_to']), '') : '',
            '',
            $provider
        );
    }

    /**
     * Get the request data for two-factor authentication.
     *
     * @return array An array containing the sanitized values of wp_auth_id, nonce, and provider.
     */
    private static function get_request_data(): array
    {
        $wp_auth_id = self::sanitize_request_data('rsssl-wp-auth-id', 0, 'absint');
        $nonce = self::sanitize_request_data('rsssl-wp-auth-nonce', '', 'wp_unslash');
        $provider = self::sanitize_request_data('provider', false, 'wp_unslash');
        $redirect_to = self::sanitize_request_data('redirect_to', '', 'wp_unslash');
        return array($wp_auth_id, $nonce, $provider, $redirect_to);
    }

    /**
     * Sanitize request data.
     *
     * @param string $key The key to retrieve from the $_REQUEST array.
     * @param mixed $default_value The default value to return if the key does not exist in the $_REQUEST array.
     * @param callable $sanitize_callback The callback function used to sanitize the value.
     *
     * @return mixed The sanitized value if it exists in the $_REQUEST array, otherwise the default value.
     */
    private static function sanitize_request_data(string $key, $default_value, callable $sanitize_callback)
    {
        return !empty($_REQUEST[$key]) ? $sanitize_callback(sanitize_text_field(wp_unslash($_REQUEST[$key]))) : $default_value;
    }

    /**
     * Checks if a user's password should be reset based on the number of failed login attempts on the 2nd factor.
     *
     * @param int $user_id The ID of the user.
     *
     * @return bool True if the password should be reset, false otherwise.
     */
    public static function should_reset_password(int $user_id): bool
    {
        $failed_attempts = (int)get_user_meta($user_id, self::RSSSL_USER_FAILED_LOGIN_ATTEMPTS_KEY, true);

        /**
         * Filters the maximum number of failed attempts on a 2nd factor before the user's
         * password will be reset. After a reasonable number of attempts, it's safe to assume
         * that the password has been compromised and an attacker is trying to brute force the 2nd
         * factor.
         *
         * ⚠️ `get_user_time_delay()` mitigates brute force attempts, but many 2nd factors --
         * like TOTP and backup codes -- are very weak on their own, so it's not safe to give
         * attackers unlimited attempts. Setting this to a very large number is strongly
         * discouraged.
         *
         * @param int $limit The number of attempts before the password is reset.
         */
        $failed_attempt_limit = apply_filters('rsssl_two_factor_failed_attempt_limit', 30);

        return $failed_attempts >= $failed_attempt_limit;
    }

    /**
     * Reset a compromised password.
     *
     * If we know that the password is compromised, we have the responsibility to reset it and inform the
     * user. `get_user_time_delay()` mitigates brute force attempts, but this acts as an extra layer of defense
     * which guarantees that attackers can't brute force it (unless they compromise the new password).
     *
     * @param WP_User $user The user who failed to log in.
     */
    public static function reset_compromised_password(WP_User $user): void
    {
        // Unhook because `wp_password_change_notification()` wouldn't notify the site admin when
        // their password is compromised.
        remove_action('after_password_reset', 'wp_password_change_notification');
        reset_password($user, wp_generate_password(25));
        update_user_meta($user->ID, self::RSSSL_USER_PASSWORD_WAS_RESET_KEY, true);
        add_action('after_password_reset', 'wp_password_change_notification');

        Rsssl_Two_Fa_Authentication::delete_login_nonce($user->ID);
        delete_user_meta($user->ID, self::RSSSL_USER_RATE_LIMIT_KEY);
        delete_user_meta($user->ID, self::RSSSL_USER_FAILED_LOGIN_ATTEMPTS_KEY);
    }

    /**
     * Notify the user and admin that a password was reset for being compromised.
     *
     * @param WP_User $user The user whose password should be reset.
     */
    public static function send_password_reset_emails(WP_User $user): void
    {
        self::notify_user_password_reset($user);

        /**
         * Filters whether to email the site admin when a user's password has been
         * compromised and reset.
         *
         * @param bool $reset `true` to notify the admin, `false` to not notify them.
         */
        $notify_admin = apply_filters('rsssl_two_factor_notify_admin_user_password_reset', true);
        $admin_email = get_option('admin_email');

        if ($notify_admin && $admin_email !== $user->user_email) {
            self::notify_admin_user_password_reset($user);
        }
    }


    /**
     * Show the password reset error when on the login screen.
     */
    public static function show_password_reset_error(): void
    {
        $error = new WP_Error(
            'too_many_attempts',
            sprintf(
                '<p>%s</p>
				<p style="margin-top: 1em;">%s</p>',
                __(
                    'There have been too many failed two-factor authentication attempts, which often indicates that the password has been compromised. The password has been reset in order to protect the account.',
                    'really-simple-ssl'
                ),
                __(
                    'If you are the owner of this account, please check your email for instructions on regaining access.',
                    'really-simple-ssl'
                )
            )
        );

        login_header(__('Password Reset', 'really-simple-ssl'), '', $error);
        login_footer();
    }

    /**
     * Should the login session persist between sessions.
     *
     * @return boolean
     */
    public static function rememberme(): bool
    {
        $rememberme = false;

        if (!empty($_REQUEST['rememberme'])) {
            $rememberme = true;
        }

        return (bool)apply_filters('rsssl_two_factor_rememberme', $rememberme);
    }

    /**
     * Check if the user has completed the onboarding process.
     *
     * @param WP_User $user The WP_User object representing the user.
     *
     * @return void
     * @throws Exception If the onboarding screen template cannot be loaded.
     */
    private static function is_onboarding_complete(WP_User $user): void
    {
        // If the user has not completed the onboarding process, they should be shown the onboarding screen.
        $onboarding_complete = get_user_meta($user->ID, self::RSSSL_USER_META_ONBOARDING_COMPLETE, true);
        if (!$onboarding_complete) {
            self::onboarding_user_html($user);
        }
    }

    /**
     * Display the expired onboarding error. Manually load our login header and
     * footer functions to ensure they are  available.
     */
    private static function display_expired_onboarding_error(): void
    {
        if (!function_exists('login_header')) {
            include_once __DIR__ . '/function-login-header.php';
        }

        if (!function_exists('login_footer')) {
            include_once __DIR__ . '/function-login-footer.php';
        }

	    rsssl_load_template('expired.php', [
            'message' => esc_html__('Your 2FA grace period expired. Please contact your site administrator to regain access and to configure 2FA.', 'really-simple-ssl'),
        ], rsssl_path . 'assets/templates/two_fa/');
    }

    /**
     * Validate that TOTP key exists for the user when TOTP provider is used.
     * Destroys session and displays error if key is corrupted/missing.
     *
     * @param WP_User $user The user object.
     * @param string $provider_key The provider key being used.
     *
     * @return void
     */
    private static function validate_totp_key_exists( WP_User $user, string $provider_key ): void
    {
        if ( 'totp' !== $provider_key ) {
            return;
        }

        if ( ! class_exists( 'RSSSL\Pro\Security\WordPress\Two_Fa\Providers\Rsssl_Two_Factor_Totp' ) ) {
            return;
        }

        $totp_key = get_user_meta(
            $user->ID,
            \RSSSL\Pro\Security\WordPress\Two_Fa\Providers\Rsssl_Two_Factor_Totp::SECRET_META_KEY,
            true
        );

        if ( empty( $totp_key ) ) {
            // Verify we have a valid user before destroying their session
            if ( ! $user instanceof WP_User || ! $user->exists() ) {
                wp_die( esc_html__( 'Invalid user.', 'really-simple-ssl' ), 403 );
            }

            // TOTP key is missing/corrupted
            self::destroy_current_session_for_user( $user );
            wp_clear_auth_cookie();
            self::display_corrupted_totp_error();
            exit;
        }
    }

    /**
     * Display error when TOTP key is corrupted/missing. Manually load our login header and
     * footer functions to ensure they are available.
     * Follows the same template as the expired onboarding error.
     */
    private static function display_corrupted_totp_error(): void
    {
        if (!function_exists('login_header')) {
            include_once __DIR__ . '/function-login-header.php';
        }

        if (!function_exists('login_footer')) {
            include_once __DIR__ . '/function-login-footer.php';
        }

	    rsssl_load_template('expired.php', [
            'message' => esc_html__('Your Two-Factor Authentication configuration is corrupted. Please contact your site administrator to regain access.', 'really-simple-ssl'),
        ], rsssl_path . 'assets/templates/two_fa/');
    }

    /**
     * Generate the HTML for the onboarding screen for a given user.
     *
     * @param WP_User $user The user object.
     *
     * @return void
     * @throws Exception If the onboarding screen template cannot be loaded.
     */
    private static function onboarding_user_html(WP_User $user): void
    {
        $passkey_onboarding = get_user_meta($user->ID, 'rsssl_two_fa_status_passkey', true) === 'open';
        // Variables needed for the template and scripts
        $onboarding_url = self::login_url(array('action' => 'rsssl_onboarding'), 'login_post');
        $provider_loader = Rsssl_Provider_Loader::get_loader();
        $provider = self::get_primary_provider_for_user($user);
        $redirect_to = isset($_REQUEST['redirect_to']) ? wp_validate_redirect(wp_unslash($_REQUEST['redirect_to']), admin_url()) : admin_url();
        $enabled_providers = $provider_loader::get_user_enabled_providers($user);
        $login_nonce = self::generate_login_nonce_for_user($user->ID);
        $is_forced = Rsssl_Two_Factor_Settings::is_user_forced_to_use_2fa($user->ID);
        $grace_period = Rsssl_Two_Factor_Settings::is_user_in_grace_period($user);
        $is_today = Rsssl_Two_Factor_Settings::is_today($user);

        if ($passkey_onboarding) {
            $is_forced = false;
            //if only passkey is available, set it as the only provider
            if (count($enabled_providers) === 1 && isset($enabled_providers['passkey'])) {
                $provider = 'passkey';
            }
        }
        // Ensure login_header and login_footer functions are available
        if (!function_exists('login_header')) {
            include_once __DIR__ . '/function-login-header.php';
        }

        if (!function_exists('login_footer')) {
            include_once __DIR__ . '/function-login-footer.php';
        }

        //Add the styles for the two-factor authentication.
        add_action('login_enqueue_styles', array(__CLASS__, 'enqueue_onboarding_styles'));

        $uri = trailingslashit(rsssl_url) . 'assets/features/two-fa/assets.min.js';
		$uri_file = trailingslashit(rsssl_path) . 'assets/features/two-fa/assets.min.js';
	    add_filter('wp_script_attributes', [self::class, 'handle_script_attributes'], 10, 2);
        wp_enqueue_script('rsssl-frontend-settings', $uri, array(), filemtime($uri_file), true);

        wp_localize_script('rsssl-frontend-settings', 'rsssl_onboard', array(
            'nonce' => wp_create_nonce('wp_rest'),
            'root' => esc_url_raw(rest_url(self::REST_NAMESPACE)),
            'login_nonce' => $login_nonce,
            'redirect_to' => $redirect_to,
            'user_id' => $user->ID,
            'origin' => 'onboarding',
            'translatables' => apply_filters('rsssl_two_factor_translatables', []),
        ));

        login_header(
            __('Two-Factor Authentication Setup', 'really-simple-ssl'),
            '',
            null
        );

        rsssl_load_template(
            'onboarding.php',
            array(
                'user' => $user,
                'login_nonce' => $login_nonce,
                'url' => $onboarding_url,
                'provider' => $provider,
                'redirect_to' => $redirect_to,
                'available_providers' => $enabled_providers,
                'interim_login' => isset($_REQUEST['interim-login']),
                'rememberme' => (int)self::rememberme(),
                'primary_provider' => $provider,
                'is_forced' => $is_forced,
                'grace_period' => $grace_period,
                'is_today' => $is_today,
                'skip_two_fa_url' => Rsssl_Two_Factor_Settings::rsssl_one_time_login_url($user->ID),
            ),
            rsssl_path . 'assets/templates/two_fa/'
        );

        wp_enqueue_script('rsssl-rest-settings');


        login_footer();

        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
        exit; //This was the original exit.
    }

	/**
	 * Handles the script attributes.
	 *
	 *
	 * @param array $attributes
	 * @param string $handle
	 *
	 * @return array
	 */
    public static function handle_script_attributes( array $attributes, string $handle = ''):array
	{
		if ( $handle === 'rsssl-profile-settings' ) {
			$attributes['type'] = 'module';
		}
		return $attributes;
	}


    /**
     * Enqueues the RSSSL profile settings stylesheet.
     *
     * @return void
     */
    public static function enqueue_onboarding_styles(): void
    {
	    $url = trailingslashit(rsssl_url) . 'assets/features/two-fa/styles.css';
	    $file = trailingslashit(rsssl_path) . 'assets/features/two-fa/styles.css';
	    wp_enqueue_style('rsssl-profile-settings', $url, array(), filemtime($file));
    }

	/**
	 * Return the translatable strings for the two-factor authentication.
	 * @return array
	 */
	public static function translatables(): array {
		return self::rsssl_translatables([]);
	}

	/**
     * places all translatable strings.
     *
     *
     * @return array
     */
    public static function rsssl_translatables(array $translatables): array {
		$new_translatables = [
			'download_codes' => esc_html__('Download Backup Codes', 'really-simple-ssl'),
			'keyCopied' => __('Key copied', 'really-simple-ssl'),
			'keyCopiedFailed' => __('Could not copy text: ', 'really-simple-ssl'),
		];
		return array_merge($translatables, $new_translatables);
	}

	/**
	 * Generates a login nonce for a user. and returns the key.
	 *
	 * @param $user_id
	 *
	 * @return string
	 */
	protected static function generate_login_nonce_for_user( $user_id ): string {
		$login_nonce = Rsssl_Two_Fa_Authentication::create_login_nonce( $user_id );
		if ( ! $login_nonce ) {
			$error = new WP_Error();
            $error->add(
                'login_nonce_creation_failed',
                __( 'Failed to create a login nonce.', 'really-simple-ssl' )
            );
		}
		return $login_nonce['rsssl_key'];
	}
}

/**
 * Hook as soon as the file is required. Which is the plugins_loaded hook.
 * @see security/integrations.php
 */
$rsssl_two_factor_compat = new Rsssl_Two_Factor_Compat();
Rsssl_Two_Factor::add_hooks($rsssl_two_factor_compat);