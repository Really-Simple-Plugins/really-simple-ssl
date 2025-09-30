<?php

namespace RSSSL\Security\WordPress\Two_Fa\Repositories;

use RSSSL\Security\WordPress\Two_Fa\Contracts\Rsssl_Two_Fa_User_Repository_Interface;
use RSSSL\Security\WordPress\Two_Fa\Models\Rsssl_Two_FA_Data_Parameters;
use RSSSL\Security\WordPress\Two_Fa\Models\Rsssl_Two_Factor_User_Factory;
use RSSSL\Security\WordPress\Two_Fa\Models\Rsssl_Two_Fa_User_Collection;
use WP_User_Query;

class Rsssl_Two_Fa_User_Repository implements Rsssl_Two_Fa_User_Repository_Interface
{
	/** @var Rsssl_Two_Factor_User_Factory */
	private Rsssl_Two_Factor_User_Factory $factory;

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$this->factory      = new Rsssl_Two_Factor_User_Factory();
	}

	/**
	 * Helper to build and fetch a user collection with a chain of builder operations.
	 *
	 * @param Rsssl_Two_FA_Data_Parameters $params
	 * @param callable $chain Rsssl_Two_Fa_User_Query_Builder $chain
	 * @return Rsssl_Two_Fa_User_Collection
	 */
	private function fetchBy(Rsssl_Two_FA_Data_Parameters $params, callable $chain): Rsssl_Two_Fa_User_Collection {
		$builder = new Rsssl_Two_Fa_User_Query_Builder($params);
		$chain($builder);
		return $this->buildUserCollection($builder->getArgs(), $params);
	}

	/**
	 * Retrieve two-factor authentication users based on the provided parameters.
	 */
	public function getTwoFaUsers(Rsssl_Two_FA_Data_Parameters $params): Rsssl_Two_Fa_User_Collection
	{
		// we check if there is a rolesFilter set.
		$filter = false;
		if ( ! empty( $params->filter_value ) && $params->filter_value !== 'all' ) {
			// we have a roles filter set, so we add it to the params.
			$filter = true;
		}
		return $this->fetchBy($params, fn($b) => $b
			->addRolesFilter($filter)
		);
	}

	/**
	 * Retrieve two-factor authentication users that are considered "expired."
	 *
	 * Expiration is determined by comparing the user's last login to a threshold date,
	 * and only users with a last login older than that threshold (plus the two-factor
	 * status conditions) are returned.
	 */
	public function geTwoFAExpiredUsers(Rsssl_Two_FA_Data_Parameters $params): Rsssl_Two_Fa_User_Collection
	{
		return $this->fetchBy($params, fn($b) => $b->addExpiredAndTwoFA());
	}


	/**
	 * Retrieve two-factor authentication users that are disabled.
	 */
	public function getTwoFaDisabledUsers(Rsssl_Two_FA_Data_Parameters $params): Rsssl_Two_Fa_User_Collection
	{
		return $this->fetchBy($params, fn($b) => $b->addDisabled());
	}

	/**
	 * Execute the WP_User_Query with the given arguments and convert the results
	 * to a Rsssl_Two_Fa_User_Collection.
	 */
	private function buildUserCollection(array $args, Rsssl_Two_FA_Data_Parameters $params): Rsssl_Two_Fa_User_Collection
	{
		$collection = new Rsssl_Two_Fa_User_Collection();
		$enabledRoles = $params->getEnabledRoles();
		if ( empty( $enabledRoles ) ) {
			// we have no enabled roles, so we cannot query users
			return $collection;
		}
		// 1) Gather raw WP_User results, either network-wide or single-site
		if ( is_multisite() ) {
			$args = $this->buildMultiSiteBaseQuery($args, $params);
		} else {
			// single site installation
			$args = $this->buildSingleSiteBaseQuery($args, $params);
		}

		$query   = new WP_User_Query( $args );
		$results = $query->get_results();
		$total   = $query->get_total();

		// 2) Set total records and bail early if no users
		$collection->setTotalRecords( $total );
		if ( empty( $results ) ) {
			return $collection;
		}

		// 3) Map WP_User → TwoFA user objects exactly as before
		$forcedRoles   = $params->getForcedRoles();
		$enabledRoles  = $params->getEnabledRoles();
		$daysThreshold = $params->getDaysThreshold();

		foreach ( $results as $user ) {
			$wpUser = get_userdata( $user->ID );
			if ( ! $wpUser instanceof \WP_User ) {
				// If the user is not a WP_User instance, skip to the next iteration.
				continue;
			}

			$twoFaUser = $this->factory->createFromWPUser(
				$wpUser,
				$forcedRoles,
				$enabledRoles,
				$daysThreshold
			);

			if ( $twoFaUser !== null && array_intersect($twoFaUser->getRoles(), $enabledRoles) ) {
				$collection->add( $twoFaUser );
			}
		}

		return $collection;
	}

	/**
	 * Build the base WP_User_Query for single-site installations.
	 */
	private function buildSingleSiteBaseQuery(array $args, Rsssl_Two_FA_Data_Parameters $params): array
	{
	    // Ensure we only look at the current blog and keep the query lean.
	    $args['blog_id']     = get_current_blog_id();
	    $args['fields']      = [ 'ID' ];
	    $args['count_total'] = true;
	    return $args;
	}

	/**
	 * Build the base WP_User_Query for multi-site installations.
	 */
	private function buildMultiSiteBaseQuery(array $args, Rsssl_Two_FA_Data_Parameters $params): array
	{
	    global $wpdb;

	    // Query users across the entire network (ignore site membership constraint).
	    // `blog_id` = 0 makes WP_User_Query ignore per-site membership filtering in multisite.
	    $args['blog_id']     = 0;
	    $args['fields']      = [ 'ID' ];
	    $args['count_total'] = true;

	    // Collect role filters (if any) that may have been added by the builder.
	    $roles = [];
	    if ( isset( $args['role'] ) && $args['role'] ) {
	        $roles[] = $args['role'];
	        unset( $args['role'] );
	    }
	    if ( isset( $args['role__in'] ) && is_array( $args['role__in'] ) ) {
	        $roles = array_merge( $roles, $args['role__in'] );
	        unset( $args['role__in'] );
	    }
	    $roles = array_values( array_unique( array_filter( $roles ) ) );

	    // If we have role constraints, translate them to a meta_query that checks ANY site's
	    // capabilities usermeta. For the main site the meta key is `${base_prefix}capabilities`,
	    // for subsites it is `${base_prefix}{$blog_id}_capabilities`.
	    if ( ! empty( $roles ) ) {
	        $site_ids = get_sites( [ 'fields' => 'ids' ] );
	        $outer    = [ 'relation' => 'OR' ];

	        foreach ( $site_ids as $blog_id ) {
	            $cap_key = ( (int) $blog_id === 1 )
	                ? $wpdb->base_prefix . 'capabilities'
	                : $wpdb->base_prefix . (int) $blog_id . '_capabilities';

	            // A user matches this site if ANY of the requested roles is present.
	            $per_site = [ 'relation' => 'OR' ];
	            foreach ( $roles as $role ) {
	                $per_site[] = [
	                    'key'     => $cap_key,
	                    // Serialized array contains the role name as a string key; LIKE is sufficient.
	                    'value'   => '"' . $role . '"',
	                    'compare' => 'LIKE',
	                ];
	            }
	            $outer[] = $per_site;
	        }

	        if ( ! empty( $outer ) ) {
	            if ( isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ) {
	                // Combine with an existing meta_query (AND the existing with our OR block).
	                $args['meta_query'] = [
	                    'relation' => 'AND',
	                    $args['meta_query'],
	                    $outer,
	                ];
	            } else {
	                $args['meta_query'] = $outer;
	            }
	        }
	    }

	    return $args;
	}

	/**
	 * Retrieve forced two-factor authentication users with an "open" status. and nearing expiry.
	 * within the forced roles.
	 */
	public function getForcedTwoFaUsersWithOpenStatus(Rsssl_Two_FA_Data_Parameters $params ): Rsssl_Two_Fa_User_Collection
	{
	    return $this->fetchBy($params, fn($b) => $b
	        ->addOpenStatus()
	        ->addForcedRoles($params->getForcedRoles())
	        ->addNearingExpiry()
	    );
	}


	/**
	 * Retrieve forced two-factor authentication users with an "open" status and within the changed roles.
	 */
	public function getAddedForcedTwoFaUsersWithOpenStatus(Rsssl_Two_FA_Data_Parameters $params, array $changedRoles): Rsssl_Two_Fa_User_Collection
	{
		return $this->fetchBy($params, fn($b) => $b
			->addOpenStatus()
			->addForcedRolesFor($changedRoles)
		);
	}

	/**
	 * Retrieve forced two-factor authentication users with disabled status.
	 */
	public function getForcedTwoFaUsersWithDisabledStatus(Rsssl_Two_FA_Data_Parameters $params, array $newForcedRoles): Rsssl_Two_Fa_User_Collection
	{
		return $this->fetchBy($params, fn($b) => $b
			->addForcedRolesFor($newForcedRoles)
			->addDisabled()
		);
	}

	/**
	 * Retrieve forced two-factor authentication users with disabled status.
	 */
	public function getForcedTwoFaUsersWithExpiredStatus(Rsssl_Two_FA_Data_Parameters $params, array $newForcedRoles): Rsssl_Two_Fa_User_Collection
	{
		return $this->fetchBy($params, fn($b) => $b
			->addForcedRolesFor($newForcedRoles)
			->addExpired()
		);
	}
}
