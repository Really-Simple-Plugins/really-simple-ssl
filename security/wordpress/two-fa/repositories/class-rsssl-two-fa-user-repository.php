<?php

namespace RSSSL\Security\WordPress\Two_Fa\Repositories;

use RSSSL\Security\WordPress\Two_Fa\Contracts\Rsssl_Two_Fa_User_Repository_Interface;
use RSSSL\Security\WordPress\Two_Fa\Models\Rsssl_Two_FA_Data_Parameters;
use RSSSL\Security\WordPress\Two_Fa\Models\Rsssl_Two_Factor_User_Factory;
use RSSSL\Security\WordPress\Two_Fa\Models\Rsssl_Two_Fa_User_Collection;
use WP_User_Query;

class Rsssl_Two_Fa_User_Repository implements Rsssl_Two_Fa_User_Repository_Interface
{
	/**
	 * Object-cache group for cross-request reuse of multisite candidate IDs.
	 */
	private const CACHE_GROUP = 'rsssl_2fa';

	/**
	 * Object-cache key holding the current cache "version". Bumping it
	 * invalidates every existing role-set entry at once, so we never have to
	 * enumerate keys to delete them.
	 */
	private const CACHE_VERSION_KEY = 'rsssl_multisite_role_user_ids_version';

	/**
	 * Soft TTL on the candidate-IDs object-cache entries. Acts as a safety net
	 * if an invalidation hook is ever missed (e.g. roles changed via direct
	 * SQL outside WordPress).
	 */
	private const CACHE_TTL = 60;

	/**
	 * Idempotency guard for registerCacheInvalidationHooks().
	 */
	private static bool $cacheHooksRegistered = false;

	/** @var Rsssl_Two_Factor_User_Factory */
	private Rsssl_Two_Factor_User_Factory $factory;

