<?php
if ( ! defined('ABSPATH')) {
    exit;
}

if ( ! function_exists('rsssl_plugin_plugin_page_scripts')) {
    function rsssl_plugin_plugin_page_scripts($hook)
    {
        if ( $hook !== 'plugins.php' ) {
            return;
        }

		if ( is_multisite() && ! is_network_admin() ) {
			return;
		}

		$js_data = rsssl_get_chunk_translations( 'modal/build' );
		if (empty($js_data)) {
			return;
		}

	    wp_enqueue_style('wp-components');
        $handle = 'rsssl-modal';
        wp_enqueue_script(
	        $handle,
            plugins_url('build/' . $js_data['js_file'], __FILE__),
	        $js_data['dependencies'],
	        $js_data['version'],
            true
        );
        wp_set_script_translations($handle, 'really-simple-ssl');
	    $token = wp_create_nonce('rsssl_deactivate_plugin');
	    $deactivate_keep_ssl_link = add_query_arg( [ 'page' => 'really-simple-security', 'action' => 'uninstall_keep_ssl', 'token' => $token ], rsssl_admin_url() );

	    wp_localize_script(
	        $handle,
            'rsssl_modal',
            apply_filters('rsssl_localize_script', [
                'json_translations' => $js_data['json_translations'],
                'plugin_url' => rsssl_url,
                'deactivate_keep_https' => $deactivate_keep_ssl_link,
                'pro_plugin_active' => defined('rsssl_pro_version'),
            ])
        );

        function rsssl_add_modal_root_div()
        {
            // Check if we're on the plugins.php page
            $screen = get_current_screen();
            if ($screen && $screen->id === 'plugins') {
                echo '<div id="rsssl-modal-root"></div>';
            }
        }

        add_action('admin_footer', 'rsssl_add_modal_root_div');
    }
}
add_action('admin_enqueue_scripts', 'rsssl_plugin_plugin_page_scripts');