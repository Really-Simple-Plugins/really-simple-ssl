<?php defined('ABSPATH') or die();
/**
 * Two possibilities:
 * - a new install: show activation notice, and process onboarding
 * - an upgrade to 6. Only show new features.
 * @param WP_REST_Request $request
 * @return array
 */

function rsssl_rest_api_onboarding($request) {
	$is_upgrade = get_option('rsssl_show_onboarding');
	// "warning", // yellow dot
	// "error", // red dot
	// "active" // green dot
	$steps = [];
	$info = "";
	$refresh = isset($_GET['forceRefresh']) && $_GET['forceRefresh']===true;
	$nonce = $_GET['nonce'] ?? false;
	if ( !wp_verify_nonce($nonce, 'rsssl_nonce') ) {
		return [];
	}
	if( !defined('rsssl_pro_version')) {
		$info = __('You can also let the automatic scan of the pro version handle this for you, and get premium support, increased security with HSTS and more!', 'really-simple-ssl'). " " . sprintf('<a target="_blank" href="%s">%s</a>', RSSSL()->admin->pro_url, __("Check out Really Simple SSL Pro", "really-simple-ssl"));;
	}

	if ( !rsssl_get_option('ssl_enabled') || get_site_option('rsssl_network_activation_status')!=='completed' ) {
		$steps[] = [
			"id" => 'activate_ssl',
			"title" => __( "Almost ready to migrate to SSL!", 'really-simple-ssl' ),
			"subtitle" => __("Before you migrate, please check for:", "really-simple-ssl"),
			"items" => rsssl_get_items_for_first_step(),
			"info_text" => $info,
			"visible" => false
		];
	}

	$steps[] = [
		"id" => 'onboarding',
		"title" => $is_upgrade ? __( "Thanks for updating!", 'really-simple-ssl' ) : __( "Congratulations!", 'really-simple-ssl' ),
		"subtitle" => __("Now have a look at our new features.", "really-simple-ssl"),
		"items" => rsssl_get_items_for_second_step(),
		"info_text" => __("Want to know more about our features and plugins?", "really-simple-ssl").' '.sprintf(__("Please read this %sarticle%s.", 'really-simple-ssl'), '<a target="_blank" href="https://really-simple-ssl.com/meet-really-simple-ssl-6/">', '</a>'),
		"visible" => false
	];

	//if the user called with a refresh action, clear the cache
	if ($refresh) {
		delete_transient('rsssl_certinfo');
	}
	return [
		"request_success" =>true,
		"steps" => $steps,
		"ssl_enabled" => rsssl_get_option("ssl_enabled"),
		"ssl_detection_overridden" => get_option('rsssl_ssl_detection_overridden'),
		'certificate_valid' => RSSSL()->certificate->is_valid(),
		"networkwide" => is_multisite() && rsssl_is_networkwide_active(),
		"network_activation_status" => get_site_option('rsssl_network_activation_status'),
	];
}

/**
 * Returns onboarding items if user upgraded plugin to 6.0 or SSL is detected
 * @return array
 */
function rsssl_get_items_for_second_step () {
	$plugins_to_install = [
		[
			"slug" => "burst-statistics",
			'constant_premium' => false,
			"title" => "Burst Statistics",
			"description" => __("Burst Statistics - Self-hosted, Privacy-friendly analytics tool", "really-simple-ssl"),
		],
		[
			"slug" => "complianz-gdpr",
			'constant_premium' => 'cmplz_premium',
			"title" => "Complianz",
			"description" => __("Complianz - Cookie Consent Management as it should be", "really-simple-ssl"),
		]
	];

	$items = [];
	$items[] = [
		"id" => 'ssl_enabled',
		"title" => __("SSL has been activated", "really-simple-ssl"),
		"action" => "none",
		"status" => "success",
	];


	$all_enabled = RSSSL()->onboarding->all_recommended_hardening_features_enabled();
	if( !$all_enabled ) {
		$items[] = [
			"title" => __("Enable recommended hardening features in Really Simple SSL", "really-simple-ssl"),
			"id" => "hardening",
			"action" => "activate_setting",
			"current_action" => "none",
			"status" => "warning",
			"button" => __("Enable", "really-simple-ssl"),
		];
	} else {
		$items[] = [
			"title" => __("Hardening features are enabled!", "really-simple-ssl"),
			"action" => "none",
			"current_action" => "none",
			"status" => "success",
			"id" => "hardening",
		];
	}

	foreach ($plugins_to_install as $plugin_info) {
		require_once(rsssl_path . 'class-installer.php');
		$plugin = new rsssl_installer($plugin_info["slug"]);
		$premium_active = $plugin_info['constant_premium'] && defined($plugin_info['constant_premium']);
		$free_active = $plugin->plugin_is_downloaded() && $plugin->plugin_is_activated();
		if( $premium_active || $free_active ) {
			$items[] = [
				"title" => sprintf(__("%s has been installed!", "really-simple-ssl"), $plugin_info["title"]),
				"action" => "none",
				"current_action" => "none",
				"status" => "success",
			];
		} else if( !$plugin->plugin_is_downloaded() && !$plugin->plugin_is_activated() ){
			$items[] = [
				"title" => $plugin_info["description"],
				"action" => "install_plugin",
				"current_action" => "none",
				"status" => "warning",
				"id" => $plugin_info['slug'],
				"button" => __("Install", "really-simple-ssl"),
			];
		} else if ( $plugin->plugin_is_downloaded() && !$plugin->plugin_is_activated() ) {
			$items[] = [
				"title" => sprintf(__("Activate our plugin %s", "really-simple-ssl"), $plugin_info["title"]),
				"action" => "activate",
				"current_action" => "none",
				"status" => "warning",
				"id" => $plugin_info['slug'],
				"button" => __("Activate", "really-simple-ssl"),
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
function rsssl_get_items_for_first_step () {
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

	if ( RSSSL()->certificate->is_valid() ) {
		$items[] = [
			"title" => __("An SSL certificate has been detected", "really-simple-ssl"),
			"status" => "success"
		];
	} else if ( RSSSL()->certificate->detection_failed() ) {
		$items[] = [
			"title" => __("Could not test certificate.", "really-simple-ssl") . " " . __("Automatic certificate detection is not possible on your server.", "really-simple-ssl"),
			"status" => "error"
		];
	} else {
		$items[] = [
			"title" => __("No SSL certificate has been detected.", "really-simple-ssl") . " " . __("Please refresh the SSL status if a certificate has been installed recently.", "really-simple-ssl"),
			"status" => "error"
		];
	}

	return $items;
}
