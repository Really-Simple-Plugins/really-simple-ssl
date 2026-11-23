<?php
defined( 'ABSPATH' ) or die();

function rsssl_menu() {
	if ( ! rsssl_user_can_manage() ) {
		return [];
	}
	$menu_items = [
		[
			"id"             => "dashboard",
			"title"          => __( "Dashboard", 'really-simple-ssl' ),
			'default_hidden' => false,
			'menu_items'     => [],
		],
		[
			"id"             => "settings",
			"title"          => __( "Settings", 'really-simple-ssl' ),
			'default_hidden' => false,
			'menu_items'     => [
				[
					'id'       => 'general',
					'group_id' => 'general',
					'title'    => __( 'General', 'really-simple-ssl' ),
					'helpLink' => rsssl_tracked_url( 'instructions/about-our-general-settings', 'documentation', 'settings_general_help' ),
					'groups'   => [
						[
							'id'       => 'general_settings',
							'group_id' => 'general_settings',
							'title'    => __( 'General', 'really-simple-ssl' ),
							'helpLink' => rsssl_tracked_url( 'instructions/about-our-general-settings', 'documentation', 'settings_general_settings_help' ),
						],
						[
							'id'       => 'general_email',
							'group_id' => 'general_email',
							'title'    => __( 'Email', 'really-simple-ssl' ),
							'helpLink' => rsssl_tracked_url( 'instructions/about-our-general-settings', 'documentation', 'settings_general_email_help' ),
						],
						[
							'id'       => 'general_captcha',
							'group_id' => 'general_captcha',
							'title'    => __( 'Captcha', 'really-simple-ssl' ),
							'helpLink' => rsssl_tracked_url( 'instructions/about-our-general-settings', 'documentation', 'settings_general_captcha_help' ),
							'premium'  => true,
							'upgrade' => rsssl_tracked_url( 'pro', 'upgrade', 'settings_general_captcha_upgrade' ),
							'premium_title' => __( 'Captcha', 'really-simple-ssl' ),
							'intro'      => __( "Really Simple Security can trigger a Captcha to limit access to your site or the log in form.", 'really-simple-ssl' ),
							'premium_text' => __( 'Protect your website against brute-force attacks with a captcha. Choose between Google reCAPTCHA or hCaptcha.', 'really-simple-ssl' ),
						],
						[
							'id'            => 'support',
							'group_id'      => 'support',
							'title'         => __('Premium Support', 'really-simple-ssl'),
							'intro'         => __('The following information is attached when you send this form: license key, scan results, your domain, .htaccess file, debug log and a list of active plugins.', 'really-simple-ssl'),
							'premium'       => true,
							'upgrade' => rsssl_tracked_url( 'pro', 'upgrade', 'settings_support_upgrade' ),
							'premium_text'  => __("Elevate your security with our Premium Support! Our expert team ensures simple, hassle-free assistance whenever you need it.", 'really-simple-ssl'),
							'premium_title' => __('Why Premium Support?', 'really-simple-ssl'),
							'helpLink'      => rsssl_tracked_url( 'instructions/debugging', 'documentation', 'settings_support_help' ),
							'helpLink_text' => __('Debugging with Really Simple Security', "really-simple-ssl"),
						],
					],
				],
				[
					'id' => 'encryption',
					'title' => 'SSL',
					'groups' => [
						[
							'id' => 'encryption_lets_encrypt',
							'group_id' => 'encryption_lets_encrypt',
							'title' => __( 'Let\'s Encrypt', 'really-simple-ssl' ),
							'intro' => __( 'Let\'s Encrypt.', 'really-simple-ssl' ),
							'helpLink' => rsssl_tracked_url( 'about-lets-encrypt', 'documentation', 'settings_encryption_lets_encrypt_help' ),
							'directLink' => rsssl_letsencrypt_wizard_url(),
						],
						[
							'id' => 'encryption_redirect',
							'group_id' => 'encryption_redirect',
							'title' => __( 'Redirection', 'really-simple-ssl' ),
							'helpLink' => rsssl_tracked_url( 'remove-htaccess-redirect-site-lockout', 'documentation', 'settings_encryption_redirect_help' ),
						],
						[
							'id' => 'mixed-content-general',
							'group_id' => 'mixed-content-general',
							'title' => __( 'Mixed content', 'really-simple-ssl' ),
							'helpLink' => rsssl_tracked_url( 'instructions/using-the-mixed-content-scan', 'documentation', 'settings_mixed_content_general_help' ),
						],
						[
							'id' => 'mixed-content-scan',
							'group_id' => 'mixed-content-scan',
							'title' => __( 'Mixed Content Scan', 'really-simple-ssl' ),
							'premium' => true,
							'upgrade' => rsssl_tracked_url( 'pro', 'upgrade', 'settings_mixed_content_scan_upgrade' ),
							'premium_title' => __( "Mixed Content Scan", 'really-simple-ssl' ),
							'premium_text' => __( "The extensive mixed content scan will list all issues and provide a fix, or instructions to fix manually.", 'really-simple-ssl' ),
							'helpLink' => rsssl_tracked_url( 'instructions/using-the-mixed-content-scan', 'documentation', 'settings_mixed_content_scan_help' ),
						],
					],
				],
				[
					'id' => 'security_headers',
					'title' => __( 'Security Headers', 'really-simple-ssl' ),
					'featured' => false,
					'menu_items' => [
						[
							'id' => 'recommended_security_headers',
							'group_id' => 'recommended_security_headers',
							'title'    => __( 'Essential Security Headers', 'really-simple-ssl' ),
							'networkwide_required' => true,
							'premium_title'         => __( "The Essentials", 'really-simple-ssl' ),
							'premium'              => true,
							'upgrade' => rsssl_tracked_url( 'pro', 'upgrade', 'settings_recommended_security_headers_upgrade' ),
							'premium_text'         => __( "Protecting your website visitors from malicious attacks and data breaches should be your #1 priority, start with the essentials with Really Simple Security", 'really-simple-ssl' ),
							'helpLink' => rsssl_tracked_url( 'instructions/about-recommended-security-headers', 'documentation', 'settings_recommended_security_headers_help' ),
						],
						[
							'id' => 'hsts',
							'group_id' => 'hsts',
							'premium'              => true,
							'upgrade' => rsssl_tracked_url( 'pro', 'upgrade', 'settings_hsts_upgrade' ),
							'networkwide_required' => true,
							'premium_text'         => __( "HSTS forces browsers always to load a website via HTTPS. It prevents unnecessary redirects and prevents manipulation of data originating from communication with your website.", 'really-simple-ssl' ),
							'title'                => 'HTTP Strict Transport Security',
							'helpLink' => rsssl_tracked_url( 'instructions/about-hsts', 'documentation', 'settings_hsts_help' ),
						],
						[
							'id' => 'permissions_policy',
							'group_id' => 'permissions_policy',
							'title' => 'Permissions Policy',
							'premium_text'         => __( "Control browser features that could allow third parties to misuse data collected by microphone, camera, GEO Location etc, when enabled for your website.", 'really-simple-ssl' ),
							'helpLink'             => rsssl_tracked_url( 'instructions/about-permissions-policy', 'documentation', 'settings_permissions_policy_help' ),
							'networkwide_required' => true,
							'premium'              => true,
							'upgrade' => rsssl_tracked_url( 'pro', 'upgrade', 'settings_permissions_policy_upgrade' ),
						],
						[
							'id' => 'content_security_policy',
							'group_id' => 'content_security_policy',
							'title' => __( 'Content Security Policy', 'really-simple-ssl' ),
							'intro' => __( 'Content Security Policy Headers', 'really-simple-ssl' ),
							'premium' => true,
							'upgrade' => rsssl_tracked_url( 'pro', 'upgrade', 'settings_content_security_policy_upgrade' ),
							'networkwide_required' => true,
							'helpLink' => rsssl_tracked_url( 'instructions/configuring-the-content-security-policy', 'documentation', 'settings_content_security_policy_help' ),
							'groups'  => [
								[
									'id'                   => 'upgrade_insecure_requests',
									'group_id'             => 'upgrade_insecure_requests',
									'networkwide_required' => true,
									'premium'              => true,
									'upgrade' => rsssl_tracked_url( 'pro', 'upgrade', 'settings_upgrade_insecure_requests_upgrade' ),
									'premium_text'         => __( "A correctly configured Content Security Policy can protect your visitors from the most common web attacks. It all starts with denying and upgrading insecure requests on your website.", 'really-simple-ssl' ),
									'helpLink'             => rsssl_tracked_url( 'instructions/configuring-the-content-security-policy', 'documentation', 'settings_upgrade_insecure_requests_help' ),
									'title'                => 'Upgrade Insecure Requests',
								],
								[
									'id'                   => 'frame_ancestors',
									'group_id'             => 'frame_ancestors',
									'networkwide_required' => true,
									'premium'              => true,
									'upgrade' => rsssl_tracked_url( 'pro', 'upgrade', 'settings_frame_ancestors_upgrade' ),
									'premium_text'         => __( "Prevent clickjacking and other malicious attacks by restricting sources that are permitted to embed content from your website.", 'really-simple-ssl' ),
									'helpLink'             => rsssl_tracked_url( 'instructions/frame-ancestors', 'documentation', 'settings_frame_ancestors_help' ),
									'title'                => 'Frame Ancestors',
								],
								[
									'id'                   => 'content_security_policy_source_directives',
									'group_id'             => 'content_security_policy_source_directives',
									'networkwide_required' => true,
									'helpLink'             => rsssl_tracked_url( 'instructions/configuring-the-content-security-policy/', 'documentation', 'settings_content_security_policy_source_directives_help' ),
									'premium'              => true,
									'upgrade' => rsssl_tracked_url( 'pro', 'upgrade', 'settings_content_security_policy_source_directives_upgrade' ),
									'premium_title'        => 'Source Directives with Learning Mode',
									'premium_text'         => __( "Allow only necessary third-party resources to be loaded on your website, thus preventing common attacks. Use our unique learning mode to automatically configure your Content Security Policy.", 'really-simple-ssl' ),
									'title'                => 'Source Directives',
								]
							],
						],
						[
							'id' => 'cross_origin_policy',
							'group_id' => 'cross_origin_policy',
							'networkwide_required' => true,
							'premium'              => true,
							'upgrade' => rsssl_tracked_url( 'pro', 'upgrade', 'settings_cross_origin_policy_upgrade' ),
							'premium_text'         => __( 'This is a security feature implemented by web browsers to control how web pages from different origins can interact with each other.', 'really-simple-ssl' ),
							'title'                => 'Cross Origin Policy',
							'premium_title'        => 'Advanced: Cross Origin Policy',
							'helpLink'             => rsssl_tracked_url( 'instructions/cross-origin-policies', 'documentation', 'settings_cross_origin_policy_help' ),
						],
					],
				],
				[
					'id'        => 'vulnerabilities',
					'title'     => __( 'Vulnerabilities', 'really-simple-ssl' ),
					'menu_items' => [
						[
							'id' => 'vulnerabilities_basic',
							'group_id' => 'vulnerabilities_basic',
							'title' => __( 'Vulnerability scan', 'really-simple-ssl' ),
							'groups' => [
								[
									'id' => 'vulnerabilities_basic',
									'group_id' => 'vulnerabilities_basic',
									'title' => __( 'Vulnerability scan', 'really-simple-ssl' ),
									'intro' => __( 'Here you can configure vulnerability detection, notifications and measures. To learn more about the features displayed, please use the instructions linked in the top-right corner.', 'really-simple-ssl' ),
									'helpLink' => rsssl_tracked_url( 'instructions/about-vulnerabilities/', 'documentation', 'settings_vulnerabilities_basic_help' ),
								],
								[
									'id'       => 'vulnerabilities_overview',
									'group_id' => 'vulnerabilities_overview',
									'title' => __( 'Scan results', 'really-simple-ssl' ),
									'intro' => __( 'This is the vulnerability overview. Here you will find current known vulnerabilities on your system. You can find more information and helpful, actionable insights for every vulnerability under details.', 'really-simple-ssl' ),
									'helpLink' => rsssl_tracked_url( 'instructions/about-vulnerabilities#components', 'documentation', 'settings_vulnerabilities_overview_help' ),
								],
							],
						],
						[
							'id' => 'vulnerabilities_notifications',
							'group_id' => 'vulnerabilities_notifications',
							'title' => __( 'Configuration', 'really-simple-ssl' ),
							'groups'  => [
								[
									'id'       => 'vulnerabilities_notifications',
									'group_id' => 'vulnerabilities_notifications',
									'title' => __( 'Notifications', 'really-simple-ssl' ),
									'intro' => __( 'These notifications are set to the minimum risk level that triggers a notification. For example, the default site-wide notification triggers on high-risk and critical vulnerabilities.', 'really-simple-ssl' ),
									'helpLink' => rsssl_tracked_url( 'instructions/about-vulnerabilities#notifications', 'documentation', 'settings_vulnerabilities_notifications_help' ),
								],
								[
									'id' => 'vulnerabilities-measures-overview',
									'group_id' => 'vulnerabilities_measures',
									'title' => __( 'Measures', 'really-simple-ssl' ),
									'intro' => __( 'You can choose to automate the most common actions for a vulnerability. Each action is set to a minimum risk level, similar to the notifications. Please read the instructions to learn more about the process.', 'really-simple-ssl' ),
									'premium' => true,
									'upgrade' => rsssl_tracked_url( 'pro', 'upgrade', 'settings_vulnerabilities_measures_overview_upgrade' ),
									'helpLink' => rsssl_tracked_url( 'instructions/about-vulnerabilities#measures', 'documentation', 'settings_vulnerabilities_measures_overview_help' ),
									'premium_title' => __( "Automated Measures", 'really-simple-ssl' ),
									'premium_text' => __( "Maintain peace of mind with our simple, but effective automated measures when vulnerabilities are discovered. When needed Really Simple Security will force update or quarantine vulnerable components, on your terms!", 'really-simple-ssl' ),
								],
							],
						],
					],
				],
				[
					'id' => 'hardening',
					'title' => __( 'Hardening', 'really-simple-ssl' ),
					'featured' => false,
					'menu_items' => [
						[
							'id'        => 'hardening-basic',
							'group_id'  => 'hardening-basic',
							'title'     => __( 'Basic', 'really-simple-ssl' ),
							'helpLink'  => rsssl_tracked_url( 'instructions/about-hardening-features', 'documentation', 'settings_hardening_basic_help' ),
						],
						[
							'id' => 'hardening-extended',
							'group_id' => 'hardening-extended',
							'title' => __( 'Advanced', 'really-simple-ssl' ),
							'premium' => true,
							'upgrade' => rsssl_tracked_url( 'pro', 'upgrade', 'settings_hardening_extended_upgrade' ),
							'groups' => [
								[
									'id' => 'hardening_extended',
									'group_id' => 'hardening_extended',
									'premium' => true,
									'upgrade' => rsssl_tracked_url( 'pro', 'upgrade', 'settings_hardening_extended_upgrade' ),
									'premium_text' => __( "Advanced hardening features complement the basic hardening functions by protecting your site against advanced threats and attacks.", 'really-simple-ssl' ),
									'helpLink' => rsssl_tracked_url( 'instructions/about-hardening-features#advanced', 'documentation', 'settings_hardening_extended_help' ),
									'title' => __( 'Advanced Hardening', 'really-simple-ssl' ),
									'intro' => __( 'Advanced hardening features to protect your site against sophisticated threats and attacks.', 'really-simple-ssl' ),
								],
								[
									'id' => 'hardening-xml',
									'group_id' => 'hardening-xml',
									'premium' => true,
									'upgrade' => rsssl_tracked_url( 'pro', 'upgrade', 'settings_hardening_xml_upgrade' ),
									'premium_title' => __( 'XML-RPC with Learning Mode', 'really-simple-ssl' ),
									'premium_text' => __( "Not sure if you're using XML-RPC, or want to restrict unauthorized use of XML-RPC? With learning mode you can see exactly which sources use XML-RPC, and you can revoke where necessary.", 'really-simple-ssl' ),
									'helpLink' => rsssl_tracked_url( 'instructions/about-hardening-features#xml-rpc', 'documentation', 'settings_hardening_xml_help' ),
									'title' => __( 'XML-RPC', 'really-simple-ssl' ),
									'intro' => __( 'Control and monitor the use of XML-RPC on your site with learning mode.', 'really-simple-ssl' ),
								],
							],
						],
					],
				],
				[
					'id' => 'login-security',
					'title' => __('Login Protection', 'really-simple-ssl'),
					'featured' => false,
					'new' => true,
					'menu_items' => [
						[
							'id'                   => 'two-fa',
							'networkwide_required' => true,
							'premium'              => false,
							'premium_text'         => __( 'Get two-factor authentication with Really Simple Security Pro', 'really-simple-ssl' ),
							'helpLink'             => rsssl_tracked_url( 'instructions/about-login-protection', 'documentation', 'settings_two_fa_help' ),
							'title'                =>  __('Login Authentication', 'really-simple-ssl'),
							'groups'  => [
								[
									'id'       => 'two_fa_general',
									'group_id' => 'two_fa_general',
									'premium'              => false,
									'premium_text'         => __( "Start login protection by adding an additional layer during authentication. This will leave authentication less dependent on just a single password. Want to force strong passwords? Check out Password Security.", 'really-simple-ssl' ),
									'helpLink'      => rsssl_tracked_url( 'instructions/about-login-protection', 'documentation', 'settings_two_fa_general_help' ),
									'title'    => __( 'Login Authentication', 'really-simple-ssl' ),
									'intro'    => __( 'Prevent account theft by offering more secure authentication methods. You can configure which methods are available per user role, or even enforce usage of secure authentication. Secure authentication can be either Two-Factor Authentication or Passkey login.', 'really-simple-ssl' ),
								],
								[
									'id'            => 'two_fa_email',
									'group_id'      => 'two_fa_email',
									'premium'       => false,
									'disabled'      => (rsssl_is_email_verified() === false),
									'premium_text'  => __( 'Send an email code during login. You can force user roles to use two-factor authentication, or leave the choose with your users, if so desired.', 'really-simple-ssl' ),
									'upgrade'              => rsssl_tracked_url( 'pro', 'upgrade', 'settings_two_fa_email_upgrade' ),
									'helpLink' => rsssl_tracked_url( 'instructions/two-factor-authentication', 'documentation', 'settings_two_fa_email_help' ),
									'title'         => __( 'Two-Factor Authentication', 'really-simple-ssl' ),
									'intro'         => __( 'Choose between Email Verification (less secure and not recommended for administrators) or the TOTP method with an authenticator app, depending on your convenience and security needs.', 'really-simple-ssl' ),
								],
                                [
                                    'id'       => 'two_fa_passkey',
                                    'group_id' => 'two_fa_passkey',
                                    'premium'              => true,
                                    'upgrade' => rsssl_tracked_url( 'pro', 'upgrade', 'settings_two_fa_passkey_upgrade' ),
                                    'premium_text'         => __( 'Get Login Protection with %sReally Simple SSL Pro%s', 'really-simple-ssl' ),
                                    'helpLink' => rsssl_tracked_url( 'instructions/two-factor-authentication', 'documentation', 'settings_two_fa_passkey_help' ),
                                    'title'    => __( 'Passkey', 'really-simple-ssl' ),
                                    'intro'    => __( '', 'really-simple-ssl' ),
                                ],
								[
									'id'       => 'two_fa_totp',
									'group_id' => 'two_fa_totp',
									'premium'              => true,
									'premium_text'         => __( 'Get Login Protection with Really Simple Security Pro', 'really-simple-ssl' ),
									'helpLink' => rsssl_tracked_url( 'instructions/two-factor-authentication', 'documentation', 'settings_two_fa_totp_help' ),
									'upgrade'              => rsssl_tracked_url( 'pro', 'upgrade', 'settings_two_fa_totp_upgrade' ),
									'title'    => __( 'Authenticator App (TOTP)', 'really-simple-ssl' ),
									'intro'    => __( 'TOTP requires users to authenticate using a third-party app such as Google Authenticator.', 'really-simple-ssl' ),
								],
								[
									'id'            => 'two_fa_users',
									'group_id'      => 'two_fa_users',
									'premium'       => false,
									'premium_text'  => __( 'Here you control the users that are automatically, and temporarily blocked. You can also add or remove users manually. We recommend blocking ‘admin’ as username as a start.', 'really-simple-ssl' ),
									'helpLink' => rsssl_tracked_url( 'instructions/two-factor-authentication', 'documentation', 'settings_two_fa_users_help' ),
									'upgrade'              => rsssl_tracked_url( 'pro', 'upgrade', 'settings_two_fa_users_upgrade' ),
									'title'         => __( 'Users', 'really-simple-ssl' ),
									'intro'         => __( 'Here you can see which users have configured Two-Factor Authentication. The reset button will trigger the 2FA onboarding for the selected user(s) again and allow the configured grace period.', 'really-simple-ssl' ),
									'groupFilter'  => [
										'default' => 'active',
										'id'      => 'user_role',
										'options' => [
											[
												'id'   => 'all',
												'title' => __('All', 'really-simple-ssl'),
											],
                                            ...array_map(
                                                static function( $role, $roleKey ) {
                                                    return [
                                                        'id'    => $roleKey,               // Use the key as the id.
                                                        'title' => ucfirst( $role['name'] ), // Capitalize the role name.
                                                    ];
                                                },
                                                (new WP_Roles())->roles,           // The roles array.
                                                array_keys( (new WP_Roles())->roles ) // Its keys.
                                            ),
                                        ]
									],
								],
							],
						],
						[
							'id'                   => 'password_security',
							'networkwide_required' => true,
							'title'                => __('Password Security','really-simple-ssl'),
							'helpLink'             => rsssl_tracked_url( 'instructions/password-security/', 'documentation', 'settings_password_security_help' ),
							'groups'               => [
								[
									'id'           => 'password_security_passwords',
									'group_id'     => 'password_security_passwords',
									'premium_title'         => __( 'Password security', 'really-simple-ssl' ),
									'premium_text'         => __( 'Enforce secure password policies for your users by requiring strong passwords, and expiring passwords after a period of your choosing.', 'really-simple-ssl' ),
									'premium'      => true,
									'upgrade' => rsssl_tracked_url( 'pro', 'upgrade', 'settings_password_security_passwords_upgrade' ),
									'helpLink'     => rsssl_tracked_url( 'instructions/password-security', 'documentation', 'settings_password_security_passwords_help' ),
									'title'        => __( 'Passwords', 'really-simple-ssl' ),
									'intro'        => __( 'Improve security by requiring strong passwords and forced periodic password changes', 'really-simple-ssl' ),
								],
							]
						],
						[
							'id'      => 'limit_login_attempts',
							'title'   => __( 'Limit Login Attempts', 'really-simple-ssl' ),
							'premium' => true,
							'upgrade' => rsssl_tracked_url( 'pro', 'upgrade', 'settings_limit_login_attempts_upgrade' ),
							'groups'  => [
								[
									'id'           => 'limit_login_attempts_general',
									'group_id'     => 'limit_login_attempts_general',
									'helpLink'     => rsssl_tracked_url( 'instructions/limit-login-attempts', 'documentation', 'settings_limit_login_attempts_general_help' ),
									'premium'      => true,
									'upgrade' => rsssl_tracked_url( 'pro', 'upgrade', 'settings_limit_login_attempts_general_upgrade' ),
									'premium_title'         => __( 'Limit Login Attempts', 'really-simple-ssl' ),
									'premium_text'         => __( 'Customize login attempts, intervals, and temporary lockouts according to your preferences to regulate the level of security on your website during authentication. No additional settings required', 'really-simple-ssl' ),
									'title'        => __( 'General', 'really-simple-ssl' ),
									'intro'        => __( 'Protect your site against brute force login attacks by limiting the number of login attempts. Enabling this feature will temporary lock-out a username and the IP address that tries to login, after the set number of false logins.',
										'really-simple-ssl' ),
								],
								[
									'id'           => 'limit_login_attempts_advanced',
									'group_id'     => 'limit_login_attempts_advanced',
									'premium'      => true,
									'upgrade' => rsssl_tracked_url( 'pro', 'upgrade', 'settings_limit_login_attempts_advanced_upgrade' ),
									'premium_title'         => __( 'Settings', 'really-simple-ssl' ),
									'premium_text'         => __( 'Customize login attempts, intervals, and temporary lockouts according to your preferences to regulate the level of security on your website during authentication. No additional settings required', 'really-simple-ssl' ),
									'helpLink'     => rsssl_tracked_url( 'instructions/limit-login-attempts', 'documentation', 'settings_limit_login_attempts_advanced_help' ),
									'title'        => __( 'Limit Attempts', 'really-simple-ssl' ),
									'intro'        => __( 'The settings below determine how strict your site will be protected. You can leave these settings on their default values, unless you experience issues.',
										'really-simple-ssl' ),
								],
								[
									'id'           => 'limit_login_attempts_users',
									'group_id'     => 'limit_login_attempts_users',
									'premium'      => true,
									'upgrade' => rsssl_tracked_url( 'pro', 'upgrade', 'settings_limit_login_attempts_users_upgrade' ),
									'premium_title'         => __( 'Users', 'really-simple-ssl' ),
									'premium_text'         => __( 'Here you control the users that are automatically, and temporarily blocked. You can also add or remove users manually. We recommend blocking ‘admin’ as username as a start.', 'really-simple-ssl' ),
									'groupFilter'  => [
										'default' => 'limit_login_attempts_advanced_filter_log',
										'id'      => 'limit_login_attempts_advanced_filter',
										'options' => [
											[
												'id'    => 'blocked',
												'title' => __( 'Permanent block', 'really-simple-ssl' ),
											],
											[
												'id'    => 'allowed',
												'title' => __( 'Trusted', 'really-simple-ssl' ),
											],
											[
												'id'    => 'locked',
												'title' => __( 'Temporary block', 'really-simple-ssl' ),
											],
										],
									],
									'title'        => __( 'Users', 'really-simple-ssl' ),

									'intro'        => [
										'locked'  => __( 'Blocked usernames will be automatically unblocked after the above-configured interval. In the table below you can instantly unblock usernames.',
											'really-simple-ssl' ),
										'allowed' => __( 'You can prevent usernames from being temporarily blocked by adding them to this list. The IP address that triggers false logins will still be blocked.',
											'really-simple-ssl' ),
										'blocked' => __( 'You can add any non-existing username to this table, to instantly block IP addresses that try common usernames like "admin".',
											'really-simple-ssl' ),
									],
								],
								[
									'id'           => 'limit_login_attempts_ip_address',
									'group_id'           => 'limit_login_attempts_ip_address',
									'premium'      => true,
									'upgrade' => rsssl_tracked_url( 'pro', 'upgrade', 'settings_limit_login_attempts_ip_address_upgrade' ),
									'premium_title'         => __( 'IP Addresses', 'really-simple-ssl' ),
									'premium_text'         => __( 'IP Addresses can be allowed, blocked or will show up when your settings add them to a temporary blocklist. If you want to add your IP to the allowlist, please read the article provided at the right-hand side for instructions.', 'really-simple-ssl' ),
									'groupFilter'  => [
										'default' => 'limit_login_attempts_advanced_filter_log',
										'id'      => 'limit_login_attempts_advanced_filter',
										'options' => [
											[
												'id'    => 'blocked',
												'title' => __( 'Permanent block', 'really-simple-ssl' ),
											],
											[
												'id'    => 'allowed',
												'title' => __( 'Trusted', 'really-simple-ssl' ),
											],
											[
												'id'    => 'locked',
												'title' => __( 'Temporary block', 'really-simple-ssl' ),
											],
										],
									],
									'title'     => __( 'IP Addresses', 'really-simple-ssl' ),
									'intro'     => [
										'locked'  => __( 'Blocked IP addresses will be automatically unblocked after the above-configured interval. In the table below you can instantly unblock IP addresses.',
											'really-simple-ssl' ),
										'allowed' => __( 'You can prevent IP addresses from being temporarily blocked by adding them to this list. This can be convenient if you share an IP address with other site users. Usernames that trigger false logins will still be blocked.',
											'really-simple-ssl' ),
										'blocked' => __( 'You can indefinitely block known abusive IP addresses, to completely prevent them from trying to login.',
											'really-simple-ssl' ),
									],
								],
								[
									'id'           => 'limit_login_attempts_event_log',
									'group_id'           => 'limit_login_attempts_event_log',
									'premium'      			=> true,
									'upgrade' => rsssl_tracked_url( 'pro', 'upgrade', 'settings_limit_login_attempts_event_log_upgrade' ),
									'premium_title'         => __( 'Event Log', 'really-simple-ssl' ),
									'premium_text'         => __( 'The Event Log shows all relevant events related to limit login attempts. You can filter the log using the dropdown on the top-right to only show warnings.', 'really-simple-ssl' ),
									'groupFilter'  => [
										'default' => 'limit_login_attempts_advanced_filter_log',
										'id'      => 'limit_login_attempts_advanced_filter',
										'options' => [
											[
												'id'    => 'warning',
												'title' => __( 'Warnings', 'really-simple-ssl' ),
											],
											[
												'id'    => 'all',
												'title' => __( 'All', 'really-simple-ssl' ),
											],
										],
									],
									'title'        => __( 'Event Log', 'really-simple-ssl' ),
									'intro'        => __( 'The Event Log shows all relevant events related to limit login attempts. You can filter the log using the dropdown on the top-right to only show warnings.',
										'really-simple-ssl' ),
								],
								[
									'id'           => 'limit_login_attempts_country',
									'group_id'           => 'limit_login_attempts_country',
									'premium'      => true,
									'upgrade' => rsssl_tracked_url( 'pro', 'upgrade', 'settings_limit_login_attempts_country_upgrade' ),
									'premium_title'         => __( 'Regions', 'really-simple-ssl' ),
									'premium_text'         => __( 'You can easily block countries, or entire continents. You can act on the event log below and see which countries are suspicious, or exclude all countries but your own.', 'really-simple-ssl' ),
									'groupFilter'  => [
										'default' => 'limit_login_attempts_advanced_filter_log',
										'id'      => 'limit_login_attempts_advanced_filter',
										'options' => [
											[
												'id'    => 'blocked',
												'title' => __( 'Blocked', 'really-simple-ssl' ),
											],
											[
												'id'    => 'regions',
												'title' => __( 'Continents', 'really-simple-ssl' ),
											],
											[
												'id'    => 'countries',
												'title' => __( 'Allowed', 'really-simple-ssl' ),
											],
										],
									],
									'title'         => __( 'Regions', 'really-simple-ssl' ),
									'intro'         => __( 'If your site is only intended for users to login from specific geographical regions, you can entirely prevent logins from certain continents or countries.',
										'really-simple-ssl' ),
								]
							],
						],
					],

				],
				[
					'id' => 'firewall',
					'title' => __( 'Firewall', 'really-simple-ssl' ),
					'featured'   => false,
					'premium'    => true,
					'upgrade' => rsssl_tracked_url( 'pro', 'upgrade', 'settings_firewall_upgrade' ),
					'new'        => true,
					'menu_items' => [
						[
							'id'      => 'rules',
							'title'   => __( 'Rules', 'really-simple-ssl' ),
							'premium' => true,
							'upgrade' => rsssl_tracked_url( 'pro', 'upgrade', 'settings_rules_upgrade' ),
							'groups'  => [
								[
									'id'            => 'firewall_list_general',
									'group_id'      => 'firewall_list_general',
									'helpLink'      => rsssl_tracked_url( 'instructions/about-the-firewall', 'documentation', 'settings_firewall_list_general_help' ),
									'premium'       => true,
									'premium_title' => __( 'Firewall Rules', 'really-simple-ssl' ),
									'premium_text'  => __( 'Restrict access from specific countries or continents. You can also allow only specific countries.', 'really-simple-ssl' ),
									'upgrade'       => rsssl_tracked_url( 'pro', 'upgrade', 'settings_firewall_list_general_upgrade' ),
									'title'         => __( 'Firewall', 'really-simple-ssl' ),
									'intro'         => __( 'The lightweight Firewall can be used to lockout malicious traffic from your site. You can configure generic rules below, or block specific IP addresses by adding them to the Blocklist.',
										'really-simple-ssl' ),
								],
								[
									'id' => '404_blocking',
									'group_id' => '404_blocking',
									'premium' => true,
									'upgrade' => rsssl_tracked_url( 'pro', 'upgrade', 'settings_404_blocking_upgrade' ),
									'title' => __( '404 Blocking', 'really-simple-ssl' ),
									'premium_title' => __( '404 Blocking', 'really-simple-ssl' ),
									'premium_text' => __( 'Crawlers might scan your site looking for possible exploits. One way to detect this is the fact that they trigger more 404 (not found) errors than legitimate visitors would. Below you can set the threshold and lockout duration for 404 blocking.', 'really-simple-ssl' ),
									'intro' => __( 'Crawlers might scan your site looking for possible exploits. One way to detect this is the fact that they trigger more 404 (not found) errors than legitimate visitors would. Below you can set the threshold and lockout duration for 404 blocking.', 'really-simple-ssl' ),
								],
                                [
                                    'id' => 'user_agents',
                                    'group_id' => 'user_agents',
                                    'premium' => true,
                                    'upgrade' => rsssl_tracked_url( 'pro', 'upgrade', 'settings_user_agents_upgrade' ),
                                    'title' => __( 'User-Agents', 'really-simple-ssl' ),
                                    'premium_title' => __( 'User-Agents', 'really-simple-ssl' ),
                                    'premium_text' => __( 'The Firewall can also block traffic from malicious or resource-consuming bots that might crawl your website. A list of well-known bad User-Agents is automatically included. You can manually add or delete user-agents if so desired.', 'really-simple-ssl' ),
                                    'intro' => __( 'The Firewall can also block traffic from malicious or resource-consuming bots that might crawl your website. A list of well-known bad User-Agents is automatically included. You can manually add or delete user-agents if so desired.', 'really-simple-ssl' ),
                                    'groupFilter'          => [
                                        'default' => 'blocked',
                                        'id'      => 'rsssl-group-filter-user_agent_listing_overview',
                                        'options' => [
                                            [
                                                'id'    => 'blocked',
                                                'title' => __( 'Blocked', 'really-simple-ssl' ),
                                            ],
                                            [
                                                'id'   => 'deleted',
                                                'title' => __( 'Deleted', 'really-simple-ssl' ),
                                            ],
                                        ],
                                    ],
                                    ],
								[
									'id'                   => 'firewall_list_listing',
									'group_id'             => 'firewall_list_listing',
									'networkwide_required' => true,
									'premium'              => true,
									'upgrade' => rsssl_tracked_url( 'pro', 'upgrade', 'settings_firewall_list_listing_upgrade' ),
									'premium_text'         => __( 'This feature allows you to block visitors from your website based on country',
										'really-simple-ssl' ),
									'title'                => __( 'Regions', 'really-simple-ssl' ),
									'premium_title'        => __( 'Regions', 'really-simple-ssl' ),
									'intro' 			  => __( 'Restrict access to your site based on user location. By default, all regions are allowed. You can also block entire continents.',
										'really-simple-ssl' ),
									'groupFilter'          => [
										'default' => 'regions',
										'id'      => 'rsssl-group-filter-geo_block_list',
										'options' => [
											[
												'id'    => 'blocked',
												'title' => __( 'Blocked', 'really-simple-ssl' ),
											],
											[
												'id'   => 'regions',
												'title' => __( 'Continents', 'really-simple-ssl' ),
											],
											[
												'id'    => 'countries',
												'title' => __( 'Allowed', 'really-simple-ssl' ),
											],
										],
									],
								],
							]
						],
						[
							'id'      => 'firewall_blocklists',
							'title'   => __( 'Blocklists', 'really-simple-ssl' ),
							'premium' => true,
							'upgrade' => rsssl_tracked_url( 'pro', 'upgrade', 'settings_firewall_blocklists_upgrade' ),
							'groups' => [
								[
									'id'                   => 'firewall_white_list_listing',
									'group_id'             => 'firewall_white_list_listing',
									'networkwide_required' => true,
									'premium'              => true,
									'premium_text'         => __( 'This feature allows you to block visitors from your website based on country',
										'really-simple-ssl' ),
									'upgrade'              => rsssl_tracked_url( 'pro', 'upgrade', 'settings_firewall_white_list_listing_upgrade' ),
									'title'                => __( 'Trusted IP addresses', 'really-simple-ssl' ),
									'premium_title'        => __( 'Trusted IP addresses', 'really-simple-ssl' ),
									'intro' 			  => __( 'Here you can add IP addresses that should never be blocked by the Firewall. We will automatically add the IP address of the administrator that enabled the Firewall.',
										'really-simple-ssl' ),
								],
								[
									'id'                   => 'firewall_block_list_listing',
									'group_id'             => 'firewall_block_list_listing',
									'networkwide_required' => true,
									'premium'              => true,
									'premium_text'         => __( 'This feature allows you to block visitors from your website based on country',
										'really-simple-ssl' ),
									'upgrade'              => rsssl_tracked_url( 'pro', 'upgrade', 'settings_firewall_block_list_listing_upgrade' ),
									'title'                => __( 'IP Blocklist', 'really-simple-ssl' ),
									'premium_title'        => __( 'IP Blocklist', 'really-simple-ssl' ),
									'intro' 			  => __( 'This list shows all individually blocked IP addresses. On top the top-right you can filter between permanent blocks and temporary blocks. By default, blocks are only temporary, as attackers and bots will frequently alter between IP addresses. However, you can manually configure permanent blocks.',
										'really-simple-ssl' ),
									'groupFilter'          => [
										'default' => 'all',
										'id'      => 'rsssl-group-filter-geo_block_list',
										'options' => [
											[
												'id'    => 'all',
												'title' => __( 'All', 'really-simple-ssl' ),
											],
											[
												'id'    => 'temp',
												'title' => __( 'Temporary', 'really-simple-ssl' ),
											],
											[
												'id'    => 'permanent',
												'title' => __( 'Permanent', 'really-simple-ssl' ),
											],
										],
									],
								],
							]
						],
						[
							'id' => 'firewall_logs',
							'title' => __( 'Logs', 'really-simple-ssl' ),
							'premium' => true,
							'upgrade' => rsssl_tracked_url( 'pro', 'upgrade', 'settings_firewall_logs_upgrade' ),
							'groups' => [
								[
									'id'                   => 'firewall_logs_content',
									'group_id'             => 'firewall_logs_content',
									'premium'              => true,
									'premium_text'         => __( 'This feature allows you to block visitors from your website based on country',
										'really-simple-ssl' ),
									'upgrade'              => rsssl_tracked_url( 'pro', 'upgrade', 'settings_firewall_logs_content_upgrade' ),
									'title'                => __( 'Event Logs', 'really-simple-ssl' ),
									'premium_title'        => __( 'Event Logs', 'really-simple-ssl' ),
									'intro' 			  => __( 'The Event Log shows all relevant events related to the Firewall and IP lockouts. You can filter the log using the dropdown on the top-right to only show warnings.',
										'really-simple-ssl' ),
									'groupFilter'          => [
										'default' => 'all',
										'id'      => 'rsssl-group-filter-firewall_logs_content',
										'options' => [
											[
												'id'    => 'all',
												'title' => __( 'All', 'really-simple-ssl' ),
											],
											[
												'id'    => 'warning',
												'title' => __( 'Warnings', 'really-simple-ssl' ),
											],
										],
									],
								],
							]
						]
					]
				],
			],
		],

		[
			"id"             => "letsencrypt",
			'default_hidden' => true,
			"title"          => "Let's Encrypt",
			'intro'          => sprintf( __( 'We have tried to make our Wizard as simple and fast as possible. Although these questions are all necessary, if there’s any way you think we can improve the plugin, please let us %sknow%s!',
					'really-simple-ssl' ), '<a target="_blank" rel="noopener noreferrer" href="' . esc_url( rsssl_tracked_url( 'contact/', 'support', 'lets_encrypt_wizard_contact' ) ) . '">', '</a>' ) .
			                    sprintf( __( ' Please note that you can always save and finish the wizard later, use our %sdocumentation%s for additional information or log a %ssupport ticket%s if you need our assistance.',
				                    'really-simple-ssl' ), '<a target="_blank" rel="noopener noreferrer" href="' . esc_url( rsssl_tracked_url( 'install-ssl-certificate/', 'documentation', 'lets_encrypt_wizard_documentation' ) ) . '">', '</a>',
				                    '<a target="_blank" rel="noopener noreferrer" href="https://wordpress.org/support/plugin/really-simple-ssl/">', '</a>' ),
			'menu_items' => [
				[
					'id'         => 'le-system-status',
					'group_id'         => 'le-system-status',
					'title'      => __('System status', 'really-simple-ssl'),
					'intro'      => __('Letʼs Encrypt is a free, automated and open certificate authority brought to you by the non-profit Internet Security Research Group (ISRG).',
						'really-simple-ssl'),
					'helpLink'   => rsssl_tracked_url( 'about-lets-encrypt', 'documentation', 'settings_le_system_status_help' ),
					'tests_only' => true,
				],
				[
					'id'    => 'le-general',
					'title' => __('General Settings', 'really-simple-ssl'),
				],
				[
					'id'    => 'le-hosting',
					'title' => __('Hosting', 'really-simple-ssl'),
					'intro' => __('Below you will find the instructions for different hosting environments and configurations. If you start the process with the necessary instructions and credentials the next steps will be done in no time.',
						'really-simple-ssl'),
				],
				[
					'id'         => 'le-directories',
					'title'      => __('Directories', 'really-simple-ssl'),
					'tests_only' => true,
				],
				[
					'id'         => 'le-dns-verification',
					'title'      => __('DNS verification', 'really-simple-ssl'),
					'tests_only' => true,
				],
				[
					'id'         => 'le-generation',
					'title'      => __('Generation', 'really-simple-ssl'),
					'tests_only' => true,
				],
				[
					'id'         => 'le-installation',
					'title'      => __('Installation', 'really-simple-ssl'),
					'tests_only' => true,
				],
				[
					'id'         => 'le-activate_ssl',
					'title'      => __('Activate', 'really-simple-ssl'),
					'tests_only' => true,
				],
			],
		],
	];
	return apply_filters('rsssl_menu', $menu_items);
}
