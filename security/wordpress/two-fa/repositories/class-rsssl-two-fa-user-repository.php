<?php

namespace RSSSL\Security\WordPress\Two_Fa\Repositories;

use RSSSL\Security\WordPress\Two_Fa\Contracts\Rsssl_Two_Fa_User_Query_Builder_Interface;
use RSSSL\Security\WordPress\Two_Fa\Contracts\Rsssl_Two_Fa_User_Repository_Interface;
use RSSSL\Security\WordPress\Two_Fa\Models\Rsssl_Two_FA_Data_Parameters;
use RSSSL\Security\WordPress\Two_Fa\Models\Rsssl_Two_Factor_User_Factory;
use RSSSL\Security\WordPress\Two_Fa\Models\Rsssl_Two_Fa_User_Collection;
use WP_User_Query;

class Rsssl_Two_Fa_User_Repository implements Rsssl_Two_Fa_User_Repository_Interface
{
    /** @var Rsssl_Two_Fa_User_Query_Builder_Interface */
    private Rsssl_Two_Fa_User_Query_Builder_Interface $queryBuilder;

    /** @var Rsssl_Two_Factor_User_Factory */
    private Rsssl_Two_Factor_User_Factory $factory;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->queryBuilder = new Rsssl_Two_Fa_User_Query_Builder();
        $this->factory      = new Rsssl_Two_Factor_User_Factory();
    }

    /**
     * Retrieve two-factor authentication users based on the provided parameters.
     *
     * @return Rsssl_Two_Fa_User_Collection
     */
    public function getTwoFaUsers(Rsssl_Two_FA_Data_Parameters $params): Rsssl_Two_Fa_User_Collection
    {
        $args = $this->queryBuilder->buildQueryArgs($params);
        return $this->buildUserCollection($args, $params);
    }

    /**
     * Retrieve two-factor authentication users that are considered "expired."
     *
     * Expiration is determined by comparing the user's last login to a threshold date,
     * and only users with a last login older than that threshold (plus the two-factor
     * status conditions) are returned.
     *
     * @return Rsssl_Two_Fa_User_Collection
     */
    public function geTwoFAExpiredUsers(Rsssl_Two_FA_Data_Parameters $params): Rsssl_Two_Fa_User_Collection
    {
        $params->getDaysThreshold();

        // Build the base query arguments (this may include a role meta query).
        $args = $this->queryBuilder->buildQueryArgs($params);

        // Merge in the expired condition and two-factor status conditions.
        $args = $this->queryBuilder->addExpiredAndTwoFAConditionsToArgs($args, $params->days_threshold);

        return $this->buildUserCollection($args, $params);
    }


    /**
     * Retrieve two-factor authentication users that are disabled.
     *
     * @return Rsssl_Two_Fa_User_Collection
     */
    public function getTwoFaDisabledUsers(Rsssl_Two_FA_Data_Parameters $params): Rsssl_Two_Fa_User_Collection
    {
        $args = $this->queryBuilder->buildQueryArgs($params);
        $args = $this->queryBuilder->addDisabledConditionToArgs($args);
        return $this->buildUserCollection($args, $params);
    }

    /**
     * Execute the WP_User_Query with the given arguments and convert the results
     * to a Rsssl_Two_Fa_User_Collection.
     *
     * @param array $args Query arguments for WP_User_Query.
     * @return Rsssl_Two_Fa_User_Collection
     */
    private function buildUserCollection(array $args, Rsssl_Two_FA_Data_Parameters $params): Rsssl_Two_Fa_User_Collection
    {
        $query     = new WP_User_Query($args);
        $collection = new Rsssl_Two_Fa_User_Collection();
        $collection->setTotalRecords($query->get_total());
        $results = $query->get_results();
        // if the results are empty, return an empty collection
        if (empty($results)) {
            return $collection;
        }

        $forcedRoles = $params->getForcedRoles();
        $enabledRoles = $params->getEnabledRoles();
        $daysThreshold = $params->getDaysThreshold();
        foreach ($results as $user) {
            $wpUser    = get_userdata($user->ID);
            $twoFaUser = $this->factory->createFromWPUser(
                $wpUser,
                $forcedRoles,
                $enabledRoles,
                $daysThreshold
            );

            if ($twoFaUser !== null) {
                $collection->add($twoFaUser);
            }
        }
        return $collection;
    }

    /**
     * Retrieve forced two-factor authentication users with an "open" status. and nearing expiry.
     * within the forced roles.
     *
     * @return Rsssl_Two_Fa_User_Collection
     */
    public function getForcedTwoFaUsersWithOpenStatus(Rsssl_Two_FA_Data_Parameters $params ): Rsssl_Two_Fa_User_Collection
    {
        $args = $this->queryBuilder->buildQueryArgs($params);
        $args = $this->queryBuilder->addOpenStatusConditionToArgs($args);
        $args = $this->queryBuilder->addForcedRolesConditionToArgs($args, $params->getForcedRoles());
        $args = $this->queryBuilder->addNearingExpiryCondition($args, $params->getDaysThreshold(), 3);
        return $this->buildUserCollection($args, $params);
    }


    /**
     * Retrieve forced two-factor authentication users with an "open" status. and nearing expiry.
     * within the forced roles.
     *
     * @return Rsssl_Two_Fa_User_Collection
     */
    public function getAddedForcedTwoFaUsersWithOpenStatus(Rsssl_Two_FA_Data_Parameters $params, array $changedRoles): Rsssl_Two_Fa_User_Collection
    {
        $args = $this->queryBuilder->buildQueryArgs($params);
        $args = $this->queryBuilder->addOpenStatusConditionToArgs($args);
        return $this->buildUserCollection($args, $params);
    }

    /**
     * Retrieve forced two-factor authentication users with disabled status.
     *
     * @return Rsssl_Two_Fa_User_Collection
     */
    public function getForcedTwoFaUsersWithDisabledStatus(Rsssl_Two_FA_Data_Parameters $params, array $newForcedRoles): Rsssl_Two_Fa_User_Collection
    {
        $args = $this->queryBuilder->buildQueryArgs($params);
        $args = $this->queryBuilder->addForcedRolesConditionToArgs($args,$newForcedRoles);
        $args = $this->queryBuilder->addDisabledConditionToArgs($args);

        return $this->buildUserCollection($args, $params);
    }

    /**
     * Retrieve forced two-factor authentication users with disabled status.
     *
     * @return Rsssl_Two_Fa_User_Collection
     */
    public function getForcedTwoFaUsersWithExpiredStatus(Rsssl_Two_FA_Data_Parameters $params, array $newForcedRoles): Rsssl_Two_Fa_User_Collection
    {
        $args = $this->queryBuilder->buildQueryArgs($params);
        $args = $this->queryBuilder->addForcedRolesConditionToArgs($args,$newForcedRoles);
        $args = $this->queryBuilder->addExpiredCondition($args, $params->getDaysThreshold());

        return $this->buildUserCollection($args, $params);
    }
}