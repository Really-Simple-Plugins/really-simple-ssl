<?php
defined('ABSPATH') or die("you do not have access to this page!");

class rsssl_admin extends rsssl_front_end
{

    private static $_this;

    public $wpconfig_siteurl_not_fixed = FALSE;
    public $no_server_variable = FALSE;
    public $errors = Array();

    public $do_wpconfig_loadbalancer_fix = FALSE;
    public $site_has_ssl = FALSE;
    public $ssl_enabled = FALSE;

    //multisite variables
    public $sites = Array(); //for multisite, list of all activated sites.

    //general settings
    public $capability = 'activate_plugins';

    public $ssl_test_page_error;
    public $htaccess_test_success = FALSE;
    public $plugin_version = rsssl_version; //deprecated, but used in pro plugin until 1.0.25

    public $plugin_dir = "really-simple-ssl";
    public $plugin_filename = "rlrsssl-really-simple-ssl.php";
    public $ABSpath;

    public $do_not_edit_htaccess = FALSE;
    public $javascript_redirect = FALSE;
    public $htaccess_redirect = FALSE;
    public $htaccess_warning_shown = FALSE;
    public $review_notice_shown = FALSE;
    public $ssl_success_message_shown = FALSE;
    public $hsts = FALSE;
    public $debug = TRUE;
    public $debug_log;

    public $plugin_conflict = ARRAY();
    public $plugin_db_version;
    public $plugin_upgraded;
    public $mixed_content_fixer_status = "OK";
    public $ssl_type = "NA";

    private $pro_url = "https://www.really-simple-ssl.com/pro";

    function __construct()
    {
        if (isset(self::$_this))
            wp_die(sprintf(__('%s is a singleton class and you cannot create a second instance.', 'really-simple-ssl'), get_class($this)));

        self::$_this = $this;

        $this->ABSpath = $this->getABSPATH();
        $this->get_options();
        $this->get_admin_options();

        $this->get_plugin_upgraded(); //call always, otherwise db version will not match anymore.

        register_deactivation_hook(dirname(__FILE__) . "/" . $this->plugin_filename, array($this, 'deactivate'));

        add_action('admin_init', array($this, 'add_privacy_info'));

    }

    static function this()
    {
        return self::$_this;
    }

    public function add_privacy_info()
    {
        if (!function_exists('wp_add_privacy_policy_content')) {
            return;
        }

        $content = sprintf(
            __('Really Simple SSL and Really Simple SSL add-ons do not process any personal identifiable information, so the GDPR does not apply to these plugins or usage of these plugins on your website. You can find our privacy policy <a href="%s" target="_blank">here</a>.', 'really-simple-ssl'),
            'https://really-simple-ssl.com/privacy-statement/'
        );

        wp_add_privacy_policy_content(
            'Really Simple SSL',
            wp_kses_post(wpautop($content, false))
        );
    }


    /**
     * Initializes the admin class
     *
     * @since  2.2
     *
     * @access public
     *
     */

    public function init()
    {
        if (!current_user_can($this->capability)) return;
        $is_on_settings_page = $this->is_settings_page();

        if (defined("RSSSL_FORCE_ACTIVATE") && RSSSL_FORCE_ACTIVATE) {
            $options = get_option('rlrsssl_options');
            $options['ssl_enabled'] = true;
            update_option('rlrsssl_options', $options);
        }

        /*
         * check if we're one minute past the activation. Then flush rewrite rules
         * this way we lower the memory impact on activation
         * Flush should happen on shutdown, not on init, as often happens in other plugins
         * https://codex.wordpress.org/Function_Reference/flush_rewrite_rules
         * */

        $activation_time = get_option('rsssl_flush_rewrite_rules');
        $more_than_one_minute_ago = $activation_time < strtotime("-1 minute");
        $less_than_5_minutes_ago = $activation_time > strtotime("-5 minute");
        if (get_option('rsssl_flush_rewrite_rules') && $more_than_one_minute_ago && $less_than_5_minutes_ago){
            delete_option('rsssl_flush_rewrite_rules');
            add_action('shutdown', 'flush_rewrite_rules');
        }

        /*
            Detect configuration when:
            - SSL activation just confirmed.
            - on settings page
            - No SSL detected
            */

        //when configuration should run again
        if ($this->clicked_activate_ssl() || !$this->ssl_enabled || !$this->site_has_ssl || $is_on_settings_page || is_network_admin()) {

            if (is_multisite()) $this->build_domain_list();//has to come after clicked_activate_ssl, otherwise this domain won't get counted.
            $this->detect_configuration();

            //flush caches when just activated ssl
            //flush the permalinks
            if ($this->clicked_activate_ssl()) {
                if (!defined('RSSSL_NO_FLUSH') || !RSSSL_NO_FLUSH) {
                    update_option('rsssl_flush_rewrite_rules', time());
                }
                add_action('admin_init', array(RSSSL()->rsssl_cache, 'flush'), 40);
            }

            if (!$this->wpconfig_ok()) {
                //if we were to activate ssl, this could result in a redirect loop. So warn first.
                add_action("admin_notices", array($this, 'show_notice_wpconfig_needs_fixes'));
                if (is_multisite()) add_action('network_admin_notices', array($this, 'show_notice_wpconfig_needs_fixes'), 10);
                $this->ssl_enabled = false;
                $this->save_options();
            } elseif ($this->ssl_enabled) {
                add_action('init', array($this, 'configure_ssl'), 20);
            }
        }

        //when SSL is enabled, and not enabled by user, ask for activation.
        add_action("admin_notices", array($this, 'show_notice_activate_ssl'), 10);
        add_action('rsssl_activation_notice', array($this, 'no_ssl_detected'), 10);
        add_action('rsssl_activation_notice', array($this, 'ssl_detected'), 10);
        add_action('rsssl_activation_notice_inner', array($this, 'almost_ready_to_migrate'), 30);
        add_action('rsssl_activation_notice_inner', array($this, 'show_pro'), 40);
        add_action('rsssl_activation_notice_inner', array($this, 'show_enable_ssl_button'), 50);

        add_action('plugins_loaded', array($this, 'check_plugin_conflicts'), 30);

        //add the settings page for the plugin
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_init', array($this, 'load_translation'), 20);
        add_action('rsssl_configuration_page', array($this, 'configuration_page_more'), 10);

        //settings page, form  and settings link in the plugins page
        add_action('admin_menu', array($this, 'add_settings_page'), 40);
        add_action('admin_init', array($this, 'create_form'), 40);
        add_action('admin_init', array($this, 'listen_for_deactivation'), 40);

        $plugin = rsssl_plugin;
        add_filter("plugin_action_links_$plugin", array($this, 'plugin_settings_link'));

        //check if the uninstallfile is safely renamed to php.
        $this->check_for_uninstall_file();

        //callbacks for the ajax dismiss buttons
        add_action('wp_ajax_dismiss_htaccess_warning', array($this, 'dismiss_htaccess_warning_callback'));
        add_action('wp_ajax_dismiss_success_message', array($this, 'dismiss_success_message_callback'));
        add_action('wp_ajax_dismiss_review_notice', array($this, 'dismiss_review_notice_callback'));

        //handle notices
        add_action('admin_notices', array($this, 'show_notices'));
        //show review notice, only to free users
        if (!defined("rsssl_pro_version") && (!defined("rsssl_pp_version")) && (!defined("rsssl_soc_version")) && (!class_exists('RSSSL_PRO')) && (!is_multisite())) {
            add_action('admin_notices', array($this, 'show_leave_review_notice'));
        }
        add_action("update_option_rlrsssl_options", array($this, "update_htaccess_after_settings_save"), 20, 3);
    }

    /*
     * Deactivate the plugin while keeping SSL
     * Activated when the 'uninstall_keep_ssl' button is clicked in the settings tab
     *
     */

    public function listen_for_deactivation()
    {

        //check if we are on ssl settings page
        if (!$this->is_settings_page()) return;
        //check user role
        if (!current_user_can($this->capability)) return;

        //check nonce
        if (!isset($_GET['token']) || (!wp_verify_nonce($_GET['token'], 'rsssl_deactivate_plugin'))) return;
        //check for action
        if (isset($_GET["action"]) && $_GET["action"] == 'uninstall_keep_ssl') {
            //deactivate plugin, but don't revert to http.
            $plugin = $this->plugin_dir . "/" . $this->plugin_filename;
            $plugin = plugin_basename(trim($plugin));

            if (is_multisite()) {

                $network_current = get_site_option('active_sitewide_plugins', array());
                if (is_plugin_active_for_network($plugin)) {
                    unset($network_current[$plugin]);
                }
                update_site_option('active_sitewide_plugins', $network_current);

                //remove plugin one by one on each site
                $sites = get_sites();
                foreach ($sites as $site) {
                    switch_to_blog($site['blog_id']);

                    $current = get_option('active_plugins', array());
                    $current = $this->remove_plugin_from_array($plugin, $current);
                    update_option('active_plugins', $current);

                    restore_current_blog(); //switches back to previous blog, not current, so we have to do it each loop
                }

            } else {

                $current = get_option('active_plugins', array());
                $current = $this->remove_plugin_from_array($plugin, $current);
                update_option('active_plugins', $current);
            }
            wp_redirect(admin_url('plugins.php'));
            exit;
        }
    }


    /*
     * Remove the plugin from the active plugins array when called from listen_for_deactivation
     *
     * */


    public function remove_plugin_from_array($plugin, $current)
    {
        $key = array_search($plugin, $current);
        if (false !== $key) {
            unset($current[$key]);
        }
        return $current;
    }

    /*
     * @Since 3.1
     *
     * Check if site uses an htaccess.conf file, used in bitnami installations
     *
     */

    public function uses_htaccess_conf() {
        $htaccess_conf_file = dirname(ABSPATH) . "/conf/htaccess.conf";

        if (is_file($htaccess_conf_file)) {
            return true;
        } else {
            return false;
        }
    }

