<?php

namespace RSSSL\Security\WordPress\Two_Fa\Repositories;

use RSSSL\Security\WordPress\Two_Fa\Contracts\Rsssl_Two_Fa_User_Query_Builder_Interface;
use RSSSL\Security\WordPress\Two_Fa\Models\Rsssl_Two_FA_Data_Parameters;

class Rsssl_Two_Fa_User_Query_Builder implements Rsssl_Two_Fa_User_Query_Builder_Interface {

	private $wpdb;
	private Rsssl_Two_FA_Data_Parameters $params;
	private array $args;

	private array $statusKeys = [
		'rsssl_two_fa_status_email',
		'rsssl_two_fa_status_totp',
		'rsssl_two_fa_status_passkey'
	];

	public function __construct( Rsssl_Two_FA_Data_Parameters $params ) {
		global $wpdb;
		$this->wpdb   = $wpdb;
		$this->params = $params;
		$this->args   = $this->buildQueryArgs( $params );
	}

	/**
	 * Build query args based on data parameters.
	 */
	public function buildQueryArgs( Rsssl_Two_FA_Data_Parameters $params ): array {
		//  $metaQuery = $this->buildRoleMetaQuery($params);
		$pagination = [
			'number' => $params->number,
			'offset' => $params->offset,
		];

		$args = array_merge( $pagination, [
			//          'meta_query' => $metaQuery,
			'fields' => [ 'ID', 'user_login' ],
		] );

		return $args;
	}

	/**
	 * Build the meta query for the user role specifically for two_fa_users.
	 *
	 * @return array|array[]
	 */
	protected function buildRoleMetaQuery( Rsssl_Two_FA_Data_Parameters $params ): array {
		if ( 'all' !== $params->filter_value && 'user_role' === $params->filter_column ) {
			if ( ! $this->isValidRole( $params->filter_value, $params ) ) {
				// Return a query that will never match any user.
				return [
					[
						'key'     => 'non_existing_meta_key',
						'value'   => 'non_existing_value',
						'compare' => '='
					]
				];
			}

			// Use 'OR' as in the original code.
			return [
				'relation' => 'OR',
				[
					'key'     => $this->wpdb->prefix . 'capabilities',
					'value'   => sprintf( ':"%s";b:1;', $params->filter_value ),
					'compare' => 'LIKE',
				],
			];
		}
		// The "all" branch remains the same.
		$enabledRoles = $params->getEnabledRoles();
		if ( empty( $enabledRoles ) ) {
			return [];
		}
		$queries = array_map( function ( $role ) {
			return [
				'key'     => $this->wpdb->prefix . 'capabilities',
				'value'   => sprintf( ':"%s";b:1;', $role ),
				'compare' => 'LIKE',
			];
		}, $enabledRoles );

		return array_merge( [ 'relation' => 'OR' ], $queries );
	}

	/**
	 * Check if the role is valid.
	 */
	protected function isValidRole( string $role, Rsssl_Two_FA_Data_Parameters $params ): bool {
		return in_array( $role, $params->getEnabledRoles(), true )
		       || in_array( $role, $params->getForcedRoles(), true );
	}

	/**
	 * Add expired and two-factor authentication conditions to the query arguments.
	 */
	public function addExpiredAndTwoFA(): self {
		$threshold  = $this->params->getDaysThreshold();
		$this->args = $this->addExpiredAndTwoFAConditionsToArgs( $this->args, $threshold );

		return $this;
	}

