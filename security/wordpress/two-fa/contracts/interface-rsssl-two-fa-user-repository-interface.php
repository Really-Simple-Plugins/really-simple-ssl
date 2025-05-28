<?php

namespace RSSSL\Security\WordPress\Two_Fa\Contracts;

use RSSSL\Security\WordPress\Two_Fa\Models\Rsssl_Two_FA_Data_Parameters;
use RSSSL\Security\WordPress\Two_Fa\Models\Rsssl_Two_Fa_User_Collection;

interface Rsssl_Two_Fa_User_Repository_Interface {
    /**
     * Retrieve two-factor authentication users based on data parameters.
     *
     * @return Rsssl_Two_Fa_User_Collection
     */
    public function getTwoFaUsers(Rsssl_Two_FA_Data_Parameters $params): Rsssl_Two_Fa_User_Collection;


    /**
     * Needed for getting all the expired users.
     *
     * @return Rsssl_Two_Fa_User_Collection
     */
    public function geTwoFAExpiredUsers(Rsssl_Two_FA_Data_Parameters $params): Rsssl_Two_Fa_User_Collection;

    /**
     * Retrieve forced two-factor authentication users with open status.
     *
     * @return Rsssl_Two_Fa_User_Collection
     */
    public function getForcedTwoFaUsersWithOpenStatus(Rsssl_Two_FA_Data_Parameters $params): Rsssl_Two_Fa_User_Collection;

}