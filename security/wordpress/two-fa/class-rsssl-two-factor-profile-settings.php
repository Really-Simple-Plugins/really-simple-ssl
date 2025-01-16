<?php
/**
 * Holds the logic for the profile page.
 *
 * @package REALLY_SIMPLE_SSL
 */

namespace RSSSL\Security\WordPress\Two_Fa;

//require_once __DIR__ . '/class-rsssl-provider-loader.php';
//require_once __DIR__ . '/class-rsssl-parameter-validation.php';
//require_once __DIR__ . '/class-rsssl-parameter-validation.php';
//require_once __DIR__ . '/class-rsssl-two-factor-on-board-api.php';
ob_start();

use Exception;
use WP_User;

if (!class_exists('Rsssl_Two_Factor_Profile_Settings')) {
    /**
     * Class Rsssl_Two_Factor_Profile_Settings
     *
     * This class is responsible for handling the Two-Factor Authentication settings on the user profile page.
     *
     * @package REALLY_SIMPLE_SSL
     */
    class Rsssl_Two_Factor_Profile_Settings
    {

        /**
         * The available providers.
         *
         * @var array $available_providers An array to store the available providers.
         */
        private $available_providers = array();

        /**
         * The forced Two-Factor Authentication roles.
         *
         * @var array $forced_two_fa An array to store the forced Two-Factor Authentication roles.
         */
        private $forced_two_fa = array();

        /**
         * Constructor for the class.
         *
         * If the user is logged in, retrieve the user object and check if two-factor authentication is turned on for the user.
         * If two-factor authentication is enabled, add the necessary hooks.
         *
         * @return void
         */
        public function __construct()
        {
            if (is_user_logged_in()) {
                $user_id = get_current_user_id();
                $user = get_user_by('ID', $user_id);
                global $pagenow;

                if ('profile.php' === $pagenow || ('user-edit.php' === $pagenow && isset($_GET['user_id']))) {
                    if ($this->validate_two_turned_on_for_user($user)) {
                        add_action('admin_init', array($this, 'add_hooks'));
                    }
                }
            }
        }

        /**
         * Add hooks for user profile page.
         *
         * This method adds hooks to display the Two-Factor Authentication settings on user profile pages.
         *
         * @return void
         */
        public function add_hooks(): void
        {
            if (is_user_logged_in()) {
                $errors = Rsssl_Parameter_Validation::get_cached_errors(get_current_user_id());
                if (!empty($errors)) {
                    // We display the errors.
                    foreach ($errors as $error) {
                        add_settings_error(
                            'two-factor-authentication',
                            'rsssl-two-factor-authentication-error',
                            $error['message'],
                            $error['type']
                        );
                    }
                }
            }
            $error = Rsssl_Parameter_Validation::get_cached_errors(get_current_user_id());
            add_action('show_user_profile', array($this, 'show_user_profile'));
            add_action('edit_user_profile', array($this, 'show_user_profile'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
            add_action('personal_options_update', array($this, 'save_user_profile'));
            add_action('edit_user_profile_update', array($this, 'save_user_profile'));

            if (isset($_GET['profile'], $_GET['_wpnonce'])) {
                $profile = rest_sanitize_boolean(wp_unslash($_GET['profile']));
                if ($profile) {
                    Rsssl_Two_Factor_Email::set_user_status(get_current_user_id(), 'active');
                    Rsssl_Two_Factor_Totp::set_user_status(get_current_user_id(), 'disabled');
                }
            }
        }

        /**
         * Save the Two-Factor Authentication settings for the user.
         *
         * @param int $user_id The user ID.
         *
         * @return void
         */
        public function save_user_profile(int $user_id): void
        {
            // We check if the user owns the profile.
            if (!current_user_can('edit_user', $user_id)) {
                return;
            }

            if (isset($_POST['rsssl_two_fa_nonce']) && !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rsssl_two_fa_nonce'])), 'update_user_two_fa_settings')) {
                return;
            }

            if (isset($_POST['change_2fa_config_field'])) {
                // We sanitize the input needs to be a boolean.
                $reset_input = filter_var($_POST['change_2fa_config_field'], FILTER_VALIDATE_BOOLEAN);
                $this->maybe_the_user_resets_config($user_id, $reset_input);
                return;
            }

            $params = new Rsssl_Parameter_Validation();
            $params::validate_user_id($user_id);
            $user = get_user_by('ID', $user_id);
            $params::validate_user($user);

            if (!isset($_POST['two-factor-authentication'])) {
                // reset the user's 2fa settings.
                // Delete all 2fa related user meta.
                Rsssl_Two_Fa_Status::delete_two_fa_meta($user);
                // Set the rsssl_two_fa_last_login to now, so the user will be forced to use 2fa.
                update_user_meta($user->ID, 'rsssl_two_fa_last_login', gmdate('Y-m-d H:i:s'));
                // also make sure no lingering errpr messages are shown.
                Rsssl_Parameter_Validation::delete_cached_errors($user_id);
                return;
            }

            if (!isset($_POST['preferred_method'])) {
                return;
            }

            // now we check witch provider is selected from the $_POST.
            $params::validate_selected_provider($this->sanitize_method(sanitize_text_field(wp_unslash($_POST['preferred_method']))));
            $selected_provider = $this->sanitize_method(sanitize_text_field(wp_unslash($_POST['preferred_method'])));

            // if the selected provider is not then return.
            if (!$selected_provider) {
                return;
            }

            switch ($selected_provider) {
                case 'totp':
                    $current_status = Rsssl_Two_Factor_Settings::get_user_status('totp', $user_id);
                    if ('active' === $current_status) {
                        return;
                    }
                    if ((empty($_POST['two-factor-totp-authcode'])) || !isset($_POST['two-factor-totp-key'])) {
                        add_settings_error(
                            'two-factor-authentication',
                            'rsssl-two-factor-authentication-error',
                            __('Two-Factor Authentication for TOTP failed. No Authentication code provided, please try again.', 'really-simple-ssl'),
                            'error'
                        );
                        $params::cache_errors($user_id);
                        return;
                    }

                    $params::validate_auth_code(absint(wp_unslash($_POST['two-factor-totp-authcode'])));
                    $params::validate_key(sanitize_text_field(wp_unslash($_POST['two-factor-totp-key'])));
                    $auth_code = sanitize_text_field(wp_unslash($_POST['two-factor-totp-authcode']));
                    $key = sanitize_text_field(wp_unslash($_POST['two-factor-totp-key']));
                    if (Rsssl_Two_Factor_Totp::setup_totp($user, $key, $auth_code)) {
                        Rsssl_Two_Factor_Totp::set_user_status($user_id, 'active');
                        // We disable the email.
                        Rsssl_Two_Factor_Email::set_user_status($user_id, 'disabled');
                        // We generate the backup codes.
                        Rsssl_Two_Factor_Backup_Codes::generate_codes(
                            $user,
                            array(
                                'cached' => true,
                            )
                        );
                    } else {
                        add_settings_error(
                            'two-factor-authentication',
                            'rsssl-two-factor-authentication-error',
                            __('The Two-Factor Authentication setup for TOTP failed. Please try again.', 'really-simple-ssl'),
                            'error'
                        );
                    }
                    // We cache the errors.
                    $params::cache_errors($user_id);
                    break;
                case 'email':
                    $current_status = Rsssl_Two_Factor_Settings::get_user_status('email', $user_id);
                    if ('active' === $current_status) {
                        return;
                    }
                    $user = get_user_by('ID', $user_id);
                    // fetch current status of the user for the email method.
                    $status = Rsssl_Two_Factor_Settings::get_user_status('email', $user->ID);
                    if ('active' === $status) {
                        return;
                    }
                    if (Rsssl_Two_Factor_Email::get_instance()->validate_authentication($user)) {
                        // We set the user status to active.
                        Rsssl_Two_Factor_Email::set_user_status($user_id, 'active');
                        // We disable the TOTP.
                        Rsssl_Two_Factor_Totp::set_user_status($user_id, 'disabled');
                    } else {
                        add_settings_error(
                            'two-factor-authentication',
                            'rsssl-two-factor-authentication-error',
                            __('The Two-Factor Authentication setup for email failed. Please try again.', 'really-simple-ssl'),
                            'error'
                        );
                    }
                    break;
                case 'none':
                    // We disable the Two-Factor Authentication.
                    Rsssl_Two_Fa_Status::delete_two_fa_meta($user);
                    break;
                default:
                    break;
            }

            $params::cache_errors($user_id);
        }

        /**
         * Sanitize the input method.
         *
         * @param string $method The input method.
         *
         * @return string The sanitized input method. Defaults to 'email' if not found in the allowed methods.
         */
        private function sanitize_method(string $method): string
        {
            $methods = array('totp', 'email', 'none');

            return in_array($method, $methods, true) ? sanitize_text_field($method) : 'email';
        }

        /**
         * Display the user profile with Two-Factor Authentication settings.
         *
         * @param WP_User $user The user object.
         *
         * @return void
         * @throws Exception Throws an exception if the template file is not found.
         */
        public function show_user_profile(WP_User $user): void
        {
	        if ($user->ID !== get_current_user_id()) {
		        return;
	        }
            settings_errors('two-factor-authentication');
            settings_errors('rsssl-two-factor-authentication-error');
            Rsssl_Two_Factor_Totp::enqueue_qrcode_script();

            $available_providers = $this->available_providers;
            $forced = !empty(array_intersect($user->roles, $this->forced_two_fa));
            $one_enabled = 'onboarding' !== Rsssl_Two_Factor_Settings::get_login_action($user->ID);
            $providers = Rsssl_Provider_Loader::get_user_enabled_providers($user);
            $selected_provider = '';
            if ($one_enabled) {
                $selected_provider = Rsssl_Two_Factor_Settings::get_configured_provider($user->ID);
            }

            $backup_codes = Rsssl_Two_Factor_Settings::get_backup_codes($user->ID);
            $key = Rsssl_Two_Factor_Totp::generate_key();
            $totp_url = Rsssl_Two_Factor_Totp::generate_qr_code_url($user, $key);

            wp_nonce_field('update_user_two_fa_settings', 'rsssl_two_fa_nonce');

            $data = array(
                'key' => $key,
                'totp_url' => $totp_url,
                'backup_codes' => $backup_codes,
                'selected_provider' => $selected_provider,
                'one_enabled' => $one_enabled,
                'forced' => $forced,
                'available_providers' => $available_providers,
                'user' => $user,
            );

            $data_js = 'rsssl_profile.totp_data = ' . json_encode($data) . ';';

            wp_add_inline_script('rsssl-profile-settings', $data_js, 'after');

            // We load the needed template for the Two-Factor Authentication settings.
            rsssl_load_template(
                'profile-settings.php',
                compact(
                    'user',
                    'available_providers',
                    'providers',
                    'forced',
                    'one_enabled',
                    'selected_provider',
                    'backup_codes',
                    'totp_url',
                    'key'
                ),
                rsssl_path . 'assets/templates/two_fa/'
            );
        }

        /**
         * Validates if the Two-Factor Authentication is turned on for the user.
         *
         * @param WP_User $user The user object.
         *
         * @return bool Returns true if Two-Factor Authentication is turned on for the user, false otherwise.
         */
        private function validate_two_turned_on_for_user(WP_User $user): bool
        {
            // Get the setting for the system to check if it is turned on.
            $enabled_two_fa = rsssl_get_option('login_protection_enabled');
            $providers = Rsssl_Provider_Loader::get_enabled_providers_for_user($user);
            $option = rsssl_get_option('two_fa_forced_roles');
            $this->forced_two_fa = $option !== false ? $option : array();
            $this->available_providers = $providers;

            return $enabled_two_fa && !empty($providers);
        }

        /**
         * Enqueues the RSSSL profile settings script.
         *
         * @return void
         */
        public function enqueue_scripts(): void
        {
            $uri = trailingslashit(rsssl_url) . 'assets/two-fa/rtl/two-fa-assets.min.js';
            $backup_codes = Rsssl_Two_Factor_Settings::get_backup_codes(get_current_user_id());
            // We check if the backup codes are available.
            wp_enqueue_script('rsssl-profile-settings', $uri, array(), rsssl_version, true);
            wp_localize_script('rsssl-profile-settings', 'rsssl_profile', array(
                'backup_codes' => $backup_codes,
                'root' => esc_url_raw(rest_url(Rsssl_Two_Factor::REST_NAMESPACE)),
                'user_id' => get_current_user_id(),
                'redirect_to' => 'profile', //added this for comparison in the json output.
                'translatables' => [
                    'download_codes' => esc_html__('Download Backup Codes', 'really-simple-ssl'),
                    'keyCopied' => __('Key copied', 'really-simple-ssl'),
                    'keyCopiedFailed' => __('Could not copy text: ', 'really-simple-ssl')
                ]
            ));
        }

        /**
         * Enqueues the RSSSL profile settings stylesheet.
         *
         * @return void
         */
        public function enqueue_styles(): void
        {
            $uri = trailingslashit(rsssl_url) . 'assets/two-fa/rtl/two-fa-assets.min.css';
            wp_enqueue_style('rsssl-profile-settings', $uri, array(), rsssl_version);
        }

        /**
         * Checks if the user resets the configuration and actually reset everything.
         *
         * @param int $user_id The ID of the user.
         * @param $reset_input
         *
         * @return bool
         */
        private function maybe_the_user_resets_config(int $user_id, $reset_input): bool
        {
            $user = get_user_by('ID', $user_id);
            // If the reset is true, we do the reset.
            if ($reset_input && $user) {
                // We reset the user's Two-Factor Authentication settings.
                Rsssl_Two_Fa_Status::delete_two_fa_meta($user);
            }

            return $reset_input;
        }
    }
}