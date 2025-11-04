<?php

declare(strict_types=1);

namespace ReallySimplePlugins\RSS\Core\Features\Onboarding;

use ReallySimplePlugins\RSS\Core\Bootstrap\App;
use ReallySimplePlugins\RSS\Core\Services\CertificateService;
use ReallySimplePlugins\RSS\Core\Services\RelatedPluginService;
use ReallySimplePlugins\RSS\Core\Services\SettingsConfigService;

class OnboardingStepsGenerator
{
    public array $steps = [];
    private bool $proPluginEnabled;

    private App $app;
    private RelatedPluginService $pluginService;
    private SettingsConfigService $settingsService;
    private CertificateService $certificateService;

    public function __construct(App $app, RelatedPluginService $pluginService, SettingsConfigService $settingsService, CertificateService $certificateService)
    {
        $this->app = $app;
        $this->pluginService = $pluginService;
        $this->settingsService = $settingsService;
        $this->certificateService = $certificateService;

        $this->proPluginEnabled = defined('rsssl_pro');
    }

	public function generate(bool $isUpgradeFromFree = false): array
	{
		if ($isUpgradeFromFree) {
			$steps = [
				$this->activateLicenseStep(),
				$this->proStep(),
			];
		} else {
			$steps = [
				$this->activateSslStep(),
				$this->emailStep(),
				$this->essentialFeaturesStep(),
				$this->activateLicenseStep(),
				$this->relatedPluginsStep(),
				$this->proStep(),
			];
		}

		// Remove empty steps
		$steps = array_filter($steps);

		// Re-order keys to prevent issues after array_filter
		return array_values($steps);
	}

    /**
     * The activate SSL step include items related to SSL detection and
     * configuration, but only when the user is not upgrading from the free to
     * the pro version.
     */
    private function activateSslStep(): array
    {
        $items = [];

        if (strpos(site_url(), 'https://') === false) {
            $items[] = [
                'title' => esc_html__('You may need to login in again, have your credentials prepared.', 'really-simple-ssl'),
                'status' => 'inactive',
                'id' => 'login',
            ];
        }

        // Add single SSL certificate test-outcome item to the step
        $items[] = $this->getSslCertificateTestResultItem();

        return [
            'id' => 'activate_ssl',
            'title' => esc_html__('Welcome to Really Simple Security', 'really-simple-ssl'),
            'subtitle' => esc_html__('The onboarding wizard will help to configure essential security features in 1 minute! Select your hosting provider to start.', 'really-simple-ssl'),
            'items' => $items,
        ];
    }


    /**
     * Method is used for determining the single certificate status item based
     * on the detection outcome. This prevents stacking multiple certificate
     * specific notices at once.
     */
    private function getSslCertificateTestResultItem(): array
    {
        if ($this->certificateService->isValid()) {
            return [
                'title'  => esc_html__('An SSL certificate has been detected', 'really-simple-ssl'),
                'status' => 'success',
                'id'     => 'certificate',
            ];
        }

        if ($this->certificateService->detectionFailed()) {
            return [
                'title'  => esc_html__('Could not test certificate', 'really-simple-ssl') . ' ' . esc_html__('Automatic certificate detection is not possible on your server.', 'really-simple-ssl'),
                'status' => 'error',
                'id'     => 'certificate',
            ];
        }

        return [
            'title'  => esc_html__('No SSL certificate has been detected.', 'really-simple-ssl') . ' ' . esc_html__('Please refresh the SSL status if a certificate has been installed recently.', 'really-simple-ssl'),
            'status' => 'error',
            'id'     => 'certificate',
        ];
    }

    /**
     * The email step is used to verify the email address of the user and to
     * send a test email to confirm that email is correctly configured on their
     * site. But only when the user is not upgrading from the free to the pro
     * version of the plugin.
     */
    private function emailStep(): array
    {
        return [
            'id' => 'email',
            'title' => esc_html__('Verify your email', 'really-simple-ssl'),
            'subtitle' => esc_html__('Really Simple Security will send email notifications and security warnings from your server. We will send a test email to confirm that email is correctly configured on your site. Look for the confirmation button in the email.', 'really-simple-ssl'),
            'button' => esc_html__('Save and continue', 'really-simple-ssl'),
        ];
    }

    /**
     * The essential features step prompts user with recommended features. But
     * only if the user is not upgrading from free to pro. If a user is using
     * the free version of the plugin some pro features are included in the
     * step as well, this is done for upsell purposes.
     */
    private function essentialFeaturesStep(): array
    {
        $subtitle = esc_html__('Instantly configure these essential features.', 'really-simple-ssl');

        if ($this->proPluginEnabled === false) {
            $subtitle .= ' ' . sprintf(
                    wp_kses_post(__('Please %sconsider upgrading to Pro%s to enjoy all simple and performant security features.', 'really-simple-ssl')),
                    '<a href="' . $this->app->config->getUrl('uri.rsp.upgrade_from_free') . '" target="_blank">',
                    '</a>'
                );
        }

        // If pro is not enabled we do some upselling with premium features
        $includePremiumSettingsForUpsellPurposes = ($this->proPluginEnabled === false);

        return [
            'id' => 'features',
            'title'  => esc_html__('Essential security', 'really-simple-ssl'),
            'subtitle' => $subtitle,
            'items' => $this->settingsService->getRecommendedSettings($includePremiumSettingsForUpsellPurposes),
            'button' => esc_html__('Enable', 'really-simple-ssl'),
        ];
    }

    /**
     * In this step we ask the user to save and activate their license. Only
     * needed is the pro version of the plugin is active.
     */
    private function activateLicenseStep(): array
    {
        /// No need for a license step if freemium is enabled
        if ($this->proPluginEnabled === false) {
            return [];
        }

        return [
            'id' => 'activate_license',
            'title' => esc_html__('Activate your license key', 'really-simple-ssl'),
            'subtitle' => '',
            'items' => [
                'type' => 'license',
            ],
            'button' => esc_html__('Activate', 'really-simple-ssl'),
            'value' => '',
        ];
    }

    /**
     * This step is always included. If a user is using the free version these
     * recommended settings are disabled (greyed-out) for upsell purposes.
     */
    private function proStep(): array
    {
        return [
            'id' => 'pro',
            'title' => 'Really Simple Security Pro',
            'subtitle' => esc_html__('Heavyweight security features, in a lightweight performant plugin from Really Simple Plugins. Get started with below features and get the latest and greatest updates for peace of mind!', 'really-simple-ssl'),
            'items' => $this->settingsService->getRecommendedProSettings(),
            'button' => esc_html__('Install', 'really-simple-ssl'),
        ];
    }

    /**
     * This step will prompt users with other plugins of Really Simple Plugins.
     * Only included if the user is not upgrading from free to pro, then this
     * step was already done in the onboarding of the free version.
     */
    private function relatedPluginsStep(): array
    {
        return [
            'id' => 'plugins',
            'title' => esc_html__('We think you will like this', 'really-simple-ssl'),
            'subtitle' => esc_html__('Really Simple Plugins is also the author of the below privacy-focused plugins including consent management and legal documents!', 'really-simple-ssl'),
            'items' => $this->pluginService->getOnboardingConfig(),
            'button' => esc_html__('Install', 'really-simple-ssl'),
        ];
    }
}