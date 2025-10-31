<?php

declare(strict_types=1);

namespace ReallySimplePlugins\RSS\Core\Traits;

use ReallySimplePlugins\RSS\Core\Bootstrap\App;

trait HasAllowlistControl
{

    /**
     * Check if the current code execution allows access to the admin area.
     * This is the case when:
     * - user is logged in and has manage_security capability
     * - this is a REST API request and user is logged in
     * - this is a WPCLI request
     * - this is a cron request
     *
     * This ensures that auto updates can run, and cron jobs can complete.
     */
    public function adminAccessAllowed(): bool
    {
        $wpcli = defined( 'WP_CLI' ) && WP_CLI;
        $currentUserCanVisitAdmin = ((is_admin() || is_network_admin()) && current_user_can('manage_security'));

        return $currentUserCanVisitAdmin || $this->restRequestIsAllowed() || wp_doing_cron() || $wpcli;
    }

    /**
     * Check if the current request is authenticated, for a REST API request.
     * This is the case when:
     * - The request URI is set and contains the plugin namespace
     * AND
     *  - The callback URL is still active, and the request URI contains the callback URL
     *      OR
     *  - The user is logged in and has the 'manage_security' capability
     *
     * @internal Ignore the phpcs errors for this method, as they are false
     * positives. We do not actually use the $_GET or $_SERVER variables
     * directly, but we need to check if they are set and contain the
     * expected values.
     *
     * @todo Name of this method is not entirely accurate, consider renaming
     */
    public function restRequestIsAllowed(): bool
    {
        $pluginNamespace = App::config()->getString('env.http.namespace');

        $validWpJsonRequest = (
            isset($_SERVER['REQUEST_URI'])
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            && (strpos($_SERVER['REQUEST_URI'], $pluginNamespace) !== false)
        );

        $validPlainPermalinksRequest = (
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            isset($_GET['rest_route'])
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
            && (strpos($_GET['rest_route'], $pluginNamespace) !== false)
        );

        if ($validWpJsonRequest === false && $validPlainPermalinksRequest === false) {
            return false;
        }

        return is_user_logged_in() && current_user_can('manage_security');
    }

    /**
     * Check if the current user has the capability to manage the plugin.
     * This is the case when:
     * - The user is logged in and has the 'manage_security' capability
     * - This is a REST API request and the user is logged in
     * - This is a WPCLI request
     *
     * @internal This replaces Helper::user_can_manage()
     */
    public function userCanManage(): bool
    {
        // During activation, we need to allow access
        if (get_option('rss_core_activation_flag')) {
            return true;
        }

        if (defined('WP_CLI') && WP_CLI) {
            return true;
        }

        if ($this->restRequestIsAllowed()) {
            return true;
        }

        return current_user_can('manage_security');
    }
}