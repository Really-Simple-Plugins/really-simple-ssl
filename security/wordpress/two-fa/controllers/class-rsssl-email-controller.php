<?php

namespace RSSSL\Security\WordPress\Two_Fa\Controllers;


use RSSSL\Security\WordPress\Two_Fa\Rsssl_Two_Factor_Settings;
use WP_Error;
use Exception;
use RSSSL\Pro\Security\WordPress\Limitlogin\Rsssl_IP_Fetcher;
use RSSSL\Security\WordPress\Two_Fa\Providers\Rsssl_Two_Factor_Email;
use RSSSL\Security\WordPress\Two_Fa\Models\Rsssl_Request_Parameters;
use RSSSL\Security\WordPress\Two_Fa\Rsssl_Two_Fa_Authentication;
use WP_REST_Request;
use WP_REST_Response;

final class Rsssl_Email_Controller extends Rsssl_Abstract_Controller
{
    protected const METHOD = 'POST';
    protected const FEATURE_ROUTE = '/two-fa';

    protected string $namespace;

    public function __construct($namespace, $version, $featureVersion)
    {
        parent::__construct($namespace, $version, $featureVersion);
        add_action('rest_api_init', array($this, 'register_api_routes'));
    }

	/**
	 * Registers the REST API routes for the email controller.
	 *
	 * @return void
	 * @throws Exception
	 */
    public function register_api_routes(): void
    {
        $this->route($this->namespace,
            self::METHOD,
            'save_default_method_email',
            array($this, 'set_as_email'),
	        null,
            $this->build_args(array('user_id', 'login_nonce', 'provider'), array('redirect_to'))
        );
        $this->route($this->namespace,
            self::METHOD,
            'save_default_method_email_profile',
            array($this, 'set_profile_email'),
	        null,
            $this->build_args(array('user_id', 'login_nonce', 'provider'), array('redirect_to'))
        );
        $this->route($this->namespace,
            self::METHOD,
            'validate_email_setup',
            array($this, 'validate_email_setup'),
            array($this, 'permission_callback_login_actions'),
            $this->build_args(array('provider', 'user_id', 'login_nonce', 'token'), array('redirect_to'))
        );
        $this->route($this->namespace,
            self::METHOD,
            'resend_email_code',
            array($this, 'resend_email_code'),
            array($this, 'permission_callback_login_actions'),
            $this->build_args(array('user_id', 'login_nonce', 'provider'), array('profile'))
        );
    }

    ###############################
    # All callback functions here #
    ###############################

    /**
     * Sets the profile email for a user.
     *
     * @param WP_REST_Request $request The REST request object.
     *
     * @return WP_REST_Response The REST response object.
     */
    public function set_profile_email(WP_REST_Request $request): WP_REST_Response
    {
        $parameters = new Rsssl_Request_Parameters($request);

        try {
            $user = $this->check_login_and_get_user($parameters->user_id, $parameters->login_nonce);
        } catch (Exception $e) {
            return new WP_REST_Response(['error' => $e->getMessage()], 403);
        }
        // Check if the provider.
        if ('email' !== $parameters->provider) {
            return new WP_REST_Response(array('error' => 'Invalid provider'), 401);
        }

        // Finally redirect the user to the redirect_to page with a response.
        return $this->start_email_validation($user->ID, $parameters->redirect_to, $parameters->profile);
    }

    /**
     * Sets the user provider as email and redirects the user to the specified page.
     *
     * @param WP_REST_Request $request The REST request object.
     *
     * @return WP_REST_Response The REST response object if user is not logged in or provider is invalid.
     */
    public function set_as_email(WP_REST_Request $request): WP_REST_Response
    {
        $parameters = new Rsssl_Request_Parameters($request);

        // Verify the user and login nonce.
        try {
            $user = $this->check_login_and_get_user($parameters->user_id, $parameters->login_nonce);
        } catch (Exception $e) {
            return new WP_REST_Response(['error' => $e->getMessage()], 403);
        }

        // Check if the provider.
        if ('email' !== $parameters->provider) {
	        return new WP_REST_Response(array('error' => __('Invalid provider', 'really-simple-ssl')), 401);
        }

        // Finally redirect the user to the redirect_to page with a response.
        return $this->start_email_validation($user->ID, $parameters->redirect_to, $parameters->profile);
    }


