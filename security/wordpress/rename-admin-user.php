<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

add_action('admin_init', 'rsssl_rename_admin_user');
/**
 * @return void
 *
 * Rename admin user
 */
function rsssl_rename_admin_user() {

//    if ( rsssl_get_option('rsssl_rename_admin_user') == '1' && ! get_option('rsssl_admin_user_updated') ) {

        // Get user data for admin
		$admin_user = get_user_by('login','admin');

		if ( $admin_user ) {
			$admin_user_id  = $admin_user->data->ID;
			$admin_userdata = get_userdata( $admin_user_id );
			$login          = $admin_userdata->data->user_login;
			$admin_email    = $admin_userdata->data->user_email;

			if ( current_user_can( 'manage_options' ) && $login === 'admin' ) {
				error_log( print_r( $admin_userdata, true ) );

				// update e-mail for existing user
				wp_update_user( array(
					'ID'         => $admin_user_id,
					'user_email' => 'temp@example.com',
				) );

				$new_userdata = array(
//				'ID'                    => '',    //(int) User ID. If supplied, the user will be updated.
					'user_pass'            => rsssl_generate_random_string( 12 ),
					//(string) The plain-text user password.
					'user_login'           => rsssl_generate_random_string( 12 ),
					//(string) The user's login username.
					'user_nicename'        => $admin_user->data->user_nicename,
					//(string) The URL-friendly user name.
					'user_url'             => '',
					//(string) The user URL.
					'user_email'           => $admin_email,
					//(string) The user email address.
					'display_name'         => $admin_user->data->display_name,
					//(string) The user's display name. Default is the user's username.
					'nickname'             => '',
					//(string) The user's nickname. Default is the user's username.
					'first_name'           => '',
					//(string) The user's first name. For new users, will be used to build the first part of the user's display name if $display_name is not specified.
					'last_name'            => '',
					//(string) The user's last name. For new users, will be used to build the second part of the user's display name if $display_name is not specified.
					'description'          => '',
					//(string) The user's biographical description.
					'rich_editing'         => '',
					//(string|bool) Whether to enable the rich-editor for the user. False if not empty.
					'syntax_highlighting'  => '',
					//(string|bool) Whether to enable the rich code editor for the user. False if not empty.
					'comment_shortcuts'    => '',
					//(string|bool) Whether to enable comment moderation keyboard shortcuts for the user. Default false.
					'admin_color'          => '',
					//(string) Admin color scheme for the user. Default 'fresh'.
					'use_ssl'              => '',
					//(bool) Whether the user should always access the admin over https. Default false.
					'user_registered'      => '',
					//(string) Date the user registered. Format is 'Y-m-d H:i:s'.
					'show_admin_bar_front' => '',
					//(string|bool) Whether to display the Admin Bar for the user on the site's front end. Default true.
					'role'                 => $admin_user->roles[0],
					//(string) User's role.
					'locale'               => '',
					//(string) User's locale. Default empty.
				);

				// Replace admin user with new admin user
				error_log( "create user" );

				// 5YvQ^VC^RT685^Th^D1gKyE(
//            wp_create_user( rsssl_generate_random_string(12), rsssl_generate_random_string(12), $admin_email );

				wp_insert_user( $new_userdata );
				// Attribute posts to new user

				// Delete old user

//            update_option('rsssl_admin_user_updated', true);
			}
		}
//    }
}

/**
 * @return bool
 *
 * Check if user admin exists
 */
function rsssl_has_admin_user() {

	$users = get_users();
	foreach ( $users as $user ) {
		if ( $user->data->user_login === 'admin') {
			return true;
		}
	}

    return false;
}
