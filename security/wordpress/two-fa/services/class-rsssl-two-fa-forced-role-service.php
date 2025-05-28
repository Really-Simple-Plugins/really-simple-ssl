<?php

namespace RSSSL\Security\WordPress\Two_Fa\Services;

use RSSSL\Security\WordPress\Two_Fa\Contracts\Rsssl_Has_Processing_Interface;
use RSSSL\Security\WordPress\Two_Fa\Contracts\Rsssl_Two_Fa_User_Repository_Interface;
use RSSSL\Security\WordPress\Two_Fa\Models\Rsssl_Two_FA_Data_Parameters;
use RSSSL\Security\WordPress\Two_Fa\Models\Rsssl_Two_Fa_User_Collection;
use RSSSL\Security\WordPress\Two_Fa\Repositories\Rsssl_Two_Fa_User_Repository;

class Rsssl_Two_Fa_Forced_Role_Service implements Rsssl_Has_Processing_Interface
{
    private Rsssl_Two_Fa_User_Repository_Interface $userRepository;
    private Rsssl_Two_FA_Data_Parameters $params;

    public function __construct(Rsssl_Two_FA_Data_Parameters $params)
    {
        $this->userRepository = new Rsssl_Two_Fa_User_Repository();
        $this->params = $params;
    }

    /**
     * Process a batch of forced two-factor users with disabled status.
     *
     * @param string $statusType
     * @return Rsssl_Two_Fa_User_Collection
     */
    public function processBatch(array $args, string $switchValue): Rsssl_Two_Fa_User_Collection
    {
        // We set a temp trancient to enable a debig check
        set_transient('rsssl_two_fa_forced_role_service', $args, 60);
        switch ($switchValue) {
            case 'disabled':
                return $this->userRepository->getForcedTwoFaUsersWithDisabledStatus($this->params, $args);
            case 'open':
                return $this->userRepository->getAddedForcedTwoFaUsersWithOpenStatus($this->params, $args);
        }
    }

    /**
     * Check if the forced roles have changed.
     *
     * @return array
     */
    public static function getForForcedRolesChange(array $oldForcedRoles, array $newForcedRoles): array
    {
        // Check if the forced roles have changed. And only get the new Roles added.
        return array_diff($newForcedRoles, $oldForcedRoles);
    }

    /**
     * Reset the status of the forced users when the forced roles are added.
     */
    public function maybeResetForcedUsersWhenDisabled(array $changedRoles): void
    {

        $collection = $this->userRepository->getForcedTwoFaUsersWithDisabledStatus($this->params, $changedRoles);
        foreach ($collection->getUsers() as $user) {
            $user->resetStatus();
        }
    }

    /**
     * Reset the status of the forced users when the forced roles are added.
     */
    public function maybeResetForcedUsersWhenExpired(array $changedRoles): void
    {

        $collection = $this->userRepository->getForcedTwoFaUsersWithExpiredStatus($this->params, $changedRoles);
        foreach ($collection->getUsers() as $user) {
            $user->resetStatus();
        }
    }


}
