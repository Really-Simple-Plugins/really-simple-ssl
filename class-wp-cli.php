<?php

defined('ABSPATH') or die("you do not have access to this page!");

    class rsssl_wp_cli
    {

        public function __construct()
        {

        }

        public function activate_ssl()
        {
            RSSSL()->admin->activate_ssl(false);
            WP_CLI::success( 'SSL activated' );

        }

        public function deactivate_ssl()
        {
            RSSSL()->admin->deactivate();
            WP_CLI::success( 'SSL deactivated' );
        }

    }//Class closure

    WP_CLI::add_command( 'rsssl', 'rsssl_wp_cli' );
