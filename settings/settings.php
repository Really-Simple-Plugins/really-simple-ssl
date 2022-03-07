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
		'rsssl-wizard-plugin-admin-editor',
		plugins_url( 'build/index.js', __FILE__ ),
		$script_asset['dependencies'],
		$script_asset['version']
	);
	wp_set_script_translations( 'rsssl-wizard-plugin-block-editor', 'really-simple-ssl' );
	wp_localize_script(
			'rsssl-wizard-plugin-admin-editor',
			'rsssl_settings',
			array(
				'site_url' => get_rest_url(),
				'nonce' => wp_create_nonce( 'wp_rest' ),//to authenticate the logged in user
			)
	);

	wp_enqueue_style(
		'rsssl-wizard-plugin-admin',
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
		function() {
			ob_start();
			?>
            <div id="rsssl-wizard-content"></div>
			<?php
			    $html = ob_get_clean();
                $args = array(
                    'page' => 'settings',
                    'content' => $html,
                );
			    echo RSSSL()->really_simple_ssl->get_template('admin-wrap.php', rsssl_path.'/settings', $args );
		    }
    );

	add_action( "admin_print_scripts-{$page_hook_suffix}", 'rsssl_plugin_admin_scripts' );
}
add_action( 'admin_menu', 'rsssl_add_option_menu' );

/**
 * Render the settings page
 */

 function settings_page()
{
	if (!current_user_can('activate_plugins')) return;
	if ( isset ($_GET['tab'] ) ) rsssl_admin_tabs( $_GET['tab'] ); else rsssl_admin_tabs('configuration');
	if ( isset ($_GET['tab'] ) ) $tab = $_GET['tab']; else $tab = 'configuration';

	$high_contrast = RSSSL()->really_simple_ssl->high_contrast ? 'rsssl-high-contrast' : ''; ?>
    <div class="rsssl-container <?php echo $high_contrast ?>">
        <div class="rsssl-main"><?php
			switch ($tab) {
				case 'configuration' :
					RSSSL()->really_simple_ssl->render_grid(RSSSL()->really_simple_ssl->general_grid());
					do_action("rsssl_configuration_page");
					break;
			}
			//possibility to hook into the tabs.
			do_action("show_tab_{$tab}");
			?>
        </div>
    </div>
	<?php
}

/**
 * Create tabs on the settings page
 *
 * @since  2.1
 *
 * @access public
 *
 */

function rsssl_admin_tabs($current = 'homepage')
{
	$tabs = array(
		'configuration' => '',
	);
	$tabs = apply_filters("rsssl_grid_tabs", $tabs);

	//allow the license tab to show up for older version, to allow for upgrading
	$legacy_tabs = apply_filters("rsssl_tabs", array());
	if (isset($legacy_tabs['license'])) $tabs['license']= $legacy_tabs['license'];

	// Only show general tab if there are other tabs as well
	if (count($tabs) > 1) {
		$tabs['configuration'] = __("General", "really-simple-ssl");
	}

	$high_contrast = RSSSL()->really_simple_ssl->high_contrast ? 'rsssl-high-contrast' : ''; ?>
    <div class="nav-tab-wrapper <?php echo $high_contrast ?>">
        <div class="rsssl-logo-container">
            <div id="rsssl-logo"><img src="<?php echo rsssl_url?>/assets/really-simple-ssl-logo.png" alt="review-logo"></div>
        </div>
		<?php
		if (count($tabs)>1) {
			foreach ( $tabs as $tab => $name ) {
				$class = ( $tab == $current ) ? ' nav-tab-active' : '';
				echo "<a class='nav-tab$class' href='?page=rlrsssl_really_simple_ssl&tab=$tab'>$name</a>";
			}
		}
		?>
        <div class="header-links">
            <div class="documentation">
                <a href="https://really-simple-ssl.com/knowledge-base" class="<?php if (defined('rsssl_pro_version')) echo "button button-primary"?>" target="_blank"><?php _e("Documentation", "really-simple-ssl");?></a>
            </div>
            <div class="header-upsell">
				<?php if (defined('rsssl_pro_version')) { ?>
				<?php } else { ?>
                    <div class="documentation">
                        <a href="https://wordpress.org/support/plugin/really-simple-ssl/" class="button button-primary" target="_blank"><?php _e("Support", "really-simple-ssl") ?></a>
                    </div>
				<?php } ?>
            </div>
        </div>
    </div>
	<?php
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

function rsssl_rest_api_fields_set($request){
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
 * Get a Really Simple SSL option by name
 *
 * @param string $name
 * @param mixed $default
 *
 * @return mixed
 */
function rsssl_get_value($name, $default=false){
    $options = get_option( 'rsssl_options', array() );
    return isset($options[$name]) ? $options[$name]: $default;
}

/**
 * Get the rest api fields
 * @return void
 */

function rsssl_rest_api_fields_get(){
	$output = array();
	$fields = rsssl_fields();
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