    public function get_sites_bw_compatible()
    {
        global $wp_version;
        $sites = ($wp_version >= 4.6) ? get_sites() : wp_get_sites();
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


    /*
    checks if the user just clicked the "activate SSL" button.
  */

    private function clicked_activate_ssl()
    {
        if (!current_user_can($this->capability)) return;
        //if (!isset( $_POST['rsssl_nonce'] ) || !wp_verify_nonce( $_POST['rsssl_nonce'], 'rsssl_nonce' )) return false;

        if (isset($_POST['rsssl_do_activate_ssl'])) {
            $this->activate_ssl();

            //if (empty(get_option('rsssl_activation_timestamp'))) {
                update_option('rsssl_activation_timestamp', time());
            //}

            return true;
        }

        return false;
    }


    /*
        Activate the SSL for this site
     */

    public function activate_ssl()
    {
        $this->ssl_enabled = true;
        $this->wp_redirect = true;

        $this->set_siteurl_to_ssl();
        $this->save_options();
    }


    public function deactivate_ssl()
    {
        $this->ssl_enabled = false;
        $this->wp_redirect = false;
        $this->htaccess_redirect = false;

        $this->remove_ssl_from_siteurl();
        $this->save_options();
    }


    public function wpconfig_ok()
    {
        if (($this->do_wpconfig_loadbalancer_fix || $this->no_server_variable || $this->wpconfig_siteurl_not_fixed) && !$this->wpconfig_is_writable()) {
            $result = false;
        } else {
            $result = true;
        }

        return apply_filters('rsssl_wpconfig_ok_check', $result);
    }

    /*
      This message is shown when no SSL is not enabled by the user yet
  */


    public function show_notice_activate_ssl()
    {
        //prevent showing the review on edit screen, as gutenberg removes the class which makes it editable.
        $screen = get_current_screen();
        if ( $screen->parent_base === 'edit' ) return;

        if ($this->ssl_enabled) return;

        if (defined("RSSSL_DISMISS_ACTIVATE_SSL_NOTICE") && RSSSL_DISMISS_ACTIVATE_SSL_NOTICE) return;

        //for multisite, show only activate when a choice has been made to activate networkwide or per site.
        if (is_multisite() && !RSSSL()->rsssl_multisite->selected_networkwide_or_per_site) return;

        //on multisite, only show this message on the network admin. Per site activated sites have to go to the settings page.
        //otherwise sites that do not need SSL possibly get to see this message.

        if (is_multisite() && !is_network_admin()) return;

        if (!$this->wpconfig_ok()) return;

        if (!current_user_can($this->capability)) return;

        do_action('rsssl_activation_notice');

    }

    public function ssl_detected()
    {
        if ($this->site_has_ssl) { ?>
            <div id="message" class="updated notice activate-ssl">
                <?php
                  do_action('rsssl_activation_notice_inner');
                ?>
            </div>
            <?php
        }
    }

    public function no_ssl_detected()
    {
        if (!$this->site_has_ssl) { ?>
            <div id="message" class="error notice rsssl-notice-certificate">
                <h1><?php echo __("Detected possible certificate issues", "really-simple-ssl"); ?></h1>
                <p>
                    <?php
                    $reload_https_url = "https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
                    $link_open = '<p><a class="button" target="_blank" href="' . $reload_https_url . '">';
                    $link_close = '</a></p>';

                    printf(__("Really Simple SSL failed to detect a valid SSL certificate. If you do have an SSL certificate, try to reload this page over https by clicking this button: %sReload over https%s The built-in certificate check will run once daily, to force a new certificate check visit the SSL settings page. ", "really-simple-ssl"), $link_open, $link_close);

                    $ssl_test_url = "https://www.ssllabs.com/ssltest/";
                    $link_open = '<a target="_blank" href="' . $ssl_test_url . '">';
                    $link_close = '</a>';

                    printf(__("Really Simple SSL requires a valid SSL certificate. You can check your certificate on %sQualys SSL Labs%s.", "really-simple-ssl"), $link_open, $link_close);
                    ?>
                </p>
            </div>
        <?php }
    }


    public function almost_ready_to_migrate()
    { ?>
            <h1><?php _e("Almost ready to migrate to SSL!", "really-simple-ssl"); ?></h1>

            <?php //action?>


            <?php _e("Some things can't be done automatically. Before you migrate, please check for: ", 'really-simple-ssl'); ?>
            <p>
            <ul>
                <li><?php _e('Http references in your .css and .js files: change any http:// into //', 'really-simple-ssl'); ?></li>
                <li><?php _e('Images, stylesheets or scripts from a domain without an SSL certificate: remove them or move to your own server.', 'really-simple-ssl'); ?></li><?php

                $backup_link = "https://really-simple-ssl.com/knowledge-base/backing-up-your-site/";
                $link_open = '<a target="_blank" href="' . $backup_link . '">';
                $link_close = '</a>';

                ?>
                <li> <?php printf(__("It is recommended to take a %sbackup%s of your site before activating SSL", 'really-simple-ssl'), $link_open, $link_close); ?> </li>
            </ul>
            </p>
            <?php
    }

    /**
     * @since 2.3
     * Returns button to enable SSL.
     */

    public function show_enable_ssl_button()
    {
        if ($this->site_has_ssl || (defined('rsssl_force_activate') && rsssl_force_activate)) {
            ?>
            <p>
            <div class="rsssl-activate-ssl-button">
            <form action="" method="post">
                <?php wp_nonce_field('rsssl_nonce', 'rsssl_nonce'); ?>
                <input type="submit" class='button button-primary'
                       value="<?php _e("Go ahead, activate SSL!", "really-simple-ssl"); ?>" id="rsssl_do_activate_ssl"
                       name="rsssl_do_activate_ssl">
                <br><?php _e("You may need to login in again.", "really-simple-ssl") ?>
            </form>
            </div>
            </p>
            <?php
        }
    }

    /**
     * @since 2.3
     * Shows option to buy pro
     */

    public function show_pro()
    {
        if ($this->site_has_ssl) {
            ?>
            <p><?php _e('You can also let the automatic scan of the pro version handle this for you, and get premium support, increased security with HSTS and more!', 'really-simple-ssl'); ?>
                &nbsp;<a target="_blank"
                         href="<?php echo $this->pro_url; ?>"><?php _e("Check out Really Simple SSL Premium", "really-simple-ssl"); ?></a>
            </p>
            <?php
        }
    }


    public function wpconfig_is_writable()
    {
        $wpconfig_path = $this->find_wp_config_path();
        if (is_writable($wpconfig_path))
            return true;
        else
            return false;
    }

    /*
  *     Check if the uninstall file is renamed to .php
  */

    protected function check_for_uninstall_file()
    {
        if (file_exists(dirname(__FILE__) . '/force-deactivate.php')) {
            $this->errors["DEACTIVATE_FILE_NOT_RENAMED"] = true;
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

    public function get_admin_options()
    {

        $options = get_option('rlrsssl_options');
        if (isset($options)) {
            $this->site_has_ssl = isset($options['site_has_ssl']) ? $options['site_has_ssl'] : FALSE;
            $this->hsts = isset($options['hsts']) ? $options['hsts'] : FALSE;
            $this->htaccess_warning_shown = isset($options['htaccess_warning_shown']) ? $options['htaccess_warning_shown'] : FALSE;
            $this->review_notice_shown = isset($options['review_notice_shown']) ? $options['review_notice_shown'] : FALSE;
            $this->ssl_success_message_shown = isset($options['ssl_success_message_shown']) ? $options['ssl_success_message_shown'] : FALSE;
            $this->plugin_db_version = isset($options['plugin_db_version']) ? $options['plugin_db_version'] : "1.0";
            $this->debug = isset($options['debug']) ? $options['debug'] : FALSE;
            $this->do_not_edit_htaccess = isset($options['do_not_edit_htaccess']) ? $options['do_not_edit_htaccess'] : FALSE;
            $this->htaccess_redirect = isset($options['htaccess_redirect']) ? $options['htaccess_redirect'] : FALSE;
            $this->switch_mixed_content_fixer_hook = isset($options['switch_mixed_content_fixer_hook']) ? $options['switch_mixed_content_fixer_hook'] : FALSE;
            $this->debug_log = isset($options['debug_log']) ? $options['debug_log'] : $this->debug_log;
        }

        if (is_multisite()) {
            $network_options = get_site_option('rlrsssl_network_options');
            $network_htaccess_redirect = isset($network_options["htaccess_redirect"]) ? $network_options["htaccess_redirect"] : false;
            $network_do_not_edit_htaccess = isset($network_options["do_not_edit_htaccess"]) ? $network_options["do_not_edit_htaccess"] : false;
            /*
          If multiste, and networkwide, only the networkwide setting counts.
          if multisite, and per site, only the networkwide setting counts if it is true.
      */
            $ssl_enabled_networkwide = isset($network_options["ssl_enabled_networkwide"]) ? $network_options["ssl_enabled_networkwide"] : false;
            if ($ssl_enabled_networkwide) {
                $this->htaccess_redirect = $network_htaccess_redirect;
                $this->do_not_edit_htaccess = $network_do_not_edit_htaccess;
            } else {
                if ($network_do_not_edit_htaccess) $this->do_not_edit_htaccess = $network_do_not_edit_htaccess;
                if ($network_htaccess_redirect) $this->htaccess_redirect = $network_htaccess_redirect;
            }
        }

        //if the define is true, it overrides the db setting.
        if (defined('RLRSSSL_DO_NOT_EDIT_HTACCESS')) {
            $this->do_not_edit_htaccess = RLRSSSL_DO_NOT_EDIT_HTACCESS;
        }

    }

    /**
     * Creates an array of all domains where the plugin is active AND SSL is active, only used for multisite.
     *
     * @since  2.1
     *
     * @access public
     *
     */

    public function build_domain_list()
    {
        if (!is_multisite()) return;

        $this->sites = get_transient('rsssl_domain_list');
        if (!$this->sites) {

            //create list of all activated sites with SSL
            $this->sites = array();
            $nr_of_sites = RSSSL()->rsssl_multisite->get_total_blog_count();
            $sites = RSSSL()->rsssl_multisite->get_sites_bw_compatible(0, $nr_of_sites);

            if ($this->debug) $this->trace_log("building domain list for multisite...");
            foreach ($sites as $site) {
                $this->switch_to_blog_bw_compatible($site);
                $options = get_option('rlrsssl_options');

                $ssl_enabled = FALSE;
                if (isset($options)) {
                    $site_has_ssl = isset($options['site_has_ssl']) ? $options['site_has_ssl'] : FALSE;
                    $ssl_enabled = isset($options['ssl_enabled']) ? $options['ssl_enabled'] : $site_has_ssl;
                }

                if (is_plugin_active(rsssl_plugin) && $ssl_enabled) {
                    if ($this->debug) $this->trace_log("adding: " . home_url());
                    $this->sites[] = home_url();
                }
                restore_current_blog(); //switches back to previous blog, not current, so we have to do it each loop
            }

            set_transient('rsssl_domain_list', $this->sites, HOUR_IN_SECONDS);

            $this->save_options();
        }
    }

    /**
     * check if the plugin was upgraded to a new version
     *
     * @since  2.1
     *
     * @access public
     *
     */

    public function get_plugin_upgraded()
    {
        if ($this->plugin_db_version != rsssl_version) {
            $this->plugin_db_version = rsssl_version;
            $this->plugin_upgraded = true;
            $this->save_options();
        }
        $this->plugin_upgraded = false;
    }

    /**
     * Log events during plugin execution
     *
     * @since  2.1
     *
     * @access public
     *
     */

    public function trace_log($msg)
    {
        if (!$this->debug) return;
        $this->debug_log = $this->debug_log . "<br>" . $msg;
        $this->debug_log = strstr($this->debug_log, '** Detecting configuration **');
        error_log($msg);
    }

    /**
     * Configures the site for SSL
     *
     * @since  2.2
     *
     * @access public
     *
     */

    public function configure_ssl()
    {
        if (!current_user_can($this->capability)) return;

        $safe_mode = FALSE;
        if (defined('RSSSL_SAFE_MODE') && RSSSL_SAFE_MODE) $safe_mode = RSSSL_SAFE_MODE;

        if (!current_user_can($this->capability)) return;
        $this->trace_log("** Configuring SSL **");
        if ($this->site_has_ssl) {
            //when one of the used server variables was found, test if the redirect works

            if (RSSSL()->rsssl_server->uses_htaccess() && $this->ssl_type != "NA") {
                $this->test_htaccess_redirect();
            }

            //in a configuration reverse proxy without a set server variable https, add code to wpconfig
            if ($this->do_wpconfig_loadbalancer_fix) {
                $this->wpconfig_loadbalancer_fix();
            }

            if ($this->no_server_variable)
                $this->wpconfig_server_variable_fix();

            if (!$safe_mode) {
                $this->editHtaccess();
            }

            if (!$safe_mode && $this->clicked_activate_ssl()) {
                $this->wp_redirect = TRUE;
                $this->save_options();
            }

            if (!$safe_mode && $this->wpconfig_siteurl_not_fixed)
                $this->fix_siteurl_defines_in_wpconfig();

            if (!$safe_mode) {
                $this->set_siteurl_to_ssl();
            }

        }
    }


    /**
     * Check to see if we are on the settings page, action hook independent
     *
     * @since  2.1
     *
     * @access public
     *
     */

    public function is_settings_page()
    {
        if (!isset($_SERVER['QUERY_STRING'])) return false;

        parse_str($_SERVER['QUERY_STRING'], $params);
        if (array_key_exists("page", $params) && ($params["page"] == "rlrsssl_really_simple_ssl")) {
            return true;
        }
        return false;
    }

    /**
     * Find the path to wp-config
     *
     * @since  2.1
     *
     * @access public
     *
     */

    public function find_wp_config_path()
    {
        //limit nr of iterations to 20
        $i = 0;
        $maxiterations = 20;
        $dir = dirname(__FILE__);
        do {
            $i++;
            if (file_exists($dir . "/wp-config.php")) {
                return $dir . "/wp-config.php";
            }
        } while (($dir = realpath("$dir/..")) && ($i < $maxiterations));
        return null;
    }

    /**
     * remove https from defined siteurl and homeurl in the wpconfig, if present
     *
     * @since  2.1
     *
     * @access public
     *
     */

    public function remove_ssl_from_siteurl_in_wpconfig()
    {
        $wpconfig_path = $this->find_wp_config_path();
        if (!empty($wpconfig_path)) {
            $wpconfig = file_get_contents($wpconfig_path);

            $homeurl_pos = strpos($wpconfig, "define('WP_HOME','https://");
            $siteurl_pos = strpos($wpconfig, "define('WP_SITEURL','https://");

            if (($homeurl_pos !== false) || ($siteurl_pos !== false)) {
                if (is_writable($wpconfig_path)) {
                    $search_array = array("define('WP_HOME','https://", "define('WP_SITEURL','https://");
                    $ssl_array = array("define('WP_HOME','http://", "define('WP_SITEURL','http://");
                    //now replace these urls
                    $wpconfig = str_replace($search_array, $ssl_array, $wpconfig);
                    file_put_contents($wpconfig_path, $wpconfig);
                } else {
                    $this->errors['wpconfig not writable'] = TRUE;
                }
            }

        }
    }


    /**
     *
     *     Checks if the wp config contains any defined siteurl and homeurl
     *
     *
     */

    private function check_for_siteurl_in_wpconfig()
    {

        $wpconfig_path = $this->find_wp_config_path();

        if (empty($wpconfig_path)) return;

        $wpconfig = file_get_contents($wpconfig_path);
        $homeurl_pattern = '/(define\(\s*\'WP_HOME\'\s*,\s*\'http\:\/\/)/';
        $siteurl_pattern = '/(define\(\s*\'WP_SITEURL\'\s*,\s*\'http\:\/\/)/';

        $this->wpconfig_siteurl_not_fixed = FALSE;
        if (preg_match($homeurl_pattern, $wpconfig) || preg_match($siteurl_pattern, $wpconfig)) {
            $this->wpconfig_siteurl_not_fixed = TRUE;
            $this->trace_log("siteurl or home url defines found in wpconfig");
        }
    }


    /**
     * Runs only when siteurl or homeurl define was found in the wpconfig, with the check_for_siteurl_in_wpconfig function
     * and only when wpconfig is writable.
     *
     * @since  2.1
     *
     * @access public
     *
     */

    private function fix_siteurl_defines_in_wpconfig()
    {
        $wpconfig_path = $this->find_wp_config_path();

        if (empty($wpconfig_path)) return;

        $wpconfig = file_get_contents($wpconfig_path);
        $homeurl_pattern = '/(define\(\s*\'WP_HOME\'\s*,\s*\'http\:\/\/)/';
        $siteurl_pattern = '/(define\(\s*\'WP_SITEURL\'\s*,\s*\'http\:\/\/)/';

        if (preg_match($homeurl_pattern, $wpconfig) || preg_match($siteurl_pattern, $wpconfig)) {
            if (is_writable($wpconfig_path)) {
                $this->trace_log("wp config siteurl/homeurl edited.");
                $wpconfig = preg_replace($homeurl_pattern, "define('WP_HOME','https://", $wpconfig);
                $wpconfig = preg_replace($siteurl_pattern, "define('WP_SITEURL','https://", $wpconfig);
                file_put_contents($wpconfig_path, $wpconfig);
            } else {
                if ($this->debug) {
                    $this->trace_log("not able to fix wpconfig siteurl/homeurl.");
                }
                //only when siteurl or homeurl is defined in wpconfig, and wpconfig is not writable is there a possible issue because we cannot edit the defined urls.
                $this->wpconfig_siteurl_not_fixed = TRUE;
            }
        } else {
            if ($this->debug) {
                $this->trace_log("no siteurl/homeurl defines in wpconfig");
            }
        }
    }


    /**
     * Check if the wpconfig is already fixed
     *
     * @since  2.2
     *
     * @access public
     *
     */

    public function wpconfig_has_fixes()
    {
        $wpconfig_path = $this->find_wp_config_path();
        if (empty($wpconfig_path)) return false;
        $wpconfig = file_get_contents($wpconfig_path);

        //only one of two fixes possible.
        if (strpos($wpconfig, "//Begin Really Simple SSL Load balancing fix") !== FALSE) {
            return true;
        }

        if (strpos($wpconfig, "//Begin Really Simple SSL Server variable fix") !== FALSE) {
            return true;
        }

        return false;
    }


    /**
     * In case of load balancer without server https on, add fix in wp-config
     *
     * @since  2.1
     *
     * @access public
     *
     */


    public function wpconfig_loadbalancer_fix()
    {
        if (!current_user_can($this->capability)) return;

        $wpconfig_path = $this->find_wp_config_path();
        if (empty($wpconfig_path)) return;
        $wpconfig = file_get_contents($wpconfig_path);
        $this->wpconfig_loadbalancer_fix_failed = FALSE;
        //only if loadbalancer AND NOT SERVER-HTTPS-ON should the following be added. (is_ssl = false)
        if (strpos($wpconfig, "//Begin Really Simple SSL Load balancing fix") === FALSE) {
            if (is_writable($wpconfig_path)) {
                $rule = "\n" . "//Begin Really Simple SSL Load balancing fix" . "\n";
                $rule .= 'if ((isset($_ENV["HTTPS"]) && ("on" == $_ENV["HTTPS"]))' . "\n";
                $rule .= '|| (isset($_SERVER["HTTP_X_FORWARDED_SSL"]) && (strpos($_SERVER["HTTP_X_FORWARDED_SSL"], "1") !== false))' . "\n";
                $rule .= '|| (isset($_SERVER["HTTP_X_FORWARDED_SSL"]) && (strpos($_SERVER["HTTP_X_FORWARDED_SSL"], "on") !== false))' . "\n";
                $rule .= '|| (isset($_SERVER["HTTP_CF_VISITOR"]) && (strpos($_SERVER["HTTP_CF_VISITOR"], "https") !== false))' . "\n";
                $rule .= '|| (isset($_SERVER["HTTP_CLOUDFRONT_FORWARDED_PROTO"]) && (strpos($_SERVER["HTTP_CLOUDFRONT_FORWARDED_PROTO"], "https") !== false))' . "\n";
                $rule .= '|| (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && (strpos($_SERVER["HTTP_X_FORWARDED_PROTO"], "https") !== false))' . "\n";
                $rule .= '|| (isset($_SERVER["HTTP_X_PROTO"]) && (strpos($_SERVER["HTTP_X_PROTO"], "SSL") !== false))' . "\n";
                $rule .= ') {' . "\n";
                $rule .= '$_SERVER["HTTPS"] = "on";' . "\n";
                $rule .= '}' . "\n";
                $rule .= "//END Really Simple SSL" . "\n";

                $insert_after = "<?php";
                $pos = strpos($wpconfig, $insert_after);
                if ($pos !== false) {
                    $wpconfig = substr_replace($wpconfig, $rule, $pos + 1 + strlen($insert_after), 0);
                }

                file_put_contents($wpconfig_path, $wpconfig);
                if ($this->debug) {
                    $this->trace_log("wp config loadbalancer fix inserted");
                }
            } else {
                if ($this->debug) {
                    $this->trace_log("wp config loadbalancer fix FAILED");
                }
                $this->wpconfig_loadbalancer_fix_failed = TRUE;
            }
        } else {
            if ($this->debug) {
                $this->trace_log("wp config loadbalancer fix already in place, great!");
            }
        }
        $this->save_options();

    }


    /**
     * Getting WordPress to recognize setup as being SSL when no https server variable is available
     *
     * @since  2.1
     *
     * @access public
     *
     */

    public function wpconfig_server_variable_fix()
    {
        if (!current_user_can($this->capability)) return;

        $wpconfig_path = $this->find_wp_config_path();
        if (empty($wpconfig_path)) return;
        $wpconfig = file_get_contents($wpconfig_path);

        //check permissions
        if (!is_writable($wpconfig_path)) {
            if ($this->debug) $this->trace_log("wp-config.php not writable");
            return;
        }

        //when more than one blog, first remove what we have
        if (is_multisite() && !RSSSL()->rsssl_multisite->is_multisite_subfolder_install() && !RSSSL()->rsssl_multisite->ssl_enabled_networkwide && count($this->sites) > 1) {
            $wpconfig = preg_replace("/\/\/Begin\s?Really\s?Simple\s?SSL.*?\/\/END\s?Really\s?Simple\s?SSL/s", "", $wpconfig);
            $wpconfig = preg_replace("/\n+/", "\n", $wpconfig);
            file_put_contents($wpconfig_path, $wpconfig);
        }

        //now create new

        //check if the fix is already there
        if (strpos($wpconfig, "//Begin Really Simple SSL Server variable fix") !== FALSE) {
            if ($this->debug) {
                $this->trace_log("wp config server variable fix already in place, great!");
            }
            return;
        }

        if ($this->debug) {
            $this->trace_log("Adding server variable to wpconfig");
        }
        $rule = $this->get_server_variable_fix_code();

        $insert_after = "<?php";
        $pos = strpos($wpconfig, $insert_after);
        if ($pos !== false) {
            $wpconfig = substr_replace($wpconfig, $rule, $pos + 1 + strlen($insert_after), 0);
        }
        file_put_contents($wpconfig_path, $wpconfig);
        if ($this->debug) $this->trace_log("wp config server variable fix inserted");

        $this->save_options();
    }


    protected function get_server_variable_fix_code()
    {
        if (is_multisite() && !RSSSL()->rsssl_multisite->ssl_enabled_networkwide && RSSSL()->rsssl_multisite->is_multisite_subfolder_install()) {
            if ($this->debug) $this->trace_log("per site activation on subfolder install, wp config server variable fix skipped");
            return "";
        }

        if (is_multisite() && !RSSSL()->rsssl_multisite->ssl_enabled_networkwide && count($this->sites) == 0) {
            if ($this->debug) $this->trace_log("no sites left with SSL, wp config server variable fix skipped");
            return "";
        }

        if (is_multisite() && !RSSSL()->rsssl_multisite->ssl_enabled_networkwide) {
            $rule = "\n" . "//Begin Really Simple SSL Server variable fix" . "\n";
            foreach ($this->sites as $domain) {
                //remove http or https.
                if ($this->debug) {
                    $this->trace_log("getting server variable rule for:" . $domain);
                }
                $domain = preg_replace("/(http:\/\/|https:\/\/)/", "", $domain);

                //we excluded subfolders, so treat as domain
                //check only for domain without www, as the www variant is found as well with the no www search.
                $domain_no_www = str_replace("www.", "", $domain);

                $rule .= 'if ( strpos($_SERVER["HTTP_HOST"], "' . $domain_no_www . '")!==FALSE ) {' . "\n";
                $rule .= '   $_SERVER["HTTPS"] = "on";' . "\n";
                $rule .= '}' . "\n";
            }
            $rule .= "//END Really Simple SSL" . "\n";
        } else {
            $rule = "\n" . "//Begin Really Simple SSL Server variable fix" . "\n";
            $rule .= '$_SERVER["HTTPS"] = "on";' . "\n";
            $rule .= "//END Really Simple SSL" . "\n";
        }

        return $rule;
    }

    /**
     * Removing changes made to the wpconfig
     *
     * @since  2.1
     *
     * @access public
     *
     */

    public function remove_wpconfig_edit()
    {

        $wpconfig_path = $this->find_wp_config_path();
        if (empty($wpconfig_path)) return;
        $wpconfig = file_get_contents($wpconfig_path);

        //check for permissions
        if (!is_writable($wpconfig_path)) {
            if ($this->debug) $this->trace_log("could not remove wpconfig edits, wp-config.php not writable");
            $this->errors['wpconfig not writable'] = TRUE;
            return;
        }

        //remove edits
        $wpconfig = preg_replace("/\/\/Begin\s?Really\s?Simple\s?SSL.*?\/\/END\s?Really\s?Simple\s?SSL/s", "", $wpconfig);
        $wpconfig = preg_replace("/\n+/", "\n", $wpconfig);
        file_put_contents($wpconfig_path, $wpconfig);

        //in multisite environment, with per site activation, re-add
        if (is_multisite() && !RSSSL()->rsssl_multisite->ssl_enabled_networkwide) {
            if ($this->do_wpconfig_loadbalancer_fix)
                $this->wpconfig_loadbalancer_fix();

            if ($this->no_server_variable)
                $this->wpconfig_server_variable_fix();
        }

    }

    /**
     * Changes the siteurl and homeurl to https
     *
     * @since  2.0
     *
     * @access public
     *
     */

    public function set_siteurl_to_ssl()
    {
        $this->trace_log("converting siteurl and homeurl to https");

        $siteurl_ssl = str_replace("http://", "https://", get_option('siteurl'));
        $homeurl_ssl = str_replace("http://", "https://", get_option('home'));
        update_option('siteurl', $siteurl_ssl);
        update_option('home', $homeurl_ssl);
    }


    /**
     * On de-activation, siteurl and homeurl are reset to http
     *
     * @since  2.0
     *
     * @access public
     *
     */

    public function remove_ssl_from_siteurl()
    {
        $siteurl_no_ssl = str_replace("https://", "http://", get_option('siteurl'));
        $homeurl_no_ssl = str_replace("https://", "http://", get_option('home'));
        update_option('siteurl', $siteurl_no_ssl);
        update_option('home', $homeurl_no_ssl);
    }

    /**
     * Save the plugin options
     *
     * @since  2.0
     *
     * @access public
     *
     */

    public function save_options()
    {
        //any options added here should also be added to function options_validate()
        $options = array(
            'site_has_ssl' => $this->site_has_ssl,
            'hsts' => $this->hsts,
            'htaccess_warning_shown' => $this->htaccess_warning_shown,
            'review_notice_shown' => $this->review_notice_shown,
            'ssl_success_message_shown' => $this->ssl_success_message_shown,
            'autoreplace_insecure_links' => $this->autoreplace_insecure_links,
            'plugin_db_version' => $this->plugin_db_version,
            'debug' => $this->debug,
            'do_not_edit_htaccess' => $this->do_not_edit_htaccess,
            'htaccess_redirect' => $this->htaccess_redirect,
            'ssl_enabled' => $this->ssl_enabled,
            'javascript_redirect' => $this->javascript_redirect,
            'wp_redirect' => $this->wp_redirect,
            'switch_mixed_content_fixer_hook' => $this->switch_mixed_content_fixer_hook,
        );

        update_option('rlrsssl_options', $options);


    }

    /**
     * Load the translation files
     *
     * @since  1.0
     *
     * @access public
     *
     */

    public function load_translation()
    {
        load_plugin_textdomain('really-simple-ssl', FALSE, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    /**
     * Handles deactivation of this plugin
     *
     * @since  2.0
     *
     * @access public
     *
     */

    public function deactivate($networkwide)
    {
        $this->remove_ssl_from_siteurl();
        $this->remove_ssl_from_siteurl_in_wpconfig();

        $this->site_has_ssl = FALSE;
        $this->hsts = FALSE;
        $this->htaccess_warning_shown = FALSE;
        $this->review_notice_shown = FALSE;
        $this->ssl_success_message_shown = FALSE;
        $this->autoreplace_insecure_links = TRUE;
        $this->do_not_edit_htaccess = FALSE;
        $this->htaccess_redirect = FALSE;
        $this->javascript_redirect = FALSE;
        $this->wp_redirect = FALSE;
        $this->ssl_enabled = FALSE;
        $this->switch_mixed_content_fixer_hook = FALSE;

        $this->save_options();

        //when on multisite, per site activation, recreate domain list for htaccess and wpconfig rewrite actions
        if (is_multisite()) {
            RSSSL()->rsssl_multisite->deactivate();
            if (!RSSSL()->rsssl_multisite->ssl_enabled_networkwide) $this->build_domain_list();
        }

        $this->remove_wpconfig_edit();
        $this->removeHtaccessEdit();
    }


    /**
     * Checks if we are currently on SSL protocol, but extends standard wp with loadbalancer check.
     *
     * @since  2.0
     *
     * @access public
     *
     */

    public function is_ssl_extended()
    {
        $server_var = FALSE;

        if ((isset($_ENV['HTTPS']) && ('on' == $_ENV['HTTPS']))
            || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && (strpos($_SERVER['HTTP_X_FORWARDED_SSL'], '1') !== false))
            || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && (strpos($_SERVER['HTTP_X_FORWARDED_SSL'], 'on') !== false))
            || (isset($_SERVER['HTTP_CF_VISITOR']) && (strpos($_SERVER['HTTP_CF_VISITOR'], 'https') !== false))
            || (isset($_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO']) && (strpos($_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO'], 'https') !== false))
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && (strpos($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') !== false))
            || (isset($_SERVER['HTTP_X_PROTO']) && (strpos($_SERVER['HTTP_X_PROTO'], 'SSL') !== false))
        ) {
            $server_var = TRUE;
        }


        if (is_ssl() || $server_var) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks for SSL by opening a test page in the plugin directory
     *
     * @since  2.0
     *
     * @access public
     *
     */

    public function detect_configuration()
    {
        $this->trace_log("** Detecting configuration **");
        $this->trace_log("plugin version: " . rsssl_version);

        //if current page is on SSL, we can assume SSL is available, even when an errormsg was returned
        if ($this->is_ssl_extended()) {
            $this->trace_log("Already on SSL, start detecting configuration");
            $this->site_has_ssl = TRUE;
        } else {
            //if certificate is valid
            $this->trace_log("Check SSL by retrieving SSL certificate info");
            $this->site_has_ssl = RSSSL()->rsssl_certificate->is_valid();
        }

        if ($this->site_has_ssl) {
            $filecontents = $this->get_test_page_contents();

            //get filecontents to check .htaccess redirection method and wpconfig fix
            //check the type of SSL, either by parsing the returned string, or by reading the server vars.
            if ((strpos($filecontents, "#CLOUDFRONT#") !== false) || (isset($_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO']) && ($_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO'] == 'https'))) {
                $this->ssl_type = "CLOUDFRONT";
            } elseif ((strpos($filecontents, "#CLOUDFLARE#") !== false) || (isset($_SERVER['HTTP_CF_VISITOR']) && ($_SERVER['HTTP_CF_VISITOR'] == 'https'))) {
                $this->ssl_type = "CLOUDFLARE";
            } elseif ((strpos($filecontents, "#LOADBALANCER#") !== false) || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && ($_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'))) {
                $this->ssl_type = "LOADBALANCER";
            } elseif ((strpos($filecontents, "#HTTP_X_PROTO#") !== false) || (isset($_SERVER['HTTP_X_PROTO']) && ($_SERVER['HTTP_X_PROTO'] == 'SSL'))) {
                $this->ssl_type = "HTTP_X_PROTO";
            } elseif ((strpos($filecontents, "#HTTP_X_FORWARDED_SSL_ON#") !== false) || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on')) {
                $this->ssl_type = "HTTP_X_FORWARDED_SSL_ON";
            } elseif ((strpos($filecontents, "#HTTP_X_FORWARDED_SSL_1#") !== false) || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == '1')) {
                $this->ssl_type = "HTTP_X_FORWARDED_SSL_1";
            } elseif ((strpos($filecontents, "#SERVER-HTTPS-ON#") !== false) || (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on')) {
                $this->ssl_type = "SERVER-HTTPS-ON";
            } elseif ((strpos($filecontents, "#SERVER-HTTPS-1#") !== false) || (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == '1')) {
                $this->ssl_type = "SERVER-HTTPS-1";
            } elseif ((strpos($filecontents, "#SERVERPORT443#") !== false) || (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT']))) {
                $this->ssl_type = "SERVERPORT443";
            } elseif ((strpos($filecontents, "#ENVHTTPS#") !== false) || (isset($_ENV['HTTPS']) && ('on' == $_ENV['HTTPS']))) {
                $this->ssl_type = "ENVHTTPS";
            } elseif ((strpos($filecontents, "#NO KNOWN SSL CONFIGURATION DETECTED#") !== false)) {
                //if we are here, SSL was detected, but without any known server variables set.
                //So we can use this info to set a server variable ourselves.
                if (!$this->wpconfig_has_fixes()) {
                    $this->no_server_variable = TRUE;
                }
                $this->trace_log("No server variable detected ");
                $this->ssl_type = "NA";
            } else {
                //no valid response, so set to NA
                $this->ssl_type = "NA";
            }

            //check for is_ssl()
            if ((!$this->is_ssl_extended() &&
                    (strpos($filecontents, "#SERVER-HTTPS-ON#") === false) &&
                    (strpos($filecontents, "#SERVER-HTTPS-1#") === false) &&
                    (strpos($filecontents, "#SERVERPORT443#") === false)) || (!is_ssl() && $this->is_ssl_extended())) {
                //when is_ssl would return false, we should add some code to wp-config.php
                if (!$this->wpconfig_has_fixes()) {
                    $this->trace_log("is_ssl() will return false: wp-config fix needed");
                    $this->do_wpconfig_loadbalancer_fix = TRUE;
                }
            }

            $this->trace_log("SSL type: " . $this->ssl_type);
        }
        $this->check_for_siteurl_in_wpconfig();

        $this->save_options();
    }


    /**
     * Test if the htaccess redirect will work
     * This way, no redirect loops should occur.
     *
     * @since  2.1
     *
     * @access public
     *
     */

    public function test_htaccess_redirect()
    {
        if (!current_user_can($this->capability)) return;

        $this->htaccess_test_success = get_transient('rsssl_htaccess_test_success');
        if (!$this->htaccess_test_success) {

            if ($this->debug) {
                $this->trace_log("testing htaccess rules...");
            }

            $filecontents = "";
            $testpage_url = trailingslashit($this->test_url()) . "testssl/";
            switch ($this->ssl_type) {
                case "CLOUDFRONT":
                    $testpage_url .= "cloudfront";
                    break;
                case "CLOUDFLARE":
                    $testpage_url .= "cloudflare";
                    break;
                case "LOADBALANCER":
                    $testpage_url .= "loadbalancer";
                    break;
                case "HTTP_X_PROTO":
                    $testpage_url .= "serverhttpxproto";
                    break;
                case "HTTP_X_FORWARDED_SSL_ON":
                    $testpage_url .= "serverhttpxforwardedsslon";
                    break;
                case "HTTP_X_FORWARDED_SSL_1":
                    $testpage_url .= "serverhttpxforwardedssl1";
                    break;
                case "SERVER-HTTPS-ON":
                    $testpage_url .= "serverhttpson";
                    break;
                case "SERVER-HTTPS-1":
                    $testpage_url .= "serverhttps1";
                    break;
                case "SERVERPORT443":
                    $testpage_url .= "serverport443";
                    break;
                case "ENVHTTPS":
                    $testpage_url .= "envhttps";
                    break;
            }

            $testpage_url .= ("/ssl-test-page.html");

            $response = wp_remote_get($testpage_url);
            if (is_array($response)) {
                $status = wp_remote_retrieve_response_code($response);
                $filecontents = wp_remote_retrieve_body($response);
            }

            $this->trace_log("test page url, enter in browser to check manually: " . $testpage_url);

            if (!is_wp_error($response) && (strpos($filecontents, "#SSL TEST PAGE#") !== false)) {
                $htaccess_test_success = 'success';
                $this->trace_log("htaccess rules tested successfully.");
            } else {
                //.htaccess rewrite rule seems to be giving problems.
                $htaccess_test_success = 'error';
                if (is_wp_error($response)) {
                    $this->trace_log("htaccess rules test failed with error: " . $response->get_error_message());
                } else {
                    $this->trace_log("htaccess test rules failed. Set WordPress redirect in settings/SSL");
                }
            }
            if (empty($filecontents)) {
                $htaccess_test_success = 'no-response';
            }
            set_transient('rsssl_htaccess_test_success', $this->htaccess_test_success, 600);
        }


        if ($htaccess_test_success == 'no-response'){
            $this->htaccess_test_success = FALSE;
        }
        if ($htaccess_test_success == 'success'){
            $this->htaccess_test_success = true;
        }
        if ($htaccess_test_success == 'error'){
            $this->htaccess_test_success = FALSE;
        }

    }


    /**
     * Get an url with which we can test the SSL connection and htaccess redirect rules.
     *
     * @since  2.0
     *
     * @access public
     *
     */

    public function test_url()
    {
        $plugin_url = str_replace("http://", "https://", trailingslashit(rsssl_url));
        $https_home_url = str_replace("http://", "https://", home_url());

        //in some case we get a relative url here, so we check that.
        //we compare to urls replaced to https, in case one of them is still on http.
        if ((strpos($plugin_url, "https://") === FALSE) &&
            (strpos($plugin_url, $https_home_url) === FALSE)
        ) {
            //make sure we do not have a slash at the start
            $plugin_url = ltrim($plugin_url, "/");
            $plugin_url = trailingslashit(home_url()) . $plugin_url;
        }

        //for subdomains or domain mapping situations, we have to convert the plugin_url from main site to the subdomain url.
        if (is_multisite() && (!is_main_site(get_current_blog_id())) && (!RSSSL()->rsssl_multisite->is_multisite_subfolder_install())) {
            $mainsiteurl = trailingslashit(str_replace("http://", "https://", network_site_url()));

            $home = trailingslashit($https_home_url);
            $plugin_url = str_replace($mainsiteurl, $home, $plugin_url);

            //return http link if original url is http.
            //if (strpos(home_url(), "https://")===FALSE) $plugin_url = str_replace("https://","http://",$plugin_url);
        }

        return $plugin_url;
    }


    /**
     * removes the added redirect to https rules to the .htaccess file.
     *
     * @since  2.0
     *
     * @access public
     *
     */

    public function removeHtaccessEdit()
    {
        if (file_exists($this->htaccess_file()) && is_writable($this->htaccess_file())) {
            $htaccess = file_get_contents($this->htaccess_file());

            //if multisite, per site activation and more than one blog remaining on ssl, remove condition for this site only
            //the domain list has been rebuilt already, so current site is already removed.
            if (is_multisite() && !RSSSL()->rsssl_multisite->ssl_enabled_networkwide && count($this->sites) > 0) {
                //remove http or https.
                $domain = preg_replace("/(http:\/\/|https:\/\/)/", "", home_url());
                $pattern = "/#wpmu\srewritecond\s?" . preg_quote($domain, "/") . "\n.*?#end\swpmu\srewritecond\s?" . preg_quote($domain, "/") . "\n/s";

                //only remove if the pattern is there at all
                if (preg_match($pattern, $htaccess)) $htaccess = preg_replace($pattern, "", $htaccess);
                //now replace any remaining "or" on the last condition.
                $pattern = "/(\[OR\])(?!.*(\[OR\]|#start).*?RewriteRule)/s";
                $htaccess = preg_replace($pattern, "", $htaccess, 1);

            } else {
                // remove everything
                $pattern = "/#\s?BEGIN\s?rlrssslReallySimpleSSL.*?#\s?END\s?rlrssslReallySimpleSSL/s";
                //only remove if the pattern is there at all
                if (preg_match($pattern, $htaccess)) $htaccess = preg_replace($pattern, "", $htaccess);

            }

            $htaccess = preg_replace("/\n+/", "\n", $htaccess);
            file_put_contents($this->htaccess_file(), $htaccess);
            $this->save_options();
        } else {
            $this->errors['HTACCESS_NOT_WRITABLE'] = TRUE;
            if ($this->debug) $this->trace_log("could not remove rules from htaccess, file not writable");
        }
    }

    public function get_htaccess_version()
    {
        if (!file_exists($this->htaccess_file())) return false;

        $htaccess = file_get_contents($this->htaccess_file());
        $versionpos = strpos($htaccess, "rsssl_version");

        if ($versionpos === false) {
            //no version found, so not .htaccess rules.
            return false;
        } else {
            //find closing marker of version
            $close = strpos($htaccess, "]", $versionpos);
            $version = substr($htaccess, $versionpos + 14, $close - ($versionpos + 14));
            return $version;
        }
    }


    /*   deprecated   */

    function htaccess_redirect_allowed()
    {
        if (is_multisite() && RSSSL()->rsssl_multisite->is_per_site_activated_multisite_subfolder_install()) {
            return false;
        } else {
            return true;
        }
    }


    /*
    Checks if the htaccess contains redirect rules, either actual redirect or a rsssl marker.
  */

    public function htaccess_contains_redirect_rules()
    {

        if (!file_exists($this->htaccess_file()))  {
            return false;
        }

        $htaccess = file_get_contents($this->htaccess_file());

        $needle_old = "RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]";
        $needle_new = "RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]";
        if (strpos($htaccess, $needle_old) !== FALSE || strpos($htaccess, $needle_new) !== FALSE || $this->contains_rsssl_rules()) {
            return true;
        } else {
            $this->trace_log(".htaccess does not contain default Really Simple SSL redirect");
            return false;
        }

    }


    /*
  *    Checks if the htaccess contains the Really Simple SSL comment.
  *
  */

    public function contains_rsssl_rules()
    {
        if (!file_exists($this->htaccess_file())) {
            return false;
        }

        $htaccess = file_get_contents($this->htaccess_file());

        $check = null;
        preg_match("/BEGIN rlrssslReallySimpleSSL/", $htaccess, $check);
        if (count($check) === 0) {
            return false;
        } else {
            return true;
        }
    }

    /*
     *    Checks if a 301 redirect is set
     *    this is the case if either the wp_redirect is set, or the htaccess redirect is set.
     *
     */

    public function has_301_redirect()
    {
        if ($this->wp_redirect) return true;

        if (RSSSL()->rsssl_server->uses_htaccess() && $this->htaccess_contains_redirect_rules()) {
            return true;
        }

        return false;
    }

    /**
     * Checks if the HSTS rule is already in the htaccess file
     * Set the hsts variable in the db accordingly. applies to preload version as well.
     *
     * @since  2.1
     *
     * @access public
     *
     */

    public function contains_hsts()
    {
        if (!file_exists($this->htaccess_file())) {
            $this->trace_log(".htaccess not found in " . $this->ABSpath);
            $result = $this->hsts; //just return the setting.
        } else {
            $htaccess = file_get_contents($this->htaccess_file());

            preg_match("/Strict-Transport-Security/", $htaccess, $check);
            if (count($check) === 0) {
                $result = false;
            } else {
                $result = true;
            }
        }

        return $result;
    }


    /**
     * Adds redirect to https rules to the .htaccess file or htaccess.conf on Bitnami.
     *
     * @since  2.0
     *
     * @access public
     *
     */

    public function editHtaccess()
    {
        if (!current_user_can($this->capability)) return;

        //check if htaccess exists and  if htaccess is writable
        //update htaccess to redirect to ssl

        $this->trace_log("checking if .htaccess can or should be edited...");

        //does it exist?
        if (!file_exists($this->htaccess_file()) ) {
            $this->trace_log(".htaccess not found.");
            return;
        }

        //check if editing is blocked.
        if ($this->do_not_edit_htaccess) {
            $this->trace_log("Edit of .htaccess blocked by setting or define 'do not edit htaccess' in Really Simple SSL.");
            return;
        }

         $htaccess = file_get_contents($this->htaccess_file());

        if (!$this->htaccess_contains_redirect_rules()) {

            if (!is_writable($this->htaccess_file())) {
                //set the wp redirect as fallback, because .htaccess couldn't be edited.
                if ($this->clicked_activate_ssl()) $this->wp_redirect = true;
                if (is_multisite()) {
                    RSSSL()->rsssl_multisite->wp_redirect = true;
                    RSSSL()->rsssl_multisite->save_options();
                }
                $this->save_options();
                $this->trace_log(".htaccess not writable.");
                return;
            }

            $rules = $this->get_redirect_rules();

            //insert rules before wordpress part.
            if (strlen($rules) > 0) {
                $wptag = "# BEGIN WordPress";
                if (strpos($htaccess, $wptag) !== false) {
                    $htaccess = str_replace($wptag, $rules . $wptag, $htaccess);
                } else {
                    $htaccess = $htaccess . $rules;
                }
                file_put_contents($this->htaccess_file(), $htaccess);
            }

        }
    }


    public function update_htaccess_after_settings_save($oldvalue = false, $newvalue = false, $option = false)
    {
        if (!current_user_can($this->capability)) return;

        //does it exist?
        if (!file_exists($this->htaccess_file())) {
            $this->trace_log(".htaccess not found.");
            return;
        }

        if (!is_writable($this->htaccess_file())) {
            if ($this->debug) $this->trace_log(".htaccess not writable.");
            return;
        }

        //check if editing is blocked.
        if ($this->do_not_edit_htaccess) {
            $this->trace_log("Edit of .htaccess blocked by setting or define 'do not edit htaccess' in Really Simple SSL.");
            return;
        }

        $htaccess = file_get_contents($this->htaccess_file());
        $htaccess = preg_replace("/#\s?BEGIN\s?rlrssslReallySimpleSSL.*?#\s?END\s?rlrssslReallySimpleSSL/s", "", $htaccess);
        $htaccess = preg_replace("/\n+/", "\n", $htaccess);

        $rules = $this->get_redirect_rules();

        //insert rules before WordPress part.
        $wptag = "# BEGIN WordPress";
        if (strpos($htaccess, $wptag) !== false) {
            $htaccess = str_replace($wptag, $rules . $wptag, $htaccess);
        } else {
            $htaccess = $htaccess . $rules;
        }
        file_put_contents($this->htaccess_file(), $htaccess);

    }

    /**
     *
     * @since 2.2
     *  Check if the mixed content fixer is functioning on the front end, by scanning the source of the homepage for the fixer comment.
     *
     */

    public function mixed_content_fixer_detected()
    {
        $status = 0;

        $mixed_content_fixer_detected = get_transient('rsssl_mixed_content_fixer_detected');

        if (!$mixed_content_fixer_detected) {

            $web_source = "";
            //check if the mixed content fixer is active
            $response = wp_remote_get(home_url());

            if (is_array($response)) {
                $status = wp_remote_retrieve_response_code($response);
                $web_source = wp_remote_retrieve_body($response);
            }

            if ($status != 200) {
                $mixed_content_fixer_detected = 'no-response';
            } elseif (strpos($web_source, "data-rsssl=") === false) {
                $mixed_content_fixer_detected = 'error';
            } else {
                $mixed_content_fixer_detected = 'success';
            }

            set_transient('rsssl_mixed_content_fixer_detected', $mixed_content_fixer_detected, 600);
        }

        if ($mixed_content_fixer_detected === 'no-response'){
            $this->trace_log("Could not connect to website");
            $this->mixed_content_fixer_detected = FALSE;
        }
        if ($mixed_content_fixer_detected === 'error'){
            $this->trace_log("Mixed content fixer marker not found in the websource");
            $this->mixed_content_fixer_detected = FALSE;
        }
        if ($mixed_content_fixer_detected === 'success'){
            $this->trace_log("Mixed content fixer was successfully detected on the front end.");
            $this->mixed_content_fixer_detected = true;
        }
    }

    /**
     * Create redirect rules for the .htaccess.
     *
     * @since  2.1
     *
     * @access public
     *
     */

    public function get_redirect_rules($manual = false)
    {
        $this->trace_log("retrieving redirect rules");
        //only add the redirect rules when a known type of SSL was detected. Otherwise, we use https.
        $rule = "";

        //if the htaccess test was successfull, and we know the redirectype, edit
        if ($this->htaccess_redirect && ($manual || $this->htaccess_test_success) && $this->ssl_type != "NA") {
            $this->trace_log("starting insertion of .htaccess redirects.");
            $rule .= "<IfModule mod_rewrite.c>" . "\n";
            $rule .= "RewriteEngine on" . "\n";

            $or = "";
            if ($this->ssl_type == "SERVER-HTTPS-ON") {
                $rule .= "RewriteCond %{HTTPS} !=on [NC]" . "\n";
            } elseif ($this->ssl_type == "SERVER-HTTPS-1") {
                $rule .= "RewriteCond %{HTTPS} !=1" . "\n";
            } elseif ($this->ssl_type == "LOADBALANCER") {
                $rule .= "RewriteCond %{HTTP:X-Forwarded-Proto} !https" . "\n";
            } elseif ($this->ssl_type == "HTTP_X_PROTO") {
                $rule .= "RewriteCond %{HTTP:X-Proto} !SSL" . "\n";
            } elseif ($this->ssl_type == "CLOUDFLARE") {
                $rule .= "RewriteCond %{HTTP:CF-Visitor} '" . '"scheme":"http"' . "'" . "\n";//some concatenation to get the quotes right.
            } elseif ($this->ssl_type == "SERVERPORT443") {
                $rule .= "RewriteCond %{SERVER_PORT} !443" . "\n";
            } elseif ($this->ssl_type == "CLOUDFRONT") {
                $rule .= "RewriteCond %{HTTP:CloudFront-Forwarded-Proto} !https" . "\n";
            } elseif ($this->ssl_type == "HTTP_X_FORWARDED_SSL_ON") {
                $rule .= "RewriteCond %{HTTP:X-Forwarded-SSL} !on" . "\n";
            } elseif ($this->ssl_type == "HTTP_X_FORWARDED_SSL_1") {
                $rule .= "RewriteCond %{HTTP:X-Forwarded-SSL} !=1" . "\n";
            } elseif ($this->ssl_type == "ENVHTTPS") {
                $rule .= "RewriteCond %{ENV:HTTPS} !=on" . "\n";
            }

            //if multisite, and NOT subfolder install (checked for in the detect_config function)
            //, add a condition so it only applies to sites where plugin is activated
            if (is_multisite() && !RSSSL()->rsssl_multisite->ssl_enabled_networkwide) {
                $this->trace_log("multisite, per site activation");

                foreach ($this->sites as $domain) {
                    $this->trace_log("adding condition for:" . $domain);

                    //remove http or https.
                    $domain = preg_replace("/(http:\/\/|https:\/\/)/", "", $domain);
                    //We excluded subfolders, so treat as domain

                    $domain_no_www = str_replace("www.", "", $domain);
                    $domain_yes_www = "www." . $domain_no_www;

                    $rule .= "#wpmu rewritecond " . $domain . "\n";
                    $rule .= "RewriteCond %{HTTP_HOST} ^" . preg_quote($domain_no_www, "/") . " [OR]" . "\n";
                    $rule .= "RewriteCond %{HTTP_HOST} ^" . preg_quote($domain_yes_www, "/") . " [OR]" . "\n";
                    $rule .= "#end wpmu rewritecond " . $domain . "\n";
                }

                //now remove last [OR] if at least on one site the plugin was activated, so we have at lease one condition
                if (count($this->sites) > 0) {
                    $rule = strrev(implode("", explode(strrev("[OR]"), strrev($rule), 2)));
                }
            } else {
                if ($this->debug) {
                    $this->trace_log("single site or networkwide activation");
                }
            }

            //fastest cache compatibility
            if (class_exists('WpFastestCache')) {
                $rule .= "RewriteCond %{REQUEST_URI} !wp-content\/cache\/(all|wpfc-mobile-cache)" . "\n";
            }

            //Exclude .well-known/acme-challenge for Let's Encrypt validation
            if ($this->has_acme_challenge_directory() && !$this->has_well_known_needle()) {
                $rule .= "RewriteCond %{REQUEST_URI} !^/\.well-known/acme-challenge/" . "\n";
            }

            $rule .= "RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]" . "\n";

                $rule .= "</IfModule>" . "\n";
        }

        if (strlen($rule) > 0) {
            $rule = "\n" . "# BEGIN rlrssslReallySimpleSSL rsssl_version[" . rsssl_version . "]\n" . $rule . "# END rlrssslReallySimpleSSL" . "\n";
        }

        $rule = apply_filters("rsssl_htaccess_output", $rule);

        $rule = preg_replace("/\n+/", "\n", $rule);
        return $rule;
    }


    /**
     *     Show warning when wpconfig could not be fixed
     *
     * @since 2.2
     *
     */

    public function show_notice_wpconfig_needs_fixes()
    {
        //prevent showing the review on edit screen, as gutenberg removes the class which makes it editable.
        $screen = get_current_screen();
        if ( $screen->parent_base === 'edit' ) return;
        ?>
        <div id="message" class="error notice">
            <h1><?php echo __("System detection encountered issues", "really-simple-ssl"); ?></h1>

            <?php if ($this->wpconfig_siteurl_not_fixed) { ?>
                <p>
                    <?php echo __("A definition of a siteurl or homeurl was detected in your wp-config.php, but the file is not writable.", "really-simple-ssl"); ?>
                </p>
                <p><?php echo __("Set your wp-config.php to writable and reload this page.", "really-simple-ssl"); ?></p>
            <?php }
            if ($this->do_wpconfig_loadbalancer_fix) { ?>
                <p><?php echo __("Your wp-config.php has to be edited, but is not writable.", "really-simple-ssl"); ?></p>
                <p><?php echo __("Because your site is behind a loadbalancer and is_ssl() returns false, you should add the following line of code to your wp-config.php.", "really-simple-ssl"); ?>

                    <br><br><code>
                        //Begin Really Simple SSL Load balancing fix<br>
                        $server_opts = array("HTTP_CLOUDFRONT_FORWARDED_PROTO" => "https", "HTTP_CF_VISITOR"=>"https",
                        "HTTP_X_FORWARDED_PROTO"=>"https", "HTTP_X_FORWARDED_SSL"=>"on", "HTTP_X_PROTO"=>"SSL",
                        "HTTP_X_FORWARDED_SSL"=>"1");<br>
                        foreach( $server_opts as $option => $value ) {<br>
                        &nbsp;if ((isset($_ENV["HTTPS"]) && ( "on" == $_ENV["HTTPS"] )) || (isset( $_SERVER[ $option ] )
                        && ( strpos( $_SERVER[ $option ], $value ) !== false )) ) {<br>
                        &nbsp;&nbsp;$_SERVER[ "HTTPS" ] = "on";<br>
                        &nbsp;&nbsp;break;<br>
                        &nbsp;}<br>
                        }<br>
                        //END Really Simple SSL
                    </code><br>
                </p>
                <p><?php echo __("Or set your wp-config.php to writable and reload this page.", "really-simple-ssl"); ?></p>
                <?php
            }

            if ($this->no_server_variable) {
                ?>
                <p><?php echo __('Because your server does not pass a variable with which WordPress can detect SSL, WordPress may create redirect loops on SSL.', 'really-simple-ssl'); ?></p>
                <p><?php echo __("Set your wp-config.php to writable and reload this page.", "really-simple-ssl"); ?></p>
                <?php
            }
            ?>

        </div>
        <?php
    }


    /**
     *
     * @return bool
     * since 3.1
     * Check if .well-known/acme-challenge directory exists
     *
     */

    public function has_acme_challenge_directory()
    {
        if (file_exists("$this->ABSpath.well-known/acme-challenge")) {
            return true;
        }

        return false;
    }

    /**
     *
     * @return bool
     * since 3.1
     * Check if there are already .well-known rules in .htaccess file
     *
     */

    public function has_well_known_needle()
    {
        $htaccess = file_get_contents($this->htaccess_file());

        $well_known_needle = ".well-known";

        if (strpos($htaccess, $well_known_needle) !== false) {
            return true;
        }

        return false;

    }

    public function show_leave_review_notice()
    {
        //prevent showing the review on edit screen, as gutenberg removes the class which makes it editable.
        $screen = get_current_screen();
        if ( $screen->parent_base === 'edit' ) return;

        if (!$this->review_notice_shown && get_option('rsssl_activation_timestamp') && get_option('rsssl_activation_timestamp') < strtotime("-1 month")) {
            add_action('admin_print_footer_scripts', array($this, 'insert_dismiss_review'));
            ?>
            <div id="message" class="updated notice is-dismissible rlrsssl-review">
                <p><?php printf(__('Hi, you have been using Really Simple SSL for a month now, awesome! If you have a moment, please consider leaving a review on WordPress.org to spread the word. We greatly appreciate it! If you have any questions or feedback, leave us a %smessage%s.', 'really-simple-ssl'),'<a href="https://really-simple-ssl.com/contact" target="_blank">','</a>'); ?></p>
                <i>- Rogier</i>
                <?php //Inline style because the main.css stylesheet is only included on Really Simple SSL admin pages.?>
                <ul style="margin-left: 30px; list-style: square;">
                    <li><p style="margin-top: -5px;"><a target="_blank" href="https://wordpress.org/support/plugin/really-simple-ssl/reviews/#new-post"><?php _e('Leave a review', 'really-simple-ssl'); ?></a></p></li>
                    <li><p style="margin-top: -5px;"><a href="#" id="maybe-later"><?php _e('Maybe later', 'really-simple-ssl'); ?></a></p></li>
                    <li><p style="margin-top: -5px;"><a href="#" class="review-dismiss"><?php _e('No thanks', 'really-simple-ssl'); ?></a></p></li>
                </ul>
            </div>
            <?php
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
     /*
      show a notice when the .htaccess file does not contain redirect rules
     */

        if (!$this->wp_redirect && $this->ssl_enabled && !$this->htaccess_warning_shown && !$this->htaccess_contains_redirect_rules()) {

            add_action('admin_print_footer_scripts', array($this, 'insert_dismiss_htaccess'));
            ?>
            <div id="message" class="error notice is-dismissible rlrsssl-htaccess">
                <p>
                    <?php echo __("You do not have a 301 redirect to https active in the settings. For SEO purposes it is advised to use 301 redirects. You can enable a 301 redirect in the settings.", "really-simple-ssl"); ?>
                    <a href="<?php echo admin_url('options-general.php?page=rlrsssl_really_simple_ssl')?>"><?php echo __("View settings page", "really-simple-ssl"); ?></a>
                </p>
            </div>
            <?php
        }

        if (isset($this->errors["DEACTIVATE_FILE_NOT_RENAMED"])) {
            ?>
            <div id="message" class="error notice is-dismissible rlrsssl-fail">
                <h1>
                    <?php _e("Major security issue!", "really-simple-ssl"); ?>
                </h1>
                <p>
                    <?php _e("The 'force-deactivate.php' file has to be renamed to .txt. Otherwise your ssl can be deactived by anyone on the internet.", "really-simple-ssl"); ?>
                </p>
                <a href="<?php echo admin_url('options-general.php?page=rlrsssl_really_simple_ssl')?>"><?php echo __("Check again", "really-simple-ssl"); ?></a>
            </div>
            <?php
        }

        if (is_multisite() && !is_main_site(get_current_blog_id())) return;
        /*
          SSL success message
      */

        if ($this->ssl_enabled && $this->site_has_ssl && !$this->ssl_success_message_shown) {
            if (!current_user_can("activate_plugins")) return;

            add_action('admin_print_footer_scripts', array($this, 'insert_dismiss_success'));
            ?>
            <div id="message" class="updated notice is-dismissible rlrsssl-success">
                <p>
                    <?php _e("SSL activated!", "really-simple-ssl"); ?>&nbsp;
                    <?php _e("Don't forget to change your settings in Google Analytics and Webmaster tools.", "really-simple-ssl");
                    ?>&nbsp;
                    <a target="_blank"
                       href="https://really-simple-ssl.com/knowledge-base/how-to-setup-google-analytics-and-google-search-consolewebmaster-tools/"><?php _e("More info.", "really-simple-ssl"); ?></a>
                    <?php

                    $settings_link = '<a href="'.admin_url('options-general.php?page=rlrsssl_really_simple_ssl').'">';
                    echo sprintf(__("See the %ssettings page%s for further SSL optimizations." , "really-simple-ssl"), $settings_link, "</a>"); ?>
                </p>
            </div>
            <?php
        }

        //some notices for SSL situations
        if ($this->site_has_ssl) {
            if (sizeof($this->plugin_conflict) > 0) {
                //pre WooCommerce 2.5
                if (isset($this->plugin_conflict["WOOCOMMERCE_FORCEHTTP"]) && $this->plugin_conflict["WOOCOMMERCE_FORCEHTTP"] && isset($this->plugin_conflict["WOOCOMMERCE_FORCESSL"]) && $this->plugin_conflict["WOOCOMMERCE_FORCESSL"]) {
                    ?>
                    <div id="message" class="error notice"><p>
                            <?php _e("Really Simple SSL has a conflict with another plugin.", "really-simple-ssl"); ?>
                            <br>
                            <?php _e("The force http after leaving checkout in WooCommerce will create a redirect loop.", "really-simple-ssl"); ?>
                            <br>
                            <a href="admin.php?page=wc-settings&tab=checkout"><?php _e("Show me this setting", "really-simple-ssl"); ?></a>
                        </p></div>
                    <?php
                }
            }
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
        $ajax_nonce = wp_create_nonce("really-simple-ssl-dismiss");
        ?>
        <script type='text/javascript'>
            jQuery(document).ready(function ($) {
                $(".rlrsssl-success.notice.is-dismissible").on("click", ".notice-dismiss", function (event) {
                    var data = {
                        'action': 'dismiss_success_message',
                        'security': '<?php echo $ajax_nonce; ?>'
                    };

                    $.post(ajaxurl, data, function (response) {

                    });
                });
            });
        </script>
        <?php
    }

    /**
     * Insert some ajax script to dismiss the htaccess failed fail message, and stop nagging about it
     *
     * @since  2.0
     *
     * @access public
     *
     */

    public function insert_dismiss_htaccess()
    {
        $ajax_nonce = wp_create_nonce("really-simple-ssl");
        ?>
        <script type='text/javascript'>
            jQuery(document).ready(function ($) {
                $(".rlrsssl-htaccess.notice.is-dismissible").on("click", ".notice-dismiss", function (event) {
                    var data = {
                        'action': 'dismiss_htaccess_warning',
                        'security': '<?php echo $ajax_nonce; ?>'
                    };
                    $.post(ajaxurl, data, function (response) {

                    });
                });
            });
        </script>
        <?php
    }

    /**
     * Insert some ajax script to dismiss the review notice, and stop nagging about it
     *
     * @since  3.0
     *
     * @access public
     *
     * type: dismiss, later
     *
     */

    public function insert_dismiss_review()
    {
        $ajax_nonce = wp_create_nonce("really-simple-ssl");
        ?>
        <script type='text/javascript'>
            jQuery(document).ready(function ($) {
                $(".rlrsssl-review.notice.is-dismissible").on("click", ".notice-dismiss", function (event) {
                    rsssl_dismiss_review('dismiss');
                });
                $(".rlrsssl-review.notice.is-dismissible").on("click", "#maybe-later", function (event) {
                    rsssl_dismiss_review('later');
                    $(this).closest('.rlrsssl-review').remove();
                });
                $(".rlrsssl-review.notice.is-dismissible").on("click", ".review-dismiss", function (event) {
                    rsssl_dismiss_review('dismiss');
                    $(this).closest('.rlrsssl-review').remove();
                });

                function rsssl_dismiss_review(type){
                    var data = {
                        'action': 'dismiss_review_notice',
                        'type' : type,
                        'security': '<?php echo $ajax_nonce; ?>'
                    };
                    $.post(ajaxurl, data, function (response) {});
                }
            });
        </script>
        <?php
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
        //nonce check fails if url is changed to SSL.
        //check_ajax_referer( 'really-simple-ssl-dismiss', 'security' );
        $this->ssl_success_message_shown = TRUE;
        $this->save_options();
        wp_die();
    }

    /**
     * Process the ajax dismissal of the htaccess message.
     *
     * @since  2.1
     *
     * @access public
     *
     */

    public function dismiss_htaccess_warning_callback()
    {
        check_ajax_referer('really-simple-ssl', 'security');
        $this->htaccess_warning_shown = TRUE;
        $this->save_options();
        wp_die(); // this is required to terminate immediately and return a proper response
    }

    /**
     * Process the ajax dismissal of the htaccess message.
     *
     * @since  2.1
     *
     * @access public
     *
     */

    public function dismiss_review_notice_callback()
    {
        check_ajax_referer('really-simple-ssl', 'security');

        $type = isset($_POST['type']) ? $_POST['type'] : false;

        if ($type === 'dismiss'){
            $this->review_notice_shown = TRUE;
        }
        if ($type === 'later') {
            //Reset activation timestamp, notice will show again in one month.
            update_option('rsssl_activation_timestamp', time());
        }

        $this->save_options();
        wp_die(); // this is required to terminate immediately and return a proper response
    }

    /**
     * Adds the admin options page
     *
     * @since  2.0
     *
     * @access public
     *
     */

    public function add_settings_page()
    {
        if (!current_user_can($this->capability)) return;
        //hides the settings page if the hide menu for subsites setting is enabled
        if (is_multisite() && rsssl_multisite::this()->hide_menu_for_subsites && !is_super_admin()) return;

        global $rsssl_admin_page;
        $rsssl_admin_page = add_options_page(
            __("SSL settings", "really-simple-ssl"), //link title
            __("SSL", "really-simple-ssl"), //page title
            $this->capability, //capability
            'rlrsssl_really_simple_ssl', //url
            array($this, 'settings_page')); //function

        // Adds my_help_tab when my_admin_page loads
        add_action('load-' . $rsssl_admin_page, array($this, 'admin_add_help_tab'));

    }

    /**
     * Admin help tab
     *
     * @since  2.0
     *
     * @access public
     *
     */

    public function admin_add_help_tab()
    {
        $screen = get_current_screen();
        // Add my_help_tab if current screen is My Admin Page
        $screen->add_help_tab(array(
            'id' => "really-simple-ssl-documentation",
            'title' => __("Documentation", "really-simple-ssl"),
            'content' => '<p>' . __("On <a href='https://really-simple-ssl.com'>really-simple-ssl.com</a> you can find a lot of articles and documentation about installing this plugin, and installing SSL in general.", "really-simple-ssl") . '</p>',
        ));
    }

    /**
     * Create tabs on the settings page
     *
     * @since  2.1
     *
     * @access public
     *
     */

    public function admin_tabs($current = 'homepage')
    {
        $tabs = array(
            'configuration' => __("Configuration", "really-simple-ssl"),
            'settings' => __("Settings", "really-simple-ssl"),
            'debug' => __("Debug", "really-simple-ssl")
        );

        $tabs = apply_filters("rsssl_tabs", $tabs);

        echo '<h2 class="nav-tab-wrapper">';

        foreach ($tabs as $tab => $name) {
            $class = ($tab == $current) ? ' nav-tab-active' : '';
            echo "<a class='nav-tab$class' href='?page=rlrsssl_really_simple_ssl&tab=$tab'>$name</a>";
        }
        echo '</h2>';
    }

    /**
     * Build the settings page
     *
     * @since  2.0
     *
     * @access public
     *
     */

    public function settings_page()
    {
        if (!current_user_can($this->capability)) return;

        if (isset ($_GET['tab'])) $this->admin_tabs($_GET['tab']); else $this->admin_tabs('configuration');
        if (isset ($_GET['tab'])) $tab = $_GET['tab']; else $tab = 'configuration';

        ?>
        <div class="rsssl-container">
            <div class="rsssl-main"><?php

                switch ($tab) {
                    case 'configuration' :
                        /*
                  First tab, configuration
          */
                        ?>
                        <h2><?php echo __("Detected setup", "really-simple-ssl"); ?></h2>
                        <table class="really-simple-ssl-table">

                            <?php if ($this->site_has_ssl) { ?>
                                <tr>
                                    <td><?php echo $this->ssl_enabled ? $this->img("success") : $this->img("error"); ?></td>
                                    <td><?php
                                        if ($this->ssl_enabled) {
                                            _e("SSL is enabled on your site.", "really-simple-ssl") . "&nbsp;";
                                        } else {
                                            _e("SSL is not enabled yet", "really-simple-ssl") . "&nbsp;";
                                            $this->show_enable_ssl_button();
                                        }
                                        ?>
                                    </td>
                                    <td></td>
                                </tr>
                            <?php }

                            /* check if the mixed content fixer is working */
                            if ($this->ssl_enabled && $this->autoreplace_insecure_links && $this->site_has_ssl) {
                                $this->mixed_content_fixer_detected();
                                $mixed_content_fixer_detected = get_transient('rsssl_mixed_content_fixer_detected');
                                ?>
                                <tr>
                                    <td><?php echo $mixed_content_fixer_detected==="success" ? $this->img("success") : $this->img("error"); ?></td>
                                    <td><?php
                                        if ($mixed_content_fixer_detected === 'success') {
                                            echo __("Mixed content fixer was successfully detected on the front-end", "really-simple-ssl") . "&nbsp;";
                                        } elseif ($mixed_content_fixer_detected === 'no-response') {
                                            $link_open = '<a target="_blank" href="https://really-simple-ssl.com/knowledge-base/how-to-fix-no-response-from-webpage-warning/">';
                                            $link_close = '</a>';
                                            echo sprintf(__("Really Simple SSL has received no response from the webpage. See our knowledge base for %sinstructions on how to fix this warning%s.", 'really-simple-ssl'), $link_open, $link_close);
                                        }
                                        else {
                                            echo __('The mixed content fixer is active, but was not detected on the frontpage. Please follow these steps to check if the mixed content fixer is working.', "really-simple-ssl") . ":&nbsp;";
                                            echo '&nbsp;<a target="_blank" href="https://www.really-simple-ssl.com/knowledge-base/how-to-check-if-the-mixed-content-fixer-is-active/">';
                                            _e('Instructions', 'really-simple-ssl');
                                            echo '</a>';
                                        }
                                        ?>
                                    </td>
                                    <td></td>
                                </tr>
                            <?php } ?>
                            <tr>
                                <td><?php echo ($this->site_has_ssl && $this->wpconfig_ok()) ? $this->img("success") : $this->img("error"); ?></td>
                                <td><?php
                                    if (!$this->wpconfig_ok()) {
                                        _e("Failed activating SSL", "really-simple-ssl") . "&nbsp;";
                                    } elseif (!$this->site_has_ssl) {
                                        _e("No SSL detected.", "really-simple-ssl") . "&nbsp;";
                                    } else {
                                        _e("An SSL certificate was detected on your site. ", "really-simple-ssl");
                                    }
                                    ?>
                                </td>
                                <td></td>
                            </tr>
                            <?php if ($this->ssl_enabled) { ?>
                                <tr>
                                    <td>
                                        <?php echo ($this->has_301_redirect()) ? $this->img("success") : $this->img("warning"); ?>
                                    </td>
                                    <td>
                                        <?php

                                        if ($this->has_301_redirect()) {
                                            _e("301 redirect to https set: ", "really-simple-ssl");
                                            if (RSSSL()->rsssl_server->uses_htaccess() && $this->htaccess_contains_redirect_rules())
                                                _e(".htaccess redirect", "really-simple-ssl");

                                            if (RSSSL()->rsssl_server->uses_htaccess() && $this->htaccess_contains_redirect_rules() && $this->wp_redirect)
                                                echo "&nbsp;" . __("and", "really-simple-ssl") . "&nbsp;";

                                            if ($this->wp_redirect)
                                                _e("WordPress redirect", "really-simple-ssl");

                                        } elseif (RSSSL()->rsssl_server->uses_htaccess() && (!is_multisite() || !RSSSL()->rsssl_multisite->is_per_site_activated_multisite_subfolder_install())) {
                                            if (is_writable($this->htaccess_file())) {
                                                _e("Enable a .htaccess redirect or WordPress redirect in the settings to create a 301 redirect.", "really-simple-ssl");
                                            } elseif (!is_writable($this->htaccess_file())) {
                                                _e(".htaccess is not writable. Set 301 WordPress redirect, or set the .htaccess manually if you want to redirect in .htaccess.", "really-simple-ssl");
                                            } else {
                                                _e("Https redirect cannot be set in the .htaccess. Set the .htaccess redirect manually or enable WordPress redirect in the settings.", "really-simple-ssl");
                                            }
                                        } else {
                                            _e("No 301 redirect is set. Enable the WordPress 301 redirect in the settings to get a 301 permanent redirect.", "really-simple-ssl");
                                        }
                                        ?>
                                    </td>
                                    <td></td>
                                </tr>

                                <?php
                            }
                            ?>

                        </table>
                        <?php do_action("rsssl_configuration_page"); ?>
                        <?php
                        break;
                    case 'settings' :
                        /*
                            Second tab, Settings
                          */

                        ?>
                        <form action="options.php" method="post">
                            <?php
                            settings_fields('rlrsssl_options');
                            do_settings_sections('rlrsssl');
                            ?>

                            <input class="button button-primary" name="Submit" type="submit"
                                   value="<?php echo __("Save", "really-simple-ssl"); ?>"/>
                        </form>
                        <?php
                        break;

                    case 'debug' :
                        /*
            third tab: debug
          */
                        ?>
                        <div>
                            <?php
                            if ($this->debug) {
                                echo "<h2>" . __("Log for debugging purposes", "really-simple-ssl") . "</h2>";
                                echo "<p>" . __("Send me a copy of these lines if you have any issues. The log will be erased when debug is set to false", "really-simple-ssl") . "</p>";
                                echo "<div class='debug-log'>";
                                if (defined('RSSSL_SAFE_MODE') && RSSSL_SAFE_MODE) echo "SAFE MODE<br>";
                                echo "Options:<br>";
                                if ($this->htaccess_redirect) echo "* htaccess redirect<br>";
                                if ($this->wp_redirect) echo "* WordPress redirect<br>";
                                if ($this->autoreplace_insecure_links) echo "* Mixed content fixer<br>";

                                echo "SERVER: " . RSSSL()->rsssl_server->get_server() . "<br>";
                                if (is_multisite()) {
                                    echo "MULTISITE<br>";
                                    echo (!RSSSL()->rsssl_multisite->ssl_enabled_networkwide) ? "SSL is being activated per site<br>" : "SSL is activated network wide<br>";
                                }

                                echo ($this->ssl_enabled) ? "SSL is enabled for this site<br>" : "SSL is not yet enabled for this site<br>";
                                echo $this->debug_log;
                                echo "</div>";
                                //$this->debug_log.="<br><b>-----------------------</b>";
                                $this->debug_log = "";
                                $this->save_options();
                            } else {
                                echo "<br>";
                                _e("To view results here, enable the debug option in the settings tab.", "really-simple-ssl");
                            }

                            ?>
                        </div>
                        <?php
                        break;
                }
                //possibility to hook into the tabs.
                do_action("show_tab_{$tab}");
                ?>
            </div><!-- end main-->

            <?php

            /**
             *
             * Generate a sidebar for free users to advertise pro
             * When using Ultimate Member, also show Ultimate Member add-ons
             * Pro users never see the sidebar
             *
             * @since 2.5.27
             *
             */

            if (!defined("rsssl_pro_version") && (!defined("rsssl_pp_version")) && (!defined("rsssl_soc_version")) && (!class_exists('RSSSL_PRO'))) {

                //Generate the Really Simple Plugins logo and recommended plugins text

                ?>
                <div class="rsssl-sidebar">
                    <div class="rsssl-really-simple-plugins-logo">
                        <?php echo "<img class='rsssl-pro-image' src='" . trailingslashit(rsssl_url) . "assets/really-simple-plugins.png' alt='Really Simple SSL pro'>"; ?>
                    </div>
                    <div class="rsssl-sidebar-title">
                        <?php
                        $link_open = '<a target="_blank" href="https://really-simple-ssl.com/contact">';

                        ?>
                        <h3> <?php echo sprintf(__("We have some suggestions for your setup. Let us know if you have a suggestion for %sus%s!", "really-simple-ssl"), $link_open, "</a>") ?></h3>
                    </div>

                    <?php

                    /*
                     *
                     * Generate a container for Really Simple SSL pro, Ultimate Member and Moneybird plugins
                     * Pro container has different image size, text position and button color then UM and Moneybird
                     * Before generating, check if Really Simple SSL pro, Ultimate Member is active. For Moneybird, check if locale = nl_NL
                     *
                     */

                    $url = is_multisite() ? 'https://really-simple-ssl.com/downloads/really-simple-ssl-pro-multisite/' : 'https://really-simple-ssl.com/downloads/really-simple-ssl-pro/';
                    $this->get_banner_html(array(
                            'img' => 'rsssl-pro.jpg',
                            'title' => 'Really Simple SSL Pro',
                            'description' => __("Really Simple SSL pro optimizes your SSL configuration: extensive scan for mixed content issues, access to premium support, HSTS and more!", "really-simple-ssl"),
                            'url' => $url,
                            'pro' => true,
                           )
                        );
                      $this->get_banner_html(array(
                              'img' => 'complianz.jpg',
                              'title' => 'ComplianZ',
                              'description' => __("The Complianz Privacy Suite (GDPR/CaCPA) for WordPress. Simple, Quick and Complete. Up-to-date customized legal documents by a prominent IT Law firm.", "really-simple-ssl"),
                              'url' => 'https://wordpress.org/plugins/complianz-gdpr/',
                              'pro' => true,
                           )
                        );

                    if (defined("ultimatemember_version")) {

                        if (!defined("um_tagging_version")) {

                            $this->get_banner_html(array(
                                    'img' => 'tagging.jpg',
                                    'title' => 'UM Tagging',
                                    'description' => __("UM Tagging allows you to @tag or @mention all users on your platform.", "really-simple-ssl"),
                                    'url' => 'https://really-simple-plugins.com/download/um-tagging/',
                                )
                            );
                        }

                        if (!defined("um_most_visited_version")) {

                            $this->get_banner_html(array(
                                    'img' => 'most-visited.jpg',
                                    'title' => 'UM Most Visited',
                                    'description' => __("Show the most visited users and add a 'last visited users' tab to each user profile.", "really-simple-ssl"),
                                    'url' => 'https://really-simple-plugins.com/download/most-visited-members/',
                                )
                            );
                        }

                        if (!defined("um_tagging_version")) {
                            $this->get_banner_html(array(
                                    'img' => 'mail-alerts.jpg',
                                    'title' => 'UM Mail Alerts',
                                    'description' => __("Automatically send a notification when a user's post on the activity feed is liked or commented on.", "really-simple-ssl"),
                                    'url' => 'https://really-simple-plugins.com/download/um-mail-alerts/',
                                )
                            );

                        }
                    }

                        if (defined("EDD_SL_PLUGIN_DIR") && (get_locale() === 'nl_NL')) {
                            $this->get_banner_html(array(
                                    'img' => 'edd-moneybird.jpg',
                                    'title' => 'EDD Moneybird',
                                    'description' => __("Export your Easy Digital Downloads sales directly to Moneybird.", "really-simple-ssl"),
                                    'url' => 'https://really-simple-plugins.com/download/edd-moneybird/',
                                )
                            );

                        }

                        if (defined('WC_PLUGIN_FILE') && (get_locale() === 'nl_NL')) {
                            $this->get_banner_html(array(
                                    'img' => 'woocommerce-moneybird.jpg',
                                    'title' => 'WooCommerce Moneybird',
                                    'description' => __("Export your WooCommerce sales directly to Moneybird.", "really-simple-ssl"),
                                    'url' => 'https://really-simple-plugins.com/download/woocommerce-moneybird/',
                                )
                            );

                        }
                     ?>
                </div>
            <?php }
            ?>


        </div><!-- end container -->
        <?php
    }

    /**
     * Returns a success, error or warning image for the settings page
     *
     * @since  2.0
     *
     * @access public
     *
     * @param string $type the type of image
     *
     * @return string
     */

    public function img($type)
    {
        if ($type == 'success') {
            return "<img class='rsssl-icons' src='" . trailingslashit(rsssl_url) . "img/check-icon.png' alt='success'>";
        } elseif ($type == "error") {
            return "<img class='rsssl-icons' src='" . trailingslashit(rsssl_url) . "img/cross-icon.png' alt='error'>";
        } else {
            return "<img class='rsssl-icons' src='" . trailingslashit(rsssl_url) . "img/warning-icon.png' alt='warning'>";
        }
    }


    private function get_banner_html($args)
    {

        $default = array(
            'pro' => false,
        );

        $args = wp_parse_args($args, $default);

        $pro = $args['pro'] ? '-pro' : '';
        ?>
        <div class="rsssl-sidebar-single-content-container<?php echo $pro ?>">
            <img class="rsssl-sidebar-image<?php echo $pro ?>"
                 src="<?php echo trailingslashit(rsssl_url) . 'assets/' . $args['img'] ?>"
                 alt="<?php echo $args['title'] ?>">
            <div class="rsssl-sidebar-text-content<?php echo $pro ?>">
                <?php echo $args['description'] ?>
            </div>
            <div class="rsssl-more-info-button">
                <a id="rsssl-premium-button<?php echo $pro ?>" class="button"
                   href="<?php echo $args['url'] ?>"
                   target="_blank"> <?php echo __("More info", "really-simple-ssl") ?> </a>
            </div>
        </div>
        <?php
    }

    /**
     * Add some css for the settings page
     *
     * @since  2.0
     *
     * @access public
     *
     */

    public function enqueue_assets($hook)
    {
        global $rsssl_admin_page;
        //prevent from loading on other pages than settings page.
        if ((!is_network_admin() && ($hook != $rsssl_admin_page)) && $this->ssl_enabled)
            return;

        wp_register_style('rlrsssl-css', trailingslashit(rsssl_url) . 'css/main.css', "", rsssl_version);
        wp_enqueue_style('rlrsssl-css');
    }


    /*

        feedback for the free users. Pro users see something different.

  */


    public function configuration_page_more()
    {
        ?>
        <table>
            <tr>
                <td>
                    <?php echo $this->contains_hsts() ? $this->img("success") : $this->img("warning"); ?>
                </td>
                <td>
                    <?php
                    if ($this->contains_hsts()) {
                        _e("HTTP Strict Transport Security was enabled", "really-simple-ssl");
                    } else {

                        $wiki_open = '<a href="https://en.wikipedia.org/wiki/HTTP_Strict_Transport_Security" target="_blank">';
                        $link_open = '<a target="_blank" href="' . $this->pro_url . '">';
                        $link_close = '</a>';

                        printf(__('%sHTTP Strict Transport Security%s is not enabled.', "really-simple-ssl"), $wiki_open, $link_close);
                        echo "&nbsp;";
                        printf(__("To enable, %sget Premium%s ", "really-simple-ssl"), $link_open, $link_close);
                    }
                    ?>
                </td>
                <td></td>
            </tr>
            <tr>

                <td><?php echo ($this->contains_secure_cookie_settings()) ? $this->img("success") : $this->img("warning"); ?></td>
                <td><?php
                    if ($this->contains_secure_cookie_settings()) {
                        _e("Secure cookies set", "really-simple-ssl") . "&nbsp;";
                    } else {

                        $link_open = '<a target="_blank" href="' . $this->pro_url . '">';
                        $link_close = '</a>';

                        _e('Secure cookie settings not enabled.', "really-simple-ssl");
                        echo "&nbsp;";
                        printf(__("To enable, %sget Premium%s ", "really-simple-ssl"), $link_open, $link_close);
                    }
                    ?>
                </td>
                <td></td>
            </tr>
            <?php
            /**
            ?>
            <tr>

            <td><?php echo (defined('rsssl_pro_path')) ? $this->img("success") : $this->img("warning"); ?></td>
            <td>
            <?php
                $link_open = '<a target="_blank" href="' . $this->pro_url . '">';
                $link_close = '</a>';

                _e('Content Security Policy violation reporting not enabled.', "really-simple-ssl");
                echo "&nbsp;";
                printf(__("To enable, %sget Premium%s ", "really-simple-ssl"), $link_open, $link_close);

            ?>
            </td>
            </tr>
            **/?>
        </table>



        <?php

        if (!$this->site_has_ssl) {
            $this->show_pro();
        } else {
            if (!$this->ssl_enabled) { ?>
                <p><?php _e("If you want to be sure you're ready to migrate to SSL, get Premium, which includes an extensive scan and premium support.", "really-simple-ssl") ?>
                    &nbsp;<a target="_blank"
                             href="<?php echo $this->pro_url ?>"><?php _e("Learn more", "really-simple-ssl") ?></a></p>
            <?php } else { ?>
                <p><?php _e('Still having issues with mixed content? Check out Premium, which includes an extensive scan and premium support. ', "really-simple-ssl") ?>
                    &nbsp;<a target="_blank"
                             href="<?php echo $this->pro_url ?>"><?php _e("Learn more", "really-simple-ssl") ?></a></p>
                <?php
            }
        }
    }

    /**
     * Create the settings page form
     *
     * @since  2.0
     *
     * @access public
     *
     */

    public function create_form()
    {
        register_setting('rlrsssl_options', 'rlrsssl_options', array($this, 'options_validate'));
        add_settings_section('rlrsssl_settings', __("Settings", "really-simple-ssl"), array($this, 'section_text'), 'rlrsssl');
        add_settings_field('id_autoreplace_insecure_links', __("Mixed content fixer", "really-simple-ssl"), array($this, 'get_option_autoreplace_insecure_links'), 'rlrsssl', 'rlrsssl_settings');

        //only show option to enable or disable mixed content and redirect when SSL is detected
        if ($this->ssl_enabled) {
            add_settings_field('id_wp_redirect', __("Enable WordPress 301 redirection to SSL", "really-simple-ssl"), array($this, 'get_option_wp_redirect'), 'rlrsssl', 'rlrsssl_settings');

            //when enabled networkwide, it's handled on the network settings page
            if (RSSSL()->rsssl_server->uses_htaccess() && (!is_multisite() || !RSSSL()->rsssl_multisite->ssl_enabled_networkwide)) {
                add_settings_field('id_htaccess_redirect', __("Enable 301 .htaccess redirect", "really-simple-ssl"), array($this, 'get_option_htaccess_redirect'), 'rlrsssl', 'rlrsssl_settings');
            }

            add_settings_field('id_javascript_redirect', __("Enable Javascript redirection to SSL", "really-simple-ssl"), array($this, 'get_option_javascript_redirect'), 'rlrsssl', 'rlrsssl_settings');
        }

        add_settings_field('id_debug', __("Debug", "really-simple-ssl"), array($this, 'get_option_debug'), 'rlrsssl', 'rlrsssl_settings');
        //on multisite this setting can only be set networkwide
        if (RSSSL()->rsssl_server->uses_htaccess() && !is_multisite()) {
            add_settings_field('id_do_not_edit_htaccess', __("Stop editing the .htaccess file", "really-simple-ssl"), array($this, 'get_option_do_not_edit_htaccess'), 'rlrsssl', 'rlrsssl_settings');
        }

        add_settings_field('id_switch_mixed_content_fixer_hook', __("Switch mixed content fixer hook", "really-simple-ssl"), array($this, 'get_option_switch_mixed_content_fixer_hook'), 'rlrsssl', 'rlrsssl_settings');

        add_settings_field('id_deactivate_keep_ssl', __("Deactivate plugin and keep SSL", "really-simple-ssl"), array($this, 'get_option_deactivate_keep_ssl'), 'rlrsssl', 'rlrsssl_settings');


    }

    /**
     * Insert some explanation above the form
     *
     * @since  2.0
     *
     * @access public
     *
     */

    public function section_text()
    {
        ?>
        <p><?php _e('Settings to optimize your SSL configuration', 'really-simple-ssl'); ?></p>
        <?php
    }

    /**
     * Check the posted values in the settings page for validity
     *
     * @since  2.0
     *
     * @access public
     *
     */

    public function options_validate($input)
    {
        //fill array with current values, so we don't lose any
        $newinput = array();
        $newinput['site_has_ssl'] = $this->site_has_ssl;
        $newinput['ssl_success_message_shown'] = $this->ssl_success_message_shown;
        $newinput['htaccess_warning_shown'] = $this->htaccess_warning_shown;
        $newinput['review_notice_shown'] = $this->review_notice_shown;
        $newinput['plugin_db_version'] = $this->plugin_db_version;
        $newinput['ssl_enabled'] = $this->ssl_enabled;
        $newinput['debug_log'] = $this->debug_log;

        if (!empty($input['hsts']) && $input['hsts'] == '1') {
            $newinput['hsts'] = TRUE;
        } else {
            $newinput['hsts'] = FALSE;
        }

        if (!empty($input['javascript_redirect']) && $input['javascript_redirect'] == '1') {
            $newinput['javascript_redirect'] = TRUE;
        } else {
            $newinput['javascript_redirect'] = FALSE;
        }

        if (!empty($input['wp_redirect']) && $input['wp_redirect'] == '1') {
            $newinput['wp_redirect'] = TRUE;
        } else {
            $newinput['wp_redirect'] = FALSE;
        }

        if (!empty($input['autoreplace_insecure_links']) && $input['autoreplace_insecure_links'] == '1') {
            $newinput['autoreplace_insecure_links'] = TRUE;
        } else {
            $newinput['autoreplace_insecure_links'] = FALSE;
        }

        if (!empty($input['debug']) && $input['debug'] == '1') {
            $newinput['debug'] = TRUE;
        } else {
            $newinput['debug'] = FALSE;
            $this->debug_log = "";
        }

        if (!empty($input['do_not_edit_htaccess']) && $input['do_not_edit_htaccess'] == '1') {
            $newinput['do_not_edit_htaccess'] = TRUE;
        } else {
            $newinput['do_not_edit_htaccess'] = FALSE;
        }

        if (!empty($input['switch_mixed_content_fixer_hook']) && $input['switch_mixed_content_fixer_hook'] == '1') {
            $newinput['switch_mixed_content_fixer_hook'] = TRUE;
        } else {
            $newinput['switch_mixed_content_fixer_hook'] = FALSE;
        }

        if (!empty($input['htaccess_redirect']) && $input['htaccess_redirect'] == '1') {
            $newinput['htaccess_redirect'] = TRUE;
        } else {
            $newinput['htaccess_redirect'] = FALSE;
        }

        return $newinput;
    }

    /**
     * Insert option into settings form
     * deprecated
     * @since  2.0
     *
     * @access public
     *
     */

    public function get_option_debug()
    {

        ?>
        <label class="rsssl-switch">
            <input id="rlrsssl_options" name="rlrsssl_options[debug]" size="40" value="1"
                   type="checkbox" <?php checked(1, $this->debug, true) ?> />
            <span class="rsssl-slider rsssl-round"></span>
        </label>
        <?php
        RSSSL()->rsssl_help->get_help_tip(__("Enable this option to get debug info in the debug tab.", "really-simple-ssl"));

    }

    /**
     * Insert option into settings form
     * @since  2.2
     *
     * @access public
     *
     */

    public function get_option_javascript_redirect()
    {
        $javascript_redirect = $this->javascript_redirect;
        $disabled = "";
        $comment = "";

        if (is_multisite() && rsssl_multisite::this()->javascript_redirect) {
            $disabled = "disabled";
            $javascript_redirect = TRUE;
            $comment = __("This option is enabled on the network menu.", "really-simple-ssl");
        }

        ?>
        <label class="rsssl-switch">
            <input id="rlrsssl_options" name="rlrsssl_options[javascript_redirect]" size="40" value="1"
                   type="checkbox" <?php checked(1, $javascript_redirect, true) ?> />
            <span class="rsssl-slider rsssl-round"></span>
        </label>
        <?php
        RSSSL()->rsssl_help->get_help_tip(__("This is a fallback you should only use if other redirection methods do not work.", "really-simple-ssl"));
        echo $comment;

    }

    /**
     * Insert option into settings form
     * @since  2.5.0
     *
     * @access public
     *
     */

    public function get_option_wp_redirect()
    {
        $wp_redirect = $this->wp_redirect;
        $disabled = "";
        $comment = "";

        if (is_multisite() && rsssl_multisite::this()->wp_redirect) {
            $disabled = "disabled";
            $wp_redirect = TRUE;
            $comment = __("This option is enabled on the network menu.", "really-simple-ssl");
        }

        ?>
        <label class="rsssl-switch">
            <input id="rlrsssl_options" name="rlrsssl_options[wp_redirect]" size="40" value="1"
                   type="checkbox" <?php checked(1, $wp_redirect, true) ?> />
            <span class="rsssl-slider rsssl-round"></span>
        </label>
        <?php
        RSSSL()->rsssl_help->get_help_tip(__("Enable this if you want to use the internal WordPress 301 redirect. Needed on NGINX servers, or if the .htaccess redirect cannot be used.", "really-simple-ssl"));
        echo $comment;

    }


    /**
     * Insert option into settings form
     * The .htaccess redirect is not shown for multisite sites that are enabled network wide.
     *
     * @since  2.5.8
     *
     * @access public
     *
     */

    public function get_option_htaccess_redirect()
    {

        $options = get_option('rlrsssl_options');

        $htaccess_redirect = $this->htaccess_redirect;
        $disabled = "";
        $comment = "";

        //networkwide is not shown, so this only applies to per site activated sites.
        if (is_multisite() && RSSSL()->rsssl_multisite->htaccess_redirect) {
            $disabled = "disabled";
            $htaccess_redirect = TRUE;
            $comment = __("This option is enabled on the network menu.", "really-simple-ssl");
        } else {
            $disabled = ($this->do_not_edit_htaccess) ? "disabled" : "";
        }

        ?>
        <label class="rsssl-switch">
            <input id="rlrsssl_options" name="rlrsssl_options[htaccess_redirect]" size="40" value="1"
                   type="checkbox" <?php checked(1, $this->htaccess_redirect, true) ?> />
            <span class="rsssl-slider rsssl-round"></span>
        </label>
        <?php
        RSSSL()->rsssl_help->get_help_tip(__("A .htaccess redirect is faster. Really Simple SSL detects the redirect code that is most likely to work (99% of websites), but this is not 100%. Make sure you know how to regain access to your site if anything goes wrong!", "really-simple-ssl"));
        echo $comment;

        if ($this->htaccess_redirect && (!is_writable($this->htaccess_file()) || !$this->htaccess_test_success)) {
            echo "<br><br>";
            if (!is_writable($this->htaccess_file())) _e("The .htaccess file is not writable. Add these lines to your .htaccess manually, or set 644 writing permissions", "really-simple-ssl");
            if (!$this->htaccess_test_success) _e("The .htaccess redirect rules that were selected by this plugin failed in the test. The following redirect rules were tested:", "really-simple-ssl");
            echo "<br><br>";
            if ($this->ssl_type != "NA") {
                $manual = true;
                $rules = $this->get_redirect_rules($manual);

                $arr_search = array("<", ">", "\n");
                $arr_replace = array("&lt", "&gt", "<br>");
                $rules = str_replace($arr_search, $arr_replace, $rules);

                ?>
                <code>
                    <?php echo $rules; ?>
                </code>
                <?php
            } else {
                _e("The plugin could not detect any possible redirect rule.", "really-simple-ssl");
            }
        }

        //on multisite, the .htaccess do not edit option is not available
        if (!is_multisite()) {
            if ($this->do_not_edit_htaccess) {
                _e("If the setting 'do not edit htaccess' is enabled, you can't change this setting.", "really-simple-ssl");
            } elseif (!$this->htaccess_redirect) {
                $link_start = '<a target="_blank" href="https://really-simple-ssl.com/knowledge-base/remove-htaccess-redirect-site-lockout/">';
                $link_end = '</a>';
                printf(
                    __('Before you enable this, make sure you know how to %1$sregain access%2$s to your site in case of a redirect loop.', 'really-simple-ssl'),
                    $link_start,
                    $link_end
                );
            }
        }

    }

    /**
     * Insert option into settings form
     *
     * @since  2.0
     *
     * @access public
     *
     */

    public function get_option_do_not_edit_htaccess()
    {
        ?>
        <label class="rsssl-switch">
            <input id="rlrsssl_options" name="rlrsssl_options[do_not_edit_htaccess]" size="40" value="1"
                   type="checkbox" <?php checked(1, $this->do_not_edit_htaccess, true) ?> />
            <span class="rsssl-slider rsssl-round"></span>
        </label>
        <?php
        RSSSL()->rsssl_help->get_help_tip(__("If you want to customize the Really Simple SSL .htaccess, you need to prevent Really Simple SSL from rewriting it. Enabling this option will do that.", "really-simple-ssl"));
        if (!$this->do_not_edit_htaccess && !is_writable($this->htaccess_file())) _e(".htaccess is currently not writable.", "really-simple-ssl");
    }

    /**
     * Insert option into settings form
     *
     * @since  2.1
     *
     * @access public
     *
     */

    public function get_option_switch_mixed_content_fixer_hook()
    {

        ?>
        <label class="rsssl-switch">
            <input id="rlrsssl_options" name="rlrsssl_options[switch_mixed_content_fixer_hook]" size="40" value="1"
                   type="checkbox" <?php checked(1, $this->switch_mixed_content_fixer_hook, true) ?> />
            <span class="rsssl-slider rsssl-round"></span>
        </label>
        <?php
        RSSSL()->rsssl_help->get_help_tip(__("If this option is set to true, the mixed content fixer will fire on the init hook instead of the template_redirect hook. Only use this option when you experience problems with the mixed content fixer.", "really-simple-ssl"));
    }

    /*
     *
     * Add a button and thickbox to deactivate SSL while keeping SSL
     *
     *
     *
     */


    public function get_option_deactivate_keep_ssl()
    {

        ?>
        <div><input class="thickbox button" title="" type="button" style="display: block; float: left;" alt="#TB_inline?
        height=370&width=400&inlineId=deactivate_keep_ssl" value="<?php echo __('Deactivate Plugin and keep SSL', 'really-simple-ssl'); ?>"/></div>
        <div id="deactivate_keep_ssl" style="display: none;">

            <h1 style="margin: 10px 0; text-align: center;"><?php _e("Are you sure?", "really-simple-ssl") ?></h1>
            <h2 style="margin: 20px 0; text-align: left;"><?php _e("Deactivating the plugin while keeping SSL will do the following:", "really-simple-ssl") ?></h2>
            <ul style="text-align: left; font-size: 1.2em;">
                <li><?php _e("* The mixed content fixer will stop working", "really-simple-ssl") ?></li>
                <li><?php _e("* The WordPress 301 and Javascript redirect will stop working", "really-simple-ssl") ?></li>
                <li><?php _e("* Your site address will remain https://", "really-simple-ssl") ?> </li>
                <li><?php _e("* The .htaccess redirect will remain active", "really-simple-ssl") ?></li>
                <?php _e("Deactivating the plugin via the plugins overview will revert the site back to http://.", "really-simple-ssl") ?>
            </ul>

            <script>
                jQuery(document).ready(function ($) {
                    $('#rsssl_close_tb_window').click(tb_remove);
                });
            </script>
            <?php
            $token = wp_create_nonce('rsssl_deactivate_plugin');
            $deactivate_keep_ssl_link = admin_url("options-general.php?page=rlrsssl_really_simple_ssl&action=uninstall_keep_ssl&token=" . $token);

            ?>
            <a class="button rsssl-button-deactivate-keep-ssl" href="<?php add_thickbox() ?>
                <?php echo $deactivate_keep_ssl_link ?>"><?php _e("I'm sure I want to deactivate", "really-simple-ssl") ?>
            </a>
            &nbsp;&nbsp;
            <a class="button" href="#" id="rsssl_close_tb_window"><?php _e("Cancel", "really-simple-ssl") ?></a>


        </div>
        <?php
        RSSSL()->rsssl_help->get_help_tip(__("Clicking this button will deactivate the plugin while keeping your site on SSL. The WordPress 301 redirect, Javascript redirect and mixed content fixer will stop working. The site address will remain https:// and the .htaccess redirect will remain active. Deactivating the plugin via the plugins overview will revert the site back to http://.", "really-simple-ssl"));

    }

    public function get_option_autoreplace_insecure_links()
    {
        //$options = get_option('rlrsssl_options');
        $autoreplace_mixed_content = $this->autoreplace_insecure_links;
        $disabled = "";
        $comment = "";

        if (is_multisite() && rsssl_multisite::this()->autoreplace_mixed_content) {
            $disabled = "disabled";
            $autoreplace_mixed_content = TRUE;
            $comment = __("This option is enabled on the network menu.", "really-simple-ssl");
        }

        ?>
        <label class="rsssl-switch">
            <input id="rlrsssl_options" name="rlrsssl_options[autoreplace_insecure_links]" size="40" value="1"
                   type="checkbox" <?php checked(1, $autoreplace_mixed_content, true) ?> />
            <span class="rsssl-slider rsssl-round"></span>
        </label>
        <?php
        RSSSL()->rsssl_help->get_help_tip(__("In most cases you need to leave this enabled, to prevent mixed content issues on your site.", "really-simple-ssl"));
        echo $comment;
    }

    /**
     * Add settings link on plugins overview page
     *
     * @since  2.0
     *
     * @access public
     *
     */


    public function plugin_settings_link($links)
    {

        //add 'revert to http' after the Deactivate link on the plugins overview page
        if (isset($links['deactivate'])) {
            $deactivate_link = $links['deactivate'];
            $links['deactivate'] = str_replace('</a>', "&nbsp" . __("(revert to http)", "really-simple-ssl") . '</a>', $deactivate_link);
        }

        $settings_link = '<a href="' . admin_url("options-general.php?page=rlrsssl_really_simple_ssl") . '">' . __("Settings", "really-simple-ssl") . '</a>';
        array_unshift($links, $settings_link);

        $faq_link = '<a target="_blank" href="https://really-simple-ssl.com/knowledge-base/">' . __('Docs', 'really-simple-ssl') . '</a>';
        array_unshift($links, $faq_link);

        if (defined("rsssl_pro_version")) {
            if (class_exists('RSSSL_PRO')) {
                if (RSSSL_PRO()->rsssl_licensing->license_is_valid()) return $links;
            }
        }
        if (!defined("rsssl_pro_version")) {
            if (!class_exists('RSSSL_PRO')) {
                $premium_link = '<a target="_blank" href="https://really-simple-ssl.com/premium-support">' . __('Premium Support', 'really-simple-ssl') . '</a>';
                array_unshift($links, $premium_link);
            }
        }
        return $links;
    }

    /**
     * Check for possible plugin conflicts
     *
     * @since  2.0
     *
     * @access public
     * @return none
     *
     */

    public function check_plugin_conflicts()
    {
        // $this->plugin_conflict["WOOCOMMERCE_FORCESSL"] = TRUE;
    }


    /**
     * Check if wpconfig contains httponly cooky settings
     *
     * @since  2.5
     *
     * @access public
     * @return boolean
     *
     */

    public function contains_secure_cookie_settings()
    {
        $wpconfig_path = $this->find_wp_config_path();

        if (!$wpconfig_path) return false;

        $wpconfig = file_get_contents($wpconfig_path);
        if ((strpos($wpconfig, "//Begin Really Simple SSL session cookie settings") === FALSE) && (strpos($wpconfig, "cookie_httponly") === FALSE)) {
            return false;
        }

        return true;
    }


    /**
     * Get the absolute path the the www directory of this site, where .htaccess lives.
     *
     * @since  2.0
     *
     * @access public
     *
     */

    public function getABSPATH()
    {
        $path = ABSPATH;
        if ($this->is_subdirectory_install()) {
            $siteUrl = site_url();
            $homeUrl = home_url();
            $diff = str_replace($homeUrl, "", $siteUrl);
            $diff = trim($diff, "/");
            $pos = strrpos($path, $diff);
            if ($pos !== false) {
                $path = substr_replace($path, "", $pos, strlen($diff));
                $path = trim($path, "/");
                $path = "/" . $path . "/";
            }
        }

        return $path;
    }

    /**
     * Find if this WordPress installation is installed in a subdirectory
     *
     * @since  2.0
     *
     * @access protected
     *
     */

    protected function is_subdirectory_install()
    {
        if (strlen(site_url()) > strlen(home_url())) {
            return true;
        }
        return false;
    }

    /*
     * Retrieve the contents of the test page
     */


    protected function get_test_page_contents()
    {

        $filecontents = get_transient('rsssl_testpage');
        if (!$filecontents) {
            $filecontents = "";

            $testpage_url = trailingslashit($this->test_url()) . "ssl-test-page.php";
            $this->trace_log("Opening testpage to check server configuration: " . $testpage_url);

            $response = wp_remote_get($testpage_url);

            if (is_array($response)) {
                $status = wp_remote_retrieve_response_code($response);
                $filecontents = wp_remote_retrieve_body($response);
            }

            $this->trace_log("test page url, enter in browser to check manually: " . $testpage_url);

            if (!is_wp_error($response) && (strpos($filecontents, "#SSL TEST PAGE#") !== false)) {

                $this->trace_log("SSL test page loaded successfully");
            } else {

                $error = "";
                if (is_wp_error($response)) $error = $response->get_error_message();
                $this->trace_log("Could not open testpage " . $error);
            }
            if (empty($filecontents)) {
                $filecontents = 'not-valid';
            }
            set_transient('rsssl_testpage', $filecontents, 600);
        }
        return $filecontents;
    }

    /**
     *
     * @return string
     *
     * since 3.1
     *
     * Determine dirname to show in admin_notices() in really-simple-ssl-pro.php to show a warning when free folder has been renamed
     */

    public function get_current_rsssl_free_dirname() {
        return basename( __DIR__ );
    }

    /**
     * @return string
     *
     * since 3.1
     *
     * Determine the htaccess file. This can be either the regular .htaccess file, or an htaccess.conf file on bitnami installations.
     *
     *
     */

    public function htaccess_file() {
        if ($this->uses_htaccess_conf()) {
            $htaccess_file = realpath(dirname(ABSPATH) . "/conf/htaccess.conf");
        } else {
            $htaccess_file = $this->ABSpath . ".htaccess";
        }

        return $htaccess_file;
    }

} //class closure
