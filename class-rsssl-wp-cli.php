<?php

defined('ABSPATH') or die("you do not have access to this page!");

    class rsssl_wp_cli
    {

        public function __construct()
        {

        }

        public function activate_ssl()
        {
            RSSSL()->really_simple_ssl->activate_ssl();
            WP_CLI::success( 'SSL activated' );

        }

        public function deactivate_ssl()
        {
            RSSSL()->really_simple_ssl->deactivate_ssl();
            WP_CLI::success( 'SSL deactivated' );
        }

    }//Class closure

    WP_CLI::add_command( 'rsssl', 'rsssl_wp_cli' );
