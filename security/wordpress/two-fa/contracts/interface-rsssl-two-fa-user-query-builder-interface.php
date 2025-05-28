<?php
namespace RSSSL\Security\WordPress\Two_Fa\Contracts;

use RSSSL\Security\WordPress\Two_Fa\Models\Rsssl_Two_FA_Data_Parameters;

interface Rsssl_Two_Fa_User_Query_Builder_Interface {
    /**
     * Build query args based on data parameters.
     *
     * @return array
     */
    public function buildQueryArgs(Rsssl_Two_FA_Data_Parameters $params): array;

    public function addDisabledConditionToArgs(array $args): array;

    public function addOpenStatusConditionToArgs(array $args): array;

    public function addNearingExpiryCondition(array $args, int $daysThreshold, int $reminderBeforeClosingPeriod = 3): array;

    public function addForcedRolesConditionToArgs(array $args, array $getForcedRoles): array;
}
