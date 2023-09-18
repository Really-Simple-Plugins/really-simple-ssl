<?php

if ( ! defined('ABSPATH')) {
    exit;
}

if ( ! function_exists('rsssl_plugin_plugin_page_scripts')) {
    function rsssl_plugin_plugin_page_scripts($hook)
    {
        if ($hook !== 'plugins.php') {
            return;
        }

        // Build directory
        $buildDirPath = plugin_dir_path(__FILE__) . '/build';

        $filenames = scandir($buildDirPath);

        // filter the filenames to get the JavaScript and asset filenames
        $jsFilename    = '';
        $assetFilename = '';
        foreach ($filenames as $filename) {
            if (strpos($filename, 'index.') === 0) {
                if (substr($filename, -3) === '.js') {
                    $jsFilename = $filename;
                } elseif (substr($filename, -10) === '.asset.php') {
                    $assetFilename = $filename;
                }
            }
        }

        if ($jsFilename !== '' && $assetFilename !== '') {
            $assetFilePath = $buildDirPath . '/' . $assetFilename;
            $assetFile     = require($assetFilePath);

            // Enqueue wp-element and wp-components
            wp_enqueue_script('wp-element');
            wp_enqueue_script('wp-components');

            $handle = 'rsssl-plugin';
            wp_enqueue_script($handle);
            wp_enqueue_script(
                'rsssl-plugin',
                plugins_url('build/' . $jsFilename, __FILE__),
                $assetFile['dependencies'],
                $assetFile['version'],
                true
            );
            wp_set_script_translations($handle, 'really-simple-ssl');
            wp_localize_script(
                'rsssl-plugin',
                'rsssl_plugin',
                apply_filters('rsssl_localize_script', [
                    'json_translations' => rsssl_get_chunk_translations(),
                ])
            );

            wp_enqueue_style('wp-components'); // Styles for wp.components
            wp_enqueue_style('wp-element'); // Styles for wp.element
        }

        function rsssl_add_modal_root_div()
        {
            // Check if we're on the plugins.php page
            $screen = get_current_screen();
            if ($screen->id === 'plugins') {
                echo '<div id="rsssl-modal-root"></div>';
            }
        }

        add_action('admin_footer', 'rsssl_add_modal_root_div');
    }
}
add_action('admin_enqueue_scripts', 'rsssl_plugin_plugin_page_scripts');