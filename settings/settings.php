<?php
defined('ABSPATH') or die();

/**
 * Enqueue Gutenberg block assets for backend editor.
 *
 * @since 1.0.0
 */

require_once( rsssl_path . 'settings/config/config.php' );
require_once( rsssl_path . 'settings/config/disable-fields-filter.php' );
//require_once( rsssl_path . 'settings/rest-api-optimizer/rest-api-optimizer.php' );

/**
 * Fix for WPML issue where WPML breaks the rest api by adding a language locale in the url
 *
 * @param $url
 * @param $path
 * @param $blog_id
 * @param $scheme
 *
 * @return string
 */
function rsssl_fix_rest_url_for_wpml( $url, $path, $blog_id, $scheme)  {
	if ( function_exists( 'icl_register_string' ) ) {
		$current_language = apply_filters( 'wpml_current_language', null );
        if ( strpos($url, '/'.$current_language.'/wp-json/') ) {
	        $url = str_replace( '/'.$current_language.'/wp-json/', '/wp-json/', $url);
        }
	}
    return $url;
}
add_filter( 'rest_url', 'rsssl_fix_rest_url_for_wpml', 10, 4 );


function rsssl_plugin_admin_scripts() {
	$script_asset_path = __DIR__."/build/index.asset.php";
	$script_asset = require( $script_asset_path );
	wp_enqueue_script(
		'rsssl-settings',
		plugins_url( 'build/index.js', __FILE__ ),
		$script_asset['dependencies'],
		$script_asset['version']
	);
	wp_set_script_translations( 'rsssl-settings', 'really-simple-ssl' );

	wp_localize_script(
        'rsssl-settings',
        'rsssl_settings',
        apply_filters('rsssl_localize_script',[
            'site_url' => get_rest_url(),
            'dashboard_url' => add_query_arg(['page' => 'really-simple-security'], rsssl_admin_url() ),
            'letsencrypt_url' => rsssl_letsencrypt_wizard_url(),
            'upgrade_link' => is_multisite() ? 'https://really-simple-ssl.com/pro-multisite' : 'https://really-simple-ssl.com/pro',
            'plugin_url' => rsssl_url,
            'network_link' => network_site_url('plugins.php'),
            'blocks' => rsssl_blocks(),
            'pro_plugin_active' => defined('rsssl_pro_version'),
            'networkwide_active' => !is_multisite() || rsssl_is_networkwide_active(),//true for single sites and network wide activated
            'nonce' => wp_create_nonce( 'wp_rest' ),//to authenticate the logged in user
            'rsssl_nonce' => wp_create_nonce( 'rsssl_nonce' ),
            'wpconfig_fix_required' => RSSSL()->admin->do_wpconfig_loadbalancer_fix() && !RSSSL()->admin->wpconfig_has_fixes(),
        ])
	);
}

/**
 * Add SSL menu
 *
 * @return void
 */
function rsssl_add_option_menu() {
	if (!rsssl_user_can_manage()) {
        return;
	}

	//hides the settings page the plugin is network activated. The settings can be found on the network settings menu.
	if ( is_multisite() && rsssl_is_networkwide_active() ) {
        return;
	}

	$count = RSSSL()->admin->count_plusones();
	$update_count = $count > 0 ? "<span class='update-plugins rsssl-update-count'><span class='update-count'>$count</span></span>" : "";
	$page_hook_suffix = add_options_page(
		__("SSL settings", "really-simple-ssl"),
		__("SSL", "really-simple-ssl") . $update_count,
		'manage_security',
		'really-simple-security',
		'rsssl_settings_page'
    );

	add_action( "admin_print_scripts-{$page_hook_suffix}", 'rsssl_plugin_admin_scripts' );
}
add_action( 'admin_menu', 'rsssl_add_option_menu' );

/**
 * Render the settings page
 */

 function rsssl_settings_page()
{
	if (!rsssl_user_can_manage()) {
        return;
	}

    ?>
    <div id="really-simple-ssl" class="rsssl"></div>
    <div id="really-simple-ssl-modal"></div>
    <?php

}

