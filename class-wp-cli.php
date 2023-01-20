<?php
defined('ABSPATH') or die();

/**
 * Usage
 * php wp-cli.phar rsssl activate_ssl
 * php wp-cli.phar rsssl deactivate_ssl
 */
class rsssl_wp_cli
{
    public function __construct()
    {

    }

    public function activate_ssl()
    {
        $success = RSSSL()->admin->activate_ssl(false);
		if ($success) {
			WP_CLI::success( 'SSL activated successfully' );
		} else {
			WP_CLI::error( 'SSL activation failed' );
		}
    }

    public function deactivate_ssl()
    {
        RSSSL()->admin->deactivate();
        WP_CLI::success( 'SSL deactivated' );
    }
}

WP_CLI::add_command( 'rsssl', 'rsssl_wp_cli' );
