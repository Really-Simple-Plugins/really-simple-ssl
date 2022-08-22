<?php defined('ABSPATH') or die();


/**
 * Two possibilities:
 * - a new install: show activation notice, and process onboarding
 * - an upgrade to 6. Only show new features.
 * @return array
 */
function rsssl_rest_api_onboarding() {
	$is_upgrade = true;//get_option('rsssl_upgraded_to_6');
	// "warning", // yellow dot
	// "error", // red dot
	// "active" // green dot

	$steps = [];
	$info = "";
	if( !defined('rsssl_pro_version')) {
		$info = __('You can also let the automatic scan of the pro version handle this for you, and get premium support, increased security with HSTS and more!', 'really-simple-ssl'). " " . sprintf('<a target="_blank" href="%s">%s</a>', RSSSL()->really_simple_ssl->pro_url, __("Check out Really Simple SSL Pro", "really-simple-ssl"));;
	}

	if ( !$is_upgrade ) {
		$steps[] = [
			"title" => __( "Almost ready to migrate to SSL!", 'really-simple-ssl' ),
			"subtitle" => __("Before you migrate, please check for:", "really-simple-ssl"),
			"items" => get_items_for_new_installs(),
			"info_text" => $info,
			"buttons" => get_buttons_for_new_installs(),
			"visible" => false
		];
	}

	$steps[] = [
		"title" => $is_upgrade ? __( "Thanks for updating!", 'really-simple-ssl' ) : __( "Congratulations!", 'really-simple-ssl' ),
		"subtitle" => __("Now have a look at our new features", "really-simple-ssl"),
		"items" => get_items_for_upgrade(),
		"info_text" => __("Want to know more about our features and plugins? Please read this article.", 'really-simple-ssl'),
		"buttons" => [
			[
				"title" => __('Go to Dashboard', 'really-simple-ssl'),
				"variant" => "primary",
				"disabled" => false,
				"type" => "button",
				"action" => "dismiss"
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
		"dismissed" => get_option("rsssl_onboarding_dismissed") || !RSSSL()->onboarding->show_notice_activate_ssl(),
	];
}

/**
 * Returns onboarding items if user upgraded plugin to 6.0 or SSL is detected
 * @return array
 */
function get_items_for_upgrade () {
	$plugins_to_install = [
		[
			"slug" => "burst-statistics",
			"title" => __("Burst Statistics", "really-simple-ssl")
		],
		[
			"slug" => "complianz-gdpr",
			"title" => __("Complianz - The Privacy Suite for Wordpress", "really-simple-ssl")
		]
	];

	$items = [];

	$items[] = [
		"title" => __("SSL has been activated with Really Simple SSL", "really-simple-ssl"),
		"action" => "none",
		"status" => "success",
	];

	$all_enabled = RSSSL()->onboarding->all_recommended_hardening_features_enabled();
	if( !$all_enabled ) {
		$items[] = [
			"title" => __("Enable recommended hardening features in Really Simple SSL", "really-simple-ssl"),
			"id" => "hardening",
			"action" => "activate",
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
			"status" => "success",
			"id" => "hardening",
		];
	}

	foreach ($plugins_to_install as $plugin_info) {
		require_once(rsssl_path . 'class-installer.php');
		$plugin = new rsssl_installer($plugin_info["slug"]);
		if(!$plugin->plugin_is_downloaded() && !$plugin->plugin_is_activated()){
			$items[] = [
				"title" => sprintf(__("Install our plugin %s", "really-simple-ssl"), $plugin_info["title"]),
				"action" => "install_plugin",
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
				"action" => "activate_plugin",
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
function get_buttons_for_new_installs () {

	$buttons = [];
	$buttons[] = [
		"title" => __("Activate SSL", "really-simple-ssl"),
		"variant" => "primary",
		"disabled" => true,
		"type" => "button",
		"action" => "activate_ssl",
	];

	if( !defined('rsssl_pro_version') ) {
		$buttons[] = [
			"title" => __("Improve Security with PRO", "really-simple-ssl"),
			"variant" => "secondary",
			"disabled" => false,
			"type" => "link",
			"target" => "_blank",
			"href" => RSSSL()->really_simple_ssl->pro_url
		];
	}

	//if ( !RSSSL()->rsssl_certificate->is_valid()) {
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
	//}

	return $buttons;
}

/**
 * Return onboarding items for fresh installs
 * @param $ssl_detected
 * @return array[]
 */
function get_items_for_new_installs () {
	$items = [
		[
			"title" => __("Http references in your .css and .js files: change any http:// into https://", "really-simple-ssl"),
			"status" => "warning",
		],
		[
			"title" => __("Images, stylesheets or scripts from a domain without an SSL certificate: remove them or move to your own server.", "really-simple-ssl"),
			"status" => "warning",
		],
		[
			"title" => __("You may need to login in again.", "really-simple-ssl"),
			"status" => "warning",
		],
	];

	if (RSSSL()->rsssl_certificate->is_valid()) {
		$items[] = [
			"title" => __("An SSL certificate has been detected", "really-simple-ssl"),
			"status" => "active"
		];
	} else if ( RSSSL()->rsssl_certificate->detection_failed() ) {
		$items[] = [
			"title" => __("Could not test certificate.", "really-simple-ssl") . " " . __("Automatic certificate detection is not possible on your server.", "really-simple-ssl"),
			"help" => __("If you’re certain an SSL certificate is present, please check “Override SSL detection” to continue activating SSL.", "really-simple-ssl"),
			"status" => "error"
		];
	} else {
		$items[] = [
			"title" => __("No SSL certificate has been detected.", "really-simple-ssl") . " " . sprintf(__("Please %srefresh detection%s if a certificate has been installed recently.", "really-simple-ssl"), '<a href="'.add_query_arg(array('page'=>'rlrsssl_really_simple_ssl', 'rsssl_recheck_certificate'=>1), admin_url('options-general.php')).'">', '</a>'),
			"help" => __("This detection method is not 100% accurate.", "really-simple-ssl")." ".__("If you’re certain an SSL certificate is present, please check “Override SSL detection” to continue activating SSL.", "really-simple-ssl"),
			"status" => "error"
		];
	}

	return $items;
}