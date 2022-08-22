<?php
defined('ABSPATH') or die();

/**
 * Enqueue Gutenberg block assets for backend editor.
 *
 * @since 1.0.0
 */

require_once( rsssl_path . 'settings/config/config.php' );
require_once( rsssl_path . 'settings/config/disable-fields-filter.php' );

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

	$menu = apply_filters("rsssl_grid_tabs",
		[
			[
                'id' => 'dashboard',
                'label'=> __("Dashboard", "really-simple-ssl"),
            ],
			[
				'id' => 'settings',
				'label'=> __("Settings", "really-simple-ssl"),
			]
		]
	);
	wp_localize_script(
			'rsssl-settings',
			'rsssl_settings',
			apply_filters('rsssl_localize_script',array(
				'site_url' => get_rest_url(),
				'plugin_url' => rsssl_url,
				'blocks' => rsssl_blocks(),
				'pro_plugin_active' => defined('rsssl_pro_version'),
				'menu' => $menu,
				'nonce' => wp_create_nonce( 'wp_rest' ),//to authenticate the logged in user
				'rsssl_nonce' => wp_create_nonce( 'rsssl_save' ),
			))
	);
}

function rsssl_add_option_menu() {
	if (!current_user_can('activate_plugins')) return;

	//hides the settings page if the hide menu for subsites setting is enabled
	if (is_multisite() && rsssl_multisite::this()->hide_menu_for_subsites && !is_super_admin()) return;

	$count = RSSSL()->really_simple_ssl->count_plusones();
	$update_count = $count > 0 ? "<span class='update-plugins rsssl-update-count'><span class='update-count'>$count</span></span>" : "";
	$page_hook_suffix = add_options_page(
		__("SSL settings", "really-simple-ssl"),
		__("SSL", "really-simple-ssl") . $update_count,
		'activate_plugins',
		'rlrsssl_really_simple_ssl',
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
	if (!current_user_can('activate_plugins')) return;
	$tab = isset($_GET['tab']) ? sanitize_title($_GET['tab']) : 'dashboard';
    ?>
    <div id="really-simple-ssl" class="rsssl">
        <?php do_action("rsssl_show_tab_{$tab}"); ?>
    </div>
    <div id="really-simple-ssl-modal"></div>
	<?php
}

function rsssl_ajax_load_page(){
	if (!current_user_can('activate_plugins')) return;
    $tab='dashboard';
	switch ($tab) {
		case 'dashboard' :
			break;
		case 'settings' :
        default:
			break;
	}
}

add_action( 'rest_api_init', 'rsssl_settings_rest_route', 10 );
function rsssl_settings_rest_route() {

	if (!current_user_can('manage_options')) {
		return;
	}

	register_rest_route( 'reallysimplessl/v1', 'fields/get', array(
		'methods'  => 'GET',
		'callback' => 'rsssl_rest_api_fields_get',
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		}
	) );

	register_rest_route( 'reallysimplessl/v1', 'fields/set', array(
		'methods'  => 'POST',
		'callback' => 'rsssl_rest_api_fields_set',
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		}
	) );

	register_rest_route( 'reallysimplessl/v1', 'block/(?P<block>[a-z\_\-]+)', array(
		'methods'  => 'GET',
		'callback' => 'rsssl_rest_api_block_get',
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		}
	) );

	register_rest_route( 'reallysimplessl/v1', 'tests/(?P<test>[a-z\_\-]+)', array(
		'methods'  => 'GET',
		'callback' => 'rsssl_run_test',
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		}
	) );

    register_rest_route( 'reallysimplessl/v1', 'onboarding', array(
        'methods'  => 'GET',
        'callback' => 'rsssl_rest_api_onboarding',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        }
    ) );
}

/**
 * @param WP_REST_Request $request
 *
 * @return void
 */
function rsssl_run_test($request){
	if (!current_user_can('manage_options')) {
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
        case 'fileEditingAllowed':
	        require_once( rsssl_path . 'security/dashboard/file-editing.php' );
	        $progress = new rsssl_progress($state);
            $data = $progress->get();
            break;
        case 'userRegisgrationAllowed':
	        require_once( rsssl_path . 'security/dashboard/user-registration.php' );
	        $progress = new rsssl_progress($state);
            $data = $progress->get();
            break;
        case 'debuggingEnabled':
	        require_once( rsssl_path . 'security/dashboard/debugging-enabled.php' );
	        $progress = new rsssl_progress($state);
            $data = $progress->get();
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
        'radio',
        'text',
        'textarea',
        'number',
        'email',
        'select',
        'permissionspolicy',
        'contentsecuritypolicy',
        'mixedcontentscan',
    ];
    if ( in_array($type, $types) ){
        return $type;
    }
    error_log("TYPE NOT FOUND");
    return 'checkbox';
}

/**
 * @param WP_REST_Request $request
 *
 * @return void
 */