add_action( 'rest_api_init', 'rsssl_settings_rest_route', 10 );
function rsssl_settings_rest_route() {
	if (!rsssl_user_can_manage()) {
		return;
	}

	register_rest_route( 'reallysimplessl/v1', 'fields/get', array(
		'methods'  => 'GET',
		'callback' => 'rsssl_rest_api_fields_get',
		'permission_callback' => function () {
			return rsssl_user_can_manage();
		}
	) );

	register_rest_route( 'reallysimplessl/v1', 'fields/set', array(
		'methods'  => 'POST',
		'callback' => 'rsssl_rest_api_fields_set',
		'permission_callback' => function () {
			return rsssl_user_can_manage();
		}
	) );

	register_rest_route( 'reallysimplessl/v1', 'block/(?P<block>[a-z\_\-]+)', array(
		'methods'  => 'GET',
		'callback' => 'rsssl_rest_api_block_get',
		'permission_callback' => function () {
			return rsssl_user_can_manage();
		}
	) );

	register_rest_route( 'reallysimplessl/v1', 'tests/(?P<test>[a-z\_\-]+)', array(
		'methods'  => 'GET',
		'callback' => 'rsssl_run_test',
		'permission_callback' => function () {
			return rsssl_user_can_manage();
		}
	) );

	register_rest_route( 'reallysimplessl/v1', 'do_action/(?P<action>[a-z\_\-]+)', array(
		'methods'  => 'POST',
		'callback' => 'rsssl_do_action',
		'permission_callback' => function () {
			return rsssl_user_can_manage();
		}
	) );
}

/**
 * Store SSL Labs result
 * @param WP_REST_Request $request
 *
 * @return array
 */
function rsssl_store_ssl_labs($request){
	if (!rsssl_user_can_manage()) {
		return [];
	}
	$data = $request->get_json_params();
	update_option('rsssl_ssl_labs_data', $data, false);
    return [];
}

/**
 * @param WP_REST_Request $request
 *
 * @return void
 */
function rsssl_do_action($request){
	if ( !rsssl_user_can_manage() ) {
		return;
	}

	$action = sanitize_title($request->get_param('action'));
	$data = $request->get_params();
	$nonce = $data['nonce'];
	if ( !wp_verify_nonce($nonce, 'rsssl_nonce') ) {
		return;
	}

	switch($action){
		case 'ssl_status_data':
			$data = rsssl_ssl_status_data();
			break;
		case 'ssltest_run':
			$data = rsssl_ssltest_run($request);
			break;
        case 'store_ssl_labs':
			$data = rsssl_store_ssl_labs($request);
			break;
        case 'plugin_actions':
			$data = rsssl_plugin_actions($request);
			break;
		default:
			$data = apply_filters("rsssl_do_action", [], $action, $request);
	}
	$response = json_encode( $data );
	header( "Content-Type: application/json" );
	echo $response;
	exit;
}

/**
 * Process plugin installation or activation actions
 *
 * @param WP_REST_Request $request
 *
 * @return array
 */

function rsssl_plugin_actions($request){
	if ( !rsssl_user_can_manage() ) {
		return [];
	}
    $data = $request->get_params();
    $slug = sanitize_title($data['slug']);
    $action = sanitize_title($data['pluginAction']);
	$installer = new rsssl_installer($slug);
    if ($action==='download') {
	    $installer->download_plugin();
    } else if ( $action === 'activate' ) {
        $installer->activate_plugin();
    }
    return rsssl_other_plugins_data($slug);
}

/**
 * Run a request to SSL Labs
 * 
 * @param $request
 *
 * @return string
 */
function rsssl_ssltest_run($request) {
	if ( !rsssl_user_can_manage() ) {
		return '';
	}
    $data = $request->get_params();
    $url = $data['url'];
	$response = wp_remote_get( $url );
	$data     = wp_remote_retrieve_body( $response );
    if ( empty($data) ) {
	    $data = json_encode(['errors' => 'Request failed, please try again.']);
    }
    return $data;
}

/**
 * @param WP_REST_Request $request
 *
 * @return void
 */