    /**
     * Validates the email setup for a user.
     *
     * This function handles the validation of the email setup process. It checks the provided token
     * and updates the user's two-factor authentication status accordingly. If the token is invalid,
     * it resets the user's two-factor authentication settings and logs the user out.
     *
     * @param WP_REST_Request $request The REST request object containing the necessary parameters.
     *
     * @return WP_REST_Response The REST response object indicating the result of the validation process.
     */
    public function validate_email_setup(WP_REST_Request $request): WP_REST_Response
    {
        // Extract parameters from the request.
        $parameters = new Rsssl_Request_Parameters($request);

        // Check if the provider is 'email'.
        if ('email' !== $parameters->provider) {
            return new WP_REST_Response(array('error' => 'Invalid provider'), 401);
        }

        try {
            $user = $this->check_login_and_get_user($parameters->user_id, $parameters->login_nonce);
        } catch (Exception $e) {
            return new WP_REST_Response(['error' => $e->getMessage()], 403);
        }


        // Validate the provided token.
        if (!Rsssl_Two_Factor_Email::get_instance()->validate_token($user->ID, self::sanitize_token($parameters->token))) {
            // Reset all the settings if the token is invalid.
            Rsssl_Two_Factor_Email::set_user_status($user->ID, 'open');
            // Log out the user.
            wp_logout();

            return new WP_REST_Response(array('error' => __('Code was was invalid, try "Resend Code"', 'really-simple-ssl')), 401);
        }

        // Mark all other providers as inactive.
        self::set_active_provider($user->ID, 'email');

        // Authenticate the user and redirect them to the specified URL.
        return $this->authenticate_and_redirect($user->ID, $parameters->redirect_to);
    }

    /**
     * Resends the email verification code for a user.
     *
     * @param WP_REST_Request $request The REST request object.
     * @return WP_REST_Response The REST response object.
     */
    public function resend_email_code( WP_REST_Request $request ): WP_REST_Response {
        $parameters = new Rsssl_Request_Parameters($request);

        // Verify the user and login nonce.
        try {
            $user = $this->check_login_and_get_user($parameters->user_id, $parameters->login_nonce);
        } catch (Exception $e) {
            return new WP_REST_Response(['error' => $e->getMessage()], 403);
        }

        // Sanitize and verify the provider.
        $provider = sanitize_text_field($parameters->provider);
        if ('email' !== $provider) {
            return new WP_REST_Response(['error' => __('Invalid provider', 'really-simple-ssl')], 400);
        }

        // Determine email 2FA status for this user.
	    $email_status = get_user_meta($parameters->user_id, 'rsssl_two_fa_status_email', true ) ?? 'open';
        $login_action = Rsssl_Two_Factor_Settings::get_login_action($parameters->user_id);

		// if the status has an empty value, set it to 'open'
	    if (empty($email_status)) {
		    $email_status = 'open';
	    }

	    if ('active' !== $email_status && !('open' === $email_status && 'onboarding' === $login_action)) {
		    return new WP_REST_Response([
			    'error' => __('Email authentication is not active for this user', 'really-simple-ssl')
		    ], 403);
	    }

        // Generate and send a new token.
        try {
            Rsssl_Two_Factor_Email::get_instance()->generate_and_email_token($user, (bool) $parameters->profile);
        } catch (WP_Error $e) {
            return new WP_REST_Response(['error' => $e->get_error_message()], 500);
        }

        return new WP_REST_Response(
            ['message' => __('A verification code has been sent to the email address associated with your account.', 'really-simple-ssl')],
            200
        );
    }


