<?php defined('ABSPATH') or die();
/**
 * Two possibilities:
 * - a new install: show activation notice, and process onboarding
 * - an upgrade to 6. Only show new features.
 * @return array
 */
function rsssl_rest_api_onboarding() {
	$is_upgrade = get_option('rsssl_upgraded_to_6');
	// "warning", // yellow dot
	// "error", // red dot
	// "active" // green dot
	$steps = [];
	$info = "";
	if( !defined('rsssl_pro_version')) {
		$info = __('You can also let the automatic scan of the pro version handle this for you, and get premium support, increased security with HSTS and more!', 'really-simple-ssl'). " " . sprintf('<a target="_blank" href="%s">%s</a>', RSSSL()->really_simple_ssl->pro_url, __("Check out Really Simple SSL Pro", "really-simple-ssl"));;
	}

	if ( !rsssl_get_option('ssl_enabled') || get_site_option('rsssl_network_activation_status')!=='completed' ) {
		$steps[] = [
			"title" => __( "Almost ready to migrate to SSL!", 'really-simple-ssl' ),
			"subtitle" => __("Before you migrate, please check for:", "really-simple-ssl"),
			"items" => get_items_for_first_step(),
			"info_text" => $info,
			"buttons" => get_buttons(),
			"visible" => false
		];
	}

	$steps[] = [
		"title" => $is_upgrade ? __( "Thanks for updating!", 'really-simple-ssl' ) : __( "Congratulations!", 'really-simple-ssl' ),
		"subtitle" => __("Now have a look at our new features", "really-simple-ssl"),
		"items" => get_items_for_second_step(),
		"info_text" => __("Want to know more about our features and plugins?", "really-simple-ssl").' '.sprintf(__("Please read this %sarticle%s.", 'really-simple-ssl'), '<a target="_blank" href="https://really-simple-ssl.com">', '</a>'),
		"buttons" => [
			[
				"title" => __('Go to Dashboard', 'really-simple-ssl'),
				"variant" => "primary",
				"disabled" => false,
				"type" => "button",
				"action" => "dismiss",
			],
			[
				"title" => __('Dismiss', 'really-simple-ssl'),
				"variant" => "secondary",
				"disabled" => false,
				"type" => "button",
				"action" => "dismiss",
			]
		],
		"visible" => false
	];

	return [
		"steps" => $steps,
		"ssl_enabled" => rsssl_get_option("ssl_enabled"),
		"networkwide" => rsssl_treat_as_multisite(),
		"network_activation_status" => get_site_option('rsssl_network_activation_status'),
		"dismissed" => get_option("rsssl_onboarding_dismissed") || !RSSSL()->onboarding->show_onboarding_modal(),
	];
}

/**
 * Returns onboarding items if user upgraded plugin to 6.0 or SSL is detected
 * @return array
 */
function get_items_for_second_step () {
	$plugins_to_install = [
		[
			"slug" => "burst-statistics",
			"title" => __("Burst Statistics", "really-simple-ssl"),
			"description" => __("Gather privacy-friendly statistics with Burst Statistics", "really-simple-ssl"),
		],
		[
			"slug" => "complianz-gdpr",
			"title" => __("Complianz - The Privacy Suite for Wordpress", "really-simple-ssl"),
			"description" => __("Manage privacy compliance with Complianz", "really-simple-ssl"),
		]
	];

	$items = [];
	if ( rsssl_treat_as_multisite() ) {
		if ( get_site_option('rsssl_network_activation_status')==='completed') {
			$items[] = [
				"id" => 'ssl_enabled',
				"title"  => __( "SSL has been activated network wide with Really Simple SSL", "really-simple-ssl" ),
				"action" => "none",
				"status" => "success",
			];
		} else {
			$items[] = [
				"id" => 'ssl_enabled',
				"title"  => __( "Processing activation of subsites networkwide", "really-simple-ssl" ),
				"action" => "none",
				"status" => "processing",
				"percentage" => true,
			];
		}
	} else {
		$items[] = [
			"id" => 'ssl_enabled',
			"title" => __("SSL has been activated with Really Simple SSL", "really-simple-ssl"),
			"action" => "none",
			"status" => "success",
		];
	}

	$all_enabled = RSSSL()->onboarding->all_recommended_hardening_features_enabled();
	if( !$all_enabled ) {
		$items[] = [
			"title" => __("Enable recommended hardening features in Really Simple SSL", "really-simple-ssl"),
			"id" => "hardening",
			"action" => "activate",
			"current_action" => "none",
			"status" => "warning",
			"type" => "setting",
			"button" => [
				"title" => __("Enable", "really-simple-ssl"),
			]
		];
	} else {
		$items[] = [
			"title" => __("Hardening features are enabled!", "really-simple-ssl"),
			"type" => "setting",
			"action" => "none",
			"current_action" => "none",
			"status" => "success",
			"id" => "hardening",
		];
	}

	foreach ($plugins_to_install as $plugin_info) {
		require_once(rsssl_path . 'class-installer.php');
		$plugin = new rsssl_installer($plugin_info["slug"]);
		if(!$plugin->plugin_is_downloaded() && !$plugin->plugin_is_activated()){
			$items[] = [
				"title" => $plugin_info["description"],
				"action" => "install_plugin",
				"current_action" => "none",
				"status" => "warning",
				"type" => "plugin",
				"id" => $plugin_info['slug'],
				"button" => [
					"title" => __("Install", "really-simple-ssl"),
				]
			];
		}

		if ($plugin->plugin_is_downloaded() && !$plugin->plugin_is_activated() ) {
			$items[] = [
				"title" => sprintf(__("Activate our plugin %s", "really-simple-ssl"), $plugin_info["title"]),
				"action" => "activate",
				"current_action" => "none",
				"status" => "warning",
				"type" => "plugin",
				"id" => $plugin_info['slug'],
				"button" => [
					"title" => __("Activate", "really-simple-ssl"),
				]
			];
		}

		if($plugin->plugin_is_downloaded() && $plugin->plugin_is_activated()) {
			$items[] = [
				"title" => sprintf(__("%s has been installed!", "really-simple-ssl"), $plugin_info["title"]),
				"action" => "none",
				"current_action" => "none",
				"status" => "success",
			];
		}
	}

	return $items;
}
/**
 * Return onboarding items for fresh installs
 * @param $ssl_detected
 * @return array[]
 */