function rsssl_run_test($request){
	if (!rsssl_user_can_manage()) {
		return;
	}

	$test = sanitize_title($request->get_param('test'));
    $state = $request->get_param('state');
    $state =  $state !== 'undefined' ? $state : false;
	switch($test){
        case 'ssl_status_data':
            $data = rsssl_ssl_status_data();
            break;
        case 'ssltest_get':
	        $data = get_option('rsssl_ssl_labs_data');
            break;
        case 'progressdata':
            $data = RSSSL()->progress->get();
            break;
        case 'otherpluginsdata':
            $data = rsssl_other_plugins_data();
            break;
        case 'dismiss_task':
	        $data = RSSSL()->progress->dismiss_task($state);
            break;
        default:
	        $data = apply_filters("rsssl_run_test", [], $test, $request);
	}
	$response = json_encode( $data );
	header( "Content-Type: application/json" );
	echo $response;
	exit;
}

/**
 * Get plugin data for other plugin section
 * @param string $slug
 * @return array
 */
function rsssl_other_plugins_data($slug=false){
	if ( !rsssl_user_can_manage() ) {
		return [];
	}
	$plugins = array(
		[
			'slug' => 'burst-statistics',
			'constant_free' => 'burst_version',
			'wordpress_url' => 'https://wordpress.org/plugins/burst-statistics/',
			'upgrade_url' => 'https://burst-statistics.com/?src=rsssl-plugin',
			'title' => 'Burst Statistics - '. __("Self-hosted, Privacy-friendly analytics tool", "really-simple-ssl"),
		],
		[
			'slug' => 'complianz-gdpr',
			'constant_free' => 'cmplz_plugin',
			'constant_premium' => 'cmplz_premium',
			'wordpress_url' => 'https://wordpress.org/plugins/complianz-gdpr/',
			'upgrade_url' => 'https://complianz.io/pricing?src=rsssl-plugin',
			'title' => __("Complianz - Cookie Consent Management as it should be", "really-simple-ssl" ),
		],
		[
			'slug' => 'complianz-terms-conditions',
			'constant_free' => 'cmplz_tc_version',
			'wordpress_url' => 'https://wordpress.org/plugins/complianz-terms-conditions/',
			'upgrade_url' => 'https://complianz.io?src=rsssl-plugin',
			'title' => 'Complianz - '. __("Terms and Conditions", "really-simple-ssl"),
		],
	);

    foreach ($plugins as $index => $plugin ){
	    $installer = new rsssl_installer($plugin['slug']);
        if ( isset($plugin['constant_premium']) && defined($plugin['constant_premium']) ) {
	        $plugins[ $index ]['pluginAction'] = 'installed';
        } else if ( !$installer->plugin_is_downloaded() && !$installer->plugin_is_activated() ) {
	        $plugins[$index]['pluginAction'] = 'download';
        } else if ( $installer->plugin_is_downloaded() && !$installer->plugin_is_activated() ) {
	        $plugins[ $index ]['pluginAction'] = 'activate';
        } else {
	        if (isset($plugin['constant_premium']) ) {
		        $plugins[$index]['pluginAction'] = 'upgrade-to-premium';
	        } else {
		        $plugins[ $index ]['pluginAction'] = 'installed';
	        }
	    }
    }

    if ( $slug ) {
        foreach ($plugins as $key=> $plugin) {
            if ($plugin['slug']===$slug){
                return $plugin;
            }
        }
    }
    return $plugins;

}

/**
 * Get activation data
 * @return array
 */
function rsssl_ssl_status_data(){
	if ( !rsssl_user_can_manage() ) {
		return [];
	}

	return [
		'certificate_is_valid' => RSSSL()->certificate->is_valid() || ( defined( 'RSSSL_FORCE_ACTIVATE' ) && RSSSL_FORCE_ACTIVATE ),
		'ssl_enabled' => rsssl_get_option('ssl_enabled'),
	];
}

/**
 * List of allowed field types
 * @param $type
 *
 * @return mixed|string
 */
