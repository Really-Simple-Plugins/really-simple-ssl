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
				'premium' => defined('rsssl_pro_version'),
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

    $high_contrast = RSSSL()->really_simple_ssl->high_contrast ? 'rsssl-high-contrast' : ''; ?>
    <div id="really-simple-ssl" class="rsssl <?php echo $high_contrast ?>">
        <?php do_action("rsssl_show_tab_{$tab}"); ?>
    </div>
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


add_action( 'rest_api_init', 'rsssl_settings_rest_route' );
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
	        $data = apply_filters("rsssl_run_test", array(), $test);
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
        'license',
        'checkbox',
        'radio',
        'text',
        'number',
        'email',
        'select',
        'permissionspolicy',
        'contentsecuritypolicy',
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
		        if (is_array($endpoint)) {
			        $main = $endpoint[0];
			        $class = $endpoint[1];
			        $function = $endpoint[2];
                    $main()->$class->$function( $value, $update_item_id );
		        }
	        }
	        unset($fields[$index]);
            continue;
        }

		$field['value'] = $value;
		$fields[$index] = $field;
	}
	if ( rsssl_is_networkwide_active() ) {
		$options = get_site_option( 'rsssl_options', array() );
	} else {
		$options = get_option( 'rsssl_options', array() );
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
	        update_option( 'rsssl_options', $options );
        }
    }
    foreach ( $fields as $field ) {
        do_action( "rsssl_after_save_option", $field['id'], $field['value'], $prev_value, $field['type'] );
    }

	do_action('rsssl_after_saved_fields', $fields );
	$output   = ['success' => true];
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
	if ( !$config_field_index ){
		error_log("exiting ".$name." as not existing field in RSSSL ");
		return;
	}

	$type = isset( $config_field['type'] ) ? $config_field['type'] : false;
    if ( !$type ) {
        return;
    }
	if ( rsssl_is_networkwide_active() ) {
		$options = get_site_option( 'rsssl_options', array() );
	} else {
		$options = get_option( 'rsssl_options', array() );
	}

    $name = sanitize_text_field($name);
	$value = rsssl_sanitize_field( $value, rsssl_sanitize_field_type($config_field['type']), $name );
	$value = apply_filters("rsssl_fieldvalue", $value, sanitize_text_field($name));
	$options[$name] = $value;
	if ( rsssl_is_networkwide_active() ) {
		update_site_option( 'rsssl_options', $options );
	} else {
		update_option( 'rsssl_options', $options );
	}
}

/**
 * Get the rest api fields
 * @return void
 */

function rsssl_rest_api_fields_get(  ){
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
				$field['value'] = $main()->$class->$function();
			}
		}

		$fields[$index] = $field;
	}
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
			return intval($value);
		case 'select':
		case 'text':
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
        case 'contentsecuritypolicy':
            return rsssl_sanitize_datatable($value, $type, $id);
		default:
			return sanitize_text_field( $value );
	}
}

function rsssl_sanitize_datatable( $value, $type, $id ){
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
    //	    [owndomain] =>
    //      [status] =>
    //   )
    //)
    if ( !is_array($value) ) {
        return false;
    } else {
        foreach ($value as $row_index => $row) {
	        //has to be an array.
	        if ( !is_array($row) ) {
                //in this case, there's something off with our data, so we reset to default values, if available.
		        $config_fields = rsssl_fields(false);
		        foreach ($config_fields as $config_field ) {
			        if ($config_field['id']===$id){
				        $default = $config_field['default'];
			        }
		        }
                if (isset($default[$row_index])) {
	                $value[$row_index] = $default[$row_index];
                } else {
                    unset($value[$row_index]);
                }
	        }
            foreach ( $row as $col_index => $col_value ){
                if ( !isset( $possible_keys[$col_index])) {
                    unset($value[$row_index]);
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
            foreach ( $possible_keys as $col_index => $datatype ) {
                if ( !isset($value[$row_index][$col_index])){
	                $value[$row_index][$col_index] = false;
                }
            }
        }
    }
    return $value;
}
