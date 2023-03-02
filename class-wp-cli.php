<?php
defined('ABSPATH') or die();

/**
 * Usage
 * php wp rsssl activate_ssl
 * php wp rsssl deactivate_ssl
 * php wp rsssl update_option --site_has_ssl=true
 * php wp rsssl update_option --site_has_ssl=true --x_xss_protection=one
* or: php wp-cli.phar rsssl update_option --x_xss_protection=one
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
		if ( !$this->wp_cli_active() ) {
			return;
		}

	    update_option("rsssl_onboarding_dismissed", true, false);
	    update_option('rsssl_6_upgrade_completed', true, false);
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
	public function update_option($args, $assoc_args)
	{
		if (!$this->wp_cli_active() ) {
			return;
		}

		if ( empty($assoc_args) ) {
			WP_CLI::error( 'No options passed' );
		}

		foreach ($assoc_args as $name => $value ) {
			rsssl_update_option(sanitize_title($name), $value);
			WP_CLI::success( "Option $name updated" );
		}

	}
}

WP_CLI::add_command( 'rsssl', 'rsssl_wp_cli' );
