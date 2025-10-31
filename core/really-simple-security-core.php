<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Find the Jetpack packages autoloader.
 * @see https://packagist.org/packages/automattic/jetpack-autoloader
 */
$autoloaderFilePath = __DIR__ . '/vendor/autoload_packages.php';
if (file_exists($autoloaderFilePath) === false) {
    error_log('Really Simple Security: Core could not be booted, run `composer install` first.');
    return;
}

// When it exists we require the Jetpack packages autoloader.
require_once $autoloaderFilePath;

// Prevent boot when the core Plugin file is missing.
if (class_exists('\ReallySimplePlugins\RSS\Core\Bootstrap\Plugin') === false) {
    error_log('Really Simple Security: Core could not be booted, main `Plugin` class could not be found.');
    return;
}

// Boot.
$corePlugin = new \ReallySimplePlugins\RSS\Core\Bootstrap\Plugin();
$corePlugin->boot();

// Cleanup.
unset($corePlugin);