function rsssl_rest_api_fields_set($request){
    if ( !current_user_can('manage_options')) {
        return;
    }
	$fields = $request->get_json_params();
    $config_fields = rsssl_fields(false);
    $config_ids = array_column($config_fields, 'id');
	foreach ( $fields as $index => $field ) {
        //the updateItemId allows us to update one specific item in a field set.
        $update_item_id = isset($field['updateItemId']) ? $field['updateItemId'] : false;
        $config_field_index = array_search($field['id'], $config_ids);
        $config_field = $config_fields[$config_field_index];
		if ( !$config_field_index ){
            error_log("unsetting ".$field['id']." as not existing field in RSSSL ");
			unset($fields[$index]);
			continue;
		}
		$value = rsssl_sanitize_field( $field['value'] , rsssl_sanitize_field_type($field['type']), $field['id'] );
		$value = apply_filters("rsssl_fieldvalue", $value, sanitize_text_field($field['id']));

        //if an endpoint is defined, we use that endpoint instead
        if ( isset($config_field['data_endpoint'])){
	        /**
	         * Update data to an endpoint
	         */
	        if ( isset($config_field['data_endpoint']) ){
		        $endpoint = $config_field['data_endpoint'];
		        if (is_array($endpoint) ) {
			        $main = $endpoint[0];
			        $class = $endpoint[1];
			        $function = $endpoint[2];
			        if (function_exists($main)) {
				        $main()->$class->$function( $value, $update_item_id );
			        }
		        }
	        }
	        unset($fields[$index]);
            continue;
        }

		$field['value'] = $value;
		$fields[$index] = $field;
	}
	if ( rsssl_is_networkwide_active() ) {
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
        if ( rsssl_is_networkwide_active() ) {
	        update_site_option( 'rsssl_options', $options );
        } else {
            error_log("fields_set function");
            error_log(print_r($options, true));
	        update_option( 'rsssl_options', $options );
        }
    }

	foreach ( $fields as $field ) {
        do_action( "rsssl_after_save_field", $field['id'], $field['value'], $prev_value, $field['type'] );
    }
	do_action('rsssl_after_saved_fields', $fields );
	$output   = [
            'success' => true,
            'progress' => RSSSL()->progress->get()
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
	if ( !current_user_can('manage_options') ) {
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
	if ( rsssl_is_networkwide_active() ) {
		$options = get_site_option( 'rsssl_options', [] );
	} else {
		$options = get_option( 'rsssl_options', [] );
	}

    $name = sanitize_text_field($name);
	$value = rsssl_sanitize_field( $value, rsssl_sanitize_field_type($config_field['type']), $name );
	$value = apply_filters("rsssl_fieldvalue", $value, sanitize_text_field($name));
	$options[$name] = $value;
	if ( rsssl_is_networkwide_active() ) {
		update_site_option( 'rsssl_options', $options );
	} else {
		error_log("fields_set function");
		error_log(print_r($options, true));
		update_option( 'rsssl_options', $options );
	}
}

/**
 * Get the rest api fields
 * @return void
 */

function rsssl_rest_api_fields_get(){
	if (!current_user_can('manage_options')) {
		return;
	}

	$output = array();
	$fields = rsssl_fields();
	$menu_items = rsssl_menu('settings');
	foreach ( $fields as $index => $field ) {
		/**
		 * Load data from source
		 */
		if ( isset($field['data_source']) ){
			$data_source = $field['data_source'];
			if (is_array($data_source)) {
				$main = $data_source[0];
				$class = $data_source[1];
				$function = $data_source[2];
				$field['value'] = [];
                if (function_exists($main)){
	                $field['value'] = $main()->$class->$function();
                }
			}
		}

		$fields[$index] = $field;
	}

    $updated_menu_items = rsssl_filter_menu_items($menu_items['menu_items'], $fields);
    $menu_items['menu_items'] = $updated_menu_items;
	$output['fields'] = $fields;
	$output['menu'] = $menu_items;
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
function rsssl_filter_menu_items( $menu_items, $fields) {
    $new_menu_items = $menu_items;
    foreach($menu_items as $key => $menu_item) {
        $searchResult = array_search($menu_item['id'], array_column($fields, 'menu_id'));
        if($searchResult === false) {
            unset($new_menu_items[$key]);
        } else {
            if(isset($menu_item['menu_items'])){
                $updatedValue = rsssl_filter_menu_items($menu_item['menu_items'], $fields);
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
	if (!current_user_can('manage_options')) {
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
	if ( ! current_user_can( 'manage_options' ) ) {
		return false;
	}

	switch ( $type ) {
		case 'checkbox':
		case 'hidden':
		case 'database':
			return intval($value);
		case 'select':
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
		case 'email':
			return sanitize_email( $value );
		case 'url':
			return esc_url_raw( $value );
		case 'number':
			return intval( $value );
        case 'permissionspolicy':
	        return rsssl_sanitize_permissions_policy($value, $type, $id);
		case 'contentsecuritypolicy':
            return rsssl_sanitize_datatable($value, $type, $id);
        case 'mixedcontentscan':
            return $value;
		default:
			return sanitize_text_field( $value );
	}
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
			error_log("exists in arr ".$item['id']);
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
                error_log("adding missing item ");
                error_log(print_r($def_row, true));
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