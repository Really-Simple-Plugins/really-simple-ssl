<?php
defined('ABSPATH') or die();

/**
 * Enqueue Gutenberg block assets for backend editor.
 *
 * @since 1.0.0
 */


require_once( rsssl_path . 'settings/config/config.php' );

function rsp_react_plugin_admin_scripts() {
	$script_asset_path = __DIR__."/build/index.asset.php";
	$script_asset = require( $script_asset_path );
	wp_enqueue_script(
		'rsp-react-plugin-admin-editor',
		plugins_url( 'build/index.js', __FILE__ ),
		$script_asset['dependencies'],
		$script_asset['version']
	);
	wp_set_script_translations( 'rsp-react-plugin-block-editor', 'rsp-react' );
	wp_localize_script(
			'rsp-react-plugin-admin-editor',
			'rsp_react',
			array(
				'site_url' => get_rest_url(),
				'nonce' => wp_create_nonce( 'wp_rest' ),//to authenticate the logged in user
			)
	);

	wp_enqueue_style(
		'rsp-react-plugin-admin',
		plugins_url( 'css/admin.css', __FILE__ ),
		['wp-components'],
		filemtime( __DIR__."/css/admin.css" )
	);
}

function rsp_react_add_option_menu() {
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
		function() {
			ob_start();
			?>
            <div id="rsp-react-content"></div>
			<?php
			    $html = ob_get_clean();
                $args = array(
                    'page' => 'settings',
                    'content' => $html,
                );
			    echo RSSSL()->really_simple_ssl->get_template('admin-wrap.php', rsssl_path.'/settings', $args );
		    }
    );

	add_action( "admin_print_scripts-{$page_hook_suffix}", 'rsp_react_plugin_admin_scripts' );
}

add_action( 'admin_menu', 'rsp_react_add_option_menu' );




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
		'callback' => 'cmplz_rest_api_fields_set',
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		}
	) );
}

function cmplz_rest_api_fields_set($request){
	$fields = $request->get_json_params();
	$fields_by_source = [];
	foreach ( $fields as $field ) {
		if ( !isset( COMPLIANZ::$config->fields[ $field['id']] ) ){
			continue;
		}
		if ( isset( $field['callback'] ) ) {
			return;
		}
		if (class_exists($field['source'], false)) {
			return;
		}
		$field['value'] = apply_filters("cmplz_fieldvalue", $field['value'], $field['id']);
		$field['value'] = cmplz_sanitize_field( $field['value'], $field['type'] );
		//make translatable
		if ( $field['type'] == 'text' || $field['type'] == 'textarea' || $field['type'] == 'editor' ) {
			if ( isset( $field['translatable'] )
			     && $field['translatable']
			) {
				do_action( 'cmplz_register_translation', $field['id'], $field['value'] );
			}
		}
		$fields_by_source[ $field['source'] ][] = $field;
	}

	foreach ( $fields_by_source  as $source => $fields) {
		$options = get_option( 'complianz_options_' . $source );
		if ( ! is_array( $options ) ) {
			$options = array();
		}
		//build a new options array
		foreach ( $fields as $field ) {
			$prev_value = isset( $options[ $field['id'] ] ) ? $options[ $field['id'] ] : false;
			do_action( "complianz_before_save_" . $source . "_option", $field['id'], $field['value'], $prev_value, $field['type'] );
			$options[ $field['id'] ] = $field['value'];
		}

		if ( ! empty( $options ) ) {
			update_option( 'complianz_options_' . $source, $options );
		}

		foreach ( $fields as $field ) {
			do_action( "complianz_after_save_" . $source . "_option", $field['id'], $field['value'], $prev_value, $field['type'] );
		}

	}

	do_action('rsssl_after_saved_fields', $fields );

	$output = ['success' => true];
	$response               = json_encode( $output );
	header( "Content-Type: application/json" );
	echo $response;
	exit;
}

/**
 * Get a Really Simple SSL option by name
 *
 * @param string $name
 * @param mixed $default
 *
 * @return mixed
 */
function rsssl_get_value($name, $default=false){
    $options = get_option('rsssl_options', array());
    return isset($options[$name]) ? $options[$name]: $default;
}

/**
 * Get the rest api fields
 * @return void
 */

function rsssl_rest_api_fields_get(){
	$output = array();
    $current_menu = 'general';

	$fields = rsssl_fields($current_menu);
	$menu_items = rsssl_menu('group_general');
	foreach ( $fields as $index => $field ) {
		$fields[$index]['value'] = rsssl_get_value($field['id']);
	}

	$output['fields'] = $fields;
	$output['menu'] = $menu_items;
	$response = json_encode( $output );
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
		case 'colorpicker':
			return is_array($value ) ? array_map( 'sanitize_hex_color', $value ) : sanitize_hex_color($value);
		case 'text_checkbox':
			$value['text'] = sanitize_text_field($value['text']);
			$value['show'] = intval($value['show']);
			return $value;
		case 'text':
			return sanitize_text_field( $value );
		case 'multicheckbox':
			if ( ! is_array( $value ) ) {
				$value = array( $value );
			}

			return array_map( 'sanitize_text_field', $value );
		case 'phone':
			$value = sanitize_text_field( $value );

			return $value;
		case 'email':
			return sanitize_email( $value );
		case 'url':
			return esc_url_raw( $value );
		case 'number':
			return intval( $value );
		case 'css':
		case 'javascript':
			return  $value ;
		case 'editor':
		case 'textarea':
		    return wp_kses_post( $value );
		default:
			return sanitize_text_field( $value );
	}
}