	/**
	 * Add the expired condition and two-factor status conditions to the query arguments.
	 *
	 * @param array $args The base query arguments.
	 * @param int $daysThreshold Number of days to determine expiration.
	 *
	 * @return array The modified query arguments.
	 */
	public function addExpiredAndTwoFAConditionsToArgs( array $args, int $daysThreshold ): array {
		$metaConditions = [];

		// Include any existing meta_query conditions.
		if ( ! empty( $args['meta_query'] ) ) {
			$metaConditions[] = $args['meta_query'];
		}

		// Append the expired condition.
		$metaConditions[] = $this->getExpiredCondition( $daysThreshold );

		// Append the two-factor status conditions.
		$metaConditions = array_merge( $metaConditions, $this->getTwoFAStatusConditions() );

		// Combine all meta conditions with an AND relation.
		$args['meta_query'] = array_merge( [ 'relation' => 'AND' ], $metaConditions );

		return $args;
	}

	/**
	 * Build and return an array of meta query conditions for two‑factor status keys.
	 *
	 * For each key, users will be matched if the meta key is either set to "open" or does not exist.
	 *
	 * @return array
	 */
	private function getTwoFAStatusConditions(): array {
		$conditions = [];
		foreach ( $this->statusKeys as $key ) {
			$conditions[] = [
				'relation' => 'OR',
				[
					'key'     => $key,
					'value'   => 'open',
					'compare' => '=',
				],
				[
					'key'     => $key,
					'compare' => 'NOT EXISTS',
				],
			];
		}

		return $conditions;
	}

	/**
	 * Build and return the expired condition meta query.
	 *
	 * This condition checks that the user's 'rsssl_two_fa_last_login' date is older than the current time minus the threshold days.
	 *
	 * @param int $daysThreshold The number of days to subtract from now.
	 *
	 * @return array
	 */
	private function getExpiredCondition( int $daysThreshold ): array {
		$expiredDateValue = date( "Y-m-d H:i:s", strtotime( "-{$daysThreshold} days" ) );

		return [
			'key'     => 'rsssl_two_fa_last_login',
			'value'   => $expiredDateValue,
			'compare' => '<',
			'type'    => 'DATETIME',
		];
	}

	public function addRolesFilter($filter = false): self {
		if ( $filter ) {
			$this->args = $this->addRoleFilterConditionToArgs( $this->args, $this->params->filter_value );
		}
		return $this;

	}

	private function addRoleFilterConditionToArgs( array $args, string $role ): array {
		// We check the enabled roles to see if the role is valid.
		$enabledRoles = $this->params->getEnabledRoles();
		if ( ! in_array( $role, $enabledRoles, true ) ) {
			// If the role is not valid, we set a condition that matches no users.
			$args['role__in'] = [ 'non_existing_role' ];
			return $args;
		}
		$args['role__in'] = [ $role ];

		return $args;
	}

	/**
	 * Build and add the expired condition to the meta query.
	 *
	 * This condition checks that the user's 'rsssl_two_fa_last_login' date is older than the current time minus the threshold days.
	 *
	 * @param int $daysThreshold The number of days to subtract from now.
	 *
	 * @return array
	 */
	public function addExpiredCondition( array $args, int $daysThreshold ): array {
		$expiredDateValue = date( "Y-m-d H:i:s", strtotime( "-{$daysThreshold} days" ) );
		$metaQuery[]      = [
			'key'     => 'rsssl_two_fa_last_login',
			'value'   => $expiredDateValue,
			'compare' => '<',
			'type'    => 'DATETIME',
		];

		if ( isset( $args['meta_query'] ) ) {
			$args['meta_query'] = [
				'relation' => 'AND',
				$args['meta_query'],
				$metaQuery,
			];
		} else {
			$args['meta_query'] = $metaQuery;
		}

		return $args;
	}

	/**
	 * Add disabled condition to the query arguments.
	 */
	public function addDisabled(): self {
		$this->args = $this->addDisabledConditionToArgs( $this->args );

		return $this;
	}

