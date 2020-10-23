<?php

defined('ABSPATH') or die("you do not have access to this page!");

if (!class_exists('rsssl_multisite')) {
    class rsssl_multisite
    {
        private static $_this;

        public $section = "rsssl_network_options_section";
        public $ssl_enabled_networkwide;
        public $selected_networkwide_or_per_site;
        public $wp_redirect;
        public $htaccess_redirect;
        public $do_not_edit_htaccess;
        public $autoreplace_mixed_content;
        public $javascript_redirect;
        public $hsts;
        public $mixed_content_admin;
        public $cert_expiration_warning;
        public $hide_menu_for_subsites;

        function __construct()
        {

            if (isset(self::$_this))
                wp_die(sprintf(__('%s is a singleton class and you cannot create a second instance.', 'really-simple-ssl'), get_class($this)));

            self::$_this = $this;

            $this->load_options();
            register_activation_hook(dirname(__FILE__) . "/" . rsssl_plugin, array($this, 'activate'));

            /*filters to make sure WordPress returns the correct protocol */
            add_filter("admin_url", array($this, "check_admin_protocol"), 20, 3);
            add_filter('home_url', array($this, 'check_site_protocol'), 20, 4);
            add_filter('site_url', array($this, 'check_site_protocol'), 20, 4);

            add_action("plugins_loaded", array($this, "process_networkwide_choice"), 10, 0);
            add_action("plugins_loaded", array($this, "networkwide_choice_notice"), 20, 0);

            add_action('network_admin_menu', array(&$this, 'add_multisite_menu'));
            add_action('network_admin_edit_rsssl_update_network_settings', array($this, 'update_network_options'));

            if (is_network_admin()) {
                add_action('network_admin_notices', array($this, 'show_notices'), 10);
                add_action('admin_print_footer_scripts', array($this, 'insert_dismiss_success'));
                add_action('admin_print_footer_scripts', array($this, 'insert_dismiss_wildcard_warning'));
            }

            $plugin = rsssl_plugin;
	        add_filter("network_admin_plugin_action_links_$plugin", array($this, 'plugin_settings_link'));

            add_action('wp_ajax_dismiss_success_message_multisite', array($this, 'dismiss_success_message_callback'));
            add_action('wp_ajax_dismiss_wildcard_warning', array($this, 'dismiss_wildcard_message_callback'));
            add_action("rsssl_show_network_tab_settings", array($this, 'settings_tab'));

            //If WP version is 5.1 or higher, use wp_insert_site hook for multisite SSL activation in new blogs
            if(version_compare(get_bloginfo('version'),'5.1', '>=') ) {
                add_action('wp_insert_site', array($this, 'maybe_activate_ssl_in_new_blog'), 20, 1);
            } else {
                add_action('wpmu_new_blog', array($this, 'maybe_activate_ssl_in_new_blog_deprecated'), 10, 6);
            }
            //Listen for run_ssl_process hook switch
            add_action('admin_init', array($this, 'listen_for_ssl_conversion_hook_switch'), 40);


        }

        static function this()
        {
            return self::$_this;
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
		    $settings_link = '<a href="' . admin_url("options-general.php?page=rlrsssl_really_simple_ssl") . '">' . __("Settings", "really-simple-ssl") . '</a>';
		    array_unshift($links, $settings_link);

		    if ( apply_filters('rsssl_settings_link', 'free') === 'free' ) {
			    $support = '<a target="_blank" href="https://wordpress.org/support/plugin/really-simple-ssl/">' . __('Support', 'really-simple-ssl') . '</a>';
		    } else {
			    $support = '<a target="_blank" href="https://really-simple-ssl.com/support">' . __('Premium Support', 'really-simple-ssl') . '</a>';
		    }
		    array_unshift($links, $support);

		    if ( ! defined( 'rsssl_pro_version' ) ) {
			    $upgrade_link = '<a style="color:#f8be2e;font-weight:bold" target="_blank" href="https://really-simple-ssl.com/pro/#multisite">'
			                    . __( 'Upgrade to premium', 'really-simple-ssl' ) . '</a>';
			    array_unshift( $links, $upgrade_link );
		    }
		    return $links;
	    }

        /*

            When a new site is added, maybe activate SSL as well.

        */

        public function maybe_activate_ssl_in_new_blog_deprecated($blog_id, $user_id=false, $domain=false, $path=false, $site_id=false, $meta=false)
        {

            if ($this->ssl_enabled_networkwide) {
                $site = get_blog_details($blog_id);
                $this->switch_to_blog_bw_compatible($site);
                RSSSL()->really_simple_ssl->activate_ssl();
                restore_current_blog(); //switches back to previous blog, not current, so we have to do it each loop
            }
        }

        /**
         * Activate SSl in new block
         * @since 3.1.6
         * @param $new_site
         * @return void
         */

//        public function maybe_activate_ssl_in_new_blog($site)
//        {
//
//            if ($this->ssl_enabled_networkwide) {
//                $this->switch_to_blog_bw_compatible($site);
//                RSSSL()->really_simple_ssl->activate_ssl();
//                restore_current_blog(); //switches back to previous blog, not current, so we have to do it each loop
//            }
//        }


        public function networkwide_choice_notice()
        {
            if ($this->plugin_network_wide_active() && !$this->selected_networkwide_or_per_site) {
                add_action('network_admin_notices', array($this, 'show_notice_activate_networkwide'), 10);
            }
        }

        public function load_options()
        {
            $options = get_site_option('rlrsssl_network_options');
            $this->selected_networkwide_or_per_site = isset($options["selected_networkwide_or_per_site"]) ? $options["selected_networkwide_or_per_site"] : false;
            $this->ssl_enabled_networkwide = isset($options["ssl_enabled_networkwide"]) ? $options["ssl_enabled_networkwide"] : false;
            $this->wp_redirect = isset($options["wp_redirect"]) ? $options["wp_redirect"] : false;
            $this->htaccess_redirect = isset($options["htaccess_redirect"]) ? $options["htaccess_redirect"] : false;
            $this->do_not_edit_htaccess = isset($options["do_not_edit_htaccess"]) ? $options["do_not_edit_htaccess"] : false;
            $this->autoreplace_mixed_content = isset($options["autoreplace_mixed_content"]) ? $options["autoreplace_mixed_content"] : false;
            $this->javascript_redirect = isset($options["javascript_redirect"]) ? $options["javascript_redirect"] : false;
            $this->hsts = isset($options["hsts"]) ? $options["hsts"] : false;
            $this->mixed_content_admin = isset($options["mixed_content_admin"]) ? $options["mixed_content_admin"] : false;
            $this->cert_expiration_warning = isset($options["cert_expiration_warning"]) ? $options["cert_expiration_warning"] : false;
            $this->hide_menu_for_subsites = isset($options["hide_menu_for_subsites"]) ? $options["hide_menu_for_subsites"] : false;
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
                $this->selected_networkwide_or_per_site = true;
                $this->ssl_enabled_networkwide = false;
                $this->save_options();
            }

        }

        /**
            Add network menu for SSL
            Only when plugin is network activated.
        */

        public function add_multisite_menu()
        {
            if (!$this->plugin_network_wide_active()) return;

            register_setting('rsssl_network_options', 'rsssl_options');
            add_settings_section('rsssl_network_settings', __("Settings", "really-simple-ssl"), array($this, 'section_text'), "really-simple-ssl");

            add_settings_field('id_ssl_enabled_networkwide', __("Enable SSL", "really-simple-ssl"), array($this, 'get_option_enable_multisite'), "really-simple-ssl", 'rsssl_network_settings');
            RSSSL()->rsssl_network_admin_page = add_submenu_page('settings.php', "SSL", "SSL", 'manage_options', "really-simple-ssl", array(&$this, 'multisite_menu_page'));


        }

        /**
            Shows the content of the multisite menu page
        */

        public function section_text() {}

        public function get_option_enable_multisite()
        {
	        rsssl_help::this()->get_help_tip(__("Select to enable SSL networkwide or per site.", "really-simple-ssl"));
            ?>
            <select name="rlrsssl_network_options[ssl_enabled_networkwide]">
                <?php if (!$this->selected_networkwide_or_per_site) { ?>
                <option value="-1" <?php if (!$this->selected_networkwide_or_per_site) echo "selected"; ?>><?php _e("No selection was made", "really-simple-ssl") ?>
                    <?php } ?>
                <option value="1" <?php if ($this->ssl_enabled_networkwide) echo "selected"; ?>><?php _e("networkwide", "really-simple-ssl") ?>
                <option value="0" <?php if (!$this->ssl_enabled_networkwide) echo "selected"; ?>><?php _e("per site", "really-simple-ssl") ?>
            </select>
            <?php
        }


        /**
         * Displays the options page. The big difference here is where you post the data
         * because, unlike for normal option pages, there is nowhere to process it by
         * default so we have to create our own hook to process the saving of our options.
         */

        public function multisite_menu_page()
        {
            $tab = "settings";
            if (isset ($_GET['tab'])) $tab = $_GET['tab'];
            $this->admin_tabs($tab);

            do_action("rsssl_show_network_tab_{$tab}");
        }


		/**
		 * Build the settings page
		 *
		 * @since  2.0
		 *
		 * @access public
		 *
		 */

		public function general_grid(){
			$grid_items = array(
				'ms_settings' => array(
					'title' => __("Settings", "really-simple-ssl"),
					'header' => rsssl_template_path . 'header.php',
					'content' => rsssl_template_path . 'ms-settings.php',
					'footer' => rsssl_template_path . 'settings-footer.php',
					'class' => ' settings',
					'type' => 'settings',
					'can_hide' => true,
				),
				'support' => array(
					'title' => __("Support forum", "really-simple-ssl"),
					'header' => '',
					'content' => rsssl_template_path . 'support.php',
					'footer' => rsssl_template_path . 'support-footer.php',
					'type' => 'tasks',
					'class' => 'half-height',
					'can_hide' => true,
				),
				'plugins' => array(
					'title' => __("Our plugins", "really-simple-ssl"),
					'header' => rsssl_template_path . 'header.php',
					'content' => rsssl_template_path . 'other-plugins.php',
					'footer' => '',
					'class' => 'half-height no-border no-background upsell-grid-container',
					'type' => 'plugins',
					'can_hide' => false,
				),
			);
			return apply_filters( 'rsssl_grid_items',  $grid_items );
		}

		public function settings_tab()
        {
            if (isset($_GET['updated'])): ?>
                <div id="message" class="updated notice is-dismissible">
                    <p><?php _e('Options saved.', 'really-simple-ssl') ?></p>
                </div>
            <?php endif; ?>

            <div class="nav-tab-wrapper">
                <div class="rsssl-logo-container">
                    <div id="rsssl-logo"><img height="50px" src="<?php echo rsssl_url?>/assets/logo-really-simple-ssl.png" alt="logo"></div>
                </div>

                <div class="header-links">
                    <div class="documentation">
                        <a href="https://really-simple-ssl.com/knowledge-base" target="_blank"><?php _e("Documentation", "really-simple-ssl");?></a>
                    </div>
                    <?php if (!defined('rsssl_pro_version')) { ?>
                        <div class="header-upsell">
                            <a href="https://really-simple-ssl.com/pro#multisite" target="_blank">
                                <div class="header-upsell-pro"><?php _e("PRO", "really-simple-ssl"); ?></div>
                            </a>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="rsssl-container">
                <div class="rsssl-main"><?php
                    RSSSL()->really_simple_ssl->render_grid($this->general_grid());
                    do_action("rsssl_configuration_page");
			        ?>
                </div>
            </div>

            <?php
        }


        /**
         * Save network settings
         */

        public function update_network_options()
        {
            check_admin_referer('rsssl_network_options' . '-options');

            if (isset($_POST["rlrsssl_network_options"])) {
                $prev_ssl_enabled_networkwide = $this->ssl_enabled_networkwide;
                $options = array_map(array($this, "sanitize_boolean"), $_POST["rlrsssl_network_options"]);
                $options["selected_networkwide_or_per_site"] = true;
                $this->ssl_enabled_networkwide = isset($options["ssl_enabled_networkwide"]) ? $options["ssl_enabled_networkwide"] : false;
                $this->wp_redirect = isset($options["wp_redirect"]) ? $options["wp_redirect"] : false;
                $this->htaccess_redirect = isset($options["htaccess_redirect"]) ? $options["htaccess_redirect"] : false;
                $this->do_not_edit_htaccess = isset($options["do_not_edit_htaccess"]) ? $options["do_not_edit_htaccess"] : false;
                $this->autoreplace_mixed_content = isset($options["autoreplace_mixed_content"]) ? $options["autoreplace_mixed_content"] : false;
                $this->javascript_redirect = isset($options["javascript_redirect"]) ? $options["javascript_redirect"] : false;
                $this->hsts = isset($options["hsts"]) ? $options["hsts"] : false;
                $this->mixed_content_admin = isset($options["mixed_content_admin"]) ? $options["mixed_content_admin"] : false;
                $this->cert_expiration_warning = isset($options["cert_expiration_warning"]) ? $options["cert_expiration_warning"] : false;
                $this->hide_menu_for_subsites = isset($options["hide_menu_for_subsites"]) ? $options["hide_menu_for_subsites"] : false;
                $this->selected_networkwide_or_per_site = isset($options["selected_networkwide_or_per_site"]) ? $options["selected_networkwide_or_per_site"] : false;
            }

            $this->save_options();

            if ($this->ssl_enabled_networkwide && !$prev_ssl_enabled_networkwide) {
                //reset
                $this->start_ssl_activation();
                //enable SSL on all  sites on the network
            }

            if (!$this->ssl_enabled_networkwide && $prev_ssl_enabled_networkwide ) {
                //if we switch to per page, we deactivate SSL on all pages first, but only if the setting was changed.
                $this->start_ssl_deactivation();

            }

            // At last we redirect back to our options page.
            wp_redirect(add_query_arg(array('page' => "really-simple-ssl", 'updated' => 'true'), network_admin_url('settings.php')));
            exit;
        }

        public function sanitize_boolean($value)
        {
            if ($value == true) {
                return true;
            } else {
                return false;
            }
        }


        /**
         * Give the user an option to activate networkwide or not.
         * Needs to be called after detect_configuration function
         *
         * @since  2.3
         *
         * @access public
         *
         */

        public function show_notice_activate_networkwide()
        {
            //prevent showing the review on edit screen, as gutenberg removes the class which makes it editable.
            $screen = get_current_screen();
            if ( $screen->parent_base === 'edit' ) return;

            if (RSSSL()->really_simple_ssl->site_has_ssl) {
                if (is_main_site(get_current_blog_id()) && RSSSL()->really_simple_ssl->wpconfig_ok()) {
                    $class = "updated notice activate-ssl really-simple-plugins";
                    $title = __("Setup", "really-simple-ssl");
                    $content = '<h2>' . __("Some things can't be done automatically. Before you migrate, please check for: ", "really-simple-ssl") . '</h2>';
                    $content .= '<ul>
                                    <li>'. __("Http references in your .css and .js files: change any http:// into //", "really-simple-ssl") .'</li>
                                    <li>'. __("Images, stylesheets or scripts from a domain without an SSL certificate: remove them or move to your own server.", "really-simple-ssl") .'</li>
                                </ul>';
                    $content .= __('You can also let the automatic scan of the pro version handle this for you, and get premium support and increased security with HSTS included.', 'really-simple-ssl') . " "
                        . '<a target="_blank"
                         href="https://www.really-simple-ssl.com/pro-multisite">' . __("Check out Really Simple SSL Premium", "really-simple-ssl") . '</a>' . "<br>";

                    $footer = '<form action="" method="post">
                                '. wp_nonce_field('rsssl_nonce', 'rsssl_nonce').'
                                <input type="submit" class="button button-primary"
                                       value="'. __("Activate SSL networkwide", "really-simple-ssl").'"
                                       id="rsssl_do_activate_ssl_networkwide" name="rsssl_do_activate_ssl_networkwide">
                                <input type="submit" class="button button-primary"
                                       value="'. __("Activate SSL per site", "really-simple-ssl").'"
                                       id="rsssl_do_activate_ssl_per_site" name="rsssl_do_activate_ssl_per_site">
                            </form>';
                    $content .= __("Networkwide activation does not check if a site has an SSL certificate. It just migrates all sites to SSL.", "really-simple-ssl");
	                echo RSSSL()->really_simple_ssl->notice_html($class, $title, $content, $footer);
                }
            }

        }

        /**
         * @since 2.3
         * Shows option to buy pro
         */

        public function show_pro()
        {
            ?>
            <p><?php _e('You can also let the automatic scan of the pro version handle this for you, and get premium support and increased security with HSTS included.', 'really-simple-ssl') ?>
                &nbsp;<a target="_blank"
                         href="https://www.really-simple-ssl.com/pro-multisite"><?php _e("Check out Really Simple SSL Premium", "really-simple-ssl"); ?></a>
            </p>
            <?php
        }


        /*

            Check if the plugin is network activated.

        */


        public function plugin_network_wide_active()
        {
            if (!function_exists('is_plugin_active_for_network'))
                require_once(ABSPATH . '/wp-admin/includes/plugin.php');

            if (is_plugin_active_for_network(rsssl_plugin)) {
                return true;
            } else {
                return false;
            }
        }


        public function process_networkwide_choice()
        {

            if (!$this->plugin_network_wide_active()) return;

            if (isset($_POST['rsssl_do_activate_ssl_networkwide'])) {

                $this->selected_networkwide_or_per_site = true;
                $this->ssl_enabled_networkwide = true;
                $this->wp_redirect = true;
                $this->save_options();

                //enable SSL on all sites on the network
                $this->start_ssl_activation();

            }

            if (isset($_POST['rsssl_do_activate_ssl_per_site'])) {

                $this->selected_networkwide_or_per_site = true;
                $this->ssl_enabled_networkwide = false;
                $this->save_options();
            }

        }


        public function save_options()
        {
            $options = get_site_option("rlrsssl_network_options");
            if (!is_array($options)) $options = array();

            $options["selected_networkwide_or_per_site"] = $this->selected_networkwide_or_per_site;
            $options["ssl_enabled_networkwide"] = $this->ssl_enabled_networkwide;
            $options["wp_redirect"] = $this->wp_redirect;
            $options["htaccess_redirect"] = $this->htaccess_redirect;
            $options["do_not_edit_htaccess"] = $this->do_not_edit_htaccess;
            $options["autoreplace_mixed_content"] = $this->autoreplace_mixed_content;
            $options["javascript_redirect"] = $this->javascript_redirect;
            $options["hsts"] = $this->hsts;
            $options["mixed_content_admin"] = $this->mixed_content_admin;
            $options["cert_expiration_warning"] = $this->cert_expiration_warning;
            $options["hide_menu_for_subsites"] = $this->hide_menu_for_subsites;

            update_site_option("rlrsssl_network_options", $options);
        }


        public function ssl_process_active(){

            if (get_site_option('rsssl_ssl_activation_active')){
                return true;
            }

            if ( get_site_option('rsssl_ssl_deactivation_active')){
                return true;
            }

            return false;
        }

        public function run_ssl_process(){
            // if (!get_site_option('rsssl_run')) return;

            if (get_site_option('rsssl_ssl_activation_active')){
                $this->activate_ssl_networkwide();
            }

            if (get_site_option('rsssl_ssl_deactivation_active')){
                $this->deactivate_ssl_networkwide();
            }

            update_site_option('rsssl_run', false);

        }

        public function redirect_to_network_settings_page_after_activation() {
            $url = add_query_arg( array(
                "page" => "really-simple-ssl",
            ), network_admin_url( "settings.php" ) );
            wp_safe_redirect( $url );
            exit;
        }

        public function get_process_completed_percentage(){
            $complete_count = get_site_option('rsssl_siteprocessing_progress');

            $percentage = round(($complete_count/$this->get_total_blog_count())*100,0);
            if ($percentage > 99) $percentage = 99;
            return $percentage;
        }

        public function start_ssl_activation(){
            update_site_option('rsssl_siteprocessing_progress', 0);
            update_site_option('rsssl_ssl_activation_active', true);
        }

        public function end_ssl_activation(){
            update_site_option('rsssl_ssl_activation_active', false);
            update_site_option('run_ssl_process_hook_switched', false);
        }

        public function start_ssl_deactivation(){
            update_site_option('rsssl_siteprocessing_progress', 0);
            update_site_option('rsssl_ssl_deactivation_active', true);
        }

        public function end_ssl_deactivation(){
            update_site_option('rsssl_ssl_deactivation_active', false);
            update_site_option('run_ssl_process_hook_switched', false);
        }

        public function deactivate_ssl_networkwide(){
            //run chunked
            $nr_of_sites = 200;
            $current_offset = get_site_option('rsssl_siteprocessing_progress');

            //set batch of sites
            $sites = $this->get_sites_bw_compatible($current_offset, $nr_of_sites);

            //if no sites are found, we assume we're done.
            if (count($sites)==0) {
                $this->end_ssl_deactivation();
            } else {
                foreach ($sites as $site) {
                    $this->switch_to_blog_bw_compatible($site);
                    RSSSL()->really_simple_ssl->deactivate_ssl();
                    restore_current_blog(); //switches back to previous blog, not current, so we have to do it each loop
                    update_site_option('rsssl_siteprocessing_progress', $current_offset+$nr_of_sites);
                }
            }

        }


        public function activate_ssl_networkwide()
        {

            //run chunked
            $nr_of_sites = 200;
            $current_offset = get_site_option('rsssl_siteprocessing_progress');

            //set batch of sites
            $sites = $this->get_sites_bw_compatible($current_offset, $nr_of_sites);

            //if no sites are found, we assume we're done.
            if (count($sites)==0) {
                $this->end_ssl_activation();
            } else {
                foreach ($sites as $site) {
                    $this->switch_to_blog_bw_compatible($site);
                    RSSSL()->really_simple_ssl->activate_ssl();
                    restore_current_blog(); //switches back to previous blog, not current, so we have to do it each loop
                    update_site_option('rsssl_siteprocessing_progress', $current_offset+$nr_of_sites);
                }
            }
            $this->redirect_to_network_settings_page_after_activation();
        }


        //change deprecated function depending on version.
        /*
         * Offset is used to chunk the site loops.
         * But offset is not used in the pre 4.6 function.
         *
         *
         * */
        public function get_sites_bw_compatible($offset=0, $nr_of_sites=100)
        {
            global $wp_version;

            $args = array(
                'number' => $nr_of_sites,
                'offset' => $offset,
                'public' => 1,
            );
            $sites = ($wp_version >= 4.6) ? get_sites($args) : wp_get_sites();
            return $sites;
        }

        /*
              The new get_sites function returns an object.

        */

        public function switch_to_blog_bw_compatible($site)
        {
            global $wp_version;
            if ($wp_version >= 4.6) {
                switch_to_blog($site->blog_id);
            } else {
                switch_to_blog($site['blog_id']);
            }
        }

        public function deactivate()
        {
            $options = get_site_option("rlrsssl_network_options");
            $options["selected_networkwide_or_per_site"] = false;
            $options["wp_redirect"] = false;
            $options["htaccess_redirect"] = false;
            $options["do_not_edit_htaccess"] = false;
            $options["autoreplace_mixed_content"] = false;
            $options["javascript_redirect"] = false;
            $options["hsts"] = false;
            $options["mixed_content_admin"] = false;
            $options["cert_expiration_warning"] = false;
            $options["hide_menu_for_subsites"] = false;

            unset($options["ssl_enabled_networkwide"]);
            update_site_option("rlrsssl_network_options", $options);

            //because the deactivation should be a one click procedure, chunking this would cause dificulties
            $sites = $this->get_sites_bw_compatible(0, $this->get_total_blog_count());
            foreach ($sites as $site) {
                $this->switch_to_blog_bw_compatible($site);
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
            if (!$blog_id) $blog_id = get_current_blog_id();

            if (get_current_blog_id() == $blog_id) return $url;

            if (!$this->ssl_enabled_networkwide) {
                $home_url = get_blog_option($blog_id, 'home');
                if (strpos($home_url, "https://") === false) {
                    $url = str_replace("https://", "http://", $url);
                }
            }
            return $url;
        }


        /*
         * Checks if we are on a subfolder install. (domain.com/site1 )
         *
         * @since  2.2
         *
         * @access public
         *
         **/

        public function is_multisite_subfolder_install()
        {
            if (!is_multisite()) return FALSE;
            //we check this manually, as the SUBDOMAIN_INSTALL constant of wordpress might return false for domain mapping configs
            $is_subfolder = FALSE;
            $sites = $this->get_sites_bw_compatible(0, 10);
            foreach ($sites as $site) {
                $this->switch_to_blog_bw_compatible($site);
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
         *
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
            if ( $screen->parent_base === 'edit' ) return;

	        if ( !$this->is_settings_page() ) {
		        $notices = RSSSL()->really_simple_ssl->get_notices_list( array('admin_notices'=>true) );
		        foreach ( $notices as $id => $notice ){
			        $notice = $notice['output'];
			        $class = ( $notice['status'] !== 'completed' ) ? 'error' : 'updated';
			        echo RSSSL()->really_simple_ssl->notice_html( $class.' '.$id, $notice['title'], $notice['msg'] );
		        }
            }

            /*
             * ssl switch for sites processing active
             */

            if ($this->ssl_process_active()) {
                $class = "error notice is-dismissible rlrsssl-fail";
                $title = "Website conversion";

                //In some cases the rsssl_ssl_process_hook hook can fail. Therefore we offer the option to switch the hook to admin_init when the conversion is stuck.
                $token = wp_create_nonce('run_ssl_to_admin_init');
                $run_ssl_process_hook_switch_link = network_admin_url("settings.php?page=really-simple-ssl&action=ssl_conversion_hook_switch&token=" . $token);

                $link_open = '<a target="_self" href="' . $run_ssl_process_hook_switch_link . '">';
                $link_close = '</a>';

                $content = sprintf(__("Conversion of websites %s percent complete.", "really-simple-ssl"), $this->get_process_completed_percentage()) . " ";
                $content .= __("You have just started enabling or disabling SSL on multiple websites at once, and this process is not completed yet. Please refresh this page to check if the process has finished. It will proceed in the background.", "really-simple-ssl") . " ";
                $content .= sprintf(__("If the conversion does not proceed after a few minutes, click %shere%s to force the conversion process.", "really-simple-ssl"), $link_open, $link_close);

                echo RSSSL()->really_simple_ssl->notice_html($class, $title, $content);

            }

            /*
                SSL success message
            */

            if ($this->selected_networkwide_or_per_site && !get_site_option("rsssl_success_message_shown")) {

                $class = "updated notice is-dismissible rlrsssl-multisite-success";
                $title = __("SSL activated!", "really-simple-ssl");

                if ($this->ssl_enabled_networkwide)
                    $content = __("SSL was activated on your entire network.", "really-simple-ssl");
                else
                    $content = __("SSL was activated per site.", "really-simple-ssl") . " ";

                $content .= __("Don't forget to change your settings in Google Analytics and Webmaster tools.", "really-simple-ssl") . " ";
                $content .= '<a target="_blank"
                   href="https://really-simple-ssl.com/knowledge-base/how-to-setup-google-analytics-and-google-search-consolewebmaster-tools/">' . __("More info.", "really-simple-ssl") . '</a>';

                echo RSSSL()->really_simple_ssl->notice_html($class, $title, $content);
            }

            if (!$this->ssl_enabled_networkwide && $this->selected_networkwide_or_per_site && $this->is_multisite_subfolder_install()) {
                //with no server variables, the website could get into a redirect loop.
                if (RSSSL()->really_simple_ssl->no_server_variable) {
                    $class = "error notice";
                    $title = __("Warning!", "really-simple-ssl");

                    $content =  __('You run a Multisite installation with subfolders, which prevents this plugin from fixing your missing server variable in the wp-config.php.', 'really-simple-ssl') . " ";
                    $content .=  __('Because the $_SERVER["HTTPS"] variable is not set, your website may experience redirect loops.', 'really-simple-ssl') . " ";
                    $content .= __('Activate networkwide to fix this.', 'really-simple-ssl');

                    echo RSSSL()->really_simple_ssl->notice_html($class, $title, $content);
                }
            }

            if (!RSSSL()->really_simple_ssl->ssl_enabled && !$this->is_multisite_subfolder_install() && !RSSSL()->rsssl_certificate->is_wildcard() && !get_site_option("rsssl_wildcard_message_shown")) {
                $class = "error notice is-dismissible rlrsssl-multisite-wildcard-warning";
                $title = __("Multisite installation with subdomains", "really-simple-ssl");
                $content = __("You run a Multisite installation with subdomains, but your site doesn't have a wildcard certificate.", 'really-simple-ssl') . " ";
                $content .= __("This leads to issues when activating SSL networkwide since subdomains will be forced over SSL as well while they don't have a valid certificate.", 'really-simple-ssl') . " ";
                $content .= __("Activate SSL per site or install a wildcard certificate to fix this.", 'really-simple-ssl');
                echo RSSSL()->really_simple_ssl->notice_html($class, $title, $content);
            }
        }


        /**
         * Insert some ajax script to dismiss the SSL success message, and stop nagging about it
         *
         * @since  2.0
         *
         * @access public
         *
         */

        public function insert_dismiss_success()
        {
            if ($this->selected_networkwide_or_per_site && !get_site_option("rsssl_success_message_shown")) {
                $ajax_nonce = wp_create_nonce("really-simple-ssl-dismiss");
                ?>
                <script type='text/javascript'>
                    jQuery(document).ready(function ($) {
                        $(".rlrsssl-multisite-success.notice.is-dismissible").on("click", ".notice-dismiss", function (event) {

                            var data = {
                                'action': 'dismiss_success_message_multisite',
                                'security': '<?php echo $ajax_nonce; ?>'
                            };

                            $.post(ajaxurl, data, function (response) {

                            });
                        });
                    });
                </script>
                <?php
            }
        }

        public function insert_dismiss_wildcard_warning()
        {
            if ($this->selected_networkwide_or_per_site && !get_site_option("rsssl_success_message_shown")) {
                $ajax_nonce = wp_create_nonce("really-simple-ssl-dismiss");
                ?>
                <script type='text/javascript'>
                    jQuery(document).ready(function ($) {
                        $(".rlrsssl-multisite-wildcard-warning.notice.is-dismissible").on("click", ".notice-dismiss", function (event) {

                            var data = {
                                'action': 'dismiss_wildcard_warning',
                                'security': '<?php echo $ajax_nonce; ?>'
                            };

                            $.post(ajaxurl, data, function (response) {

                            });
                        });
                    });
                </script>
                <?php
            }
        }


        /**
         * Process the ajax dismissal of the success message.
         *
         * @since  2.0
         *
         * @access public
         *
         */

        public function dismiss_success_message_callback()
        {
            check_ajax_referer('really-simple-ssl-dismiss', 'security');
            update_site_option("rsssl_success_message_shown", true);
            wp_die();
        }

        public function dismiss_wildcard_message_callback()
        {
            check_ajax_referer('really-simple-ssl-dismiss', 'security');
            update_site_option("rsssl_wildcard_message_shown", true);
            wp_die();
        }

        public function is_settings_page()
        {
            return (isset($_GET['page']) && $_GET['page'] == 'really-simple-ssl') ? true : false;
        }

        /**
         * Create tabs on the settings page
         *
         * @since  1.0.0
         *
         * @access public
         *
         */

        public function admin_tabs($current = 'settings')
        {
            $tabs = array(
                'settings' => __("Settings", "really-simple-ssl"),
            );

            $tabs = apply_filters("rsssl_network_tabs", $tabs);

            if (count($tabs) > 1) {
                echo '<h2 class="nav-tab-wrapper">';

                foreach ($tabs as $tab => $name) {
                    $class = ($tab == $current) ? ' nav-tab-active' : '';
                    echo "<a class='nav-tab$class' href='?page=really-simple-ssl&tab=$tab'>$name</a>";
                }
                echo '</h2>';
            }
        }

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