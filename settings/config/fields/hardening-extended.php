<?php
defined( 'ABSPATH' ) or die();

add_filter( 'rsssl_fields', function( $fields ) {
	return array_merge( $fields,
		[


			[
				'id'                 => 'change_debug_log_location',
				'group_id'           => 'hardening-extended',
				'menu_id'            => 'hardening-extended',
				'type'               => 'checkbox',
				'tooltip'  => __( "A debug.log is publicly accessibile and has a standard location. This will change the location to a randomly named folder in /wp-content/", 'really-simple-ssl' ),
				'email'            => [
					'title'   => __( "Settings update: Debug.log file relocated", 'really-simple-ssl' ),
					'message' => __( "From now on, the debug.log won’t be publicly accessible whenever wp-debugging is enabled. The debug log will be stored in a randomly named folder in /wp-content/. This prevents possible leakage of sensitive debugging information.", 'really-simple-ssl' ),
					'url'     => 'https://really-simple-ssl.com/instructions/debug-log-has-been-relocated-but-where',
				],
				'label'              => __( "Change debug.log file location", 'really-simple-ssl' ),
				'disabled'           => false,
				'default'            => false,
			],
			[
				'id'       => 'disable_application_passwords',
				'menu_id'  => 'hardening-extended',
				'group_id' => 'hardening-extended',
				'type'     => 'checkbox',
				'label'    => __( "Disable application passwords", 'really-simple-ssl' ),
				'disabled' => false,
				'default'  => false,
			],
			[
				'id'       => 'block_admin_creation',
				'menu_id'  => 'hardening-extended',
				'group_id' => 'hardening-extended',
				'type'     => 'checkbox',
				'warning'  => __( "This setting will block attempts to assign administrator roles outside the native user creation process by WordPress. This might include other plugins that create, edit or assign roles to users. If you need to create an administrator in a third-party plugin, temporarily disable this setting while you make the changes.", 'really-simple-ssl' ),
				'tooltip'  => __( "This setting will block attempts to assign administrator roles outside the native user creation process by WordPress. This might include other plugins that create, edit or assign roles to users. If you need to create an administrator in a third-party plugin, temporarily disable this setting while you make the changes.", 'really-simple-ssl' ),
				'help'               => [
					'label' => 'default',
					'url'   => 'instructions/about-hardening-features/',
					'title' => __( "Unauthorized administrators", 'really-simple-ssl' ),
					'text'  => __( 'Many vulnerabilities are exploited by injecting a user with administrator capabilities outside of the native WordPress creation process. Under advanced hardening you can prevent this from happening.', 'really-simple-ssl' ),
				],
				'label'    => __( "Restrict creation of administrator roles", 'really-simple-ssl' ),
				'disabled' => false,
				'default'  => false,
			],
			[
				'id'       => 'disable_http_methods',
				'menu_id'  => 'hardening-extended',
				'group_id' => 'hardening-extended',
				'type'     => 'checkbox',
				'tooltip'  => __( "This will limit or fully disable HTTP requests that are not needed, but could be used with malicious intent.", 'really-simple-ssl' ),
				'label'    => __( "Disable HTTP methods", 'really-simple-ssl' ),
				'disabled' => false,
				'default'  => false,
			],
			[
				'id'       => 'rename_db_prefix',
				'menu_id'  => 'hardening-extended',
				'group_id' => 'hardening-extended',
				'email'            => [
					'title'   => __( "Settings update: Database prefix changed", 'really-simple-ssl' ),
					'message' => __( "Security through obscurity. Your site is no longer using the default wp_ prefix for database tables. The process has been designed to only complete and replace the tables after all wp_ tables are successfully renamed. In the unlikely event that this does lead to database issues on your site, please navigate to our troubleshooting article.", 'really-simple-ssl' ),
					'url'     => 'instructions/database-issues-after-changing-prefix',
				],
				'tooltip'  => __( "This will permanently change your database prefixes and you can NOT rollback this feature. Please make sure you have a back-up.", 'really-simple-ssl' ),
				'warning'  => __( "This will permanently change your database prefixes and you can NOT rollback this feature. Please make sure you have a back-up.", 'really-simple-ssl' ),
				'type'     => 'checkbox',
				'label'    => __( "Rename and randomize your database prefix", 'really-simple-ssl' ),
				'disabled' => false,
				'default'  => false,
			],
			[
				'id'       => 'permission_detection',
				'menu_id'  => 'hardening-extended',
				'group_id' => 'hardening-extended',
				'type'     => 'checkbox',
				'tooltip'  => __( "Really Simple Security will scan for insecure file and folder permissions on a weekly basis. You will receive an email report and Dashboard notice if insecure permissions are found.", 'really-simple-ssl' ),
				'label'    => __( "File permissions check", 'really-simple-ssl' ),
				'disabled' => false,
				'default'  => false,
			],
			[
				'id'       => 'change_login_url_enabled',
				'menu_id'  => 'hardening-extended',
				'group_id' => 'hardening-extended',
				'warning'  => true,
				'type'     => 'checkbox',
				'tooltip'  => __( "Allows you to enter a custom login URL.", 'really-simple-ssl' ),
				'label'    => __( "Enable Custom login URL", 'really-simple-ssl' ),
				'email'            => [
					'title'   => __( "You have changed your login URL", 'really-simple-ssl' ),
					'message' => __( "Your login URL has changed to {login_url} to prevent common bot attacks on standard login URLs. Learn more about this feature, common questions and measures to prevent any issues.", 'really-simple-ssl' ),
					'url'     => 'https://really-simple-ssl.com/instructions/login-url-changed',
				],
				'disabled' => false,
				'default'  => false,
			],
			[
				'id'       => 'change_login_url',
				'menu_id'  => 'hardening-extended',
				'group_id' => 'hardening-extended',
				'type'     => 'text',
				'tooltip'  => __( "Enter a custom login URL. This allows you to log in via this custom URL instead of /wp-admin or /wp-login.php", 'really-simple-ssl' ),
				'placeholder'  => __( "Example: If you want to change your login page from /wp-admin/ to /control/ answer: control", 'really-simple-ssl' ),
				'label'    => __( "Custom login URL", 'really-simple-ssl' ),
				'disabled' => false,
				'default'  => false,
				'condition_action'   => 'hide',
				'pattern' => '[a-zA-Z0-9\-_]+',
				'react_conditions' => [
					'relation' => 'AND',
					[
						'change_login_url_enabled' => 1,
					]
				],
			],
			[
				'id'       => 'change_login_url_failure_url',
				'menu_id'  => 'hardening-extended',
				'group_id' => 'hardening-extended',
				'type'     => 'postdropdown',
				'tooltip'  => __( "Users trying to enter via /wp-admin or /wp-login.php will be redirected to this URL.", 'really-simple-ssl' ),
				'label'    => '',
				'disabled' => false,
				'default'  => '404_default',
				'condition_action'   => 'hide',
				'react_conditions' => [
					'relation' => 'AND',
					[
						'change_login_url_enabled' => 1,
					]
				],
			],
		]
	);
}, 200 );
