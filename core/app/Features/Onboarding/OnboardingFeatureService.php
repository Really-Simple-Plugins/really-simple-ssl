<?php

declare(strict_types=1);

namespace ReallySimplePlugins\RSS\Core\Features\Onboarding;

use ReallySimplePlugins\RSS\Core\Bootstrap\App;
use ReallySimplePlugins\RSS\Core\Services\GlobalOnboardingService;

/**
 * Business logic for the onboarding feature.
 * Queue management and feature-specific onboarding logic.
 * Extends {@see GlobalOnboardingService} to inherit global onboarding methods.
 */
class OnboardingFeatureService extends GlobalOnboardingService
{
    protected App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * Helper method to check if we should show the onboarding.
     * @todo: I guess the order of the checks is important. If not, the code
     * can be optimized a bit.
     */
    public function showOnboardingModal(): bool
    {
        $userDismissedOnboarding = (bool) get_option('rsssl_onboarding_dismissed');
        if ($userDismissedOnboarding) {
            return false;
        }

        if ($this->wpConfigNeedsFixing()) {
            return false; // First fix wp-config
        }

        if ($this->multisiteActivationNotCompleted()) {
            return true; // Finish activation with the onboarding modal
        }

        $sslIsEnabled = (bool) rsssl_get_option('ssl_enabled');
        $showOnboardingAfterUpdateOrUpgrade = (bool) get_option('rsssl_show_onboarding');
        if ($sslIsEnabled && ($showOnboardingAfterUpdateOrUpgrade === false)) {
            return false; // No onboarding if ssl already enabled, except after upgrade
        }

        $constantDismissedOnboarding = (defined('RSSSL_DISMISS_ACTIVATE_SSL_NOTICE') && RSSSL_DISMISS_ACTIVATE_SSL_NOTICE);
        if ($constantDismissedOnboarding) {
            return false;
        }

        if (rsssl_user_can_manage() === false) {
            return false;
        }

        return true;
    }

    /**
     * For multisite environments, check if the activation process was
     * started but not completed.
     *
     * @return bool True if multisite activation is incomplete
     */
    private function multisiteActivationNotCompleted(): bool
    {
        return (is_multisite() && RSSSL()->multisite->ssl_activation_started_but_not_completed());
    }

    /**
     * Check if wp-config needs fixing before showing onboarding.
     *
     * @todo: This check seems very legacy as these admin checks are present
     * since 2.2. Do we still need to prevent loading the onboarding when
     * we need to fix the wp-config? Do we even still fix the wp-config?
     * If no, just remove this.
     *
     * @return bool True if wp-config needs fixing
     */
    private function wpConfigNeedsFixing(): bool
    {
        if (RSSSL()->admin->configuration_loaded === false) {
            RSSSL()->admin->detect_configuration();
        }

        // wp-config still need fixes
        if (RSSSL()->admin->do_wpconfig_loadbalancer_fix() && !RSSSL()->admin->wpconfig_has_fixes()) {
            return true;
        }

        // wp-config has fixes, but still not OK
        if (RSSSL()->admin->wpconfig_ok() === false) {
            return true;
        }

        return false;
    }

    /**
     * Method processes the SSL activation step of the onboarding for multisite
     * instances.
     */
    public function processMultisiteActivationStep(): array
    {
        return RSSSL()->multisite->process_ssl_activation_step();
    }

    /**
     * Get the items from the onboarding queue
     */
    public function getQueuedItems(): array
    {
        $handle = $this->app->config->getString('env.onboarding.queue_option');
        return get_option($handle, []);
    }

    /**
     * Update the onboarding queue with the given array. It overrides the
     * current queue completely.
     */
    public function updateQueuedItems(array $queue): bool
    {
        $handle = $this->app->config->getString('env.onboarding.queue_option');
        return update_option($handle, $queue, false);
    }

    /**
     * Add an item to the onboarding queue. Method will also schedule the
     * queue event if not already done to make sure the queue will be
     * processed. Returns true when queue is correctly scheduled.
     * Process is done by {@see OnboardingController::processQueuedEvent}
     */
    public function queueOnboardingItem(string $itemId, string $action): bool
    {
        $queue = $this->getQueuedItems();
        $key = sanitize_key($itemId) . '_' . sanitize_key($action);

        $queue[$key] = [
            'item_id' => $itemId,
            'action' => $action,
            'status' => 'pending',
        ];

        $this->updateQueuedItems($queue);

        // Schedule and spawn the queue event when not yet scheduled
        $event = $this->app->config->getString('env.onboarding.queue_event');
        if (!wp_next_scheduled($event)) {
            $scheduled = wp_schedule_single_event(time() + 10, $event);
            $spawned = spawn_cron();
            return ($scheduled === true && $spawned === true);
        }

        return true;
    }

    /**
     * Clean up queued items and only keep failed or processing items. If
     * empty, we delete the queue option completely, otherwise we reschedule
     * a single event to retry the leftover items
     */
    public function cleanupQueuedItems(): void
    {
        $queuedItems = $this->getQueuedItems();
        $optionHandle = $this->app->config->getString('env.onboarding.queue_option');
        $eventHandle = $this->app->config->getString('env.onboarding.queue_event');

        /**
         * Statuses to keep even when the cleanup is triggered. Can be used to
         * debug why an action did not complete. In such a case, the status is
         * stuck 'failed'.
         */
        $shouldKeepStatusEvenWhenCleaned = apply_filters('rsssl_cleanup_onboarding_statuses', ['processing']);

        // Only keep failed or processing items
        $cleanedQueue = array_filter($queuedItems, static function ($item) use($shouldKeepStatusEvenWhenCleaned) {
            $status = $item['status'] ?? '';
            return in_array($status, $shouldKeepStatusEvenWhenCleaned);
        });

        if (empty($cleanedQueue)) {
            delete_option($optionHandle);
            return;
        }

        // Queue contains failed or processing items, schedule a next run
        update_option($optionHandle, $cleanedQueue, false);
        wp_schedule_single_event(time() + 600, $eventHandle);
    }

    /**
     * Manually process the queue right now. Useful in situations where we do
     * not need to wait for the event to be triggered automatically. For example
     * when the onboarding is dismissed in
     * {@see OnboardingController::processDismissModalAction}
     */
    public function manuallyProcessQueueNow(): void
    {
        $eventHandle = $this->app->config->getString('env.onboarding.queue_event');
        wp_clear_scheduled_hook($eventHandle);

        // fire!
        do_action($eventHandle);
    }
}