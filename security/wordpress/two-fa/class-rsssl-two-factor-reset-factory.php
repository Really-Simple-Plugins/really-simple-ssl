<?php

namespace RSSSL\Security\WordPress\Two_FA;

use RSSSL\Security\WordPress\Two_Fa\Rsssl_Two_Factor_Admin;
use rsssl_mailer;

class RSSSL_Two_Factor_Reset_Factory {

    public function __construct()
    {
        add_action('rsssl_process_batched_users', [self::class, 'batched_process'], 10, 3);
    }

    public static function reset_fix(): void
    {
        $self = new self();
		$forced_roles = rsssl_get_option('two_fa_forced_roles', array());
		if(empty($forced_roles)) {
			update_option('rsssl_reset_fix', false, false);
			return;
		}
        $user_count = (int)$self->get_count_expired_users($self->get_expired_users_query());

        if ($user_count > 0 ) {
            $batch_size = 1000;
            wp_schedule_single_event(time()+ 20, 'rsssl_process_batched_users', [$self->get_expired_users_query(), $user_count, $batch_size]);
        } else {
            update_option('rsssl_reset_fix', false, false);
        }
    }

    public function get_expired_users_query()
    {
        global $wpdb;
        $days_threshold = rsssl_get_option('two_fa_grace_period', 30);
        $filter_value = 'expired';

        $enabled_roles = array_unique(array_merge(
            defined('rsssl_pro') ? rsssl_get_option('two_fa_enabled_roles_totp', array()) : array(),
            rsssl_get_option('two_fa_enabled_roles_email', array())
        ));

        $forced_roles = rsssl_get_option('two_fa_forced_roles', array());

        $fields = ['id', 'user', 'status_for_user', 'rsssl_two_fa_providers', 'user_role']; // Example fields
        $enabled_roles_placeholders = implode(',', array_map(function($role) { return "'$role'"; }, $enabled_roles));
        $forced_roles_placeholder = implode(',', array_map(function($role) { return "'$role'"; }, $forced_roles));
        $query = Rsssl_Two_Factor_Admin::generate_query($fields, $enabled_roles_placeholders, $forced_roles_placeholder, $forced_roles);

        if ($filter_value !== 'all') {
            $query .= $wpdb->prepare(" HAVING status_for_user = %s", $filter_value);
        }
        $prepared_query = $wpdb->prepare($query, array_merge(
            array_fill(0, count($forced_roles), $days_threshold)
        ));

        return $prepared_query;
    }

    public function get_count_expired_users($query): ?string
    {
        global $wpdb;
        $count_query = "SELECT COUNT(*) FROM ($query) AS count_table";
        return $wpdb->get_var($count_query);
    }

    public static function batched_process($query, $user_count, $batch_size = 500): void
    {
        global $wpdb;
        $paged_query = $query . " LIMIT %d";

        while ($user_count > 0) {
            $current_query = $wpdb->prepare($paged_query, $batch_size);
            $users = $wpdb->get_results($current_query);

            foreach ($users as $user) {
                Rsssl_Two_Fa_Status::delete_two_fa_meta((int)$user->id);
                // Set the rsssl_two_fa_last_login to now, so the user will be forced to use 2fa.
                update_user_meta((int)$user->id, 'rsssl_two_fa_last_login', gmdate('Y-m-d H:i:s'));
            }
            $user_count -= $batch_size;
        }
    }
}