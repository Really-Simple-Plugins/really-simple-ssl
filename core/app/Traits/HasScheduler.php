<?php

declare(ticks=1);

namespace ReallySimplePlugins\RSS\Core\Traits;

/**
 * Trait DebouncedScheduler
 *
 * Provides helpers to schedule debounced single cron events using an option-based
 * lock to prevent duplicate scheduling within the debounce window.
 */
trait HasScheduler
{
    /**
     * Schedule a single cron event without debounce.
     *
     * By default, the event is scheduled for "now". You can optionally pass a
     * Unix timestamp to schedule it for a specific moment.
     *
     * @todo - no use-case yet, test thoroughly before using widely.
     *
     * @param string $hook Cron hook name.
     * @param array $args Optional arguments for the scheduled event.
     * @param int|null $timestamp Unix timestamp when the event should run. Defaults to now.
     */
    protected function schedule(string $hook, array $args = [], ?int $timestamp = null): void
    {
        $runAt = $timestamp ?? time();

        $existingScheduledTimestamp = wp_next_scheduled($hook, $args);
        if ($existingScheduledTimestamp === $runAt) {
            return; // An identical event is already scheduled at the same time.
        }

        wp_schedule_single_event($runAt, $hook, $args);
    }

    /**
     * Schedule a single cron event only if no active lock exists and no identical
     * event is already scheduled within the debounce period.
     *
     * @param string $hook Cron hook name.
     * @param int $secondsUntilExecution Debounce period in seconds.
     * @param array $args       Optional arguments for the scheduled event.
     *
     * @return void
     */
    protected function scheduleDebounced(string $hook, int $secondsUntilExecution, array $args = []): void
    {
        $now = time();
        $lockOption = $hook . '_debounce_lock';
        $lockUntil = (int) get_option($lockOption, 0);

        // If a lock is still active, nothing to do.
        if ($lockUntil > $now) {
            return;
        }

        // Set lock to expire after the debounce period (no autoload).
        update_option($lockOption, $now + $secondsUntilExecution, false);

        // Schedule only if there is no identical event already queued.
        if (wp_next_scheduled($hook, $args) === false) {
            wp_schedule_single_event($now + $secondsUntilExecution, $hook, $args);
        }
    }

    /**
     * Release the debounce lock when the task completes.
     *
     * @param string $scheduledEventName Event name where the hook used to store the lock expiry timestamp.
     *
     * @return void
     */
    protected function releaseDebounceLock(string $scheduledEventName): void
    {
        delete_option($scheduledEventName . '_debounce_lock');
    }
}
