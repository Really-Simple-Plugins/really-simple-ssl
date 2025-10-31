<?php
namespace ReallySimplePlugins\RSS\Core\Features\Onboarding;

use ReallySimplePlugins\RSS\Core\Features\AbstractLoader;

class OnboardingLoader extends AbstractLoader
{
    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        if (rsssl_user_can_manage() === false) {
            return false;
        }

        $onboardingQueueHasItems = $this->hasOnboardingQueueItems();
        if ($onboardingQueueHasItems) {
            return true; // To process the items
        }

	    // Enable if user clicked "Activate SSL" button
	    // This allows the modal to fetch data when explicitly opened by user
	    // after dismissal
        if ($this->app->requestBody->getBoolean('activateSSLClicked')) {
            return true;
        }

        // Enable if we're in the Let's Encrypt wizard context
        // The wizard needs onboarding data for the activate SSL step
        if ($this->requestIsLetsEncryptRequest()) {
            return true;
        }

        return (
            (bool) get_option('rsssl_show_onboarding', false) === true
            && (bool) get_option('rsssl_onboarding_dismissed', false) === false
        );
    }

    /**
     * @inheritDoc
     */
    public function inScope(): bool
    {
        $onboardingQueueHasItems = $this->hasOnboardingQueueItems();
        if ($onboardingQueueHasItems) {
            return true; // To process the items
        }

        return rsssl_admin_logged_in() && ($this->userIsOnDashboard() || $this->requestIsRestRequest());
    }

    /**
     * Returns true when the onboarding queue has items. For example if a user
     * has chosen to install plugins, these actions are queued and should be
     * processed later. Therefor this method is used to enable the feature
     * for request processing.
     */
    private function hasOnboardingQueueItems(): bool
    {
        $items = get_option($this->app->config->getString('env.onboarding.queue_option'), []);
        return !empty($items);
    }

	/**
	 * Check if the current request is in the Let's Encrypt wizard context.
	 * The Let's Encrypt wizard uses the onboarding component for the activate
	 * SSL step, so we need to enable the onboarding feature when in this context.
	 *
	 * @internal We access $_GET and $_SERVER superglobals directly for read-only
	 * context detection. These values are only used for comparison checks, not
	 * output or database queries, so sanitization and nonce verification are not
	 * required. The phpcs warnings are intentionally suppressed.
	 */
	protected function requestIsLetsEncryptRequest(): bool
	{
		// Check GET parameters for direct page loads
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if (isset($_GET['letsencrypt']) && $_GET['letsencrypt'] === '1'
		    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		    && isset($_GET['page']) && $_GET['page'] === 'really-simple-security') {
			return true;
		}

		// Check referer for REST API requests from the Let's Encrypt wizard
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$referer = ($_SERVER['HTTP_REFERER'] ?? '');
		if (strpos($referer, 'letsencrypt=1') !== false
		    && strpos($referer, 'page=really-simple-security') !== false) {
			return true;
		}

		return false;
	}


}