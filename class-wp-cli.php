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

	public function wp_cli_active() {
		return defined( 'WP_CLI' ) && WP_CLI;
	}

	/**
	 * Activate SSL through CLI
	 *
	 * @return void
	 * @throws \WP_CLI\ExitException
	 */
    public function activate_ssl()
    {
		if (!$this->wp_cli_active() ) {
			return;
		}
        $success = RSSSL()->admin->activate_ssl(false);
		if ($success) {
			WP_CLI::success( 'SSL activated successfully' );
		} else {
			WP_CLI::error( 'SSL activation failed' );
		}
    }

	/**
	 * Deactivate SSL through wp cli
	 *
	 * @return void
	 */
    public function deactivate_ssl()
    {
	    if (!$this->wp_cli_active() ) {
		    return;
	    }
        RSSSL()->admin->deactivate();
        WP_CLI::success( 'SSL deactivated' );
    }

	/**
	 * @param $name
	 * @param $value
	 *
	 * @return void
	 * @throws \WP_CLI\ExitException
	 */
	public function update_option($args)
	{
		if (!$this->wp_cli_active() ) {
			return;
		}

		$name = isset($args[0]) && is_string($args[0]) ? sanitize_title($args[0]) : false;
		if ( !$name ) {
			WP_CLI::error( 'Invalid option passed' );
			return;
		}
		if ( isset($args[1]) ){
			rsssl_update_option($name, $args[1]);
			WP_CLI::success( "Option $name updated" );
		} else {
			WP_CLI::error( 'Update failed: value argument not passed' );
		}
	}
}

WP_CLI::add_command( 'rsssl', 'rsssl_wp_cli' );
