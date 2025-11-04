<?php
namespace ReallySimplePlugins\RSS\Core\Features\Onboarding;

use ReallySimplePlugins\RSS\Core\Traits\HasNonces;
use ReallySimplePlugins\RSS\Core\Bootstrap\App;
use ReallySimplePlugins\RSS\Core\Services\EmailService;
use ReallySimplePlugins\RSS\Core\Support\Helpers\Storage;
use ReallySimplePlugins\RSS\Core\Interfaces\FeatureInterface;
use ReallySimplePlugins\RSS\Core\Services\CertificateService;
use ReallySimplePlugins\RSS\Core\Services\SecureSocketsService;
use ReallySimplePlugins\RSS\Core\Support\Utility\StringUtility;
use ReallySimplePlugins\RSS\Core\Services\RelatedPluginService;
use ReallySimplePlugins\RSS\Core\Services\SettingsConfigService;

class OnboardingController implements FeatureInterface
{
    use HasNonces;

    private App $app;
    private EmailService $emailService;
    private OnboardingFeatureService $service;
    private SecureSocketsService $sslService;
    private RelatedPluginService $pluginService;
    private SettingsConfigService $settingsService;
    private CertificateService $certificateService;

    public function __construct(App $app, OnboardingFeatureService $service, SecureSocketsService $sslService, EmailService $emailService, RelatedPluginService $pluginService, SettingsConfigService $settingsService, CertificateService $certificateService)
    {
        $this->app = $app;
        $this->service = $service;
        $this->sslService = $sslService;
        $this->emailService = $emailService;
        $this->pluginService = $pluginService;
        $this->settingsService = $settingsService;
        $this->certificateService = $certificateService;
    }

    public function register(): void
    {
        add_filter('rsssl_run_test', [$this, 'processOnboardingTest'], 10, 3);
        add_filter('rsssl_do_action', [$this, 'processOnboardingAction'], 10, 3);
        add_action($this->app->config->getString('env.onboarding.queue_event'), [$this, 'processQueuedEvent']);
    }

    /**
     * Method processes the onboarding request for SSL activation. The responses
     * are validated by the {@see rsssl_run_test} filter.
     * @return array|bool
     */
    public function processOnboardingTest(array $response, string $action, array $data)
    {
        switch($action) {
            case 'activate_ssl':
                $data['is_rest_request'] = true;
                $response = $this->sslService->activateSSL($data);
                break;
            case 'activate_ssl_networkwide':
                $response = $this->service->processMultisiteActivationStep();
                break;
            default:
                return $response;
        }

        return $response;
    }