	/**
	 * Per-instance cache of multisite candidate user IDs keyed by the sorted
	 * role set. Looping consumers (batched reset/forced-role queues, the
	 * 9.1.1 reset-fix while-loop) re-enter the repository many times in a
	 * single request with identical role inputs; caching avoids re-running
	 * the wp_usermeta scan on every iteration.
	 *
	 * @var array<string, int[]>
	 */
	private array $candidateIdsCache = [];

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		self::registerCacheGroup();
		$this->factory      = new Rsssl_Two_Factor_User_Factory();
	}

	/**
	 * Wire the WP action hooks that invalidate the candidate-IDs object cache
	 * when role membership or network site topology changes. Idempotent so it
	 * is safe to call from the plugin bootstrap on every request.
	 *
	 * No-op on single-site installs: `collectMultisiteUserIdsForRoles()` is
	 * only reached from the multisite branch of `buildUserCollection()`, so
	 * the cache has no consumer there and there is nothing to invalidate.
	 */
	public static function registerCacheInvalidationHooks(): void
	{
		self::registerCacheGroup();

		if ( self::$cacheHooksRegistered || ! is_multisite() ) {
			return;
		}
		self::$cacheHooksRegistered = true;

		$invalidate = [ self::class, 'bumpCandidateIdsCacheVersion' ];

		// Role membership changes.
		add_action( 'set_user_role', $invalidate );
		add_action( 'add_user_role', $invalidate );
		add_action( 'remove_user_role', $invalidate );

		// Multisite membership changes (per-blog capabilities meta keys appear/disappear).
		add_action( 'add_user_to_blog', $invalidate );
		add_action( 'remove_user_from_blog', $invalidate );

		// Network topology changes (set of capabilities meta keys to scan changes).
		add_action( 'wp_initialize_site', $invalidate );
		add_action( 'wp_delete_site', $invalidate );
	}

	/**
	 * Invalidate every cached candidate-ID set by incrementing the cache
	 * version. New lookups will compute against the bumped version and miss
	 * all prior entries; old entries expire naturally via TTL.
	 *
	 * Accepts (and ignores) any positional arguments WordPress passes from
	 * the action hooks above.
	 */
	public static function bumpCandidateIdsCacheVersion(): void
	{
		self::registerCacheGroup();

		$bumped = wp_cache_incr( self::CACHE_VERSION_KEY, 1, self::CACHE_GROUP );
		if ( false === $bumped ) {
			// Version key not yet seeded in the backend; seed it past the
			// implicit "1" we use on first read so any future lookups miss.
			wp_cache_set( self::CACHE_VERSION_KEY, 2, self::CACHE_GROUP );
		}
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
	 *
	 * Historically this method translated the role filter into a single giant
	 * OR meta_query that joined `wp_usermeta` once per site (one branch per
	 * `${prefix}{blog_id}_capabilities` key) and performed a LIKE on each
	 * branch. On networks with many sites and/or many users this produced a
	 * query with tens of millions of rows examined and runtimes measured in
	 * minutes (observed: 1097s / 68M rows examined → 504 Gateway Timeouts).
	 *
	 * Instead we now resolve the role constraint with a single indexed
	 * `wp_usermeta` query: `meta_key IN (<every site's capabilities key>)`
	 * combined with `meta_value LIKE` per role, deduped in SQL. The union of
	 * user IDs is fed into the final query via `include`. Downstream 2FA
	 * status meta filters operate on global meta keys and remain untouched.
	 */
	private function buildMultiSiteBaseQuery(array $args, Rsssl_Two_FA_Data_Parameters $params): array
	{
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

	    if ( empty( $roles ) ) {
	        return $args;
	    }

	    $candidate_ids = $this->collectMultisiteUserIdsForRoles( $roles );

	    if ( empty( $candidate_ids ) ) {
	        // user_id 0 never exists → guaranteed empty result without scanning usermeta.
	        $args['include'] = [ 0 ];
	        return $args;
	    }

	    $args['include'] = $candidate_ids;
	    return $args;
	}

	/**
	 * Gather the union of user IDs that hold any of the given roles on any
	 * site in the network.
	 *
	 * Runs a single indexed `wp_usermeta` query keyed on every site's
	 * `${prefix}{blog_id}_capabilities` meta_key, OR'd against
	 * `meta_value LIKE '%"<role>"%'` for every requested role, deduped in
	 * SQL via `DISTINCT`. Falls back to chunking the `meta_key IN (...)`
	 * list to stay clear of `max_allowed_packet` on extreme networks.
	 *
	 * @param string[] $roles
	 * @return int[]
	 */
	private function collectMultisiteUserIdsForRoles( array $roles ): array
	{
	    global $wpdb;

	    if ( empty( $roles ) ) {
	        return [];
	    }

	    // Memoize by the normalized role set: identical inputs across a single
	    // request return the cached candidate set without re-querying.
	    $cache_key = $this->candidateIdsCacheKey( $roles );
	    if ( isset( $this->candidateIdsCache[ $cache_key ] ) ) {
	        return $this->candidateIdsCache[ $cache_key ];
	    }

	    // When a persistent object cache (Redis/Memcached/etc.) is available,
	    // try cross-request reuse. With no external cache, wp_cache_* is
	    // request-scoped and indistinguishable from $candidateIdsCache, so
	    // skip the extra round trips.
	    $use_object_cache = function_exists( 'wp_using_ext_object_cache' ) && wp_using_ext_object_cache();
	    $object_cache_key = $use_object_cache ? $this->objectCacheKeyFor( $cache_key ) : '';
	    if ( $use_object_cache ) {
	        $cached = wp_cache_get( $object_cache_key, self::CACHE_GROUP );
	        if ( is_array( $cached ) ) {
	            return $this->candidateIdsCache[ $cache_key ] = $cached;
	        }
	    }

	    // `number => 0` removes the implicit cap of 100 sites that the previous
	    // implementation silently inherited from get_sites() defaults.
	    $site_ids = get_sites( [
	        'fields' => 'ids',
	        'number' => 0,
	    ] );

	    if ( empty( $site_ids ) ) {
	        if ( $use_object_cache ) {
	            wp_cache_set( $object_cache_key, [], self::CACHE_GROUP, self::CACHE_TTL );
	        }
	        return $this->candidateIdsCache[ $cache_key ] = [];
	    }

	    // Build the per-site capabilities meta_key for every site in the network.
	    $cap_keys = [];
	    foreach ( $site_ids as $blog_id ) {
	        $blog_id    = (int) $blog_id;
	        $cap_keys[] = ( $blog_id === 1 )
	            ? $wpdb->base_prefix . 'capabilities'
	            : $wpdb->base_prefix . $blog_id . '_capabilities';
	    }

	    // LIKE clauses for the requested roles (escaped for LIKE wildcards).
	    $like_clauses = [];
	    $like_params  = [];
	    foreach ( $roles as $role ) {
	        $like_clauses[] = 'meta_value LIKE %s';
	        $like_params[]  = '%"' . $wpdb->esc_like( $role ) . '"%';
	    }
	    $like_sql = '(' . implode( ' OR ', $like_clauses ) . ')';

	    // Chunk the meta_key IN(...) list so very large networks do not blow
	    // past max_allowed_packet on a single statement.
	    $candidate_ids = [];
	    foreach ( array_chunk( $cap_keys, 500 ) as $key_chunk ) {
	        $key_placeholders = implode( ',', array_fill( 0, count( $key_chunk ), '%s' ) );

	        $sql = "SELECT DISTINCT user_id FROM {$wpdb->usermeta} "
	             . "WHERE meta_key IN ($key_placeholders) AND $like_sql";

	        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- placeholders built above are all %s.
	        $prepared = $wpdb->prepare( $sql, array_merge( $key_chunk, $like_params ) );
	        // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- bulk meta_key scan, no WP API equivalent.
	        $ids = $wpdb->get_col( $prepared );

	        if ( empty( $ids ) ) {
	            continue;
	        }
	        foreach ( $ids as $uid ) {
	            $uid = (int) $uid;
	            $candidate_ids[ $uid ] = $uid;
	        }
	    }

	    $result = array_values( $candidate_ids );
	    if ( $use_object_cache ) {
	        wp_cache_set( $object_cache_key, $result, self::CACHE_GROUP, self::CACHE_TTL );
	    }
	    return $this->candidateIdsCache[ $cache_key ] = $result;
	}

	/**
	 * Build a stable cache key for a role set so input order does not cause
	 * cache misses.
	 *
	 * @param string[] $roles
	 */
	private function candidateIdsCacheKey( array $roles ): string
	{
	    $normalized = array_values( array_unique( array_filter( $roles ) ) );
	    sort( $normalized, SORT_STRING );
	    return implode( '|', $normalized );
	}

	/**
	 * Build the version-stamped object-cache key for a normalized role set.
	 * Embedding the current cache version means a single
	 * bumpCandidateIdsCacheVersion() invalidates every prior entry without
	 * having to enumerate keys.
	 */
	private function objectCacheKeyFor( string $role_set_key ): string
	{
	    self::registerCacheGroup();

	    $version = wp_cache_get( self::CACHE_VERSION_KEY, self::CACHE_GROUP );
	    if ( false === $version ) {
	        $version = 1;
	        wp_cache_set( self::CACHE_VERSION_KEY, $version, self::CACHE_GROUP );
	    }
	    return 'rsssl_multisite_role_user_ids:v' . (int) $version . ':' . md5( $role_set_key );
	}

	/**
	 * Make the 2FA object-cache group network-wide on multisite installs.
	 *
	 * The cached role candidate IDs are network-wide by design. Without a
	 * global cache group, persistent object-cache drop-ins may scope the same
	 * group/key pair per blog, so role changes on a subsite would not
	 * invalidate entries read from another site's cache namespace.
	 */
	private static function registerCacheGroup(): void
	{
	    if ( is_multisite() && function_exists( 'wp_cache_add_global_groups' ) ) {
	        wp_cache_add_global_groups( [ self::CACHE_GROUP ] );
	    }
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