    ###############################
    # All support functions here  #
    ###############################

    /**
     * Starts the process of email validation for a user.
     *
     * @param int $user_id The ID of the user for whom the email validation process needs to be started.
     * @param string $redirect_to The URL to redirect the user after the email validation process. Default is an empty string.
     *
     * @return WP_REST_Response The REST response object.
     */
    private function start_email_validation(int $user_id, string $redirect_to = '', $profile = false): WP_REST_Response
    {
        $redirect_to = $redirect_to ?: home_url();
        $user = get_user_by('id', $user_id);
        // Sending the email with the code.
        Rsssl_Two_Factor_Email::get_instance()->generate_and_email_token($user, $profile);
        $token = get_user_meta($user_id, Rsssl_Two_Factor_Email::RSSSL_TOKEN_META_KEY, true);
        if ($redirect_to === 'profile') {
            return new WP_REST_Response(array('token' => $token, 'validation_action' => 'validate_email_setup'), 200);
        }
        return new WP_REST_Response(array('token' => $token, 'redirect_to' => $redirect_to, 'validation_action' => 'validate_email_setup'), 200);
    }

    /**
     * Sanitizes a token.
     *
     * @param string $token The token to sanitize.
     * @param int $length The expected length of the token. Default is 0.
     *
     * @return string|false The sanitized token, or false if the length is invalid.
     */
    public static function sanitize_token(string $token, int $length = 0)
    {
        $code = wp_unslash($token);
        $code = preg_replace('/\s+/', '', $code);

        // Maybe validate the length.
        if ($length && strlen($code) !== $length) {
            return false;
        }

        return (string)$code;
    }

    public function permission_callback_login_actions(WP_REST_Request $request) {
        $parameters = new Rsssl_Request_Parameters($request);
        $user_id = $parameters->user_id;
        $login_nonce = $parameters->login_nonce;

        // Ensure the login nonce is a string.
        if (!is_string($login_nonce)) {
            return new WP_Error(
                'rest_forbidden',
                esc_html__('Access denied.', 'really-simple-ssl'),
                array('status' => 403)
            );
        }

        // Use IP fetcher if available.
        if (class_exists('RSSSL\Pro\Security\WordPress\Limitlogin\Rsssl_IP_Fetcher')) {
            $ip_array_found = (new Rsssl_IP_Fetcher)->get_ip_address();
            $ip_address = $ip_array_found[0] ?? $_SERVER['REMOTE_ADDR'];
        } else {
            // Fallback: use REMOTE_ADDR.
            $ip_address = $_SERVER['REMOTE_ADDR'];
        }

        // Validate the IP address.
        if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
            return new WP_Error(
                'rest_forbidden',
                esc_html__('Access denied.', 'really-simple-ssl'),
                array('status' => 403)
            );
        }

        // Rate limiting: build a transient key based on IP and route.
        $route = $request->get_route();
        $transient_key = 'rsssl_rate_limit_' . md5($ip_address . $route);
        $attempts = get_transient($transient_key);
        if ($attempts === false) {
            $attempts = 0;
        }

        // Limit to 5 attempts.
        if ($attempts >= 5) {
            return new WP_Error(
                'rest_forbidden',
                esc_html__('Too many attempts. Please try again later.', 'really-simple-ssl'),
                array('status' => 429)
            );
        }

        // Verify the login nonce and that the user exists.
        if (!Rsssl_Two_Fa_Authentication::verify_login_nonce($user_id, $login_nonce)
            || !get_user_by('id', $user_id)
        ) {
            // Increment the attempt count.
            set_transient($transient_key, $attempts + 1, 10 * MINUTE_IN_SECONDS);
            return new WP_Error(
                'rest_forbidden',
                esc_html__('Access denied.', 'really-simple-ssl'),
                array('status' => 403)
            );
        }

        // Reset the rate-limit on successful validation.
        delete_transient($transient_key);
        return true;
    }
}