	/**
	 * Add the disabled condition to the query arguments.
	 */
	public function addDisabledConditionToArgs( array $args ): array {
		$disabledConditions = [];
		foreach ( $this->statusKeys as $key ) {
			$disabledConditions[] = [
				'key'     => $key,
				'value'   => 'disabled',
				'compare' => '=',
			];
		}

		// Build the new condition group for disabled status.
		$newConditionGroup = array_merge( [ 'relation' => 'OR' ], $disabledConditions );

		if ( isset( $args['meta_query'] ) ) {
			// Merge the new conditions with the existing meta_query.
			// Here, we assume that both the existing conditions and the new disabled conditions must be true,
			// so we use 'AND' to combine them.
			$args['meta_query'] = [
				'relation' => 'AND',
				$args['meta_query'],    // existing meta_query conditions
				$newConditionGroup,     // new disabled conditions
			];
		} else {
			// If no meta_query exists, simply use the new condition group.
			$args['meta_query'] = $newConditionGroup;
		}

		return $args;
	}

	/**
	 * Add open status condition to the query arguments.
	 */
	public function addOpenStatus(): self {
		$this->args = $this->excludeActiveUsers( $this->args );
		return $this;
	}

	/**
	 * Add condition to find users with unconfigured 2FA.
	 *
	 */
	public function addUnconfigured2FAConditionToArgs( array $args ): array {
		$metaQuery = [];
		foreach ( $this->statusKeys as $key ) {
			$metaQuery[] = [
				'relation' => 'OR',
				[
					'key'     => $key,
					'value'   => 'active',
					'compare' => '!='
				],
				[
					'key'     => $key,
					'compare' => 'NOT EXISTS',
				]
			];
		}

		// Use AND relation to ensure ALL methods are not active
		$args['meta_query'] = array_merge( [ 'relation' => 'AND' ], $metaQuery );

		return $args;
	}

	/**
	 * Add nearing expiry condition to the query arguments.
	 *
	 * @param int $reminderBeforeClosingPeriod
	 *
	 * @return $this
	 */
	public function addNearingExpiry( int $reminderBeforeClosingPeriod = 3 ): self {
		$threshold  = $this->params->getDaysThreshold();
		$this->args = $this->addNearingExpiryCondition( $this->args, $threshold, $reminderBeforeClosingPeriod );

		return $this;
	}


	/**
	 * Build and return the nearing expiry condition meta query.
	 *
	 * This condition checks that the user's 'rsssl_two_fa_last_login' date is such that
	 * they have three days or less remaining in their grace period.
	 *
	 * Given a total grace period ($daysThreshold), this returns a condition that only
	 * returns users whose last login is between:
	 *   - now - $daysThreshold (i.e. the point of expiry), and
	 *   - now - ($daysThreshold - 3) (i.e. when 3 days remain).
	 *
	 * @param int $daysThreshold The total number of days in the grace period.
	 *
	 * @return array
	 */
	public function addNearingExpiryCondition( array $args, int $daysThreshold, int $reminderBeforeClosingPeriod = 3 ): array {
		// if the $daysThreshold is smaller than the reminderBeforeClosingPeriod.
		// there is no longer a need to check for the reminderBeforeClosingPeriod
		if ( $daysThreshold <= $reminderBeforeClosingPeriod ) {
			return [];
		}
		// Calculate the lower and upper bounds for the last login date.
		// Lower bound: The earliest date (i.e. furthest in the past) a user can have logged in
		// without being already expired.
		$lowerBound = date( "Y-m-d H:i:s", strtotime( "-{$daysThreshold} days" ) );
		// Upper bound: The date corresponding to when exactly three days remain.
		$upperBound = date( "Y-m-d H:i:s", strtotime( "-" . ( $daysThreshold - $reminderBeforeClosingPeriod ) . " days" ) );

		$nearingExpiryCondition = [
			'key'     => 'rsssl_two_fa_last_login',
			'value'   => [ $lowerBound, $upperBound ],
			'compare' => 'BETWEEN',
			'type'    => 'DATETIME',
		];

		if ( isset( $args['meta_query'] ) ) {
			// Preserve existing meta_query and add our condition
			$args['meta_query'] = [
				'relation' => 'AND',
				$args['meta_query'],
				$nearingExpiryCondition,
			];
		} else {
			$args['meta_query'] = [ 'relation' => 'AND', $nearingExpiryCondition ];
		}

		return $args;
	}

