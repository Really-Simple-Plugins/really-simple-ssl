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
	public function createFromWPUser( WP_User $user, array $forcedRoles, array $enabledRoles, int $daysThreshold ): ?Rsssl_Two_FA_user {
		// Retrieve user roles.
		$userRoles = $user->roles;
		if ( is_multisite() && empty( $userRoles ) ) {
			$userRoles = [];

			// On multisite, roles are stored per site under `<blog_prefix>capabilities` user meta.
			// get_blogs_of_user() does not reliably include the role; fetch capabilities per site instead.
			$blogs = get_blogs_of_user( $user->ID );
			if ( ! empty( $blogs ) && is_array( $blogs ) ) {
				global $wpdb;
				foreach ( $blogs as $blog ) {
					// Determine a blog ID property that exists on this object/array.
					$blogId = 0;
					if ( is_object( $blog ) ) {
						$blogId = isset( $blog->userblog_id ) ? (int) $blog->userblog_id : ( isset( $blog->blog_id ) ? (int) $blog->blog_id : ( isset( $blog->id ) ? (int) $blog->id : 0 ) );
					} elseif ( is_array( $blog ) ) {
						$blogId = isset( $blog['userblog_id'] ) ? (int) $blog['userblog_id'] : ( isset( $blog['blog_id'] ) ? (int) $blog['blog_id'] : ( isset( $blog['id'] ) ? (int) $blog['id'] : 0 ) );
					}

					if ( $blogId > 0 ) {
						$prefix    = $wpdb->get_blog_prefix( $blogId );
						$caps      = get_user_meta( $user->ID, $prefix . 'capabilities', true );
						if ( is_array( $caps ) ) {
							// Collect roles where the capability is truthy.
							$rolesForSite = array_keys( array_filter( $caps ) );
							if ( ! empty( $rolesForSite ) ) {
								$userRoles = array_merge( $userRoles, $rolesForSite );
							}
						}
					}
				}
			}

			// Fall back for network admins who may not have per-site roles.
			if ( function_exists( 'is_super_admin' ) && is_super_admin( $user->ID ) ) {
				$userRoles[] = 'administrator';
			}

			$userRoles = array_values( array_unique( $userRoles ) );
		}

		if ( empty( $userRoles ) ) {
			return null;
		}

		// Use the status service to determine the user's status.
		$statusForUser = $this->statusService->determineStatus( $user->ID, $forcedRoles, $daysThreshold );

		// Determine two-factor provider.
		$provider = $this->determineTwoFaProvider( $user->ID );

		// Identify matching roles.
		$matchingRoles = array_intersect( $userRoles, $enabledRoles );


		// If multiple roles exist and one of them is forced, prefer the forced role.
		if ( ! empty( $forcedRoles ) && count( $matchingRoles ) > 1 ) {
			$matchingForcedRoles = array_intersect( $matchingRoles, $forcedRoles );
			if ( ! empty( $matchingForcedRoles ) ) {
				$matchingRoles = $matchingForcedRoles;
			}
		}

		// If multiple roles remain, choose the most important one based on the defined hierarchy.
		if ( count( $matchingRoles ) > 1 ) {
			usort( $matchingRoles, function ( $role1, $role2 ) {
				$priority1 = $this->roleHierarchy[ $role1 ] ?? 0;
				$priority2 = $this->roleHierarchy[ $role2 ] ?? 0;

				return $priority2 <=> $priority1;
			} );
		}

		// Determine the most important role.
		$mostImportantRole = reset( $matchingRoles );

		return new Rsssl_Two_FA_user (
			$user->ID,
			$user->user_login,
			$statusForUser,
			$provider,
			$userRoles
		);
	}

	/**
	 * Determine the active two-factor provider.
	 */
	protected function determineTwoFaProvider( int $userId ): string {
		$providers = [
			[ 'provider' => 'totp', 'meta_key' => 'rsssl_two_fa_status_totp' ],
			[ 'provider' => 'email', 'meta_key' => 'rsssl_two_fa_status_email' ],
			[ 'provider' => 'passkey', 'meta_key' => 'rsssl_two_fa_status_passkey' ],
		];

		foreach ( $providers as $entry ) {
			if ( get_user_meta( $userId, $entry['meta_key'], true ) === 'active' ) {
				return $entry['provider'];
			}
		}

		return 'none';
	}
}