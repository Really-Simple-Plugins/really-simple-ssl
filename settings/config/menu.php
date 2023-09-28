<?php
defined('ABSPATH') or die();


function rsssl_menu()
{
    if ( ! rsssl_user_can_manage()) {
        return [];
    }
    $menu_items = [
        [
            "id"             => "dashboard",
            "title"          => __("Dashboard", 'really-simple-ssl'),
            'default_hidden' => false,
            'menu_items'     => [],
        ],
        [
            "id"             => "settings",
            "title"          => __("Settings", 'really-simple-ssl'),
            'default_hidden' => false,
            'menu_items'     => [
                [
                    'id'       => 'general',
                    'group_id' => 'general',
                    'title'    => __('General', 'really-simple-ssl'),
                    'helpLink' => 'https://really-simple-ssl.com/instructions/about-our-general-settings/?mtm_campaign=instructions&mtm_source=free',
                    'groups'   => [
                        [
                            'id'       => 'general',
                            'title'    => __('General', 'really-simple-ssl'),
                            'helpLink' => 'https://really-simple-ssl.com/instructions/about-our-general-settings/?mtm_campaign=instructions&mtm_source=free',
                        ],
                        [
                            'id'            => 'email',
							'title'    => __( 'Email', 'really-simple-ssl' ),
							'helpLink' => 'https://really-simple-ssl.com/email-notifications/',
						],
						[
							'id' => 'support',
                            'title'         => __('Premium Support', 'really-simple-ssl'),
                            'intro'         => __('The following information is attached when you send this form: license key, scan results, your domain, .htaccess file, debug log and a list of active plugins.',
                                'really-simple-ssl'),
                            'premium'       => true,
                            'premium_text'  => __("Get Premium Support with %sReally Simple SSL Pro%s",
                                'really-simple-ssl'),
                            'helpLink'      => 'https://really-simple-ssl.com/instructions/debugging/?mtm_campaign=instructions&mtm_source=free',
                            'upgrade'       => 'https://really-simple-ssl.com/pro/?mtm_campaign=premiumsupport&mtm_source=free&mtm_content=upgrade',
                            'helpLink_text' => __('Debugging with Really Simple SSL', "really-simple-ssl"),
                        ],
                    ],
                ],
                [
                    'id'       => 'hardening',
                    'title'    => __('Hardening', 'really-simple-ssl'),
                    'featured' => false,
                    'groups'   => [
                        [
                            'id'       => 'hardening_basic',
                            'helpLink' => 'https://really-simple-ssl.com/instructions/about-hardening-features/?mtm_campaign=instructions&mtm_source=free',
                            'title'    => __('Hardening', 'really-simple-ssl'),
                        ],
                        [
                            'id'           => 'hardening_extended',
                            'premium'      => true,
                            'helpLink'     => 'https://really-simple-ssl.com/instructions/about-hardening-features#advanced/?mtm_campaign=instructions&mtm_source=free',
                            'upgrade'      => 'https://really-simple-ssl.com/instructions/about-hardening-features#advanced/?mtm_campaign=upgrade&mtm_source=free',
                            'title'        => __('Advanced Hardening', 'really-simple-ssl'),
                            'premium_text' => __("Get Advanced Hardening with %sReally Simple SSL Pro%s",
                                'really-simple-ssl'),
                        ],
                        [
                            'id'           => 'hardening_xml',
                            'premium'      => true,
                            'helpLink'     => 'https://really-simple-ssl.com/instructions/about-hardening-features#xml-rpc?mtm_campaign=instructions&mtm_source=free',
                            'upgrade'      => 'https://really-simple-ssl.com/instructions/about-hardening-features#xml-rpc?mtm_campaign=upgrade&mtm_source=free',
                            'title'        => __('XML-RPC', 'really-simple-ssl'),
                            'premium_text' => __("Get XML-RPC with %sReally Simple SSL Pro%s", 'really-simple-ssl'),
                        ],
                    ],
                ],
                [
                    'id'       => 'vulnerabilities',
                    'title'    => __('Vulnerabilities', 'really-simple-ssl'),
                    'featured' => true,//TODO: change this after beta
                    'groups'   => [
                        [
                            'id'       => 'vulnerabilities_basic',
                            'helpLink' => 'https://really-simple-ssl.com/instructions/about-vulnerabilities/',
                            'title'    => __('Vulnerabilities', 'really-simple-ssl'),
                            'intro'    => __('Here you can configure vulnerability detection, notifications and measures. To learn more about the features displayed, please use the instructions linked in the top-right corner.',
                                'really-simple-ssl'),
                        ],
                        [
                            'id'       => 'vulnerabilities_notifications',
                            'helpLink' => 'https://really-simple-ssl.com/instructions/about-vulnerabilities#notifications',
                            'title'    => __('Notifications', 'really-simple-ssl'),
                            'intro'    => __('These notifications are set to the minimum risk level that triggers a notification. For example, the default site-wide notification triggers on high-risk and critical vulnerabilities.',
                                'really-simple-ssl'),
                        ],
                        [
                            'id'       => 'vulnerabilities_overview',
                            'helpLink' => 'https://really-simple-ssl.com/instructions/about-vulnerabilities#components',
                            'title'    => __('Overview', 'really-simple-ssl'),
                            'intro'    => __('This is the vulnerability overview. Here you will find current known vulnerabilities on your system. You can find more information and helpful, actionable insights for every vulnerability under details.',
                                'really-simple-ssl'),
                        ],
                        [
                            'id'           => 'vulnerabilities_measures',
                            'premium'      => true,
                            'helpLink'     => 'https://really-simple-ssl.com/instructions/about-vulnerabilities#measures',
                            'title'        => __('Measures', 'really-simple-ssl'),
                            'intro'        => __('You can choose to automate the most common actions for a vulnerability. Each action is set to a minimum risk level, similar to the notifications. Please read the instructions to learn more about the process.',
                                'really-simple-ssl'),
                            'premium_text' => __("Improve Security with %sReally Simple SSL Pro%s",
                                'really-simple-ssl'),
                        ],
                    ],
                ],
//                [
//                    'id'      => 'limit_login_attempts',
//                    'title'   => __('Limit Login Attempts', 'really-simple-ssl'),
//                    'premium' => false,
//                    'groups'  => [
//                        [
//                            'id'           => 'limit_login_attempts_general',
//                            'helpLink'     => 'https://really-simple-ssl.com/knowledge-base/limit-login-attempts/?mtm_campaign=instructions&mtm_source=free',
//                            'premium'      => true,
//                            'title'        => __('General', 'really-simple-ssl'),
//                            'intro'        => __('Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et.',
//                                'really-simple-ssl'),
//                            'premium_text' => __('Get Limit Login Attempts with %sReally Simple SSL Pro%s',
//                                'really-simple-ssl'),
//                        ],
//                        [
//                            'id'           => 'limit_login_attempts_advanced',
//                            'premium'      => true,
//                            'helpLink'     => 'https://really-simple-ssl.com/knowledge-base/limit-login-attempts/?mtm_campaign=instructions&mtm_source=free',
//                            'title'        => __('Limit Attempts', 'really-simple-ssl'),
//                            'intro'        => __('.feugait aptent adhuc conceptam risus regione epicurei ne litora simul suspendisse magna luctus risus senserit inceptos omittantur tritani iisque scripta pri fabellas latine dicant sale',
//                                'really-simple-ssl'),
//                            'premium_text' => __('Get Limit Login Attempts with %sReally Simple SSL Pro%s',
//                                'really-simple-ssl'),
//                        ],
//                        [
//                            'id'           => 'limit_login_attempts_users',
//                            'premium'      => true,
//                            'groupFilter'  => [
//                                'default' => 'limit_login_attempts_advanced_filter_log',
//                                'id'      => 'limit_login_attempts_advanced_filter',
//                                'options' => [
//                                    [
//                                        'id'    => 'blocked',
//                                        'title' => __('Blocked', 'really-simple-ssl'),
//                                    ],
//                                    [
//                                        'id'    => 'allowed',
//                                        'title' => __('Allowed', 'really-simple-ssl'),
//                                    ],
//                                    [
//                                        'id'    => 'locked',
//                                        'title' => __('Locked-out', 'really-simple-ssl'),
//                                    ]
//                                ],
//                            ],
//                            'title'        => __('Users', 'really-simple-ssl'),
//                            'intro'        => __('Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et',
//                                'really-simple-ssl'),
//                            'premium_text' => __('Get Limit Login Attempts with %sReally Simple SSL Pro%s',
//                                'really-simple-ssl'),
//                        ],
//                        [
//                            'id'           => 'limit_login_attempts_ip_address',
//                            'premium'      => true,
//                            'groupFilter'  => [
//                                'default' => 'limit_login_attempts_advanced_filter_log',
//                                'id'      => 'limit_login_attempts_advanced_filter',
//                                'options' => [
//                                    [
//                                        'id'    => 'blocked',
//                                        'title' => __('Blocked', 'really-simple-ssl'),
//                                    ],
//                                    [
//                                        'id'    => 'allowed',
//                                        'title' => __('Allowed', 'really-simple-ssl'),
//                                    ],
//                                    [
//                                        'id'    => 'locked',
//                                        'title' => __('Locked-out', 'really-simple-ssl'),
//                                    ],
//                                ],
//                            ],
//                            'title'        => __('IP Addresses', 'really-simple-ssl'),
//                            'intro'        => __('Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et',
//                                'really-simple-ssl'),
//                            'premium_text' => __('Get Limit Login Attempts with %sReally Simple SSL Pro%s',
//                                'really-simple-ssl'),
//                        ],
//                        [
//                            'id'       => 'limit_login_attempts_event_log',
//                            'premium'  => true,
//                            'groupFilter'  => [
//                                'default' => 'limit_login_attempts_advanced_filter_log',
//                                'id'      => 'limit_login_attempts_advanced_filter',
//                                'options' => [
//                                    [
//                                        'id'    => 'warning',
//                                        'title' => __('Warnings', 'really-simple-ssl'),
//                                    ],
//                                    [
//                                        'id'    => 'all',
//                                        'title' => __('All', 'really-simple-ssl'),
//                                    ],
//                                ],
//                            ],
//                            'title'    => __('Event Log', 'really-simple-ssl'),
//                            'intro'    => __('Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna.',
//                                'really-simple-ssl'),
//                            'premium_text' => __('Get Limit Login Attempts with %sReally Simple SSL Pro%s',
//                                'really-simple-ssl'),
//                        ],
//                        [
//                            'id'       => 'limit_login_attempts_country',
//                            'premium'  => true,
//                            'groupFilter'  => [
//                                'default' => 'limit_login_attempts_advanced_filter_log',
//                                'id'      => 'limit_login_attempts_advanced_filter',
//                                'options' => [
//                                    [
//                                        'id'    => 'blocked',
//                                        'title' => __('Blocked', 'really-simple-ssl'),
//                                    ],
//                                    [
//                                        'id'    => 'regions',
//                                        'title' => __('Continents', 'really-simple-ssl'),
//                                    ],
//                                    [
//                                        'id'    => 'countries',
//                                        'title' => __('Countries', 'really-simple-ssl'),
//                                    ],
//                                ],
//                            ],
//                            'title'    => __('Countries', 'really-simple-ssl'),
//                            'intro'    => __('Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna.',
//                                'really-simple-ssl'),
//                            'premium_text' => __('Get Limit Login Attempts with %sReally Simple SSL Pro%s',
//                                'really-simple-ssl'),
//                        ]
//                    ],
//                ],
                [
                    'id'           => 'mixed_content_scan',
                    'title'        => __('Mixed Content Scan', 'really-simple-ssl'),
                    'premium'      => true,
                    'upgrade'      => 'https://really-simple-ssl.com/pro/?mtm_campaign=mixedcontent&mtm_source=free&mtm_content=upgrade',
                    'helpLink'     => 'https://really-simple-ssl.com/pro/?mtm_campaign=mixedcontent&mtm_source=free&mtm_content=instructions',
                    'premium_text' => __("Get the Mixed Content Scan with %sReally Simple SSL Pro%s",
                        'really-simple-ssl'),
                    'groups'       => [
                        [
                            'id'           => 'mixedcontentscan',
                            'title'        => __('Mixed Content Scan', 'really-simple-ssl'),
                            'helpLink'     => 'https://really-simple-ssl.com/pro/?mtm_campaign=mixedcontent&mtm_source=free&mtm_content=upgrade',
                            'upgrade'      => 'https://really-simple-ssl.com/pro/?mtm_campaign=mixedcontent&mtm_source=free&mtm_content=upgrade',
                            'premium'      => true,
                            'premium_text' => __("Get the Mixed Content Scan with %sReally Simple SSL Pro%s",
                                'really-simple-ssl'),
                        ],
                    ],
                ],
                [
                    'id'      => 'recommended_security_headers',
                    'title'   => __('Recommended Security Headers', 'really-simple-ssl'),
                    'premium' => true,
                    'groups'  => [
                        [
                            'id'                   => 'recommended_security_headers',
                            'networkwide_required' => true,
                            'premium'              => true,
                            'premium_text'         => __("Get Recommended Security Headers with %sReally Simple SSL Pro%s",
                                'really-simple-ssl'),
                            'upgrade'              => 'https://really-simple-ssl.com/pro/?mtm_campaign=recommendedheaders&mtm_source=free&mtm_content=upgrade',
                            'title'                => __('Recommended Security Headers', 'really-simple-ssl'),
                            'helpLink'             => 'https://really-simple-ssl.com/instructions/about-recommended-security-headers/?mtm_campaign=instructions&mtm_source=free',
                        ],
                    ],
                ],
                [
                    'id'      => 'hsts',
                    'title'   => 'HTTP Strict Transport Security',
                    'premium' => true,
                    'groups'  => [
                        [
                            'id'                   => 'hsts',
                            'premium'              => true,
                            'networkwide_required' => true,
                            'premium_text'         => __("Get HTTP Strict Transport Security with %sReally Simple SSL Pro%s",
                                'really-simple-ssl'),
                            'upgrade'              => 'https://really-simple-ssl.com/pro/?mtm_campaign=hsts&mtm_source=free&mtm_content=upgrade',
                            'title'                => __('HTTP Strict Transport Security', 'really-simple-ssl'),
                            'helpLink'             => 'https://really-simple-ssl.com/instructions/about-hsts/?mtm_campaign=instructions&mtm_source=free',
                        ],
                    ],
                ],
                [
                    'id'      => 'permissions_policy',
                    'title'   => 'Permissions Policy',
                    'premium' => true,
                    'groups'  => [
                        [
                            'id'                   => 'permissions_policy',
                            'premium_text'         => __("Get the Permissions Policy with %sReally Simple SSL Pro%s",
                                'really-simple-ssl'),
                            'upgrade'              => 'https://really-simple-ssl.com/pro/?mtm_campaign=permissionspolicy&mtm_source=free&mtm_content=upgrade',
                            'helpLink'             => 'https://really-simple-ssl.com/instructions/about-permissions-policy/?mtm_campaign=instructions&mtm_source=free',
                            'networkwide_required' => true,
                            'premium'              => true,
                            'title'                => 'Permissions Policy',
                        ],
                    ],
                ],
                [
                    'id'      => 'content_security_policy',
                    'title'   => 'Content Security Policy',
                    'premium' => true,
                    'groups'  => [
                        [
                            'id'                   => 'upgrade_insecure_requests',
                            'networkwide_required' => true,
                            'premium'              => true,
                            'premium_text'         => __("Get Upgrade Insecure Requests with %sReally Simple SSL Pro%s",
                                'really-simple-ssl'),
                            'upgrade'              => 'https://really-simple-ssl.com/pro/?mtm_campaign=upgradeinsecurerequests&mtm_source=free&mtm_content=upgrade',
                            'helpLink'             => 'https://really-simple-ssl.com/instructions/upgrade-insecure-requests/?mtm_campaign=instructions&mtm_source=free',
                            'title'                => 'Upgrade Insecure Requests',
                        ],
                        [
                            'id'                   => 'frame_ancestors',
                            'networkwide_required' => true,
                            'premium'              => true,
                            'premium_text'         => __("Get Frame Ancestors with %sReally Simple SSL Pro%s",
                                'really-simple-ssl'),
                            'upgrade'              => 'https://really-simple-ssl.com/pro/?mtm_campaign=frameancestors&mtm_source=free&mtm_content=upgrade',
                            'helpLink'             => 'https://really-simple-ssl.com/instructions/frame-ancestors/?mtm_campaign=instructions&mtm_source=free',
                            'title'                => 'Frame Ancestors',
                        ],
                        [
                            'id'                   => 'content_security_policy',
                            'networkwide_required' => true,
                            'helpLink'             => 'https://really-simple-ssl.com/instructions/source-directives/',
                            'premium'              => true,
                            'premium_text'         => __("Get Source Directives with %sReally Simple SSL Pro%s",
                                'really-simple-ssl'),
                            'upgrade'              => 'https://really-simple-ssl.com/pro/?mtm_campaign=csp&mtm_source=free&mtm_content=upgrade',
                            'title'                => 'Source Directives',
                        ]
                    ],
                ],
                [
                    'id'                   => 'cross_origin_policy',
                    'networkwide_required' => true,
                    'premium'              => true,
                    'premium_text'         => __('Get Cross Origin Policy Headers with %sReally Simple SSL Pro%s',
                        'really-simple-ssl'),
                    'upgrade'              => 'https://really-simple-ssl.com/pro/?mtm_campaign=cops&mtm_source=free&mtm_content=upgrade',
                    'title'                => 'Cross Origin Policy',
                    'helpLink'             => 'https://really-simple-ssl.com/instructions/cross-origin-policies/?mtm_campaign=instructions&mtm_source=free',

				],
				[
					'id'                   => 'two_fa',
					'networkwide_required' => true,
					'premium'              => true,
					'premium_text'         => __( 'Get two-factor authentication with %sReally Simple SSL Pro%s', 'really-simple-ssl' ),
					'upgrade'              => 'https://really-simple-ssl.com/pro/?mtm_campaign=cops&mtm_source=free&mtm_content=upgrade',
					'title'                => __('Two-Step Verification', 'really-simple-ssl'),
					'helpLink'             => 'https://really-simple-ssl.com/instructions/two-factor-authentication/?mtm_campaign=instructions&mtm_source=free',
                    'groups'  => [
                        [
                            'id'       => 'two_fa_general',
                            'premium'              => true,
                            'premium_text'         => __( "Get Login Protection with %sReally Simple SSL Pro%s", 'really-simple-ssl' ),
                            'helpLink' => 'https://really-simple-ssl.com/instructions/two-factor-authentication',
                            'title'    => __( 'General', 'really-simple-ssl' ),
                            'intro'    => __( 'Enhancing the authentication process and optimizing user management makes Login Protection a foundational element in securing your website.', 'really-simple-ssl' ),
                        ],
                        [
                            'id'       => 'two_fa_email',
                            'premium'              => true,
                            'premium_text'         => __( "Get Login Protection with %sReally Simple SSL Pro%s", 'really-simple-ssl' ),
                            'helpLink' => 'https://really-simple-ssl.com/instructions/two-factor-authentication',
                            'title'    => __( 'Email', 'really-simple-ssl' ),
                            'intro'    => __( 'Two-step verification will enhance the authentication process by adding an additional security layer. Selected users will be required to enter their credentials first, and then they must provide a one-time code sent to their email as an extra step.', 'really-simple-ssl' ),
                        ],
                        [
                            'id'       => 'two_fa_users',
                            'premium'              => true,
                            'premium_text'         => __( "Get Login Protection with %sReally Simple SSL Pro%s", 'really-simple-ssl' ),
//                            'helpLink' => 'https://really-simple-ssl.com/instructions/two-factor-authentication',
                            'title'    => __( 'Users', 'really-simple-ssl' ),
                            'intro'    => __( 'Below you will find the users that you have selected for an additional verification method and their subsequent status. A reset is possible to revert a choice made by a user.', 'really-simple-ssl' ),
                            'groupFilter'  => [
	                            'default' => 'active',
	                            'id'      => 'two_fa_user_filter',
	                            'options' => [
		                            [
			                            'id'    => 'active',
			                            'title' => __('Active', 'really-simple-ssl'),
		                            ],
		                            [
			                            'id'    => 'open',
			                            'title' => __('Open', 'really-simple-ssl'),
		                            ],
		                            [
			                            'id'    => 'disabled',
			                            'title' => __('Disabled', 'really-simple-ssl'),
		                            ]
	                            ],
                            ],
	                    ],
                    ],

				],
			],
		],
        [
            "id"             => "letsencrypt",
            'default_hidden' => true,
            "title"          => "Let's Encrypt",
            'intro'          => sprintf(__('We have tried to make our Wizard as simple and fast as possible. Although these questions are all necessary, if there’s any way you think we can improve the plugin, please let us %sknow%s!',
                    'really-simple-ssl'),
                    '<a target="_blank" href="https://really-simple-ssl.com/contact/?mtm_campaign=instructions&mtm_source=free">',
                    '</a>').
                                sprintf(__(' Please note that you can always save and finish the wizard later, use our %sdocumentation%s for additional information or log a %ssupport ticket%s if you need our assistance.',
                                    'really-simple-ssl'),
                                    '<a target="_blank" href="https://really-simple-ssl.com/install-ssl-certificate/?mtm_campaign=instructions&mtm_source=free">',
                                    '</a>',
                                    '<a target="_blank" href="https://wordpress.org/support/plugin/really-simple-ssl/">',
                                    '</a>'),

            'menu_items' => [
                [
                    'id'         => 'le-system-status',
                    'title'      => __('System Status', 'really-simple-ssl'),
                    'intro'      => __('Letʼs Encrypt is a free, automated and open certificate authority brought to you by the nonprofit Internet Security Research Group (ISRG).',
                        'really-simple-ssl'),
                    'helpLink'   => 'https://really-simple-ssl.com/about-lets-encrypt/?mtm_campaign=letsencrypt&mtm_source=free',
                    'tests_only' => true,
                ],
                [
                    'id'    => 'le-general',
                    'title' => __('General Settings', 'really-simple-ssl'),
                ],
                [
                    'id'    => 'le-hosting',
                    'title' => __('Hosting', 'really-simple-ssl'),
                    'intro' => __('Below you will find the instructions for different hosting environments and configurations. If you start the process with the necessary instructions and credentials the next view steps will be done in no time.',
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