	/**
	 * Add expired only condition to the query arguments.
	 */
	public function addExpired(): self {
		$threshold  = $this->params->getDaysThreshold();
		$this->args = $this->addExpiredCondition( $this->args, $threshold );

		return $this;
	}

	/**
	 * Add forced roles condition to the query arguments.
	 */
	public function addForcedRoles(): self {
		$forced     = $this->params->getForcedRoles();
		$this->args = $this->filterForcedRolesFromEnabledRoles( $this->args, $forced );

		return $this;
	}

	/**
	 * Add forced roles condition to the query arguments.
	 */
	public function addForcedRolesFor( array $forcedRoles ): self {
		$this->args = $this->addForcedRolesConditionToArgs( $this->args, $forcedRoles );

		return $this;
	}

	/**
	 * Filter Specific on the forced roles
	 */
	public function filterForcedRolesFromEnabledRoles( array $args, array $getForcedRoles ): array {
		// we filter the forced roles to only those that are enabled.
		$enabledRoles = $this->params->getEnabledRoles();
		// We only keep the roles that are in both forced and enabled.
		$forcedRoles  = array_intersect( $getForcedRoles, $enabledRoles );
		if ( empty( $forcedRoles ) ) {
			// If there are no forced roles after filtering, we set a condition that matches no users.
			$args['role__in'] = [ 'non_existing_role' ];
			return $args;
		}
		$args['role__in'] = $forcedRoles;

		return $args;
	}

	/**
	 * retrieve the query arguments.
	 */
	public function getArgs(): array {
		return $this->args;
	}

	/**
	 * Apply a list of fluent calls in order.
	 *
	 * e.g. $b->chain(['addOpenStatus','addDisabled']);
	 *
	 * @param string[] $methods
	 *
	 * @return $this
	 */
	public function chain( array $methods ): self {
		foreach ( $methods as $m ) {
			if ( ! method_exists( $this, $m ) ) {
				throw new \InvalidArgumentException( "Unknown chain step: $m" );
			}
			$this->{$m}();
		}

		return $this;
	}

	private function excludeActiveUsers( array $args ): array {
		if (empty($this->statusKeys)) {
			return $args;
		}

		$conditions = [];
		foreach ($this->statusKeys as $key) {
			// Voor deze key: ofwel niet 'active', ofwel niet aanwezig
			$conditions[] = [
				'relation' => 'OR',
				[
					'key'     => $key,
					'value'   => 'active',
					'compare' => '!=',
				],
				[
					'key'     => $key,
					'compare' => 'NOT EXISTS',
				],
			];
		}

		// Alle keys moeten voldoen → AND
		$newConditionGroup = [
			'relation' => 'AND',
			...$conditions,
		];

		if (isset($args['meta_query'])) {
			$args['meta_query'] = [
				'relation' => 'AND',
				$args['meta_query'],
				$newConditionGroup,
			];
		} else {
			$args['meta_query'] = $newConditionGroup;
		}

		return $args;
	}

	private function addOpenStatusConditionToArgs( array $args ) {
		$openStatusConditions = [];
		foreach ( $this->statusKeys as $key ) {
			$openStatusConditions[] = [
				'key'     => $key,
				'value'   => 'open',
				'compare' => '=',
			];
		}

		$newConditionGroup = array_merge( [ 'relation' => 'OR' ], $openStatusConditions );

		if ( isset( $args['meta_query'] ) ) {
			$args['meta_query'] = [
				'relation' => 'AND',
				$args['meta_query'],
				$newConditionGroup,
			];
		} else {
			$args['meta_query'] = $newConditionGroup;
		}

		var_dump( $args );

		return $args;
	}

	public function addForcedRolesConditionToArgs( array $args, array $getForcedRoles ): array {
		return $args;
	}
}