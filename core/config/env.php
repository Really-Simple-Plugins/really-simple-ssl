<?php

if (!defined('ABSPATH')) {
    exit;
}

// The environment config can be used BEFORE the 'init' hook.
return [
    'plugin' => [
        'name' => 'Really Simple Security',
        'version' => '9.5.5',
        'pro' => false,
        'core_path' => dirname(__DIR__),
        'path' => dirname(__DIR__, 2),
        'base_path' => dirname(__DIR__, 2). DIRECTORY_SEPARATOR . plugin_basename(dirname(__DIR__, 2)) . '.php',
        'assets_path' => dirname(__DIR__) . DIRECTORY_SEPARATOR .'assets' . DIRECTORY_SEPARATOR,
        'lang_path' => dirname(__DIR__, 2). DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR,
        'view_path' => dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR,
        'feature_path' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Features' . DIRECTORY_SEPARATOR,
        'react_path' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'react',
        'dir'  => plugin_basename(dirname(__DIR__, 2)),
        'base_file' => plugin_basename(dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR . plugin_basename(dirname(__DIR__, 2)) . '.php',
        'lang' => plugin_basename(dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'languages',
        'url'  => plugin_dir_url(__DIR__),
        'assets_url' => plugin_dir_url(__DIR__).'assets/',
        'views_url' => plugin_dir_url(__DIR__).'app/views/',
        'react_url' => plugin_dir_url(__DIR__).'react',
        'dashboard_url' => is_multisite()
	        ? add_query_arg(['page' => 'really-simple-security'], network_admin_url('settings.php'))
	        : add_query_arg(['page' => 'really-simple-security'], admin_url('admin.php')),
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