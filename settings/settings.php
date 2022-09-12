<?php
defined('ABSPATH') or die();

/**
 * Enqueue Gutenberg block assets for backend editor.
 *
 * @since 1.0.0
 */

require_once( rsssl_path . 'settings/config/config.php' );
require_once( rsssl_path . 'settings/config/disable-fields-filter.php' );
require_once( rsssl_path . 'settings/rest-api-optimizer/rest-api-optimizer.php' );

function rsssl_plugin_admin_scripts() {
	$script_asset_path = __DIR__."/build/index.asset.php";
	$script_asset = require( $script_asset_path );
	wp_enqueue_script(
		'rsssl-settings',
		plugins_url( 'build/index.js', __FILE__ ),
		$script_asset['dependencies'],
		$script_asset['version']
	);
	wp_set_script_translations( 'rsssl-wizard-plugin-block-editor', 'really-simple-ssl' );

	wp_localize_script(
        'rsssl-settings',
        'rsssl_settings',
        apply_filters('rsssl_localize_script',[
            'site_url' => get_rest_url(),
            'plugin_url' => rsssl_url,
            'network_link' => network_site_url('plugins.php'),
            'blocks' => rsssl_blocks(),
            'pro_plugin_active' => defined('rsssl_pro_version'),
            'networkwide_active' => !is_multisite() || rsssl_is_networkwide_active(),//true for single sites and network wide activated
            'nonce' => wp_create_nonce( 'wp_rest' ),//to authenticate the logged in user
            'rsssl_nonce' => wp_create_nonce( 'rsssl_save' ),
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

	$count = RSSSL()->really_simple_ssl->count_plusones();
	$update_count = $count > 0 ? "<span class='update-plugins rsssl-update-count'><span class='update-count'>$count</span></span>" : "";
	$page_hook_suffix = add_options_page(
		__("SSL settings", "really-simple-ssl"),
		__("SSL", "really-simple-ssl") . $update_count,
		'activate_plugins',
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
	if (!rsssl_user_can_manage()) return;

	if ( !get_option('permalink_structure') ){
        $permalinks_url = admin_url('options-permalink.php');
        ?>
            <div class="rsssl-permalinks-warning notice notice-error settings-error is-dismissible">
                <h1><?php _e("Pretty permalinks not enabled", "really-simple-ssl")?></h1>
                <p><?php _e("Pretty permalinks are not enabled on your site. This prevents the REST API from working, which is required for the settings page.", "really-simple-ssl")?></p>
                <p><?php printf(__('To resolve, please go to the <a href="%s">permalinks settings</a>, and set to anything but plain.', "really-simple-ssl"), $permalinks_url)?></p>
            </div>
        <?php
    } else {
        ?>
        <div id="really-simple-ssl" class="rsssl"></div>
        <div id="really-simple-ssl-modal"></div>
        <?php
    }
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
        case 'ssltest':
	        require_once( rsssl_path . 'ssllabs/class-ssllabs.php' );
	        $test = new rsssl_ssllabs();
	        $data = $test->get($state);
            break;
        case 'progressdata':
            $data = RSSSL()->progress->get();
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
    $config_fields = rsssl_fields(false);
    $config_ids = array_column($config_fields, 'id');
	foreach ( $fields as $index => $field ) {

        $config_field_index = array_search($field['id'], $config_ids);
        $config_field = $config_fields[$config_field_index];
		if ( !$config_field_index ){
            error_log("unsetting ".$field['id']." as not existing field in RSSSL ");
			unset($fields[$index]);
			continue;
		}
        $type = rsssl_sanitize_field_type($field['type']);
        $field_id = sanitize_text_field($field['id']);
		$value = rsssl_sanitize_field( $field['value'] , $type,  $field_id);
		error_log("update option");
		if ($type==='password') {
			error_log($field_id);
			error_log($value);
		}
		$value = rsssl_sanitize_field( $value, $type, $field_id );
		if ($type==='password') {
			error_log("after sanitize");
			error_log($value);
		}

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
        $options[ $field['id'] ] = $field['value'];
    }

    if ( ! empty( $options ) ) {
        if ( is_multisite() && rsssl_is_networkwide_active() ) {
	        update_site_option( 'rsssl_options', $options );
        } else {
	        update_option( 'rsssl_options', $options );
        }
    }

	foreach ( $fields as $field ) {
        do_action( "rsssl_after_save_field", $field['id'], $field['value'], $prev_value, $field['type'] );
    }
	do_action('rsssl_after_saved_fields', $fields );
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
		error_log("exiting ".$name." as not existing field in RSSSL ");
		return;
	}

	$type = isset( $config_field['type'] ) ? $config_field['type'] : false;
    if ( !$type ) {
	    error_log("exiting ".$name." has not existing type ");
	    return;
    }
	if ( is_multisite() && rsssl_is_networkwide_active() ) {
		$options = get_site_option( 'rsssl_options', [] );
	} else {
		$options = get_option( 'rsssl_options', [] );
	}
    if ( !is_array($options) ) $options = [];
    $name = sanitize_text_field($name);
	$type = rsssl_sanitize_field_type($config_field['type']);
    error_log("update option");
    if ($type==='password') {
        error_log($name);
        error_log($value);
    }
	$value = rsssl_sanitize_field( $value, $type, $name );
	if ($type==='password') {
		error_log("after sanitize");
		error_log($value);
	}
	$value = apply_filters("rsssl_fieldvalue", $value, sanitize_text_field($name), $type);
	$options[$name] = $value;
	if ( is_multisite() && rsssl_is_networkwide_active() ) {
		update_site_option( 'rsssl_options', $options );
	} else {
		update_option( 'rsssl_options', $options );
	}
}

/**
 * Get the rest api fields
 * @return void
 */

function rsssl_rest_api_fields_get(){
	if (!rsssl_user_can_manage()) {
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
                if (function_exists($main)){
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
 * @param mixed $value
 * @param string $type
 * @oaram string $id
 *
 * @return array|bool|int|string|void
 */
function rsssl_sanitize_field( $value, $type, $id ) {
	if ( ! rsssl_user_can_manage() ) {
		return false;
	}

	switch ( $type ) {
		case 'checkbox':
			return intval($value);
		case 'hidden':
		case 'database':
			return sanitize_title($value);
		case 'select':
		case 'host':
		case 'text':
		    return sanitize_text_field( $value );
        case 'textarea':
		    return sanitize_text_field( $value );
		case 'license':
		    return $value;
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
		case 'number':
			return intval( $value );
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