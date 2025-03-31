<?php

namespace RSSSL\Security\WordPress\Two_Fa\Contracts;

use RSSSL\Security\WordPress\Two_Fa\Models\Rsssl_Two_FA_Data_Parameters;
use RSSSL\Security\WordPress\Two_Fa\Models\Rsssl_Two_Fa_User_Collection;

interface Rsssl_Two_Fa_User_Repository_Interface {
    /**
     * Retrieve two-factor authentication users based on data parameters.
     *
     * @param Rsssl_Two_FA_Data_Parameters $params
     * @return Rsssl_Two_Fa_User_Collection
     */
    public function getTwoFaUsers(Rsssl_Two_FA_Data_Parameters $params): Rsssl_Two_Fa_User_Collection;


    /**
     * Needed for getting all the expired users.
     *
     * @param Rsssl_Two_FA_Data_Parameters $params
     * @return Rsssl_Two_Fa_User_Collection
     */
    public function geTwoFAExpiredUsers(Rsssl_Two_FA_Data_Parameters $params): Rsssl_Two_Fa_User_Collection;

    /**
     * Retrieve forced two-factor authentication users with open status.
     *
     * @param Rsssl_Two_FA_Data_Parameters $params
     * @return Rsssl_Two_Fa_User_Collection
     */
    public function getForcedTwoFaUsersWithOpenStatus(Rsssl_Two_FA_Data_Parameters $params): Rsssl_Two_Fa_User_Collection;

}