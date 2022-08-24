<?php

defined('ABSPATH') or die("you do not have access to this page!");

if (!class_exists('rsssl_multisite')) {
    class rsssl_multisite
    {
        private static $_this;

        function __construct()
        {

            if (isset(self::$_this))
                wp_die();

            self::$_this = $this;

            register_activation_hook(dirname(__FILE__) . "/" . rsssl_plugin, array($this, 'activate'));

            /*filters to make sure WordPress returns the correct protocol */
            add_filter("admin_url", array($this, "check_admin_protocol"), 20, 3);
            add_filter('home_url', array($this, 'check_site_protocol'), 20, 4);
            add_filter('site_url', array($this, 'check_site_protocol'), 20, 4);
            add_action('network_admin_menu', array(&$this, 'add_multisite_menu'));

            if ( is_network_admin() ) {
                add_action('network_admin_notices', array($this, 'show_notices'), 10);
            }

            $plugin = rsssl_plugin;
	        add_filter("network_admin_plugin_action_links_$plugin", array($this, 'plugin_settings_link'));

            //If WP version is 5.1 or higher, use wp_insert_site hook for multisite SSL activation in new blogs
            if( version_compare(get_bloginfo('version'),'5.1', '>=') ) {
                add_action('wp_initialize_site', array($this, 'maybe_activate_ssl_in_new_blog'), 20, 1);
            } else {
                add_action('wpmu_new_blog', array($this, 'maybe_activate_ssl_in_new_blog_deprecated'), 10, 6);
            }

            //Listen for run_ssl_process hook switch
            add_action('admin_init', array($this, 'listen_for_ssl_conversion_hook_switch'), 40);
	        add_filter('rsssl_notices', array($this, 'add_multisite_notices'));
        }

        static function this()
        {
            return self::$_this;
        }

        public function add_multisite_notices($notices) {
        	//only on network
	        if ( !is_network_admin()) {
				return $notices;
	        }

            $unset_array = array(
                'mixed_content_fixer_detected',
                'elementor',
                'divi',
            );

            foreach ( $unset_array as $unset_item ) {
                unset( $notices[$unset_item] );
            }

	        $notices['ssl_enabled'] = array(
		        'callback' => 'RSSSL()->rsssl_multisite->ssl_activation_status',
		        'score' => 30,
		        'output' => array(
			        'ssl-networkwide' => array(
				        'msg' =>__('SSL is enabled networkwide.', 'really-simple-ssl'),
				        'icon' => 'success'
			        ),
			        'ssl-not-enabled' => array(
				        'msg' => __('SSL is not enabled yet', 'really-simple-ssl'),
				        'icon' => 'open',
			        ),
		        ),
	        );

	        $notices['multisite_server_variable_warning'] = array(
		        'callback' => 'RSSSL()->rsssl_multisite->multisite_server_variable_warning',
		        'score' => 30,
		        'output' => array(
			        'no-server-variable' => array(
				        'msg' => __('You run a Multisite installation with subfolders, which prevents this plugin from fixing your missing server variable in the wp-config.php.', 'really-simple-ssl') . " "
                                .__('Because the $_SERVER["HTTPS"] variable is not set, your website may experience redirect loops.', 'really-simple-ssl') . " "
                                .__('Activate networkwide to fix this.', 'really-simple-ssl'),
				        'icon' => 'warning'
			        ),
		        ),
	        );

	        $notices['subdomains_no_wildcard'] = array(
		        'callback' => 'RSSSL()->rsssl_multisite->subdomains_no_wildcard',
		        'score' => 30,
		        'output' => array(
			        'subdomains-no-wildcard' => array(
				        'msg' => __("You run a Multisite installation with subdomains, but your site doesn't have a wildcard certificate.", 'really-simple-ssl') . " "
				                 . __("This leads to issues when activating SSL networkwide since subdomains will be forced over SSL as well while they don't have a valid certificate.", 'really-simple-ssl') . " "
				                 . __("Activate SSL per site or install a wildcard certificate to fix this.", 'really-simple-ssl'),
				        'icon' => 'warning',
				        'dismissible' => true,
			        ),
		        ),
	        );

            return $notices;
        }

	    /**
         * Get string success or fail network wide or per site
	     * @return string
	     */

        public function ssl_activation_status(){
            if ( $this->ssl_enabled_networkwide ){
               return 'ssl-networkwide';
            } else {
                return 'ssl-not-enabled';
            }
        }

	    /**
         * Check if site has a server var issue.
	     * @return string
	     */

        public function multisite_server_variable_warning(){
	        if ( !$this->ssl_enabled_networkwide && $this->is_multisite_subfolder_install() ) {
		        //with no server variables, the website could get into a redirect loop.
		        if (RSSSL()->really_simple_ssl->no_server_variable) {
                    return 'no-server-variable';
		        }
	        }
	        return 'success';
        }

	    /**
         * Check if we have a subdomains setup, but no wildcard
	     * @return string
	     */

	    public function subdomains_no_wildcard(){
		    if (!RSSSL()->really_simple_ssl->ssl_enabled && !$this->is_multisite_subfolder_install() && !RSSSL()->rsssl_certificate->is_wildcard() && !get_site_option("rsssl_wildcard_message_shown")) {
                return 'subdomains-no-wildcard';
		    }
		    return 'success';
	    }

	    /**
         * Add settings link on plugins overview page
	     * @param array $links
         * @since  2.0
	     * @access public
	     * @return array
	     */

	    public function plugin_settings_link($links)
	    {
		    $settings_link = '<a href="' . admin_url("network/settings.php?page=really-simple-security") . '">' . __("Settings", "really-simple-ssl") . '</a>';
		    array_unshift($links, $settings_link);
		    if ( apply_filters('rsssl_settings_link', 'free') === 'free' ) {
			    $support = '<a target="_blank" href="https://wordpress.org/support/plugin/really-simple-ssl/">' . __('Support', 'really-simple-ssl') . '</a>';
		    } else {
			    $support = '<a target="_blank" href="https://really-simple-ssl.com/support">' . __('Premium Support', 'really-simple-ssl') . '</a>';
		    }
		    array_unshift($links, $support);
		    if ( ! defined( 'rsssl_pro_version' ) ) {
			    $upgrade_link = '<a style="color:#2271b1;font-weight:bold" target="_blank" href="https://really-simple-ssl.com/pro#multisite">' . __( 'Improve security - Upgrade to Pro', 'really-simple-ssl' ) . '</a>';
			    array_unshift( $links, $upgrade_link );
		    }
		    return $links;
	    }

	    /**
         * When a new site is added, maybe activate SSL as well.
         *
	     * @param int $blog_id
	     * @param bool $user_id
	     * @param bool $domain
	     * @param bool $path
	     * @param bool $site_id
	     * @param bool $meta
	     */

        public function maybe_activate_ssl_in_new_blog_deprecated($blog_id, $user_id=false, $domain=false, $path=false, $site_id=false, $meta=false)
        {
            if ($this->ssl_enabled_networkwide) {
                $site = get_blog_details($blog_id);
	            switch_to_blog($site->blog_id);
                RSSSL()->really_simple_ssl->activate_ssl(false);
                restore_current_blog();
            }
        }

        /**
         * Activate SSl in new block
         * @since 3.1.6
         * @param $site
         * @return void
         */

        public function maybe_activate_ssl_in_new_blog($site)
        {
            if ($this->ssl_enabled_networkwide) {
	            switch_to_blog($site->blog_id);
                RSSSL()->really_simple_ssl->activate_ssl(false);
                restore_current_blog();
            }
        }

        /**
         * @param $networkwide
         *
         * On plugin activation, we can check if it is networkwide or not.
         *
         * @since  2.1
         *
         * @access public
         */

        public function activate($networkwide)
        {
            //if networkwide, we ask, if not, we set it as selected.
            if (!$networkwide) {
                $this->ssl_enabled_networkwide = false;
            }
        }

        /**
            Add network menu for SSL
            Only when plugin is network activated.
        */

        public function add_multisite_menu()
        {
            if ( !rsssl_treat_as_multisite() ) {
				return;
            }
	        $page_hook_suffix = add_submenu_page('settings.php', "SSL", "SSL", 'manage_options', "really-simple-security", 'rsssl_settings_page');
	        add_action( "admin_print_scripts-{$page_hook_suffix}", 'rsssl_plugin_admin_scripts' );
        }

        /**
         * Save network settings
         */

        public function update_network_options()
        {
            if (!current_user_can('manage_options')) {
                return;
            }
	        do_action('rsssl_process_network_options');
            if (isset($_POST["rlrsssl_network_options"])) {
	            if ( $this->ssl_enabled_networkwide ) {
		            $this->start_ssl_activation();
	            }
            }
        }

	    /**
	     * Check if an SSL process is active
	     * @return bool
	     */
        public function ssl_process_active(){

            if (get_site_option('rsssl_ssl_activation_active')){
                return true;
            }

            return false;
        }

	    /**
	     * Run SSL upgrade process
	     *
	     * @return void
	     */
        public function run_ssl_process(){
            if (get_site_option('rsssl_ssl_activation_active')){
                $this->activate_ssl_networkwide();
            }
            update_site_option('rsssl_run', false);
        }

	    /**
	     * Get SSL process completed percentage
	     * @return int
	     */
        public function get_process_completed_percentage(){
            $complete_count = get_site_option('rsssl_siteprocessing_progress');
            $percentage = round(($complete_count/$this->get_total_blog_count())*100,0);
            if ($percentage > 99) $percentage = 100;

            return intval($percentage);
        }

	    /**
	     * Start SSL activation
	     *
	     * @return void
	     */
        public function start_ssl_activation(){
            update_site_option('rsssl_siteprocessing_progress', 0);
            update_site_option('rsssl_ssl_activation_active', true);
        }

	    /**
	     * End SSL activation
	     *
	     * @return void
	     */
        public function end_ssl_activation(){
            update_site_option('rsssl_ssl_activation_active', false);
            update_site_option('run_ssl_process_hook_switched', false);
        }

	    /**
	     * Activate SSL network wide
	     */

        public function activate_ssl_networkwide()
        {
            //run chunked
            $nr_of_sites = 200;
            $current_offset = get_site_option('rsssl_siteprocessing_progress');

            //set batch of sites
	        $args = array(
		        'number' => $nr_of_sites,
		        'offset' => $current_offset,
	        );
	        $sites = get_sites($args);
            //if no sites are found, we assume we're done.
            if (count($sites)==0) {
                $this->end_ssl_activation();
            } else {
                foreach ($sites as $site) {
	                switch_to_blog($site->blog_id);
                    RSSSL()->really_simple_ssl->activate_ssl(false);
                    restore_current_blog(); //switches back to previous blog, not current, so we have to do it each loop
                    update_site_option('rsssl_siteprocessing_progress', $current_offset+$nr_of_sites);
                }
            }
        }

	    /**
	     * Deactivate SSL on all subsites
	     *
	     * @return void
	     */
        public function deactivate()
        {
			rsssl_update_option('redirect', 'none');
			rsssl_update_option('mixed_content_fixer', false );
			rsssl_update_option('mixed_content_admin', false );
			rsssl_update_option('cert_expiration_warning', false );
			rsssl_update_option('dismiss_all_notices', false );

            //because the deactivation should be a one click procedure, chunking this would cause difficulties
	        $args = array(
		        'number' => $this->get_total_blog_count(),
		        'offset' => 0,
	        );
	        $sites = get_sites($args);
            foreach ($sites as $site) {
	            switch_to_blog($site->blog_id);
                RSSSL()->really_simple_ssl->deactivate_ssl();
                restore_current_blog(); //switches back to previous blog, not current, so we have to do it each loop
            }
        }


        /**
         * filters the get_admin_url function to correct the false https urls wordpress returns for non SSL websites.
         *
         * @since 2.3.10
         *
         */

        public function check_admin_protocol($url, $path, $blog_id)
        {
            if (!$blog_id) $blog_id = get_current_blog_id();

            //if the force_ssl_admin is defined, the admin_url should not be forced back to http: all admin panels should be https.
            if (defined('FORCE_SSL_ADMIN')) return $url;

            //do not force to http if the request is made for an url of the current blog.
            //if a site is loaded over https, it should return https links, unless the url is requested for another blog.
            //In that case, we only return a https link if the site_url is https, and http otherwise.
            if (get_current_blog_id() == $blog_id) return $url;

            //now check if the blog is http or https, and change the url accordingly
            if (!$this->ssl_enabled_networkwide) {
                $home_url = get_blog_option($blog_id, 'home');
                if (strpos($home_url, "https://") === false) {
                    $url = str_replace("https://", "http://", $url);
                }
            }

            return $url;
        }

        /**
         * filters the home_url and/or site_url function to correct the false https urls wordpress returns for non SSL websites.
         *
         * @since 2.3.17
         *
         */

        public function check_site_protocol($url, $path, $orig_scheme, $blog_id)
        {
            if ( !$blog_id ) {
				$blog_id = get_current_blog_id();
            }

            if (get_current_blog_id() == $blog_id) return $url;

            if (!$this->ssl_enabled_networkwide) {
                $home_url = get_blog_option($blog_id, 'home');
                if (strpos($home_url, "https://") === false) {
                    $url = str_replace("https://", "http://", $url);
                }
            }
            return $url;
        }


        /**
         * Checks if we are on a subfolder install. (domain.com/site1 )
         *
         * @since  2.2
         *
         * @access public
         *
         **/

        public function is_multisite_subfolder_install()
        {
            if ( !is_multisite() ) {
                return false;
            }
            //we check this manually, as the SUBDOMAIN_INSTALL constant of wordpress might return false for domain mapping configs
            $is_subfolder = FALSE;
	        $args = array(
		        'number' => 5,
		        'offset' => 0,
	        );
	        $sites = get_sites($args);
            foreach ($sites as $site) {
	            switch_to_blog($site->blog_id);
				if ($this->is_subfolder(home_url())) {
                    $is_subfolder = TRUE;
                }
                restore_current_blog(); //switches back to previous blog, not current, so we have to do it each loop
                if ($is_subfolder) return true;
            }

            return $is_subfolder;
        }

        /**
         * Test if a domain has a subfolder structure
         *
         * @since  2.2
         *
         * @param string $domain
         *
         * @access private
         *
         * @return bool
         */

        public function is_subfolder($domain)
        {
            //remove slashes of the http(s)
            $domain = preg_replace("/(http:\/\/|https:\/\/)/", "", $domain);
            if (strpos($domain, "/") !== FALSE) {
                return true;
            }
            return false;
        }



        public function is_per_site_activated_multisite_subfolder_install()
        {
            if (is_multisite() && $this->is_multisite_subfolder_install() && !$this->ssl_enabled_networkwide) {
                return true;
            }
            return false;
        }

        /**
         * Sometimes conversion of websites hangs on 0%. If user clicks the link, the hook where run_ssl_process (multisite-cron.php)
         * fires on will be switched to admin_init
         *
         */

        public function listen_for_ssl_conversion_hook_switch()
        {
            //check if we are on ssl settings page
            if (!$this->is_settings_page()) return;
            //check user role
            if (!current_user_can('manage_options')) return;
            //check nonce
            if (!isset($_GET['token']) || (!wp_verify_nonce($_GET['token'], 'run_ssl_to_admin_init'))) return;
            //check for action
            if (isset($_GET["action"]) && $_GET["action"] == 'ssl_conversion_hook_switch') {
                update_site_option('run_ssl_process_hook_switched', true);
            }
        }

        /**
         * Show notices
         *
         * @since  2.0
         *
         * @access public
         *
         */

        public function show_notices()
        {
            //prevent showing the review on edit screen, as gutenberg removes the class which makes it editable.
            $screen = get_current_screen();
	        if ( $screen->base === 'post' ) return;

	        if ( !$this->is_settings_page() ) {
		        $notices = RSSSL()->really_simple_ssl->get_notices_list( array('admin_notices'=>true) );
		        foreach ( $notices as $id => $notice ){
			        $notice = $notice['output'];
			        $class = ( $notice['status'] !== 'completed' ) ? 'error' : 'updated';
			        echo RSSSL()->really_simple_ssl->notice_html( $class.' '.$id, $notice['title'], $notice['msg'] );
		        }
            }
        }

	    /**
	     * Check if we are on the settings page
	     * @return bool
	     */

        public function is_settings_page()
        {
            return (isset($_GET['page']) && $_GET['page'] === 'really-simple-security');
        }

	    /**
	     * Get blog count for all networks
	     *
	     * @return int
	     */
        public function get_total_blog_count()
        {
            //Get the total blog count from all multisite networks
            $networks = get_networks();
            $total_blog_count = 0;
            foreach($networks as $network){
                $network_id = ($network->__get('id'));
                $blog_count = get_blog_count($network_id);
                $total_blog_count += $blog_count;
            }

            return $total_blog_count;
        }

    } //class closure
}