    /**
     * Method to dynamically parse onboarding actions. The action is parsed to
     * PascalCase and if it is an onboarding action a dedicated method is
     * called based on the format: process{OnboardingAction}Action. These
     * methods all have access to the Storage object which contains all data
     * of the request. Each method cán use it, but its not mandatory of course.
     *
     * @uses processOnboardingDataAction, processGetModalStatusAction
     * @uses processDismissModalAction, processOverrideSslDetectionAction
     * @uses processUpdateEmailAction, processActivateAction
     * @uses processDownloadAction
     */
    public function processOnboardingAction(array $response, string $action, array $data): array
    {
        $actionableMethod = 'process' . StringUtility::snakeToPascalCase($action) . 'Action';

        // Current action is not one we want to process
        if (method_exists($this, $actionableMethod) === false) {
            return $response;
        }

        // Method exists. Try to execute and return the response.
        try {
            $storage = new Storage($data);
            return $this->$actionableMethod($storage);
        } catch (\Exception $exception) {
            return array_merge($response, [
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Resets onboarding state when user clicks the "Activate SSL" button
     * after dismissal.
     *
     * When a user dismisses the onboarding modal and later clicks the
     * "Activate SSL" button, we need to reset the onboarding state options
     * (rsssl_show_onboarding and rsssl_onboarding_dismissed) to allow the
     * onboarding flow to proceed.
     *
     * The OnboardingLoader has a bypass that loads the Controller when it
     * detects the activateSSLClicked flag in the request body. Once loaded,
     * we reset the options here at the start of processOnboardingDataAction
     * so the onboarding state is consistent for the rest of the request and
     * future requests.
     *
     * We can't split this into two separate actions (one to reset, one to
     * fetch data) because a separate reset action without the
     * activateSSLClicked flag would be blocked by the Loader checking the
     * dismissed state.
     *
     * @return void
     */
    private function onActivateSslClick(): void
    {
        $this->service->resetOnboarding();
    }

    /**
     * Two possibilities:
     * - a new install: show activation notice, and process onboarding
     * - an upgrade to 6. Only show new features.
     * @internal action: onboarding_data
     * @throws \RuntimeException
     */
    protected function processOnboardingDataAction(Storage $data): array
    {
        $nonce = $data->getString('nonce');
        if ($this->verifyNonce($nonce, 'rsssl_nonce') === false) {
            throw new \RuntimeException(esc_html__('Nonce validation failed', 'really-simple-ssl'));
        }

        // Reset onboarding state if user clicked "Activate SSL" after dismissal
        if ($data->getBoolean('activateSSLClicked')) {
            $this->onActivateSslClick();
        }

	    // For an upgrade from free, we should check the rsssl_free_deactivated
	    // option. When upgrading from Pro from Free, rsssl_deactivate_alternate
	    // is called in the Free plugin. Therefore, we have to check this option.
	    // This is not something we can easily change, because the free plugin has
	    // to be updated before we can check this in Pro.
	    $isUpgradeFromFree = get_option('rsssl_free_deactivated');
	    delete_option('rsssl_free_deactivated');

        $stepsGenerator = $this->app->make(OnboardingStepsGenerator::class);
        $onboardingSteps = $stepsGenerator->generate($isUpgradeFromFree);

        //if the user called with a refresh action, clear the cache
        if ($data->getBoolean('forceRefresh')) {
            delete_transient('rsssl_certinfo');
        }

        return [
            'steps' => $onboardingSteps,
            'ssl_enabled' => rsssl_get_option('ssl_enabled'),
            'ssl_detection_overridden' => get_option('rsssl_ssl_detection_overridden'),
            'certificate_valid' => $this->certificateService->isValid(),
            'networkwide' => (is_multisite() && rsssl_is_networkwide_active()),
            'network_activation_status' => get_site_option('rsssl_network_activation_status'),
            'rsssl_upgraded_from_free' => $isUpgradeFromFree,
        ];
    }

    /**
     * Method determines if the onboarding modal should be shown
     * @internal action: get_modal_status
     */
    protected function processGetModalStatusAction(Storage $data): array
    {
        return [
            'dismissed' => ($this->service->showOnboardingModal() === false),
        ];
    }

    /**
     * Method processes the user action to dismiss the onboarding modal. It will
     * trigger the event to process any queued items immediately.
     * @internal action: dismiss_modal
     */
    protected function processDismissModalAction(Storage $data): array
    {
        $updated = update_option('rsssl_onboarding_dismissed', $data->getBoolean('dismiss'), false);

        if (!empty($this->service->getQueuedItems())) {
            $this->service->manuallyProcessQueueNow();
        }

        return [
            'success' => $updated,
        ];
    }

    /**
     * Update SSL detection overridden option
     * @internal action: override_ssl_detection
     */
    protected function processOverrideSslDetectionAction(Storage $data): array
    {
        if ($data->getBoolean('overrideSSL')) {
            $success = update_option('rsssl_ssl_detection_overridden', true, false);
        } else {
            $success = delete_option('rsssl_ssl_detection_overridden');
        }

        return [
            'success'=> $success
        ];
    }

    /**
     * Method processes the given email, if it is valid we send a verification
     * mail. If the user choose to receive tips&tricks then we add them to
     * our mailing list as well.
     * @internal action: update_email
     */
    protected function processUpdateEmailAction(Storage $data): array
    {
        $email = $data->getEmail('email');

        // Abort.
        if (is_email($email) === false) {
            return [
                'success' => false,
            ];
        }

        rsssl_update_option('send_notifications_email', 1);

        if ($data->getBoolean('includeTips')) {
            $this->emailService->addEmailToMailingList($email);
        }

        $this->emailService->setEmail($email);
        return $this->emailService->sendVerificationMail();
    }

    /**
     * Method processes the download action for a related plugin. It does not
     * immediately download this related plugin, but it adds it to a queue that
     * is processed on the next page load. This prevents users breaking the
     * process by refreshing the page (for example)
     * @internal action: download
     */
    protected function processDownloadAction(Storage $data): array
    {
        $this->service->queueOnboardingItem(
            $data->getTitle('id'),
            'download'
        );

        return [
            'next_action' => 'activate',
            'success' => true,
        ];
    }

    /**
     * Method processes the activation action for a related plugin. It does not
     * immediately activate this related plugin, but it adds it to a queue that
     * is processed on the next page load. This prevents users breaking the
     * process by refreshing the page (for example)
     * @internal action: activate
     */
    protected function processActivateAction(Storage $data): array
    {
        $this->service->queueOnboardingItem(
            $data->getTitle('id'),
            'activate'
        );

        return [
            'next_action' => 'completed',
            'success' => true,
        ];
    }

    /**
     * Process the plugins to download/activate queue
     */
    public function processQueuedEvent(): void
    {
        $queuedItems = $this->service->getQueuedItems();

        foreach ($queuedItems as $key => &$item) {
            if (!isset($item['status'], $item['action']) || $item['status'] !== 'pending') {
                continue;
            }

            // Mark as processing
            $item['status'] = 'processing';
            $this->service->updateQueuedItems($queuedItems);

            $this->pluginService->setPluginConfigBySlug($item['item_id']);
            $success = $this->pluginService->executeAction(
                sanitize_text_field($item['action'])
            );

            // Update status
            $item['status'] = $success ? 'completed' : 'failed';
            $item['completed'] = time();
        }

        $this->service->updateQueuedItems($queuedItems);
        $this->service->cleanupQueuedItems();
    }

}