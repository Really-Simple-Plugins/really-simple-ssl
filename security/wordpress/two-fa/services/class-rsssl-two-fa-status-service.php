<?php
namespace RSSSL\Security\WordPress\Two_Fa\Services;

class Rsssl_Two_Fa_Status_Service {

    /**
     * Determine the two-factor status for a user.
     *
     * @return string
     */
    public function determineStatus(int $userId, array $forcedRoles, int $daysThreshold): string {
        $totpStatus   = get_user_meta($userId, 'rsssl_two_fa_status_totp', true);
        $emailStatus  = get_user_meta($userId, 'rsssl_two_fa_status_email', true);
        $passkeyStatus  = get_user_meta($userId, 'rsssl_two_fa_status_passkey', true);
        $lastLogin    = get_user_meta($userId, 'rsssl_two_fa_last_login', true);

        if (in_array('active', [$totpStatus, $emailStatus, $passkeyStatus], true)) {
            return 'active';
        }
        if ($totpStatus === 'disabled' && $emailStatus === 'disabled') {
            return 'disabled';
        }
        foreach ($forcedRoles as $role) {
            if (in_array($role, get_userdata($userId)->roles, true)
                && strtotime($lastLogin) < strtotime("-$daysThreshold days")
            ) {
                return 'expired';
            }
        }
        return $totpStatus ?: $emailStatus ?: 'open';
    }
}