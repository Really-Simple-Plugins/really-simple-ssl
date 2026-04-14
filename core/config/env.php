<?php

if (!defined('ABSPATH')) {
    exit;
}

$pluginRootPath = dirname(__DIR__, 2);
$pluginBaseFile = $pluginRootPath . DIRECTORY_SEPARATOR . plugin_basename($pluginRootPath) . '.php';

// The environment config can be used BEFORE the 'init' hook.
return [
    'plugin' => [
        'name' => 'Really Simple Security',
        'version' => '9.5.10',
        'pro' => false,
        'path' => $pluginRootPath,
        'base_path' => $pluginBaseFile,
        'assets_path' => $pluginRootPath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR,
        'lang_path' => $pluginRootPath . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR,
        'view_path' => dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR,
        'feature_path' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Features' . DIRECTORY_SEPARATOR,
        'react_path' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'react',
        'dir'  => plugin_basename(dirname(__DIR__, 2)),
        'base_file' => plugin_basename(dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR . plugin_basename(dirname(__DIR__, 2)) . '.php',
        'lang' => plugin_basename(dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'languages',
        'url'  => plugin_dir_url($pluginBaseFile),
        'assets_url' => plugins_url('assets/', $pluginBaseFile),
        'plugin_url' => plugin_dir_url($pluginBaseFile),
        'dashboard_url' => is_multisite()
            ? add_query_arg(['page' => 'really-simple-security'], network_admin_url('settings.php'))
            : add_query_arg(['page' => 'really-simple-security'], admin_url('admin.php')),
    ],
    'core' => [
        'path' => $pluginRootPath . DIRECTORY_SEPARATOR . 'core',
        'url' => plugins_url('core/', $pluginBaseFile),
        'assets_path' => $pluginRootPath . DIRECTORY_SEPARATOR . 'core/assets' . DIRECTORY_SEPARATOR,
        'assets_url' => plugins_url('core/assets/', $pluginBaseFile),
        'views_url' => plugins_url('core/app/views/', $pluginBaseFile),
        'react_url' => plugins_url('core/react/', $pluginBaseFile),
    ],
    'http' => [
        'version' => 'v1',
        'namespace' => 'really-simple-security',
    ],
    // Since we don't have enums yet:
    'onboarding' => [
        'queue_option' => 'rsssl_onboarding_actions_queue',
        'queue_event' => 'rsssl_process_onboarding_actions_queue',
    ]
];
