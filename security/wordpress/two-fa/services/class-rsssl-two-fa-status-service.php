<?php
namespace RSSSL\Security\WordPress\Two_Fa\Services;

class Rsssl_Two_Fa_Status_Service {

    /**
     * Determine the two-factor status for a user.
     *
     * @return string
     */
    public function determineStatus(int $userId, array $forcedRoles, int $daysThreshold): string {
        $userData = get_userdata($userId);
        if (!$userData) {
            return 'open';
        }

        $providerStatuses = [];
        foreach ( \RSSSL\Security\WordPress\Two_Fa\Providers\Rsssl_Provider_Loader::get_loader()::available_providers() as $method => $provider ) {
            if (!$provider::is_enabled($userData)) {
                continue;
            }

            $providerStatuses[] = \RSSSL\Security\WordPress\Two_Fa\Rsssl_Two_Factor_Settings::get_user_status($method, $userId);
        }

        $lastLogin = get_user_meta($userId, 'rsssl_two_fa_last_login', true);

        // User has active 2FA configured
        if (in_array('active', $providerStatuses, true)) {
            return 'active';
        }

        // Only mark the user as disabled when every enabled provider is disabled.
        if (!empty($providerStatuses) && !array_diff($providerStatuses, array('disabled'))) {
            return 'disabled';
        }

        // Check if user has a forced role
        $isForced = !empty(array_intersect($forcedRoles, \RSSSL\Security\WordPress\Two_Fa\Rsssl_Two_Factor_Settings::get_user_roles($userId)));

        // Non-forced users can still configure any remaining open provider.
        if (!$isForced) {
            return 'open';
        }

        // New user without lastLogin - initialize grace period
        if (empty($lastLogin)) {
            update_user_meta($userId, 'rsssl_two_fa_last_login', gmdate('Y-m-d H:i:s'));
            return 'open';
        }

        // Grace period has expired
        $lastLoginTime = strtotime($lastLogin);
        $thresholdTime = strtotime("-$daysThreshold days");

        if ($lastLoginTime !== false && $lastLoginTime < $thresholdTime) {
            return 'expired';
        }

        // Still within grace period
        return 'open';
    }
}
