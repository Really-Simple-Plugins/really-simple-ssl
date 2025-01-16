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
use RSSSL\Security\WordPress\Two_Fa\Traits\Rsssl_Email_Trait;
use WP_Error;
use WP_Session_Tokens;
use WP_User;
use rsssl_mailer;

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

    public const WEIGHT = array(
        Rsssl_Two_Factor_Totp::class,
        Rsssl_Two_Factor_Email::class,
    );

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
    public const REST_NAMESPACE = 'reallysimplessl/v1/two_fa/v2';

    /**
     * Keep track of all the password-based authentication sessions that
     * need to invalidated before the second factor authentication.
     *
     * @var array
     */
    private static $password_auth_tokens = array();

    /**
     * Set up filters and actions.
     *
     * @param object $compat A compatibility layer for plugins.
     *
     * @since 0.1-dev
     */
    public static function add_hooks(object $compat): void
    {
	    if ( ( defined( 'RSSSL_DISABLE_2FA' ) && RSSSL_DISABLE_2FA ) || ( defined( 'RSSSL_SAFE_MODE' ) && RSSSL_SAFE_MODE ) ) {
		    if ( rsssl_admin_logged_in() ) {
			    ( new Rsssl_Two_Factor_Admin() );
		    }

		    ( new Rsssl_Two_Factor_On_Board_Api() );
		    if ( is_user_logged_in() ) {
			    ( new Rsssl_Two_Factor_Profile_Settings() );
		    }

		    return;
	    }

        /**
         * Runs the fix for the reset error in 9.1.1
         */
	    if (filter_var(get_option('rsssl_reset_fix', false), FILTER_VALIDATE_BOOLEAN)) {
		    RSSSL_Two_Factor_Reset_Factory::reset_fix();
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

        (new Rsssl_Two_Factor_On_Board_Api());
        if(is_user_logged_in()) {
            (new Rsssl_Two_Factor_Profile_Settings());
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
        if (isset($_GET['rsssl_one_time_login']) && isset($_GET['_wpnonce'])) {
            $nonce = sanitize_text_field(wp_unslash($_GET['_wpnonce']));

            if (wp_verify_nonce($nonce)) {
                add_action('init', array(__CLASS__, 'maybe_skip_auth'));
            }
	        self::maybe_skip_auth();
        }

        add_action('init', array(__CLASS__, 'rsssl_collect_auth_cookie_tokens'));

        // Run only after the core wp_authenticate_username_password() check.
        add_filter('authenticate', array(__CLASS__, 'rsssl_filter_authenticate'));
//
//        // Run as late as possible to prevent other plugins from unintentionally bypassing.
        add_filter('authenticate', array(__CLASS__, 'rsssl_filter_authenticate_block_cookies'), PHP_INT_MAX);
        add_action('admin_init', array(__CLASS__, 'rsssl_enable_dummy_method_for_debug'));
        add_filter('rsssl_two_factor_providers', array(__CLASS__, 'enable_dummy_method_for_debug'));
	    add_action( 'rsssl_daily_cron', array( __CLASS__, 'maybe_send_reminder_email' ) );

        $compat->init();
    }

	/**
	 * @return void
	 *
	 * Send a reminder e-mail if Two FA has not been configured within 3 days.
	 */
	public static function maybe_send_reminder_email() {
		$roles = rsssl_get_option('two_fa_forced_roles');
		// If no roles are set, we'll get all users
		if ( empty( $roles ) ) {
			// No users with 'open' status
			return;
		} else {
			$args = array(
				'role__in' => $roles,
			);
		}

		$users = get_users( $args );

		// Get users where meta_key rsssl_two_fa_status_totp or rsssl_two_fa_status_email does not exist or is not set to 'active'
		$users_in_grace_period_without_two_fa = array();
		foreach ( $users as $user ) {
			$statusses = Rsssl_Two_Fa_Status::get_user_two_fa_status( $user );
			$methods   = Rsssl_Provider_Loader::METHODS;
            // if the status active is in the array, we don't need to send a reminder.
            if ( in_array('active', $statusses, true) ) {
                continue;
            }

            $users_in_grace_period_without_two_fa[] = $user;
		}

		foreach ( $users_in_grace_period_without_two_fa as $user ) {

			$user_id         = $user->ID;
			$two_fa_reminder_sent = get_user_meta( $user_id, 'rsssl_two_fa_reminder_sent', true );

			if ( $two_fa_reminder_sent ) {
				continue;
			}
			// Get grace period for user
			$remaining_grace_period = Rsssl_Two_Factor_Settings::is_user_in_grace_period( $user );
			$grace_period_setting   = rsssl_get_option( 'two_fa_grace_period' );

			// If grace period setting is 1 day, and remaining grace period greater than 3, continue to next user
			if ( $grace_period_setting == 1 || $remaining_grace_period > 3 ) {
				continue;
			}

			if ( ! class_exists( 'rsssl_mailer' ) ) {
				require_once rsssl_path . 'mailer/class-mail.php';
			}

			$subject = __( "Important security notice", "really-simple-ssl" );

			$login_url = wp_login_url();
			// Check if a custom login URL is set in Really Simple SSL
			if ( function_exists( 'rsssl_get_option' ) && rsssl_get_option( 'change_login_url_enabled' ) !== false && ! empty( rsssl_get_option( 'change_login_url' ) ) ) {
				$login_url = trailingslashit( site_url() ) . rsssl_get_option( 'change_login_url' );
			}

			$login_link = sprintf('<a href="%s">%s</a>', esc_url($login_url), __('Please login', 'really-simple-ssl'));

			$message = sprintf(
			/* translators:
			1: Site URL.
			*/
				__("You are receiving this email because you have an account registered at %s.", "really-simple-ssl"),
				site_url(),
			);

            $message .= "<br><br>";

			$message .= sprintf(
			/* translators:
			1: Login link with the text "Please login".
			2: Opening <strong> tag to emphasize the "within three days" text.
			3: Closing </strong> tag for "within three days".
			4: Opening <strong> tag to emphasize "you will be unable to login".
			5 Closing </strong> tag for "you will be unable to login".
			*/

                __("The site's security policy requires you to configure Two-Factor Authentication to protect against account theft. %1\$s and configure Two-Factor authentication %2\$swithin three days%3\$s. If you haven't performed the configuration by then, %4\$syou will be unable to login%5\$s.", "really-simple-ssl"),
				$login_link,
				'<strong>',
				'</strong>',
				'<strong>',
				'</strong>'
			);

			$mailer                    = new rsssl_mailer();
			$mailer->subject           = $subject;
			$mailer->branded           = false;
			$mailer->sent_by_text      = "<b>".sprintf( __( 'Notification by %s', 'really-simple-ssl' ), site_url() )."</b>";
			$mailer->template_filename = apply_filters( 'rsssl_email_template', rsssl_path . '/mailer/templates/email-unbranded.html' );
			$mailer->to                = $user->user_email;
			$mailer->title             = sprintf( __( "Hi %s %s", "really-simple-ssl" ), trim( $user->first_name ), trim( $user->last_name ) ) . ',';
			$mailer->message           = $message;
			$mailer->send_mail();

			// Update meta to set reminder e-mail send, add check in beginning of function.
			update_user_meta( $user_id, 'rsssl_two_fa_reminder_sent', true );
		}
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
                // We set the user status to active.
                Rsssl_Two_Factor_Email::set_user_status($user->ID, 'active');
                // We disable the TOTP.
                Rsssl_Two_Factor_Totp::set_user_status($user->ID, 'disabled');
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

                $provider = get_user_meta($user->ID, 'rsssl_two_fa_status_email', true);

                // Only allow skipping for users which have 2FA value open.
                if (isset($_GET['rsssl_two_fa_disable']) && 'open' === $provider) {
                    update_user_meta($user_id, 'rsssl_two_fa_status_email', 'disabled');
                }

                if ('open' === Rsssl_Two_Factor_Settings::get_user_status('email', $user_id)) {
                    update_user_meta($user_id, 'rsssl_two_fa_status', 'active');
                    update_user_meta($user_id, 'rsssl_two_fa_status_email', 'active');
                    update_user_meta($user_id, 'rsssl_two_fa_status_totp', 'disabled');
	                delete_user_meta( $user_id, '_rsssl_factor_email_token' );
                }

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
     * Get the user settings page URL.
     *
     * Fetch this from the plugin core after we introduce proper dependency injection
     * and get away from the singletons at the provider level (should be handled by core).
     *
     * @param integer $user_id User ID.
     *
     * @return string
     */
    protected static function get_user_settings_page_url(int $user_id): string
    {
        $page = 'user-edit.php';

        if (defined('IS_PROFILE_PAGE') && IS_PROFILE_PAGE) {
            $page = 'profile.php';
        }

        return add_query_arg(
            array(
                'rsssl_user_id' => (int)$user_id,
            ),
            self_admin_url($page)
        );
    }

    /**
     * Get the URL for resetting the secret token.
     * TODO: Ask Mark if still needed.
     *
     * @param integer $user_id User ID.
     * @param string $action Custom two factor action key.
     *
     * @return string
     */
    public static function get_user_update_action_url(int $user_id, string $action): string
    {
        return wp_nonce_url(
            add_query_arg(
                array(
                    self::RSSSL_USER_SETTINGS_ACTION_QUERY_VAR => $action,
                ),
                self::get_user_settings_page_url($user_id)
            ),
            sprintf('%d-%s', $user_id, $action),
            self::RSSSL_USER_SETTINGS_ACTION_NONCE_QUERY_ARG
        );
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
        return self::get_configured_providers($user);
    }

    /**
     * Get the list of configured providers for a given user.
     *
     * @param WP_User $user The user object.
     *
     * @return array The list of configured providers.
     */
    private static function get_configured_providers(WP_User $user): array
    {
        $providers = Rsssl_Provider_Loader::get_providers();
        $enabled_providers = Rsssl_Provider_Loader::get_enabled_providers_for_user($user);
        $configured_providers = array();
        foreach ($providers as $classname => $provider) {
            if (in_array($classname, $enabled_providers, true) && $provider::is_configured($user)) {
                $configured_providers[$classname] = $provider;
            }
        }
        if(empty($configured_providers)) {
            foreach ( $providers as $classname => $provider ) {
                if ( in_array( $classname, $enabled_providers, true ) && $provider::is_enabled( $user ) ) {
                    $configured_providers[ $classname ] = $provider;
                }
            }
        }

        return $configured_providers;
    }

    /**
     * Gets the Two-Factor Auth provider for the specified|current user.
     *
     * @param WP_User $user Optional. User ID, or WP_User object of the user. Defaults to current user.
     *
     * @return object|null
     * @since 0.1-dev
     */
    public static function get_primary_provider_for_user(WP_User $user): ?object
    {

        $providers = Rsssl_Provider_Loader::get_providers();
        $available_providers = self::get_available_providers_for_user($user);

        // If there's only one available provider, force that to be the primary.
        if (empty($available_providers)) {
            return null;
        }

        if (1 === count($available_providers)) {
            $provider = key($available_providers);
        } else {
            $provider = Rsssl_Provider_Loader::get_user_enabled_providers($user);
            // Check if already a provider is active.

            // If the provider specified isn't enabled, just grab the first one that is based on the Weight.
            $best_valued_provider = self::WEIGHT[0];

            if (isset($available_providers[$best_valued_provider]) && $best_valued_provider::is_enabled($user)) {
                $provider = $best_valued_provider;
            } else {
                $provider = key($available_providers);
            }
        }

        /**
         * Filter the two-factor authentication provider used for this user.
         *
         * @param string $provider The provider currently being used.
         * @param int $user_id The user ID.
         */
        $provider = apply_filters('rsssl_two_factor_primary_provider_for_user', $provider, $user->ID);

        return $providers[$provider] ?? null;
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
        if (!is_array($two_fa_forced_roles)) $two_fa_forced_roles = [];
        if (!is_array($two_fa_optional_roles)) $two_fa_optional_roles = [];
        if (!is_array($two_fa_optional_roles_totp)) $two_fa_optional_roles_totp = [];
        $two_fa_optional_roles = array_unique(array_merge($two_fa_optional_roles, $two_fa_optional_roles_totp));

        foreach ($enabled_providers_meta as $enabled_provider) {
            $status = $enabled_provider::get_status($user);
            if ('disabled' === $status) {
                if (is_object($provider) && get_class($provider) === $enabled_provider) {
                    $provider = [];
                }
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
        if (isset($_GET['nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce'])), 'rsssl_expired')) {
            if (isset($_GET['errors']) && 'expired' === $_GET['errors']) {
                $errors->add('expired', __('Your 2FA grace period expired. Please contact your site administrator to regain access and to configure 2FA.', 'really-simple-ssl'));
            }
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
                self::display_expired_onboarding_error($user);
                exit;
            case 'totp':
            case 'email':
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

        if (!$user) {
            $user = wp_get_current_user();
        }

        $login_nonce = Rsssl_Two_Fa_Authentication::create_login_nonce($user->ID);
        if (!$login_nonce) {
            $error = new WP_Error();
            $error->add('login_nonce_creation_failed', __('Failed to create a login nonce.', 'really-simple-ssl'));
        }

        $redirect_to = isset($_REQUEST['redirect_to']) ? sanitize_text_field(wp_unslash($_REQUEST['redirect_to'])) : admin_url();
        $provider = Rsssl_Two_Factor_Settings::get_login_action($user->ID);

        self::login_html($user, $login_nonce['rsssl_key'], $redirect_to);
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
        $provider_class = get_class($provider);

        $available_providers = self::get_available_providers_for_user($user);
        $backup_providers = array_diff_key($available_providers, array($provider_class => null));
        $interim_login = isset($_REQUEST['interim-login']); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        $rememberme = (int)self::rememberme();

        if (!function_exists('login_header')) {
            // We really should migrate login_header() out of `wp-login.php` so it can be called from an includes file.
            include_once __DIR__ . '/function-login-header.php';
        }

        // Load the login template.
        rsssl_load_template(
            'login.php',
            compact(
                'login_nonce',
                'redirect_to',
                'error_msg',
                'provider',
                'backup_providers',
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
        if (!is_array($params)) {
            $params = array();
        }

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
     * Login form validation.
     *
     * @throws Exception If the login nonce creation fails.
     * @since 0.1-dev
     */
    public static function rsssl_login_form_validate_2fa_email(): void
    {
        [$wp_auth_id, $nonce, $provider, $redirect_to] = self::get_request_data();

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

        if ($provider) {
            $providers = self::get_available_providers_for_user($user);
            if (isset($providers[$provider])) {
                $provider = $providers[$provider];
            } else {
                new WP_Error('cheating_detected', __('Cheatin&#8217; uh?', 'really-simple-ssl'));
                status_header(403);
            }
        } else {
            $provider = self::get_primary_provider_for_user($user);
        }

        if ($provider->user_token_has_expired($user->ID)) {
            self::login_html(
                $user,
                '',
                '',
                esc_html__(
                    'Your verification code expired, click “Resend Code” to receive a new verification code.',
                    'really-simple-ssl'
                ),
                $provider
            );
            exit;
        }

        if (true !== Rsssl_Two_Fa_Authentication::verify_login_nonce($user->ID, $nonce)) {
            wp_safe_redirect(home_url());
            exit;
        }

        // Allow the provider to re-send codes, etc.
        if (true === $provider->pre_process_authentication($user)) {
            $login_nonce = Rsssl_Two_Fa_Authentication::create_login_nonce($user->ID);
            if (!$login_nonce) {
                $error = new WP_Error();
                $error->add(
                    'login_nonce_creation_failed',
                    __('Failed to create a login nonce.', 'really-simple-ssl')
                );
            }

            self::login_html($user, $login_nonce['rsssl_key'], $redirect_to, '', $provider);
            exit;
        }

        // If the form hasn't been submitted, just display the auth form.
        if (!$is_post_request) {
            self::handle_not_post_request($user, $provider);
            exit;
        }

        // Rate limit two-factor authentication attempts.
        if (true === self::is_user_rate_limited($user)) {
            $time_delay = self::get_user_time_delay($user);
            $last_login = get_user_meta($user->ID, self::RSSSL_USER_RATE_LIMIT_KEY, true);

            $error = new WP_Error(
                'rsssl_two_factor_too_fast',
                sprintf(
                /* translators: %s: time delay between login attempts */
                    __(
                        'Too many invalid verification codes, you can try again in %s. This limit protects your account against automated attacks.',
                        'really-simple-ssl'
                    ),
                    human_time_diff($last_login + $time_delay)
                )
            );

            do_action('rsssl_wp_login_failed', $user->user_login, $error);//phpcs:ignore

            $login_nonce = Rsssl_Two_Fa_Authentication::create_login_nonce($user->ID);
            if (!$login_nonce) {
                $error = new WP_Error();
                $error->add(
                    'login_nonce_creation_failed',
                    __('Failed to create a login nonce.', 'really-simple-ssl')
                );
            }

            self::login_html(
                $user,
                $login_nonce['rsssl_key'],
                $redirect_to,
                esc_html($error->get_error_message()),
                $provider
            );
            exit;
        }

        // Ask the provider to verify the second factor.
        if (true !== $provider->validate_authentication($user)) {
            do_action(
                'rsssl_wp_login_failed',
                $user->user_login,
                new WP_Error(
                    'rsssl_two_factor_invalid',
                    __('Invalid verification code.', 'really-simple-ssl')));//phpcs:ignore

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

            $login_nonce = Rsssl_Two_Fa_Authentication::create_login_nonce($user->ID);
            if (!$login_nonce) {
                $error = new WP_Error();
                $error->add(
                    'login_nonce_creation_failed',
                    __('Failed to create a login nonce.', 'really-simple-ssl')
                );
            }

            if ($provider->user_token_has_expired($user->ID)) {
                self::login_html(
                    $user,
                    $login_nonce['rsssl_key'],
                    $redirect_to,
                    esc_html__(
                        'Your verification code expired, click “Resend Code” to receive a new verification code.',
                        'really-simple-ssl'
                    ),
                    $provider
                );
                exit;
            }

            self::login_html(
                $user,
                $login_nonce['rsssl_key'],
                $redirect_to,
                esc_html__('Invalid verification code.', 'really-simple-ssl'),
                $provider
            );
            exit;
        }

        Rsssl_Two_Fa_Authentication::delete_login_nonce($user->ID);
        delete_user_meta($user->ID, self::RSSSL_USER_RATE_LIMIT_KEY);
        delete_user_meta($user->ID, self::RSSSL_USER_FAILED_LOGIN_ATTEMPTS_KEY);

        $rememberme = false;
        if (isset($_REQUEST['rememberme']) && filter_var(wp_unslash($_REQUEST['rememberme']), FILTER_VALIDATE_BOOLEAN)) {
            $rememberme = true;
        }

        /*
         * NOTE: This filter removal is not normally required, this is included for protection against
         * a plugin/two factor provider which runs the `authenticate` filter during its validation.
         * Such a plugin would cause self::rsssl_filter_authenticate_block_cookies() to run and add this filter.
         */
        remove_filter('send_auth_cookies', '__return_false', PHP_INT_MAX);
        wp_set_auth_cookie($user->ID, $rememberme);

        do_action('rsssl_two_factor_user_authenticated', $user);

        // Must be global because that's how login_header() uses it.
        global $interim_login;
        $interim_login = isset($_REQUEST['interim-login']); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited,WordPress.Security.NonceVerification.Recommended

        if ($interim_login) {
            $customize_login = isset($_REQUEST['customize-login']);
            if ($customize_login) {
                wp_enqueue_script('customize-base');
            }
            $message = '<p class="message">' . __(
                    'You have logged in successfully.',
                    'really-simple-ssl'
                ) . '</p>';
            $interim_login = 'success'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
            login_header('', $message);
            ?>
            </div>
            <?php
            /** This action is documented in wp-login.php */
            do_action('login_footer');//phpcs:ignore
            ?>
            <?php if ($customize_login) : ?>
                <script type="text/javascript">setTimeout(function () {
                        new wp.customize.Messenger({
                            url: '<?php echo esc_url(wp_customize_url()); ?>',
                            channel: 'login'
                        }).send('login')
                    }, 1000);</script>
            <?php endif; ?>
            </body></html>
            <?php
            exit;
        }
        $redirect_to = apply_filters(
            'login_redirect',
            isset($_REQUEST['redirect_to']) ? sanitize_text_field(wp_unslash($_REQUEST['redirect_to'])) : '',
            isset($_REQUEST['redirect_to']) ? sanitize_text_field(wp_unslash($_REQUEST['redirect_to'])) : '',
            $user);//phpcs:ignore
        wp_safe_redirect($redirect_to);

        exit;
    }


    /**
     * Validate the two-factor login form.
     *
     * @return void
     * @throws Exception If the user is not logged in.
     */
    public static function rsssl_login_form_validate_2fa(): void
    {
        [$wp_auth_id, $nonce, $provider] = self::get_request_data();

        if (!$wp_auth_id || !$nonce) {
            return;
        }

        $user = get_userdata($wp_auth_id);
        if (!$user) {
            return;
        }

        // Check if nonce is valid.
        if (true !== Rsssl_Two_Fa_Authentication::verify_login_nonce($user->ID, $nonce)) {
            wp_safe_redirect(home_url());
            exit;
        }

        if ($provider) {
            $providers = self::get_available_providers_for_user($user);
            if (isset($providers[$provider])) {
                $provider = $providers[$provider];
            } else {
                wp_die(esc_html__('Cheatin&#8217; uh?', 'really-simple-ssl'), 403);
            }
        } else {
            $provider = self::get_primary_provider_for_user($user);
        }

        // Check if provider exists and is enabled.
        if (!$provider) {
            wp_die(esc_html__('Authentication provider not specified.', 'really-simple-ssl'), 403);
        }

        switch (get_class($provider)) {
            case Rsssl_Two_Factor_Email::class:
                self::rsssl_login_form_validate_2fa_email();
                break;
            case Rsssl_Two_Factor_Totp::class:
                if (true === self::is_user_rate_limited($user)) {
                    $time_delay = self::get_user_time_delay($user);
                    $last_login = get_user_meta($user->ID, self::RSSSL_USER_RATE_LIMIT_KEY, true);

                    $error = new WP_Error(
                        'rsssl_two_factor_too_fast',
                        sprintf(
                        /* translators: %s: time delay between login attempts */
                            __(
                                'Too many invalid verification codes, you can try again in %s. This limit protects your account against automated attacks.',
                                'really-simple-ssl'
                            ),
                            human_time_diff($last_login + $time_delay)
                        )
                    );

                    do_action('rsssl_wp_login_failed', $user->user_login, $error);//phpcs:ignore

                    $login_nonce = Rsssl_Two_Fa_Authentication::create_login_nonce($user->ID);
                    if (!$login_nonce) {
                        $error = new WP_Error();
                        $error->add(
                            'login_nonce_creation_failed',
                            __('Failed to create a login nonce.', 'really-simple-ssl')
                        );
                    }

                    self::login_html(
                        $user,
                        $login_nonce['rsssl_key'],
                        isset($_REQUEST['redirect_to']) ? sanitize_text_field(wp_unslash($_REQUEST['redirect_to'])) : '',
                        esc_html($error->get_error_message()),
                        $provider
                    );
                    exit;
                }
                // Validate TOTP.
                if (!$provider->validate_authentication($user)) {
                    do_action(
                        'rsssl_wp_login_failed',
                        $user->user_login,
                        new WP_Error(
                            'rsssl_two_factor_invalid',
                            __('Invalid verification code.', 'really-simple-ssl')));//phpcs:ignore

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

                    $login_nonce = Rsssl_Two_Fa_Authentication::create_login_nonce($user->ID);
                    if (!$login_nonce) {
                        wp_die(esc_html__('Failed to create a login nonce.', 'really-simple-ssl'));
                    }
                    self::login_html(
                        $user,
                        $login_nonce['rsssl_key'],
                        isset($_REQUEST['redirect_to']) ? sanitize_text_field(wp_unslash($_REQUEST['redirect_to'])) : '',
                        esc_html__('Invalid verification code.', 'really-simple-ssl'),
                        $provider
                    );
                    exit;
                }
                break;
            default:
                // Create a WP_Error object.
                $error = new WP_Error();
                // Add an error message to the object.
                $error->add('rsssl_two_factor_invalid_provider', __('Invalid two-factor authentication provider.', 'really-simple-ssl'));
                // Trigger the 'rsssl_wp_login_failed' action.
                do_action('rsssl_wp_login_failed', $user->user_login, $error);//phpcs:ignore
                // Redirect the user to the login page and clear all $_POST data.
                wp_safe_redirect(rsssl_wp_login_url());
                exit;
        }

        $rememberme = false;
        if (isset($_REQUEST['rememberme']) && filter_var(wp_unslash($_REQUEST['rememberme']), FILTER_VALIDATE_BOOLEAN)) {
            $rememberme = true;
        }
        // Authenticate the user.
        wp_set_auth_cookie($user->ID, $rememberme);

        do_action('rsssl_two_factor_user_authenticated', $user);
        // if the redirect is empty redirect to profile page.
        $redirect_to = apply_filters('login_redirect', sanitize_text_field(wp_unslash($_REQUEST['redirect_to'])), sanitize_text_field(wp_unslash($_REQUEST['redirect_to'])), $user);//phpcs:ignore
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
        $login_nonce = Rsssl_Two_Fa_Authentication::create_login_nonce($user->ID);
        if (!$login_nonce) {
            $error = new WP_Error();
            $error->add(
                'login_nonce_creation_failed',
                __('Failed to create a login nonce.', 'really-simple-ssl')
            );
        }

        self::login_html(
            $user,
            $login_nonce['rsssl_key'],
            isset($_REQUEST['redirect_to']) ? sanitize_text_field(wp_unslash($_REQUEST['redirect_to'])) : '',
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
        $provider = self::sanitize_request_data('rsssl-provider', false, 'wp_unslash');
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

    private static function display_expired_onboarding_error(): void {
	    rsssl_load_template(
		    'expired.php',
		    array(
			    'message' => esc_html__('Your 2FA grace period expired. Please contact your site administrator to regain access and to configure 2FA.', 'really-simple-ssl'),
                ),
		    rsssl_path . 'assets/templates/two_fa/'
	    );
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
        // The variables needed for the onboarding screen.
        $onboarding_url = self::login_url(array('action' => 'rsssl_onboarding'), 'login_post');

        $provider = self::get_primary_provider_for_user($user);
        if ($provider) {
            $provider_class = get_class($provider);
        }

        $available_providers = self::get_available_providers_for_user($user);

        $interim_login = isset($_REQUEST['interim-login']); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $redirect_to = isset($_REQUEST['redirect_to']) ? sanitize_text_field(wp_unslash($_REQUEST['redirect_to'])) : '';
        $enabled_providers = Rsssl_Provider_Loader::get_enabled_providers_for_user($user);

        $ordered_array = array();
        foreach (self::WEIGHT as $key) {
            if (array_key_exists($key, $available_providers)) {
                $ordered_array[$key] = $available_providers[$key];
            }
        }
        wp_set_auth_cookie($user->ID, true);

        $available_providers = $ordered_array;

        // the first provider is the primary provider.
        $primary_provider = '';

        if (!empty($ordered_array)) {
            $shifted = array_shift($ordered_array);
            if ($shifted) {
                $primary_provider = get_class($shifted);
            }
        }

        $rememberme = (int)self::rememberme();

        if (!function_exists('login_header')) {
            // We really should migrate login_header() out of `wp-login.php` so it can be called from an includes file.
            include_once __DIR__ . '/function-login-header.php';
        }

        if (!function_exists('login_footer')) {
            // We really should migrate login_header() out of `wp-login.php` so it can be called from an includes file.
            include_once __DIR__ . '/function-login-footer.php';
        }

        // Check if the user is forced to use 2FA.
        $is_forced = Rsssl_Two_Factor_Settings::is_user_forced_to_use_2fa($user->ID);

        // Check if the user is in the grace period.
        $grace_period = Rsssl_Two_Factor_Settings::is_user_in_grace_period($user);

        //Add the styles for the two-factor authentication.
        add_action('login_enqueue_styles', array(__CLASS__, 'enqueue_onboarding_styles'));

        $uri = trailingslashit(rsssl_url) . 'assets/two-fa/rtl/two-fa-assets.min.js';
//		$backup_codes = Rsssl_Two_Factor_Settings::get_backup_codes( get_current_user_id() );
        add_filter('script_loader_tag', function ($tag, $handle) {
            if ($handle !== 'rsssl-profile-settings') {
                return $tag;
            }
            return str_replace(' src', ' type="module" src', $tag);
        }, 10, 2);
        // Check if the backup codes are available.
        wp_enqueue_script('rsssl-profile-settings', $uri, array(), rsssl_version, true);

        remove_filter('script_loader_tag', function ($tag, $handle) {
            if ($handle !== 'rsssl-profile-settings') {
                return $tag;
            }
            return str_replace(' src', ' type="module" src', $tag);
        }, 10, 2);
        $login_nonce = Rsssl_Two_Fa_Authentication::create_login_nonce($user->ID)['rsssl_key'];

        wp_localize_script('rsssl-profile-settings', 'rsssl_onboard', array(
            'nonce' => wp_create_nonce('wp_rest'),
            'root' => esc_url_raw(rest_url(self::REST_NAMESPACE)),
            'login_nonce' => $login_nonce,
            'redirect_to' => isset($_REQUEST['redirect_to']) ? sanitize_text_field(wp_unslash($_REQUEST['redirect_to'])) : '',
            'user_id' => $user->ID,
        ));

        // Let's show the onboarding screen.
        // Load the template.
        rsssl_load_template(
            'onboarding.php',
            array(
                'user' => $user,
                'login_nonce' => $login_nonce,
                'url' => $onboarding_url,
                'provider' => $provider,
                'redirect_to' => $redirect_to,
                'available_providers' => $available_providers,
                'interim_login' => $interim_login,
                'rememberme' => $rememberme,
                'primary_provider' => $primary_provider,
                'is_forced' => $is_forced,
                'grace_period' => $grace_period,
                'is_today' => Rsssl_Two_Factor_Settings::is_today($user),
                'skip_two_fa_url' => Rsssl_Two_Factor_Settings::rsssl_one_time_login_url($user->ID),
            ),
            rsssl_path . 'assets/templates/two_fa/'
        );
        wp_enqueue_script('rsssl-rest-settings');
        exit;
    }

    /**
     * Enqueues the RSSSL profile settings script.
     *
     * @return void
     */
    public static function enqueue_onboarding_scripts(): void
    {
        $uri = trailingslashit(rsssl_url) . 'assets/two-fa/rtl/two-fa-assets.min.js';
        $backup_codes = Rsssl_Two_Factor_Settings::get_backup_codes(get_current_user_id());
        // Check if the backup codes are available.
        wp_enqueue_script('rsssl-profile-settings', $uri, array(), rsssl_version, true);
    }

    public static function add_module_to_script($tag, $handle, $src)
    {
        // Change 'your-script-handle' to the handle used in wp_enqueue_script()
        if ('your-script-handle' === $handle) {
            // Add type="module" to the script tag
            $tag = '<script type="module" src="' . esc_url($src) . '"></script>';
        }
        return $tag;
    }

    /**
     * Enqueues the RSSSL profile settings stylesheet.
     *
     * @return void
     */
    public static function enqueue_onboarding_styles(): void
    {
        $uri = trailingslashit(rsssl_url) . 'assets/two-fa/rtl/two-fa-assets.min.css';
        wp_enqueue_style('rsssl-profile-settings', $uri, array(), rsssl_version);
    }
}

/**
 * Hook as soon as the file is required. Which is the plugins_loaded hook.
 * @see security/integrations.php
 */
$rsssl_two_factor_compat = new Rsssl_Two_Factor_Compat();
Rsssl_Two_Factor::add_hooks($rsssl_two_factor_compat);
