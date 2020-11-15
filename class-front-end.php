<?php
defined('ABSPATH') or die("you do not have access to this page!");

if ( ! class_exists( 'rsssl_front_end' ) ) {

    class rsssl_front_end
    {
        private static $_this;
        public $javascript_redirect = FALSE;
        public $wp_redirect = TRUE;
        public $autoreplace_insecure_links = TRUE;
        public $ssl_enabled;
        public $switch_mixed_content_fixer_hook = FALSE;

        function __construct()
        {
            if (isset(self::$_this))
                wp_die(sprintf(__('%s is a singleton class and you cannot create a second instance.', 'really-simple-ssl'), get_class($this)));

            self::$_this = $this;
            $this->get_options();
            add_action('rest_api_init', array($this, 'wp_rest_api_force_ssl'), ~PHP_INT_MAX);
        }

        static function this()
        {
            return self::$_this;
        }

        /**
         * Javascript redirect, when ssl is true.
         *
         * @since  2.2
         *
         * @access public
         *
         */

        public function force_ssl()
        {
            if ($this->ssl_enabled) {
                if ($this->wp_redirect) add_action('wp', array($this, 'wp_redirect_to_ssl'), 40, 3);
            }
        }


        /**
         * Force SSL on wp rest api
         *
         * @since  2.5.14
         *
         * @access public
         *
         */

        public function wp_rest_api_force_ssl()
        {
            //check for Command Line
            if (php_sapi_name() === 'cli') return;

            if (!array_key_exists('HTTP_HOST', $_SERVER)) return;

            if ($this->ssl_enabled && !is_ssl() && !(defined("rsssl_no_rest_api_redirect") && rsssl_no_rest_api_redirect)) {
                $redirect_url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                wp_redirect($redirect_url, 301);
                exit;
            }
        }


        /**
         * Redirect using wp redirect
         *
         * @since  2.5.0
         *
         * @access public
         *
         */

        public function wp_redirect_to_ssl()
        {
            if (!array_key_exists('HTTP_HOST', $_SERVER)) return;

            if (!is_ssl() && !(defined("rsssl_no_wp_redirect") && rsssl_no_wp_redirect)) {
                $redirect_url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                $redirect_url = apply_filters("rsssl_wp_redirect_url", $redirect_url);
                wp_redirect($redirect_url, 301);
                exit;
            }
        }


        /**
         * Get the options for this plugin
         *
         * @since  2.0
         *
         * @access public
         *
         */

        public function get_options()
        {
            $options = get_option('rlrsssl_options');
            if (isset($options)) {
                $this->autoreplace_insecure_links = isset($options['autoreplace_insecure_links']) ? $options['autoreplace_insecure_links'] : TRUE;
                $this->ssl_enabled = isset($options['ssl_enabled']) ? $options['ssl_enabled'] : false;
                $this->javascript_redirect = isset($options['javascript_redirect']) ? $options['javascript_redirect'] : FALSE;
                $this->wp_redirect = isset($options['wp_redirect']) ? $options['wp_redirect'] : FALSE;
                $this->switch_mixed_content_fixer_hook = isset($options['switch_mixed_content_fixer_hook']) ? $options['switch_mixed_content_fixer_hook'] : FALSE;

                //overrides from multisite
                if (is_multisite()) {
                    $network_options = get_site_option('rlrsssl_network_options');

                    $site_wp_redirect = isset($network_options["wp_redirect"]) ? $network_options["wp_redirect"] : false;
                    $javascript_redirect = isset($network_options["javascript_redirect"]) ? $network_options["javascript_redirect"] : false;
                    $autoreplace_insecure_links = isset($network_options["autoreplace_mixed_content"]) ? $network_options["autoreplace_mixed_content"] : false;

                    if ($site_wp_redirect) $this->wp_redirect = $site_wp_redirect;
                    if ($javascript_redirect) $this->javascript_redirect = $javascript_redirect;
                    if ($autoreplace_insecure_links) $this->autoreplace_insecure_links = $autoreplace_insecure_links;

                }
            }
        }

    }
}