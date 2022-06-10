<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

add_filter( 'wp_pre_insert_user_data', 'rsssl_pre_insert_user_data_filter', 10, 4 );
add_action( 'wp_error_added', 'rsssl_handle_wp_error', 15, 4 );

/**
 *
 * Return false will return a 'Not enough data...' WP_Error object.
 * This is handled in rsssl_handle_wp_error
 *
 * @param $data
 * @param $update
 * @param $user_id
 * @param $userdata
 *
 * @return false
 *
 */
function rsssl_pre_insert_user_data_filter( $data, $update, $user_id, $userdata ) {

	if ( is_object( $data ) ) {
		if ( $data->user_login === $data->display_name ) {
            // Do not add user! Login equals display name
			return false;
		}
	}
}

/**
 *
 * Custom WP_Error handler
 * Only triggers on the empty_data user registration notice
 * Hide that notice and enter our own custom notice
 * Other notices are unaffected
 *
 * @param $code
 * @param $message
 * @param $data
 * @param $wp_error
 *
 * @return void
 */
function rsssl_handle_wp_error($code, $message, $data, $wp_error) {
	if ( $code === 'empty_data' ) {
        // Only add scripts when code = empty_data
        add_action('admin_print_scripts', 'rsssl_hide_error_js_header');
        add_action('admin_print_footer_scripts', 'rsssl_hide_error_js_footer');
	}
}

/**
 *
 * Add script in header
 *
 * @return void
 */
function rsssl_hide_error_js_header() {
	?>
    <style>
        .user-new-php .error {
            display: none;
        }
    </style>
    <?php
}

/**
 *
 * Script to add in footer. Has to be added to footer otherwise we cannot prepend our error to a non-existing div.
 * @return void
 */
function rsssl_hide_error_js_footer() {

    $text = __("Cannot create user because display name equals login name. Set a first name for your user.", "really-simple-ssl");

    ?>
    <script>
        let tag = document.createElement("div");
        tag.classList.add('rsssl-custom-error', 'notice', 'error');
        tag.style['display'] = 'block';
        tag.style['padding'] = '10px';
        let text = document.createTextNode("<?php echo $text ?>");
        tag.appendChild(text);
        let element = document.getElementById("createuser");
        element.prepend(tag);
    </script >
<?php
}