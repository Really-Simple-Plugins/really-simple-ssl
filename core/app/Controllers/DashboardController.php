<?php

declare(strict_types=1);

namespace ReallySimplePlugins\RSS\Core\Controllers;

use ReallySimplePlugins\RSS\Core\Bootstrap\App;
use ReallySimplePlugins\RSS\Core\Interfaces\ControllerInterface;

class DashboardController implements ControllerInterface
{
    protected App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function register(): void
    {
        // Redirect on the activation hook, but do it after anything else.
        add_action('rss_core_activation', [$this, 'maybeRedirectToDashboard'], 9999);
    }

    /**
     * Redirect to dashboard page on activation, but only if the user manually
     * activated the plugin via the plugins overview. React will handle
     * redirect to onboarding if needed.
     *
     * @param string $pageSource The page where the activation was triggered,
     * usually 'plugins.php' or 'update.php'.
     */
    public function maybeRedirectToDashboard(string $pageSource = ''): void
    {
        if ($pageSource !== 'plugins.php' && $pageSource !== 'update.php') {
            return;
        }

        wp_safe_redirect($this->app->config->getUrl('env.plugin.dashboard_url'));
        exit;
    }
}