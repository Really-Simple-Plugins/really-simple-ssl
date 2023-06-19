<?php
defined('ABSPATH') or die();

/**
 * Username 'admin' changed notice
 * @return array
 */
function rsssl_admin_username_changed( $notices ) {
	$notices['username_admin_changed'] = array(
		'condition' => ['rsssl_username_admin_changed'],
		'callback' => '_true_',
		'score' => 5,
		'output' => array(
			'true' => array(
				'msg' => sprintf(__("Username 'admin' has been changed to %s", "really-simple-ssl"),esc_html(get_site_transient('rsssl_username_admin_changed')) ),
				'icon' => 'open',
				'dismissible' => true,
			),
		),
	);
	return $notices;
}
add_filter('rsssl_notices', 'rsssl_admin_username_changed');

/**
 * Add admin as not allowed username
 * @param array $illegal_user_logins
 *
 * @return array
 */
function rsssl_prevent_admin_user_add(array $illegal_user_logins){
	$illegal_user_logins[] = 'admin';
	$illegal_user_logins[] = 'administrator';
	return $illegal_user_logins;
}
add_filter( 'illegal_user_logins', 'rsssl_prevent_admin_user_add' );

/**
 * Rename admin user
 * @return bool
 */
function rsssl_rename_admin_user() {
	if ( !rsssl_user_can_manage() ) {
		return false;
	}
	//to be able to update the admin user email, we need to disable this filter temporarily
	remove_filter( 'illegal_user_logins', 'rsssl_prevent_admin_user_add' );

	// Get user data for login admin
	$admin_user = get_user_by('login','admin');
	if ( $admin_user ) {
		// Get the new user login
		$new_user_login = trim(sanitize_user(rsssl_get_option('new_admin_user_login')));
		if ( rsssl_new_username_valid() ) {
			$admin_user_id  = $admin_user->data->ID;
			$admin_userdata = get_userdata( $admin_user_id );
			$admin_email    = $admin_userdata->data->user_email;
			global $wpdb;
			//get current user hash
			$user_hash = $wpdb->get_var($wpdb->prepare("select user_pass from {$wpdb->base_prefix}users where ID = %s", $admin_user_id) );
			//create temp email address
			$domain = site_url();
			$parse  = parse_url( $domain );
			$host   = $parse['host'] ?? 'example.com';
			$email  = "$new_user_login@$host";

			// Do not send an e-mail with this temporary e-mail address
			add_filter('send_email_change_email', '__return_false');

			// update e-mail for existing user. Cannot have two accounts connected to the same e-mail address
			$success = wp_update_user( array(
				'ID'         => $admin_user_id,
				'user_email' => $email,
			) );

			if ( ! $success ) {
				return false;
			}

			// Populate the new user data. Use current 'admin' userdata wherever available
			$new_userdata = array(
				'user_pass'            => rsssl_generate_random_string( 12 ), //temp, overwrite with actual hash later.
				//(string) The plain-text user password.
				'user_login'           => $new_user_login,
				//(string) The user's login username.
				'user_nicename'        => isset( $admin_user->data->user_nicename ) ? $admin_user->data->user_nicename : '',
				//(string) The URL-friendly user name.
				'user_url'             => isset( $admin_user->data->user_url ) ? $admin_user->data->user_url : '',
				//(string) The user URL.
				'user_email'           => isset( $admin_email ) ? $admin_email : '',
				//(string) The user email address.
				'display_name'         => isset( $admin_user->data->display_name ) ? $admin_user->data->display_name : '',
				//(string) The user's display name. Default is the user's username.
				'nickname'             => isset( $admin_user->data->nickname ) ? $admin_user->data->nickname : '',
				//(string) The user's nickname. Default is the user's username.
				'first_name'           => isset( $admin_user->data->user_firstname ) ? $admin_user->data->user_firstname : '',
				//(string) The user's first name. For new users, will be used to build the first part of the user's display name if $display_name is not specified.
				'last_name'            => isset( $admin_user->data->user_lastname ) ? $admin_user->data->user_lastname : '',
				//(string) The user's last name. For new users, will be used to build the second part of the user's display name if $display_name is not specified.
				'description'          => isset( $admin_user->data->description ) ? $admin_user->data->description : '',
				//(string) The user's biographical description.
				'rich_editing'         => isset( $admin_user->data->rich_editing ) ? $admin_user->data->rich_editing : '',
				//(string|bool) Whether to enable the rich-editor for the user. False if not empty.
				'syntax_highlighting'  => isset( $admin_user->data->syntax_highlighting ) ? $admin_user->data->syntax_highlighting : '',
				//(string|bool) Whether to enable the rich code editor for the user. False if not empty.
				'comment_shortcuts'    => isset( $admin_user->data->comment_shortcuts ) ? $admin_user->data->comment_shortcuts : '',
				//(string|bool) Whether to enable comment moderation keyboard shortcuts for the user. Default false.
				'admin_color'          => isset( $admin_user->data->admin_color ) ? $admin_user->data->admin_color : '',
				//(string) Admin color scheme for the user. Default 'fresh'.
				'use_ssl'              => isset( $admin_user->data->use_ssl ) ? $admin_user->data->use_ssl : '',
				//(bool) Whether the user should always access the admin over https. Default false.
				'user_registered'      => isset( $admin_user->data->user_registered ) ? $admin_user->data->user_registered : '',
				//(string) Date the user registered. Format is 'Y-m-d H:i:s'.
				'show_admin_bar_front' => isset( $admin_user->data->show_admin_bar_front ) ? $admin_user->data->show_admin_bar_front : '',
				//(string|bool) Whether to display the Admin Bar for the user on the site's front end. Default true.
				'role'                 => isset( $admin_user->roles[0] ) ? $admin_user->roles[0] : '',
				//(string) User's role.
				'locale'               => isset( $admin_user->data->locale ) ? $admin_user->data->locale : '',
				//(string) User's locale. Default empty.
			);

			// Create new admin user
			$new_user_id = wp_insert_user( $new_userdata );
			if ( ! $new_user_id || is_wp_error($new_user_id) ) {
				return false;
			}

			//store original user hash in this user.
			$wpdb->update(
				$wpdb->base_prefix.'users',
				['user_pass' => $user_hash ],
				['ID' => $new_user_id]
			);

			require_once( ABSPATH . 'wp-admin/includes/user.php' );
			wp_delete_user( $admin_user_id, $new_user_id );

			// On multisite we have to update the $wpdb->prefix . sitemeta -> meta_key -> site_admins -> meta_value to the new username
			if ( is_multisite() ) {
				global $wpdb;
				$site_admins = $wpdb->get_var( "SELECT meta_value FROM {$wpdb->base_prefix}sitemeta WHERE meta_key = 'site_admins'" );
				if ( is_serialized( $site_admins ) ) {
					$unserialized = unserialize( $site_admins );
					foreach ( $unserialized as $index => $site_admin ) {
						if ( $site_admin === 'admin' ) {
							$unserialized[ $index ] = $new_user_login;
						}
					}
					$site_admins = serialize( $unserialized );
				}
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->base_prefix}sitemeta SET meta_value = %s WHERE meta_key = 'site_admins'", $site_admins ) );
			}

			set_site_transient( 'rsssl_username_admin_changed', $new_user_login, DAY_IN_SECONDS );
		}
		return true;
	}
	return true;
}
add_action('rsssl_after_saved_fields','rsssl_rename_admin_user', 30);

/**
 * @return bool
 *
 * Notice condition
 */
function rsssl_username_admin_changed() {
	if ( get_site_transient('rsssl_username_admin_changed') ) {
		return true;
	}

	return false;
}

