<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

if ( is_admin() ) {
//	add_filter('rsssl_notices', 'rsssl_admin_username_changed', 50, 20);
//	add_filter('rsssl_notices', 'rsssl_user_id_one_enumeration', 50, 21);
}

/**
 * @return void
 *
 * Username 'admin' changed notice
 */
function rsssl_admin_username_changed( $notices ) {
	$notices['username_admin_changed'] = array(
		'condition' => 'rsssl_username_admin_changed',
		'callback' => '_true_',
		'score' => 5,
		'output' => array(
			'true' => array(
				'msg' => sprintf(__("Username 'admin' has been changed to %s", "really-simple-ssl"), get_option('rsssl_new_admin_username') ),
				'icon' => 'open',
				'dismissible' => true,
			),
		),
	);
}

/**
 * @return void
 *
 * User id 1 exists, user enumeration allowed notice
 */
function rsssl_user_id_one_enumeration( $notices ) {
	$notices['user_id_one'] = array(
		'condition' => 'rsssl_id_one_no_enumeration',
		'callback' => '_true_',
		'score' => 5,
		'output' => array(
			'true' => array(
				'msg' => __("User id 1 exists and user enumeration hasn't been disabled.", "really-simple-ssl"),
				'icon' => 'open',
				'dismissible' => true,
			),
		),
	);
}
/**
 * @return void
 *
 * Rename admin user
 */
function rsssl_rename_admin_user() {

    // Get user data for login admin
	$admin_user = get_user_by('login','admin');

	if ( $admin_user ) {
		$admin_user_id  = $admin_user->data->ID;
		$admin_userdata = get_userdata( $admin_user_id );
		$admin_email    = $admin_userdata->data->user_email;

		if ( current_user_can( 'manage_options' ) ) {

			// update e-mail for existing user. Cannot have two accounts connected to the same e-mail address
			wp_update_user( array(
				'ID'         => $admin_user_id,
				'user_email' => 'temp@example.com',
			) );

			// Generate new user login. Do it here so we can get the ID for this user later in the function
			$new_user_login = rsssl_generate_random_string( 12 );
			update_option('rsssl_new_admin_username', $new_user_login);

			// Populate the new user data. Use current 'admin' userdata wherever available
			$new_userdata = array(
				'user_pass'            => rsssl_generate_random_string( 12 ), //(string) The plain-text user password.
				'user_login'           => $new_user_login, //(string) The user's login username.
				'user_nicename'        => isset( $admin_user->data->user_nicename ) ? $admin_user->data->user_nicename : '', //(string) The URL-friendly user name.
				'user_url'             => isset( $admin_user->data->user_url ) ? $admin_user->data->user_url : '', //(string) The user URL.
				'user_email'           => isset( $admin_email ) ? $admin_email : '', //(string) The user email address.
				'display_name'         => isset( $admin_user->data->display_name ) ? $admin_user->data->display_name : '', //(string) The user's display name. Default is the user's username.
				'nickname'             => isset( $admin_user->data->nickname ) ? $admin_user->data->nickname : '', //(string) The user's nickname. Default is the user's username.
				'first_name'           => isset( $admin_user->data->user_firstname ) ? $admin_user->data->user_firstname : '', //(string) The user's first name. For new users, will be used to build the first part of the user's display name if $display_name is not specified.
				'last_name'            => isset( $admin_user->data->user_lastname ) ? $admin_user->data->user_lastname : '', //(string) The user's last name. For new users, will be used to build the second part of the user's display name if $display_name is not specified.
				'description'          => isset( $admin_user->data->description ) ? $admin_user->data->description : '', //(string) The user's biographical description.
				'rich_editing'         => isset( $admin_user->data->rich_editing ) ? $admin_user->data->rich_editing : '', //(string|bool) Whether to enable the rich-editor for the user. False if not empty.
				'syntax_highlighting'  => isset( $admin_user->data->syntax_highlighting ) ? $admin_user->data->syntax_highlighting : '', //(string|bool) Whether to enable the rich code editor for the user. False if not empty.
				'comment_shortcuts'    => isset( $admin_user->data->comment_shortcuts ) ? $admin_user->data->comment_shortcuts : '', //(string|bool) Whether to enable comment moderation keyboard shortcuts for the user. Default false.
				'admin_color'          => isset( $admin_user->data->admin_color ) ? $admin_user->data->admin_color : '', //(string) Admin color scheme for the user. Default 'fresh'.
				'use_ssl'              => isset( $admin_user->data->use_ssl ) ? $admin_user->data->use_ssl : '', //(bool) Whether the user should always access the admin over https. Default false.
				'user_registered'      => isset( $admin_user->data->user_registered ) ? $admin_user->data->user_registered : '', //(string) Date the user registered. Format is 'Y-m-d H:i:s'.
				'show_admin_bar_front' => isset( $admin_user->data->show_admin_bar_front ) ? $admin_user->data->show_admin_bar_front : '', //(string|bool) Whether to display the Admin Bar for the user on the site's front end. Default true.
				'role'                 => isset( $admin_user->roles[0] ) ? $admin_user->roles[0] : '', //(string) User's role.
				'locale'               => isset( $admin_user->data->locale ) ? $admin_user->data->locale : '', //(string) User's locale. Default empty.
			);

			// Create new admin user
			wp_insert_user( $new_userdata );

			// Delete old user and attribute posts to new user ID
			$new_user = get_user_by('login',$new_user_login);
			wp_delete_user( $admin_user_id, $new_user->data->ID);

			// On multisite we have to update the $wpdb->prefix . sitemeta -> meta_key -> site_admins -> meta_value to the new username
			if ( is_multisite() ) {

				global $wpdb;

				$site_admins = $wpdb->query("SELECT 'meta_value' FROM `wp_sitemeta` WHERE `meta_key` = 'site_admins'");
				$site_admins = str_replace('admin', $new_user_login, $site_admins);
				$wpdb->query("UPDATE `wp_sitemeta` SET `meta_value` = $site_admins WHERE `meta_key` = 'site_admins'");

			}

			update_option('rsssl_username_admin_changed', true );
		}
	}
}


/**
 * @return bool
 *
 * Notice condition
 */
function rsssl_username_admin_changed() {
	if ( get_option('rsssl_username_admin_changed') ) {
		return true;
	}

	return false;
}

/**
 * @return bool
 *
 * Check if user ID 1 exists end if user enumeration has been disabled
 */
function rsssl_id_one_no_enumeration() {
	$user_id_one = get_user_by('id', 1);
	if ( $user_id_one && !rsssl_get_option('disable_user_enumeration') ) {
		return true;
	}

	return false;
}
