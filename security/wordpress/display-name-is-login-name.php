<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

add_filter( 'wp_pre_insert_user_data', 'rsssl_pre_insert_user_data_filter', 10, 4 );
add_action( 'wp_error_added', 'rsssl_handle_wp_error', 15, 4 );

/**
 * @param $data
 * @param $update
 * @param $user_id
 * @param $userdata
 *
 * @return false
 *
 * Filter user registration
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
 * Add a custom
 * @param $code
 * @param $message
 * @param $data
 * @param $wp_error
 *
 * @return void
 */
function rsssl_handle_wp_error($code, $message, $data, $wp_error) {
	if ( $code === 'empty_data' ) {
        error_log("Add custom error");
		// Add custom error
		// After h1 #add-new-user
        // Do not use 'error' class as it's hidden by JS
	}
}

/**
 *
 * Add javascript to hide error on add user page
 *
 * @return void
 */
function rsssl_hide_error_js() {
	?>
		<script>
            if ( document.querySelector('.error') ) {
                document.querySelector('.error').style['display'] = 'none';
                document.querySelector('.user-new-php .error').style['display'] = 'none';
            }
		 </script >
    <style>
        .user-new-php .error {
            display: none;
        }
    </style>
    <?php
}

add_action('admin_print_scripts', 'rsssl_hide_error_js');