function rsssl_sanitize_field_type($type){
    $types = [
        'hidden',
        'license',
        'database',
        'checkbox',
        'password',
        'radio',
        'text',
        'textarea',
        'number',
        'email',
        'select',
        'host',
        'permissionspolicy',
        'learningmode',
        'mixedcontentscan',
        'LetsEncrypt',
    ];
    if ( in_array($type, $types) ){
        return $type;
    }
    return 'checkbox';
}

/**
 * @param WP_REST_Request $request
 *
 * @return void
 */
function rsssl_rest_api_fields_set($request){
    if ( !rsssl_user_can_manage()) {
        return;
    }
	$fields = $request->get_json_params();
	//get the nonce
	$nonce = false;
	foreach ( $fields as $index => $field ){
		if ( isset($field['nonce']) ) {
			$nonce = $field['nonce'];
			unset($fields[$index]);
		}
	}

    if ( !wp_verify_nonce($nonce, 'rsssl_nonce') ) {
        return;
    }

    $config_fields = rsssl_fields(false);
    $config_ids = array_column($config_fields, 'id');
	foreach ( $fields as $index => $field ) {

        $config_field_index = array_search($field['id'], $config_ids);
        $config_field = $config_fields[$config_field_index];
		if ( !$config_field_index ){
			unset($fields[$index]);
			continue;
		}
        $type = rsssl_sanitize_field_type($field['type']);
        $field_id = sanitize_text_field($field['id']);
		$value = rsssl_sanitize_field( $field['value'] , $type,  $field_id);
        //if an endpoint is defined, we use that endpoint instead
        if ( isset($config_field['data_endpoint'])){
	        //the updateItemId allows us to update one specific item in a field set.
	        $update_item_id = isset($field['updateItemId']) ? $field['updateItemId'] : false;
	        $action = isset($field['action']) && $field['action']==='delete' ? 'delete' : 'update';
            $endpoint = $config_field['data_endpoint'];
            if (is_array($endpoint) ) {
                $main = $endpoint[0];
                $class = $endpoint[1];
                $function = $endpoint[2];
                if (function_exists($main)) {
                    $main()->$class->$function( $value, $update_item_id, $action );
                }
            } else if ( function_exists($endpoint) ){
                $endpoint($value, $update_item_id, $action);
            }

	        unset($fields[$index]);
            continue;
        }

		$field['value'] = $value;
		$fields[$index] = $field;
	}

	if ( is_multisite() && rsssl_is_networkwide_active() ) {
		$options = get_site_option( 'rsssl_options', [] );
	} else {
		$options = get_option( 'rsssl_options', [] );
	}

	//build a new options array
    foreach ( $fields as $field ) {
        $prev_value = isset( $options[ $field['id'] ] ) ? $options[ $field['id'] ] : false;
        do_action( "rsssl_before_save_option", $field['id'], $field['value'], $prev_value, $field['type'] );
        $options[ $field['id'] ] = 	apply_filters("rsssl_fieldvalue",  $field['value'], $field['id'], $field['type']);
    }

    if ( ! empty( $options ) ) {
        if ( is_multisite() && rsssl_is_networkwide_active() ) {
	        update_site_option( 'rsssl_options', $options );
        } else {
	        update_option( 'rsssl_options', $options );
        }
    }
	do_action('rsssl_after_saved_fields' );
	foreach ( $fields as $field ) {
        do_action( "rsssl_after_save_field", $field['id'], $field['value'], $prev_value, $field['type'] );
    }

	$output   = [
            'success' => true,
            'progress' => RSSSL()->progress->get(),
            'fields' => rsssl_fields(true),
    ];
	$response = json_encode( $output );
	header( "Content-Type: application/json" );
	echo $response;
	exit;
}

/**
 * Update a rsssl option
 * @param string $name
 * @param mixed $value
 *
 * @return void
 */

