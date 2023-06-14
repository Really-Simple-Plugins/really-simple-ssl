<?php
defined('ABSPATH') or die("you do not have access to this page!");

if ( ! class_exists( 'rsssl_front_end' ) ) {

    class rsssl_front_end
    {
        private static $_this;
        public $wp_redirect;
        public $ssl_enabled;

        function __construct()
        {
            if (isset(self::$_this))
                wp_die(sprintf(__('%s is a singleton class and you cannot create a second instance.', 'really-simple-ssl'), get_class($this)));

            self::$_this = $this;
	        $this->ssl_enabled = rsssl_get_option('ssl_enabled');
	        $this->wp_redirect = rsssl_get_option('redirect', 'redirect') === 'wp_redirect';
			add_action( 'rest_api_init', array($this, 'wp_rest_api_force_ssl'), ~PHP_INT_MAX);
	        add_filter( 'wp_safe_redirect_fallback', array($this, 'set_fallback_url'), 10, 2 );
	        add_filter( 'allowed_redirect_hosts', array($this, 'add_alternative_domain'), 10, 2 );
        }

        static function this()
        {
            return self::$_this;
        }

	    /**
	     * Set fallback url to site url.
	     * @param string $url
	     * @param $status
	     *
	     * @return string|null
	     */
	    public function set_fallback_url( $url, $status ) {
		    return site_url();
	    }

	    /**
	     * If a site has a non www domain, the www domain is not in the allowed_hosts list. As a consenquence, redirects from
	     * http://www to https://www will redirect to the fallback url. This filter adds the www domain to the allowed hosts list in that case.
	     * If the site_url is www, add non www. Otherwise add www to the safe list.
	     * Because a domain can be a subdomain, this will add www.sub.domain.com to the safe list.
	     * As there is no solid way to check if a domain is a subdomain, this is the best we can do.
	     *
	     * @param array $domains
	     *
	     * @return array
	     */
		public function add_alternative_domain( array $domains): array {
			$domain = site_url();
			//Parse to strip off any /subfolder/
			$parse = parse_url($domain);
			$domain = $parse['host'] ?? str_replace( array( 'http://', 'https://' ), '', $domain );
			$new_domain = strpos($domain, 'www.') !== false ? str_replace('www.', '', $domain) : 'www.' . $domain;
			if (! in_array( $new_domain, $domains, true ) ) {
				$domains[] = $new_domain;
			}
			return $domains;
		}

        /**
         * PHP redirect, when ssl is true.
         *
         * @since  2.2
         *
         * @access public
         *
         */

        public function force_ssl()
        {
            if ( $this->ssl_enabled && $this->wp_redirect ) {
                add_action('wp', array($this, 'wp_redirect_to_ssl'), 40, 3);
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

        public function wp_rest_api_force_ssl(): void {
            //check for Command Line
            if (php_sapi_name() === 'cli') return;

            if (!array_key_exists('HTTP_HOST', $_SERVER)) return;

            if ($this->ssl_enabled && !is_ssl() && !(defined("rsssl_no_rest_api_redirect") && rsssl_no_rest_api_redirect)) {
                $redirect_url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	            wp_safe_redirect($redirect_url, 301, 'Really Simple SSL');
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

        public function wp_redirect_to_ssl(): void {
            if ( !array_key_exists('HTTP_HOST', $_SERVER) ) {
				return;
            }

            if ( !is_ssl() && !(defined("rsssl_no_wp_redirect") && rsssl_no_wp_redirect) ) {
                $redirect_url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                $redirect_url = apply_filters("rsssl_wp_redirect_url", $redirect_url);
                wp_safe_redirect($redirect_url, 301, 'Really Simple SSL');
                exit;
            }
        }
    }
}