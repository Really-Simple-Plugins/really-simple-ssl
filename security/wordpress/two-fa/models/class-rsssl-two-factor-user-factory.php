<?php
namespace RSSSL\Security\WordPress\Two_Fa\Models;

use RSSSL\Security\WordPress\Two_Fa\Services\Rsssl_Two_Fa_Status_Service;
use WP_User;

class Rsssl_Two_Factor_User_Factory {

    /**
     * Defines a role hierarchy.
     *
     * @var array<string, int>
     */
    protected array $roleHierarchy = [
        'administrator' => 100,
        'editor'        => 80,
        'author'        => 60,
        'subscriber'    => 40,
        'contributor'   => 20,
    ];

    private Rsssl_Two_Fa_Status_Service $statusService;

    /**
     * Inject the status service.
     *
     */
    public function __construct() {
        $this->statusService = new Rsssl_Two_Fa_Status_Service();
    }

    /**
     * Create a TwoFaUser from a WP_User object.
     *
     * @return Rsssl_Two_FA_user|null Returns null if the user has no roles.
     */
    public function createFromWPUser(WP_User $user, array $forcedRoles, array $enabledRoles, int $daysThreshold): ?Rsssl_Two_FA_user {
        // Retrieve user roles.
        $userRoles = $user->roles;
        if (empty($userRoles)) {
            return null;
        }

        // Determine user login.
        $userLogin = $user->user_login;

        // Use the status service to determine the user's status.
        $statusForUser = $this->statusService->determineStatus($user->ID, $forcedRoles, $daysThreshold);

        // Determine two-factor provider.
        $provider = $this->determineTwoFaProvider($user->ID);

        // Identify matching roles.
        $matchingRoles = array_intersect($userRoles, $enabledRoles);


        // If multiple roles exist and one of them is forced, prefer the forced role.
        if (!empty($forcedRoles) && count($matchingRoles) > 1) {
            $matchingForcedRoles = array_intersect($matchingRoles, $forcedRoles);
            if (!empty($matchingForcedRoles)) {
                $matchingRoles = $matchingForcedRoles;
            }
        }

        // If multiple roles remain, choose the most important one based on the defined hierarchy.
        if (count($matchingRoles) > 1) {
            usort($matchingRoles, function ($role1, $role2) {
                $priority1 = $this->roleHierarchy[$role1] ?? 0;
                $priority2 = $this->roleHierarchy[$role2] ?? 0;
                return $priority2 <=> $priority1;
            });
        }

        // Determine the most important role.
        $mostImportantRole = reset($matchingRoles);

        return new Rsssl_Two_FA_user (
            $user->ID,
            $userLogin,
            $statusForUser,
            $provider,
            $userRoles
        );
    }

    /**
     * Determine the active two-factor provider.
     *
     * @return string
     */
    protected function determineTwoFaProvider(int $userId): string {
        $providers = [
            ['provider' => 'totp',   'meta_key' => 'rsssl_two_fa_status_totp'],
            ['provider' => 'email',  'meta_key' => 'rsssl_two_fa_status_email'],
            // Note: if you really want to return 'email' even when passkey is active, do it this way:
            ['provider' => 'passkey',  'meta_key' => 'rsssl_two_fa_status_passkey'],
        ];

        foreach ($providers as $entry) {
            if (get_user_meta($userId, $entry['meta_key'], true) === 'active') {
                return $entry['provider'];
            }
        }

        return 'none';
    }
}