function rsssl_update_option( $name, $value ) {
	if ( !rsssl_user_can_manage() ) {
		return;
	}

	$config_fields = rsssl_fields(false);
	$config_ids = array_column($config_fields, 'id');
	$config_field_index = array_search($name, $config_ids);
	$config_field = $config_fields[$config_field_index];
	if ( $config_field_index === false ){
		return;
	}

	$type = $config_field['type'] ?? false;
    if ( !$type ) {
	    return;
    }
	if ( is_multisite() && rsssl_is_networkwide_active() ) {
		$options = get_site_option( 'rsssl_options', [] );
	} else {
		$options = get_option( 'rsssl_options', [] );
	}
    if ( !is_array($options) ) $options = [];
	$prev_value = $options[ $name ] ?? false;
    $name = sanitize_text_field($name);
	$type = rsssl_sanitize_field_type($config_field['type']);
	$value = rsssl_sanitize_field( $value, $type, $name );
	$value = apply_filters("rsssl_fieldvalue", $value, sanitize_text_field($name), $type);
	$options[$name] = $value;
	if ( is_multisite() && rsssl_is_networkwide_active() ) {
		update_site_option( 'rsssl_options', $options );
	} else {
		update_option( 'rsssl_options', $options );
	}
	do_action('rsssl_after_saved_fields' );
	do_action( "rsssl_after_save_field", $name, $value, $prev_value, $type );
}

/**
 * Get the rest api fields
 * @return void
 */
function rsssl_rest_api_fields_get(){
	if ( !rsssl_user_can_manage() ) {
		return;
	}

	$output = array();
	$fields = rsssl_fields();
	$menu = rsssl_menu();
	foreach ( $fields as $index => $field ) {
		/**
		 * Load data from source
		 */
		if ( isset($field['data_source']) ){
			$data_source = $field['data_source'];
			if ( is_array($data_source)) {
				$main = $data_source[0];
				$class = $data_source[1];
				$function = $data_source[2];
				$field['value'] = [];
                if ( function_exists($main) ){
                    $field['value'] = $main()->$class->$function();
                }
			} else if ( function_exists($field['data_source'])) {
                $func = $field['data_source'];
				$field['value'] = $func();
            }
		}

		$fields[$index] = $field;
	}

    //remove empty menu items
    foreach ($menu as $key => $menu_group ){
	    $menu_group['menu_items'] = rsssl_drop_empty_menu_items($menu_group['menu_items'], $fields);
	    $menu[$key] = $menu_group;
    }

	$output['fields'] = $fields;
	$output['menu'] = $menu;
	$output['progress'] = RSSSL()->progress->get();
    $output = apply_filters('rsssl_rest_api_fields_get', $output);
	$response = json_encode( $output );
	header( "Content-Type: application/json" );
	echo $response;
	exit;
}

/**
 * Checks if there are field linked to menu_item if not removes menu_item from menu_item array
 * @param $menu_items
 * @param $fields
 * @return array
 */
function rsssl_drop_empty_menu_items( $menu_items, $fields) {
	if ( !rsssl_user_can_manage() ) {
		return $menu_items;
	}
    $new_menu_items = $menu_items;
    foreach($menu_items as $key => $menu_item) {
        $searchResult = array_search($menu_item['id'], array_column($fields, 'menu_id'));
        if($searchResult === false) {
            unset($new_menu_items[$key]);
            //reset array keys to prevent issues with react
	        $new_menu_items = array_values($new_menu_items);
        } else {
            if(isset($menu_item['menu_items'])){
                $updatedValue = rsssl_drop_empty_menu_items($menu_item['menu_items'], $fields);
                $new_menu_items[$key]['menu_items'] = $updatedValue;
            }
        }
    }
    return $new_menu_items;
}

/**
 * Get grid block data
 * @param WP_REST_Request $request
 * @return void
 */
function rsssl_rest_api_block_get($request){
	if (!rsssl_user_can_manage()) {
		return;
	}
	$block = $request->get_param('block');
    $blocks = rsssl_blocks();
	$out = isset($blocks[$block]) ? $blocks[$block] : [];
	$response = json_encode( $out );
	header( "Content-Type: application/json" );
	echo $response;
	exit;
}

/**
 * Sanitize a field
 *
 * @param mixed  $value
 * @param string $type
 * @oaram string $id
 *
 * @return array|bool|int|string|void
 */