function get_buttons () {
	$has_valid_cert = RSSSL()->rsssl_certificate->is_valid();
	$buttons = [];
	$buttons[] = [
		"title" => rsssl_treat_as_multisite() ? __("Activate SSL networkwide", "really-simple-ssl") : __("Activate SSL", "really-simple-ssl"),
		"variant" => "primary",
		"disabled" => !$has_valid_cert,
		"type" => "button",
		"action" => "activate_ssl",
	];

	if( $has_valid_cert && !defined('rsssl_pro_version') ) {
		$buttons[] = [
			"title" => __("Improve Security with PRO", "really-simple-ssl"),
			"variant" => "secondary",
			"disabled" => false,
			"type" => "link",
			"target" => "_blank",
			"href" => RSSSL()->really_simple_ssl->pro_url
		];
	}

	if ( !$has_valid_cert ) {
		$buttons[] = [
			"title" => __("Install SSL", "really-simple-ssl"),
			"variant" => "secondary",
			"disabled" => false,
			"type" => "link",
			"target" => "_self",
			"href" => rsssl_letsencrypt_wizard_url()
		];

		$buttons[] = [
			"title" => __("Override SSL Detection", "really-simple-ssl"),
			"disabled" => false,
			"type" => "checkbox",
		];
	}

	return $buttons;
}

/**
 * Return onboarding items for fresh installs
 * @param $ssl_detected
 * @return array[]
 */
function get_items_for_first_step () {
	$items = [
		[
			"title" => __("Http references in your .css and .js files: change any http:// into https://", "really-simple-ssl"),
			"status" => "inactive",
		],
		[
			"title" => __("Images, stylesheets or scripts from a domain without an SSL certificate: remove them or move to your own server.", "really-simple-ssl"),
			"status" => "inactive",
		],
		[
			"title" => __("You may need to login in again.", "really-simple-ssl"),
			"status" => "inactive",
		],
	];

	if (RSSSL()->rsssl_certificate->is_valid()) {
		$items[] = [
			"title" => __("An SSL certificate has been detected", "really-simple-ssl"),
			"status" => "success"
		];
	} else if ( RSSSL()->rsssl_certificate->detection_failed() ) {
		$items[] = [
			"title" => __("Could not test certificate.", "really-simple-ssl") . " " . __("Automatic certificate detection is not possible on your server.", "really-simple-ssl"),
			"help" => __("If you’re certain an SSL certificate is present, please check “Override SSL detection” to continue activating SSL.", "really-simple-ssl"),
			"status" => "error"
		];
	} else {
		$items[] = [
			"title" => __("No SSL certificate has been detected.", "really-simple-ssl") . " " . sprintf(__("Please %srefresh detection%s if a certificate has been installed recently.", "really-simple-ssl"), '<a href="'.add_query_arg(array('page'=>'really-simple-security', 'rsssl_recheck_certificate'=>1), admin_url('options-general.php')).'">', '</a>'),
			"help" => __("This detection method is not 100% accurate.", "really-simple-ssl")." ".__("If you’re certain an SSL certificate is present, please check “Override SSL detection” to continue activating SSL.", "really-simple-ssl"),
			"status" => "error"
		];
	}

	return $items;
}