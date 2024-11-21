<?php
defined( 'ABSPATH' ) or die();

add_filter( 'rsssl_fields', function( $fields ) {
	return array_merge( $fields,
		[

			[
				'id' => 'enable_vulnerability_scanner',
				'menu_id' => 'vulnerabilities_basic',
				'group_id' => 'vulnerabilities_basic',
				'type' => 'checkbox',
				'label' => __('Enable vulnerability scanning', 'really-simple-ssl'),
				'tooltip'  => __( "This feature depends on multiple standard background processes. If a process fails or is unavailable on your system, detection might not work. We run frequent tests for this purpose. We will notify you accordingly if there are any issues.", 'really-simple-ssl' ),
				'disabled' => false,
				'default' => false,
				'warning' => true,
				'help'               => [
					'label' => 'default',
					'url'   => 'instructions/about-vulnerabilities/',
					'title' => __( "About Vulnerabilities", 'really-simple-ssl' ),
					'text'  => __( 'Really Simple Security collects information about plugins, themes, and core vulnerabilities from our database powered by WPVulnerability. Anonymized data about these vulnerable components will be sent to Really Simple Security for statistical analysis to improve open-source contributions. For more information, please read our privacy statement.', 'really-simple-ssl' ),
				],
			],
			[
				'id' => 'vulnerabilities_intro_shown',
				'menu_id' => 'vulnerabilities_basic',
				'group_id' => 'vulnerabilities_basic',
				'type' => 'hidden',
				'label' => '',
				'disabled' => false,
				'default' => false,
			],
			[
				'id' => 'enable_feedback_in_plugin',
				'menu_id' => 'vulnerabilities_notifications',
				'group_id' => 'vulnerabilities_notifications',
				'tooltip'  => __( "If there's a vulnerability, you will also get feedback on the themes and plugin overview.", 'really-simple-ssl' ),
				'warning' => false,
				'type' => 'checkbox',
				'label' => __('Feedback in plugin overview', 'really-simple-ssl'),
				'disabled' => false,
				'default' => false,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'enable_vulnerability_scanner' => 1,
					]
				],
			],
			/* Vulnerability advanced Section */
			[
				'id' => 'vulnerability_notification_dashboard',
				'menu_id' => 'vulnerabilities_notifications',
				'group_id' => 'vulnerabilities_notifications',
				'type' => 'select',
				'options' => [
					'*' => __('None', 'really-simple-ssl'),
					'l' => __('Low-risk (default)', 'really-simple-ssl'),
					'm' => __('Medium-risk', 'really-simple-ssl'),
					'h' => __('High-risk', 'really-simple-ssl'),
					'c' => __('Critical', 'really-simple-ssl'),
				],
				'label' => __('Really Simple Security dashboard', 'really-simple-ssl'),
				'disabled' => false,
				'default' => 'l',
				'react_conditions' => [
					'relation' => 'AND',
					[
						'enable_vulnerability_scanner' => 1,
					]
				],
			],
			[
				'id' => 'vulnerability_notification_sitewide',
				'menu_id' => 'vulnerabilities_notifications',
				'group_id' => 'vulnerabilities_notifications',
				'type' => 'select',
				'options' => [
					'*' => __('None', 'really-simple-ssl'),
					'l' => __('Low-risk ', 'really-simple-ssl'),
					'm' => __('Medium-risk', 'really-simple-ssl'),
					'h' => __('High-risk (default)', 'really-simple-ssl'),
					'c' => __('Critical', 'really-simple-ssl'),
				],
				'label' => __('Site-wide, admin notification', 'really-simple-ssl'),
				'disabled' => false,
				'default' => 'h',
				'react_conditions' => [
					'relation' => 'AND',
					[
						'enable_vulnerability_scanner' => 1,
					]
				],
			],
			[
				'id' => 'vulnerability_notification_email_admin',
				'menu_id' => 'vulnerabilities_notifications',
				'group_id' => 'vulnerabilities_notifications',
				'type' => 'select',
				'options' => [
					'*' => __('None', 'really-simple-ssl'),
					'l' => __('Low-risk', 'really-simple-ssl'),
					'm' => __('Medium-risk', 'really-simple-ssl'),
					'h' => __('High-risk', 'really-simple-ssl'),
					'c' => __('Critical (default)', 'really-simple-ssl'),
				],
				'label' => __('Email', 'really-simple-ssl'),
				'tooltip'  => __( "This will send emails about vulnerabilities directly from your server. Make sure you can receive emails by the testing a preview below. If this feature is disabled, please enable notifications under general settings.", 'really-simple-ssl' ),
				'warning' => true,
				'disabled' => false,
				'default' => 'c',
				'react_conditions' => [
					'relation' => 'AND',
					[
						'enable_vulnerability_scanner' => 1,
					],
					[
						'send_notifications_email' => 1,
					]
				],
			],
			[
				'id' => 'vulnerabilities_test',
				'menu_id' => 'vulnerabilities_notifications',
				'group_id' => 'vulnerabilities_notifications',
				'type' => 'notificationtester',
				'action' => 'test_vulnerability_notification',
				'label' => __('Test notifications', 'really-simple-ssl'),
				'tooltip' => __('Test notifications can be used to test email delivery and shows how vulnerabilities will be reported on your WordPress installation.', 'really-simple-ssl'),
				'disabled' => false,
				'button_text' => __( "Test notifications", "really-simple-ssl" ),
				'react_conditions' => [
					'relation' => 'AND',
					[
						'enable_vulnerability_scanner' => 1,
						'send_notifications_email' => 1,
					]
				],
			],
			[
				'id'    => 'vulnerabilities-overview',
				'menu_id' => 'vulnerabilities_basic',
				'group_id' => 'vulnerabilities_overview',
				'type' => 'vulnerabilitiestable',

				'label' => __('Vulnerabilities Overview', 'really-simple-ssl'),
				'disabled' => false,
				'default' => false,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'enable_vulnerability_scanner' => 1,
					]
				],
				'columns'          => [
					[
						'id'       => 'component',
						'name'     => __('Component', 'really-simple-ssl'),
						'sortable' => false,
						'column'   => 'Name',
						'width'    => '55%',
						'searchable' => true,
					],
					[
						'id'       => 'date',
						'name'     => __('Date', 'really-simple-ssl'),
						'sortable' => false,
						'column'   => 'date',
						'width'    => '15%',
					],
					[
						'id'       => 'risk',
						'name'     => __('Risk', 'really-simple-ssl'),
						'sortable' => false,
						'column'   => 'risk_name',
						'width'    => '10%',
						'searchable' => true,
					],
					[
						'id'       => 'action',
						'name'     => __('', 'really-simple-ssl'),
						'sortable' => false,
						'column'   => 'vulnerability_action',
						'width'   => '20%',
					]
				]
			],
			[
				'id'               => 'vulnerabilities-measures-overview',
				'menu_id'          => 'vulnerabilities_measures',
				'group_id'         => 'vulnerabilities_measures',
				'type'             => 'riskcomponent',
				'options'          => [
					'*' => __('None', 'really-simple-ssl'),
					'l' => __('Low-risk', 'really-simple-ssl'),
					'm' => __('Medium-risk', 'really-simple-ssl'),
					'h' => __('High-risk', 'really-simple-ssl'),
					'c' => __('Critical', 'really-simple-ssl'),
				],
				'react_conditions' => [
					'relation' => 'AND',
					[
						'measures_enabled' => true,
					]
				],
				'disabled'         => false,
				'default'          => false,
				'columns'          => [
					[
						'name'     => __( 'Action', 'really-simple-ssl' ),
						'sortable' => false,
						'column'   => 'name',
						'width'    => '20%',
					],
					[
						'name'     => __( 'Risk', 'really-simple-ssl' ),
						'sortable' => false,
						'column'   => 'riskSelection',
						'width'         => '24%',
					],
					[
						'name'     => __( 'Description', 'really-simple-ssl' ),
						'sortable' => false,
						'column'   => 'description',
						'type'   => 'text',
					],
				],
			],
			[
				'id'       => 'measures_enabled',
				'menu_id'  => 'vulnerabilities_notifications',
				'group_id' => 'vulnerabilities_measures',
				'type'     => 'checkbox',
				'label'    => __("I have read and understood the risks to intervene with these measures.","really-simple-ssl"),
				'comment' => '<a href="https://really-simple-ssl.com/instructions/about-vulnerabilities#measures" target="_blank" rel="noopener noreferrer">'.__("Read more", "really-simple-ssl") .'</a>',
				'disabled' => false,
				'default'  => false,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'enable_vulnerability_scanner' => true,
					]
				],
			],
		]
	);
}, 200 );