function rsssl_sanitize_field( $value, string $type, string $id ) {
	switch ( $type ) {
		case 'checkbox':
		case 'number':
			return (int) $value;
		case 'hidden':
			return sanitize_title($value);
		case 'select':
		case 'host':
		case 'text':
        case 'textarea':
		case 'license':
		    return sanitize_text_field( $value );
		case 'multicheckbox':
			if ( ! is_array( $value ) ) {
				$value = array( $value );
			}
			return array_map( 'sanitize_text_field', $value );
		case 'password':
			return rsssl_encode_password($value);
		case 'email':
			return sanitize_email( $value );
		case 'url':
			return esc_url_raw( $value );
        case 'permissionspolicy':
	        return rsssl_sanitize_permissions_policy($value, $type, $id);
		case 'learningmode':
            return rsssl_sanitize_datatable($value, $type, $id);
        case 'mixedcontentscan':
            return $value;
		default:
			return sanitize_text_field( $value );
	}
}

/**
 * Sanitize and encode a password
 *
 * @param $password
 *
 * @return mixed|string
 */
function rsssl_encode_password($password) {
	if (!rsssl_user_can_manage()) {
		return $password;
	}
	if ( strlen(trim($password)) === 0 ) {
		return $password;
	}

    $password = sanitize_text_field($password);
	if (strpos( $password , 'rsssl_') !== FALSE ) {
		return $password;
	}

	$key = get_site_option('rsssl_key');
	if ( !$key ) {
		update_site_option( 'rsssl_key' , time() );
		$key = get_site_option('rsssl_key');
	}

	$ivlength = openssl_cipher_iv_length('aes-256-cbc');
	$iv = openssl_random_pseudo_bytes($ivlength);
	$ciphertext_raw = openssl_encrypt($password, 'aes-256-cbc', $key, 0, $iv);
	$key = base64_encode( $iv.$ciphertext_raw );

	return 'rsssl_'.$key;
}

/**
 * Dedicated permission policy sanitization
 *
 * @param $value
 * @param $type
 * @param $field_name
 *
 * @return array|false
 */
function rsssl_sanitize_permissions_policy( $value, $type, $field_name ){
	$possible_keys = apply_filters("rsssl_datatable_datatypes_$type", [
		'id'=>'string',
		'title' =>'string',
		'status' => 'boolean',
	]);
	// Datatable array will look something like this, whith 0 the row index, and id, title the col indexes.
	// [0] => Array
	//	(
	//		[id] => camera
	//		[title] => Camera
	//	    [value] => ()
	//      [status] => 1/0
	//   )
	//)
	$config_fields = rsssl_fields(false);
	//check if there is a default available
	$default = false;
	foreach ($config_fields as $config_field ) {
		if ($config_field['id']===$field_name){
			$default = isset($config_field['default']) ? $config_field['default'] : false;
		}
	}

    $stored_ids = [];
	if ( !is_array($value) ) {
		return $default;
	} else {
		foreach ($value as $row_index => $row) {
			//check if we have invalid values
			if ( is_array($row) ) {
				foreach ($row as $column_index => $row_value ) {
					if ($column_index==='id' && $row_value===false) {
						unset($value[$column_index]);
					}
				}
			}

			//has to be an array.
			if ( !is_array($row) ) {
				if (isset($default[$row_index])) {
					$value[$row_index] = $default[$row_index];
				} else {
					unset($value[$row_index]);
				}
			}

			foreach ( $row as $col_index => $col_value ){
				if ( !isset( $possible_keys[$col_index])) {
					unset($value[$row_index][$col_index]);
				} else {
					$datatype = $possible_keys[$col_index];
					switch ($datatype) {
						case 'string':
							$value[$row_index][$col_index] = sanitize_text_field($col_value);
							break;
						case 'int':
						case 'boolean':
						default:
							$value[$row_index][$col_index] = intval($col_value);
							break;
					}
				}
			}

			//Ensure that all required keys are set with at least an empty value
			foreach ($possible_keys as $key => $data_type ) {
				if ( !isset($value[$row_index][$key])){
					$value[$row_index][$key] = false;
				}
			}
		}
	}

	//ensure that there are no duplicate ids
	foreach ($value as $index => $item ) {
		if ( in_array($item['id'], $stored_ids) ){
			unset($value[$index]);
			continue;
		}
		$stored_ids[] = $item['id'];
	}

    //if the default contains items not in the setting (newly added), add them.
    if ( count($value)<count($default) ) {
        foreach ($default as $def_row_index => $def_row ) {
            //check if it is available in the array. If not, add
            if ( !in_array($def_row['id'], $stored_ids) ) {
                $value[] = $def_row;
            }
        }
    }
	return $value;
}

