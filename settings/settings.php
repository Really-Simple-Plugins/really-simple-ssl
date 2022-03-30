<?php
defined('ABSPATH') or die();

/**
 * Enqueue Gutenberg block assets for backend editor.
 *
 * @since 1.0.0
 */


require_once( rsssl_path . 'settings/config/config.php' );

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
			array(
				'site_url' => get_rest_url(),
				'plugin_url' => rsssl_url,
				'blocks' => rsssl_blocks(),
				'premium' => defined('rsssl_pro_version'),
				'menu' => $menu,
				'nonce' => wp_create_nonce( 'wp_rest' ),//to authenticate the logged in user
			)
	);
	wp_enqueue_style(
		'rsssl-settings-css',
		plugins_url( 'css/admin.css', __FILE__ ),
		['wp-components'],
		filemtime( __DIR__."/css/admin.css" )
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
    <div id="really-simple-ssl" class="<?php echo $high_contrast ?>">
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
	        require_once( rsssl_path . 'progress/class-progress.php' );
	        $progress = new rsssl_progress($state);
            $data = $progress->get();
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
	        $data = array();
    }
    error_log(print_r($data, true));
	$response = json_encode( $data );
	header( "Content-Type: application/json" );
	echo $response;
	exit;
}

function rsssl_sanitize_field_type($type){
    $types = [
        'checkbox',
        'radio',
        'text',
        'number',
        'email',
        'select',
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
    if (!current_user_can('manage_options')) {
        return;
    }

	$fields = $request->get_json_params();
    $config_fields = rsssl_fields();
	foreach ( $fields as $index => $field ) {
		if ( !isset( $config_fields[ $field['id']] ) ){
			unset($fields[$index]);
		}
        $value = rsssl_sanitize_field( $field['value'] , rsssl_sanitize_field_type($field['type']) );
		$value = apply_filters("rsssl_fieldvalue", $value, sanitize_text_field($field['id']));
		$field['value'] = $value;
		$fields[$index] = $field;
	}
    $options = get_option( 'rsssl_options', array() );

    //build a new options array
    foreach ( $fields as $field ) {
        $prev_value = isset( $options[ $field['id'] ] ) ? $options[ $field['id'] ] : false;
        do_action( "rsssl_before_save_option", $field['id'], $field['value'], $prev_value, $field['type'] );
        $options[ $field['id'] ] = $field['value'];
    }

    if ( ! empty( $options ) ) {
        update_option( 'rsssl_options', $options );
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
 * Get the rest api fields
 * @return void
 */

function rsssl_rest_api_fields_get(  ){
	if (!current_user_can('manage_options')) {
		return;
	}
	$output = array();
	$fields = rsssl_fields();
	$menu_items = rsssl_menu('group_general');
	foreach ( $fields as $index => $field ) {
		$fields[$index]['value'] = rsssl_get_option($field['id']);
	}

	$output['fields'] = $fields;
	$output['menu'] = $menu_items;
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
 * @param $value
 * @param $type
 *
 * @return array|bool|int|string|void
 */
function rsssl_sanitize_field( $value, $type ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return false;
	}

	switch ( $type ) {
		case 'checkbox':
			return intval($value);
		case 'select':
		case 'text':
			return sanitize_text_field( $value );
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
		default:
			return sanitize_text_field( $value );
	}
}

