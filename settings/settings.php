<?php
/**
 * Enqueue Gutenberg block assets for backend editor.
 *
 * @since 1.0.0
 */

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

	$page_hook_suffix = add_submenu_page(
			'complianz',
			__( 'Settings' ),
			__( 'Settings' ),
			'manage_options',
			"cmplz-settings",
			function() {
				ob_start();
				?>
					<div id="rsp-react-content">
					</div>
				<?php
				$html = ob_get_clean();
				$args = array(
						'page' => 'settings',
						'content' => $html,
				);
				echo cmplz_get_template('admin_wrap.php', $args );
			}
	);

	add_action( "admin_print_scripts-{$page_hook_suffix}", 'rsp_react_plugin_admin_scripts' );
}

add_action( 'cmplz_admin_menu', 'rsp_react_add_option_menu' );

function rsp_react_plugin_register_settings() {
	register_setting(
		'rsp_react_plugin_settings_group_2',
		'rsp_react_plugin_example_select_2',
		[
			'default'      => '',
			'show_in_rest' => true,
			'type'         => 'string',
		]
	);

	register_setting(
		'complianz_options_settings',
		'complianz_options_settings',
		[
			'default'      => array(),
			'show_in_rest' => true,
			'type'         => 'string',
		]
	);
	register_setting(
		'rsp_react_plugin_settings',
		'rsp_react_plugin_example_select',
		[
			'default'      => '',
			'show_in_rest' => true,
			'type'         => 'string',
		]
	);

	register_setting(
		'rsp_react_plugin_settings',
		'rsp_react_plugin_example_text',
		[
			'default'      => '',
			'show_in_rest' => true,
			'type'         => 'string',
		]
	);

	register_setting(
		'rsp_react_plugin_settings',
		'rsp_react_plugin_example_text_2',
		[
			'default'      => '',
			'show_in_rest' => true,
			'type'         => 'string',
		]
	);

	register_setting(
		'rsp_react_plugin_settings',
		'rsp_react_plugin_example_text_3',
		[
			'default'      => '',
			'show_in_rest' => true,
			'type'         => 'string',
		]
	);

	register_setting(
		'rsp_react_plugin_settings',
		'rsp_react_plugin_example_toggle',
		[
			'default'      => '',
			'show_in_rest' => true,
			'type'         => 'string',
		]
	);
}
add_action( 'init', 'rsp_react_plugin_register_settings', 10 );



add_action( 'rest_api_init', 'cmplz_settings_rest_route' );
function cmplz_settings_rest_route() {
	if (!current_user_can('manage_options')) {
		return;
	}

	register_rest_route( 'complianz/v1', 'fields/get', array(
		'methods'  => 'GET',
		'callback' => 'cmplz_rest_api_fields_get',
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		}
	) );
	register_rest_route( 'complianz/v1', 'fields/set', array(
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

	//		@todo update this hook to use the new type of field array
	do_action('cmplz_after_saved_all_fields', $fields );

	$output = ['success' => true];
	$response               = json_encode( $output );
	header( "Content-Type: application/json" );
	echo $response;
	exit;
}

/**
 * Get the rest api fields
 * @return void
 */

function cmplz_rest_api_fields_get(){
	$fields_output = array();
	$output = array();
	$fields = COMPLIANZ::$config->fields('settings');
	$menu_items = COMPLIANZ::$config->menu('settings');
	foreach ( $fields as $id => $field ) {
		$field['id'] = $id;
		$field['value'] = cmplz_get_value($id);
		$fields_output[] = $field;
	}

	$output['fields'] = $fields_output;
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
function cmplz_sanitize_field( $value, $type ) {
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
		case 'add_script':
		case 'block_script':
		case 'whitelist_script':
			return array_map( array($this, 'sanitize_custom_scripts'), $value );
	}

	return sanitize_text_field( $value );
}