function rsssl_sanitize_datatable( $value, $type, $field_name ){
    $possible_keys = apply_filters("rsssl_datatable_datatypes_$type", [
	    'id'=>'string',
	    'title' =>'string',
	    'status' => 'boolean',
    ]);

    if ( !is_array($value) ) {
        return false;
    } else {
        foreach ($value as $row_index => $row) {
            //check if we have invalid values
	        if ( is_array($row) ) {
		        foreach ($row as $column_index => $row_value ) {
                    if ($column_index==='id' && $row_value===false) {
	                    unset($value[$column_index]);
                    }
                }
	        }

	        //has to be an array.
	        if ( !is_array($row) ) {
                unset($value[$row_index]);
	        }

            foreach ( $row as $col_index => $col_value ){
                if ( !isset( $possible_keys[$col_index])) {
                    unset($value[$row_index][$col_index]);
                } else {
	                $datatype = $possible_keys[$col_index];
	                switch ($datatype) {
		                case 'string':
			                $value[$row_index][$col_index] = sanitize_text_field($col_value);
			                break;
		                case 'int':
		                case 'boolean':
		                default:
			                $value[$row_index][$col_index] = intval($col_value);
			                break;
	                }
                }
            }

	        //Ensure that all required keys are set with at least an empty value
            foreach ($possible_keys as $key => $data_type ) {
	            if ( !isset($value[$row_index][$key])){
		            $value[$row_index][$key] = false;
	            }
            }
        }
    }
    return $value;
}


/**
 * Check if the server side conditions apply
 *
 * @param array $conditions
 *
 * @return bool
 */

function rsssl_conditions_apply( array $conditions ){

	$defaults = ['relation' => 'AND'];
	$conditions = wp_parse_args($conditions, $defaults);
	$relation = $conditions['relation'] === 'AND' ? 'AND' : 'OR';
	unset($conditions['relation']);
	$condition_applies = true;
	foreach ( $conditions as $condition => $condition_value ) {
		$invert = substr($condition, 1)==='!';
		$condition = ltrim($condition, '!');

		if ( is_array($condition_value)) {
			$this_condition_applies = rsssl_conditions_apply($condition_value);
		} else {
			//check if it's a function
			if (substr($condition, -2) === '()'){
				$func = $condition;
				if ( preg_match( '/(.*)\(\)\-\>(.*)->(.*)/i', $func, $matches)) {
					$base = $matches[1];
					$class = $matches[2];
					$func = $matches[3];
					$func = str_replace('()', '', $func);
					$this_condition_applies = call_user_func( array( $base()->{$class}, $func ) ) === $condition_value ;
				} else {
					$func = str_replace('()', '', $func);
					$this_condition_applies = $func() === $condition_value;
				}
			} else {
				$var = $condition;
				if ( preg_match( '/(.*)\(\)\-\>(.*)->(.*)/i', $var, $matches)) {
					$base = $matches[1];
					$class = $matches[2];
					$var = $matches[3];
					$this_condition_applies = $base()->{$class}->_get($var) === $condition_value ;
				} else {
					$this_condition_applies = rsssl_get_option($var) === $condition_value;
				}
			}

			if ( $invert ){
				$this_condition_applies = !$this_condition_applies;
			}

		}

		if ($relation === 'AND') {
			$condition_applies = $condition_applies && $this_condition_applies;
		} else {
			$condition_applies = $condition_applies || $this_condition_applies;
		}
	}

	return $condition_applies;
}