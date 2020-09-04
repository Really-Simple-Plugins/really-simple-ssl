<?php
defined('ABSPATH') or die("you do not have access to this page!");

class rsssl_admin extends rsssl_front_end
{

    private static $_this;
    public $grid_items;

    public $wpconfig_siteurl_not_fixed = FALSE;
    public $no_server_variable = FALSE;
    public $errors = Array();
    public $tasks = array();

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
    public $dismiss_review_notice = FALSE;
    public $ssl_success_message_shown = FALSE;
    public $hsts = FALSE;
    public $debug = TRUE;
    public $debug_log;

    public $plugin_conflict = ARRAY();
    public $plugin_db_version;
    public $plugin_upgraded;
    public $mixed_content_fixer_status = "OK";
    public $ssl_type = "NA";
    public $dismiss_all_notices = false;

    public $pro_url;

    function __construct()
    {

	    if (isset(self::$_this))
            wp_die(sprintf(__('%s is a singleton class and you cannot create a second instance.', 'really-simple-ssl'), get_class($this)));

        self::$_this = $this;

        $this->ABSpath = $this->getABSPATH();
        $this->get_options();
        $this->get_admin_options();

        $this->get_plugin_upgraded(); //call always, otherwise db version will not match anymore.

	    if (isset($_GET['rsssl_dismiss_review_notice'])){
		    $this->get_dismiss_review_notice();
	    }

	    if (is_multisite()) {
	        $this->pro_url = 'https://really-simple-ssl.com/pro-multisite';
        } else {
	        $this->pro_url = 'https://really-simple-ssl.com/pro';
        }

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

    public function get_dismiss_review_notice() {
        $this->review_notice_shown = true;
        $this->dismiss_review_notice = true;
        $this->save_options();
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

        // Set default progress toggle to remaining tasks if it hasn't been set
        if (!get_option('rsssl_all_tasks') && !get_option('rsssl_remaining_tasks') ) {
            update_option('rsssl_remaining_tasks', true);
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
//        add_action('rsssl_activation_notice', array($this, 'no_ssl_detected'), 10);
        add_action('rsssl_activation_notice', array($this, 'ssl_detected'), 10);
        add_action('rsssl_activation_notice_inner', array($this, 'almost_ready_to_migrate'), 30);
        add_action('rsssl_activation_notice_inner', array($this, 'show_enable_ssl_button'), 50);

        add_action('plugins_loaded', array($this, 'check_plugin_conflicts'), 30);

        //add the settings page for the plugin
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_init', array($this, 'load_translation'), 20);

        //settings page, form  and settings link in the plugins page
        add_action('admin_menu', array($this, 'add_settings_page'), 40);
	    add_action('admin_init', array($this, 'create_form'), 40);
        add_action('admin_init', array($this, 'listen_for_deactivation'), 40);

	    //Only redirect while on own settings page, otherwise deactivate link in plugins overview will break.
	    //if ($this->is_settings_page()) {
		    add_action( 'update_option_rlrsssl_options', array( $this, 'maybe_remove_highlight_from_url' ), 50 );
        //}

        $plugin = rsssl_plugin;
        add_filter("plugin_action_links_$plugin", array($this, 'plugin_settings_link'));

        //Add update notification to Settings admin menu
        add_action('admin_menu', array($this, 'rsssl_edit_admin_menu') );

        //check if the uninstallfile is safely renamed to php.
        $this->check_for_uninstall_file();

        //callbacks for the ajax dismiss buttons
        add_action('wp_ajax_dismiss_htaccess_warning', array($this, 'dismiss_htaccess_warning_callback'));
        add_action('wp_ajax_dismiss_success_message', array($this, 'dismiss_success_message_callback'));
        add_action('wp_ajax_rsssl_dismiss_review_notice', array($this, 'dismiss_review_notice_callback'));
        add_action('wp_ajax_rsssl_dismiss_settings_notice', array($this, 'dismiss_settings_notice_callback'));
        add_action('wp_ajax_rsssl_get_updated_percentage', array($this, 'get_score_percentage'));
        add_action('wp_ajax_rsssl_get_updated_task_count', array($this, 'get_remaining_tasks_count'));
        add_action('wp_ajax_rsssl_update_task_toggle_option', array($this, 'update_task_toggle_option'));


        //handle notices
        add_action('admin_notices', array($this, 'show_notices'));
        //show review notice, only to free users
        if (!defined("rsssl_pro_version") && (!defined("rsssl_pp_version")) && (!defined("rsssl_soc_version")) && (!class_exists('RSSSL_PRO')) && (!is_multisite())) {
            add_action('admin_notices', array($this, 'show_leave_review_notice'));
        }
        add_action("update_option_rlrsssl_options", array($this, "update_htaccess_after_settings_save"), 20, 3);
//        add_action('shutdown', array($this, 'redirect_to_settings_page'));

    }

    public function check_upgrade() {

        $prev_version = get_option( 'rsssl_current_version', false );

        if ( $prev_version && version_compare( $prev_version, '4.0', '<' ) ) {
            update_option('rsssl_remaining_tasks', true);
        }
        update_option( 'rsssl_current_version', rsssl_version );
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
                    RSSSL()->rsssl_multisite->switch_to_blog_bw_compatible($site);

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
        //conf/htaccess.conf can be outside of open basedir, return false if so
        $open_basedir = ini_get("open_basedir");

        if (!empty($open_basedir)) return false;

        if (is_file($htaccess_conf_file) ) {
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

    public function redirect_to_settings_page() {
        if (isset($_GET['page']) && $_GET['page'] == 'rlrsssl_really_simple_ssl') return;
	        $url = add_query_arg( array(
		        "page" => "rlrsssl_really_simple_ssl",
	        ), admin_url( "options-general.php" ) );
	        wp_redirect( $url );
	        exit;
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
        if ($this->site_has_ssl) {
	        ?>
            <style>
                .activate-ssl {
                    border-left: 4px solid #F8BE2E;
                }
                .activate-ssl .button {
                    margin-bottom: 5px;
                    margin-right: 10px;
                }
                <?php echo apply_filters('rsssl_pro_inline_style', ''); ?>
            </style>
            <div id="message" class="notice activate-ssl <?php echo apply_filters('rsssl_activate_notice_class', '');?>">
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
            <style>
                .rsssl-notice {
                    background-color: #fff;
                    border-left: 4px solid #F8BE2E;
                    padding: 1px 15px;
                }
            </style>
            <div id="message" class="error notice rsssl-notice-certificate">
                <h1><?php echo __("Detected possible certificate issues", "really-simple-ssl"); ?></h1>
                <p>
                    <?php
                    $reload_https_url = esc_url_raw("https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
                    $link_open = '<p><a class="button" target="_blank" href="' . $reload_https_url . '">';
                    $link_close = '</a></p>';

                    printf(__("Really Simple SSL failed to detect a valid SSL certificate. If you do have an SSL certificate, try to reload this page over https by clicking this button: %sReload over https%s The built-in certificate check will run once daily, to force a new certificate check visit the SSL settings page. ", "really-simple-ssl"), $link_open, $link_close);

                    $ssl_test_url = "https://www.ssllabs.com/ssltest/";
                    $link_open = '<a target="_blank" href="' . $ssl_test_url . '">';
                    $link_close = '</a>';

                    printf(__("Really Simple SSL requires a valid SSL certificate. You can check your certificate on %sQualys SSL Labs%s.", "really-simple-ssl"), $link_open, $link_close);
                    echo "<br><br>";
                    printf(__("If your site has cPanel, you can %sget a free SSL certificate%s. ", "really-simple-ssl"), '<a target="_blank" href="https://really-simple-ssl.com/knowledge-base/how-to-install-a-free-ssl-certificate-on-your-wordpress-cpanel-hosting/">', '</a>');

                    ?>
                </p>
            </div>
        <?php }
    }

    public function almost_ready_to_migrate()
    {
        ?>
        <div class="rsssl-activation-notice">
            <div class="rsssl-activation-notice-header">
                <h1><?php _e("Almost ready to migrate to SSL!", "really-simple-ssl"); ?></h1>
                <div id="rsssl-logo-activation"><img width="180px" src="<?php echo rsssl_url?>/assets/logo-really-simple-ssl.png" alt="really-simple-ssl-logo"></div>
            </div>
            <div class="rsssl-activation-notice-content">
                <?php _e("Some things can't be done automatically. Before you migrate, please check for: ", 'really-simple-ssl'); ?>
                <p>
                    <ul class="message-ul">
                        <li class="rsssl-activation-notice-li"><div class="rsssl-bullet"></div><?php _e('Http references in your .css and .js files: change any http:// into //', 'really-simple-ssl'); ?></li>
                        <li class="rsssl-activation-notice-li"><div class="rsssl-bullet"></div><?php _e('Images, stylesheets or scripts from a domain without an SSL certificate: remove them or move to your own server', 'really-simple-ssl'); ?></li><?php

                        $backup_link = "https://really-simple-ssl.com/knowledge-base/backing-up-your-site/";
                        $link_open = '<a target="_blank" href="'.$backup_link.'">';
                        $link_close = '</a>';
                        ?>
                        <li class="rsssl-activation-notice-li"><div class="rsssl-bullet"></div><?php printf(__("We strongly recommend to take a %sbackup%s of your site before activating SSL", 'really-simple-ssl'), $link_open, $link_close); ?> </li>
                        <li class="rsssl-activation-notice-li"><div class="rsssl-bullet"></div><?php _e("You may need to login in again.", "really-simple-ssl") ?></li>
                    </ul>
                <p><?php _e('You can also let the automatic scan of the pro version handle this for you, and get premium support, increased security with HSTS and more!', 'really-simple-ssl'); ?>
                    <a target="_blank"
                       href="<?php echo $this->pro_url; ?>"><?php _e("Check out Really Simple SSL Pro", "really-simple-ssl"); ?></a>
                </p>
                </p>
            </div>
        </div>
        <style>
            .rsssl-activation-notice-header {
                height: 60px;
                border-bottom: 1px solid #dedede;
                display: flex;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }

            .rsssl-activation-notice-content {
                margin-top: 10px;
                border-bottom: 1px solid #dedede;
            }

            .rsssl-activation-notice-footer {
                height: 35px;
                display: flex;
                align-items: center;
            }

            .rsssl-activation-notice-li {
                display: flex;
                align-items: center;
            }

            .rsssl-bullet {
                border-radius: 50%;
                height: 13px;
                width: 13px;
                background-color: #FBC43D;
                margin-right: 10px;
            }

            .message-ul {
                list-style-type: none;
            }

            #message .message-li::before {
                vertical-align: middle;
                margin-right: 25px;
                color: lightgrey;
                content: "\f345";
                font: 400 21px/1 dashicons;
            }
        </style>
        <?php
    }

    /**
     * @since 2.3
     * Returns button to enable SSL.
     * @access public
     */

    public function show_enable_ssl_button()
    {
        if ($this->site_has_ssl || (defined('RSSSL_FORCE_ACTIVATE') && RSSSL_FORCE_ACTIVATE)) {
            ?>
            <style>
                .button-rsssl-secondary {
                    border-color: @button-border-color;
                    color: #7B8CB7;
                    background-color: #fff;
                }
            </style>
            <p>
            <div class="rsssl-activation-notice-footer">
            <div class="rsssl-activate-ssl-button">
            <form action="" method="post">
                <?php wp_nonce_field('rsssl_nonce', 'rsssl_nonce'); ?>
                <input type="submit" class='button button-primary'
                       value="<?php _e("Go ahead, activate SSL!", "really-simple-ssl"); ?>" id="rsssl_do_activate_ssl"
                       name="rsssl_do_activate_ssl">
                <?php if (!defined("rsssl_pro_version") ) { ?>
                <a class="button button-rsssl-secondary" href="<?php echo $this->pro_url ?>" target="_blank"><?php _e("Get ready with PRO!", "really-simple-ssl"); ?></a>
                <?php } ?>
            </form>
            </div>
            </p>
            </div>
            <?php
        }
    }

    /**
     * @return bool
     *
     * Check if wp-config.php is writeable
     *
     * @access public
     */

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
	        $this->dismiss_all_notices = isset($options['dismiss_all_notices']) ? $options['dismiss_all_notices'] : FALSE;
	        $this->debug_log = isset($options['debug_log']) ? $options['debug_log'] : $this->debug_log;
            $this->dismiss_review_notice = isset($options['dismiss_review_notice']) ? $options['dismiss_review_notice'] : $this->dismiss_review_notice;
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
     * @param string $msg
     *
     * @since  2.1
     *
     * @access public
     *
     */

    public function trace_log($msg)
    {
        if (!$this->debug) return;
        if (strpos($this->debug_log, $msg)) return;
        $this->debug_log = $this->debug_log . "<br>" . $msg;
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
        $this->trace_log("<br>" . "<b>" . "SSL Configuration" . "</b>");
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

	        if (!is_multisite()) {
		        $this->redirect_to_settings_page();
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


    /**
     * @return string
     *
     * Get code for server variable fix
     *
     * @access protected
     *
     */

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
        $this->reset_plusone_cache();
	    $this->reset_open_remaining_task_cache();

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
            'dismiss_all_notices' => $this->dismiss_all_notices,
            'dismiss_review_notice' => $this->dismiss_review_notice,

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
	    $this->dismiss_all_notices = FALSE;
	    $this->dismiss_review_notice = FALSE;


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
        $this->trace_log("<b>" . "Detecting configuration" . "</b>");
        //if current page is on SSL, we can assume SSL is available, even when an errormsg was returned
        if ($this->is_ssl_extended()) {
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
            } elseif ((strpos($filecontents, "#CLOUDFLARE#") !== false) || (isset($_SERVER['HTTP_CF_VISITOR']) && (strpos($_SERVER["HTTP_CF_VISITOR"], "https") !== false))) {
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
                default:
                    $testpage_url .= "serverhttpson";
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

    /**
     * @return bool|string
     *
     * Get the .htaccess version
     *
     * @access public
     *
     */

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

	/**
	 * @return bool
     *
     * Check if the .htaccess redirect is allowed on this setup
     *
     * @since 2.0
     *
	 */

    public function htaccess_redirect_allowed()
    {
        if (is_multisite() && RSSSL()->rsssl_multisite->is_per_site_activated_multisite_subfolder_install()) {
            return false;
        } if (RSSSL()->rsssl_server->uses_htaccess()) {
            return true;
        } else {
            return false;
        }
    }

	/**
	 * @return bool
     *
     * Checks if the htaccess contains redirect rules, either actual redirect or a rsssl marker.
     *
     * @since 2.0
     *
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

	/**
	 * @return bool
     *
     * Checks if a 301 redirect is set
	 * this is the case if either the wp_redirect is set, or the htaccess redirect is set.
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

	/**
	 * @param bool $oldvalue
	 * @param bool $newvalue
	 * @param bool $option
     *
     * Update the .htaccess file after saving settings
     *
	 */

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
     * Check if the mixed content fixer is functioning on the front end, by scanning the source of the homepage for the fixer comment.
     * @access public
     * @return string $mixed_content_fixer_detected
     */

    public function mixed_content_fixer_detected()
    {
        $status = 0;

        $mixed_content_fixer_detected = get_transient('rsssl_mixed_content_fixer_detected');

        if (!$mixed_content_fixer_detected) {

            $web_source = "";
            //check if the mixed content fixer is active
            $response = wp_remote_get(home_url());

            if (!is_wp_error($response)) {
	            if ( is_array( $response ) ) {
		            $status = wp_remote_retrieve_response_code( $response );
		            $web_source = wp_remote_retrieve_body( $response );
	            }

	            if ( $status != 200 ) {
		            $mixed_content_fixer_detected = 'no-response';
	            } elseif ( strpos( $web_source, "data-rsssl=" ) === false ) {
		            $mixed_content_fixer_detected = 'not-found';
	            } else {
		            $mixed_content_fixer_detected = 'found';
	            }
            }

            if (is_wp_error($response)) {
                $mixed_content_fixer_detected = 'error';
                $error = $response->get_error_message();
                set_transient('rsssl_curl_error' , $error, 600);
                if (!empty($error) && (strpos($error, "cURL error") !== false) ) {
                    $mixed_content_fixer_detected = 'curl-error';
                }
            }

            set_transient('rsssl_mixed_content_fixer_detected', $mixed_content_fixer_detected, 600);
        }

        if ($mixed_content_fixer_detected === 'no-response'){
            //Could not connect to website
            $this->trace_log("Could not connect to webpage to detect mixed content fixer");
            $this->mixed_content_fixer_detected = FALSE;
        }
        if ($mixed_content_fixer_detected === 'not-found'){
            //Mixed content fixer marker not found in the websource
            $this->trace_log("Mixed content marker not found in websource");
            $this->mixed_content_fixer_detected = FALSE;
        }
	    if ($mixed_content_fixer_detected === 'error'){
	        $this->trace_log("Mixed content marker not found: unknown error");
		    //Error encountered while retrieving the webpage. Fallback since most errors should be cURL errors
		    $this->mixed_content_fixer_detected = FALSE;
	    }
	    if ($mixed_content_fixer_detected === 'curl-error'){
		    //Site has has a cURL error
            $this->trace_log("Mixed content fixer could not be detected: cURL error");
		    $this->mixed_content_fixer_detected = FALSE;
	    }
        if ($mixed_content_fixer_detected === 'found'){
            $this->trace_log("Mixed content fixer successfully detected");
            //Mixed content fixer was successfully detected on the front end
            $this->mixed_content_fixer_detected = true;
        }

        return $mixed_content_fixer_detected;
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

                //now remove last [OR] if at least on one site the plugin was activated, so we have at least one condition
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
     * Show warning when wpconfig could not be fixed
     *
     * @since 2.2
     *
     * @access public
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
                        if ((isset($_ENV["HTTPS"]) && ("on" == $_ENV["HTTPS"]))<br>
                        || (isset($_SERVER["HTTP_X_FORWARDED_SSL"]) && (strpos($_SERVER["HTTP_X_FORWARDED_SSL"], "1") !== false))<br>
                        || (isset($_SERVER["HTTP_X_FORWARDED_SSL"]) && (strpos($_SERVER["HTTP_X_FORWARDED_SSL"], "on") !== false))<br>
                        || (isset($_SERVER["HTTP_CF_VISITOR"]) && (strpos($_SERVER["HTTP_CF_VISITOR"], "https") !== false))<br>
                        || (isset($_SERVER["HTTP_CLOUDFRONT_FORWARDED_PROTO"]) && (strpos($_SERVER["HTTP_CLOUDFRONT_FORWARDED_PROTO"], "https") !== false))<br>
                        || (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && (strpos($_SERVER["HTTP_X_FORWARDED_PROTO"], "https") !== false))<br>
                        || (isset($_SERVER["HTTP_X_PROTO"]) && (strpos($_SERVER["HTTP_X_PROTO"], "SSL") !== false))<br>
                        ) {<br>
                        &nbsp;&nbsp; $_SERVER["HTTPS"] = "on";<br>
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
     * @access public
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
     * @access public
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

        //this user has never had the review notice yet.
        if ($this->ssl_enabled && !get_option('rsssl_activation_timestamp')){
            $month = rand ( 0, 11);
            $trigger_notice_date = time() + $month * MONTH_IN_SECONDS;
	        update_option('rsssl_activation_timestamp', $trigger_notice_date);
	        update_option('rsssl_before_review_notice_user', true);
        }

        if (!$this->review_notice_shown && get_option('rsssl_activation_timestamp') && get_option('rsssl_activation_timestamp') < strtotime("-1 month")) {
            if ($this->dismiss_review_notice) return;
            add_action('admin_print_footer_scripts', array($this, 'insert_dismiss_review'));
            ?>
            <style>
                .rsssl-container {
                    display: flex;
                    padding:12px;
                }
                .rsssl-container .dashicons {
                    margin-left:10px;
                    margin-right:5px;
                }
                .rsssl-review-image img{
                    margin-top:0.5em;
                }
                .rsssl-buttons-row {
                    margin-top:10px;
                    display: flex;
                    align-items: center;
                }
            </style>
            <div id="message" class="updated fade notice is-dismissible rlrsssl-review really-simple-plugins" style="border-left:4px solid #333">
                <div class="rsssl-container">
                    <div class="rsssl-review-image"><img width=80px" src="<?php echo rsssl_url?>/assets/icon-128x128.png" alt="review-logo"></div>
                    <div style="margin-left:30px">
                        <?php if (get_option("rsssl_before_review_notice_user")){?>
                            <p><?php printf(__('Hi, Really Simple SSL has kept your site secure for some time now, awesome! If you have a moment, please consider leaving a review on WordPress.org to spread the word. We greatly appreciate it! If you have any questions or feedback, leave us a %smessage%s.', 'really-simple-ssl'),'<a href="https://really-simple-ssl.com/contact" target="_blank">','</a>'); ?></p>
                        <?php } else {?>
                            <p><?php printf(__('Hi, Really Simple SSL has kept your site secure for a month now, awesome! If you have a moment, please consider leaving a review on WordPress.org to spread the word. We greatly appreciate it! If you have any questions or feedback, leave us a %smessage%s.', 'really-simple-ssl'),'<a href="https://really-simple-ssl.com/contact" target="_blank">','</a>'); ?></p>
	                    <?php }?>

                        <i>- Rogier</i>
                        <div class="rsssl-buttons-row">
                            <a class="button button-rsssl-primary" target="_blank"
                               href="https://wordpress.org/support/plugin/really-simple-ssl/reviews/#new-post"><?php _e('Leave a review', 'really-simple-ssl'); ?></a>
                            <div class="dashicons dashicons-calendar"></div><a href="#" id="maybe-later"><?php _e('Maybe later', 'really-simple-ssl'); ?></a>
                            <div class="dashicons dashicons-no-alt"></div><a href="<?php echo esc_url(add_query_arg(array("page"=>"rlrsssl_really_simple_ssl", "tab"=>"configuration", "rsssl_dismiss_review_notice"=>1),admin_url("options-general.php") ) );?>" class="review-dismiss"><?php _e('Don\'t show again', 'really-simple-ssl'); ?></a>
                        </div>
                    </div>
                </div>
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

	    $options = get_option('rlrsssl_options');

        if (!$this->wp_redirect && $this->ssl_enabled && !$this->htaccess_warning_shown && !$this->htaccess_contains_redirect_rules() && $options['dismiss_all_notices'] !== true) {

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
                    <?php _e("The 'force-deactivate.php' file has to be renamed to .txt. Otherwise your ssl can be deactivated by anyone on the internet.", "really-simple-ssl"); ?>
                </p>
                <a href="<?php echo admin_url('options-general.php?page=rlrsssl_really_simple_ssl')?>"><?php echo __("Check again", "really-simple-ssl"); ?></a>
            </div>
            <?php
        }

        if (is_multisite() && !is_main_site(get_current_blog_id())) return;

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
                        'action': 'rsssl_dismiss_review_notice',
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
	 *
     * Insert the script to dismiss dashboard notices
	 */

    public function insert_dismiss_settings_script()
    {
        $ajax_nonce = wp_create_nonce("really-simple-ssl");

        ?>
        <script type='text/javascript'>
            jQuery(document).ready(function ($) {
            $(".rsssl-dashboard-dismiss").on("click", ".rsssl-close-warning, .rsssl-close-warning-x",function (event) {
                var type = $(this).closest('.rsssl-dashboard-dismiss').data('dismiss_type');
                var data = {
                    'action': 'rsssl_dismiss_settings_notice',
                    'type' : type,
                    'security': '<?php echo $ajax_nonce; ?>'
                };
                $.post(ajaxurl, data, function (response) {});
                $(this).closest('tr').remove();
            });
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
        if (!current_user_can($this->capability) ) return;
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
        if (!current_user_can($this->capability) ) return;
        check_ajax_referer('really-simple-ssl', 'security');
        $this->htaccess_warning_shown = TRUE;
        $this->save_options();
        wp_die(); // this is required to terminate immediately and return a proper response
    }

    /**
     * Process the ajax dismissal of settings notice
     *
     * Since 3.1
     *
     * @access public
     *
     */

    public function dismiss_settings_notice_callback()
    {
        if (!current_user_can($this->capability) ) return;

        check_ajax_referer('really-simple-ssl', 'security');
        if (isset($_POST['type'])) {
	        $dismiss_type = sanitize_title( $_POST['type'] );
	        update_option( "rsssl_".$dismiss_type."_dismissed", true );
            delete_transient( 'rsssl_plusone_count' );
        }
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

        $count = $this->count_plusones();

        if ($count > 0 ) {
            $update_count = "<span class='update-plugins rsssl-update-count'><span class='update-count'>$count</span></span>";
        } else {
            $update_count = "";
        }

        $rsssl_admin_page = add_options_page(
            __("SSL settings", "really-simple-ssl"), //link title
            __("SSL", "really-simple-ssl") . $update_count, //page title
            $this->capability, //capability
            'rlrsssl_really_simple_ssl', //url
            array($this, 'settings_page')); //function

        // Adds my_help_tab when my_admin_page loads
//        add_action('load-' . $rsssl_admin_page, array($this, 'admin_add_help_tab'));

    }

    /**
     *
     * @since 3.1.6
     *
     * Add an update count to the WordPress admin Settings menu item
     * Doesn't work when the Admin Menu Editor plugin is active
     *
     */

	    public function rsssl_edit_admin_menu()
	    {
		    if (!current_user_can($this->capability)) return;

		    global $menu;

		    $count = $this->count_plusones();

		    $menu_slug = 'options-general.php';
		    $menu_title = __('Settings');

		    foreach($menu as $index => $menu_item){
			    if (!isset($menu_item[2]) || !isset($menu_item[0])) continue;
			    if ($menu_item[2]===$menu_slug){
				    $pattern = '/<span.*>([1-9])<\/span><\/span>/i';
					    if (preg_match($pattern, $menu_item[0], $matches)){
						    if (isset($matches[1])) $count = intval($count) + intval($matches[1]);
					    }

				    $update_count = $count > 0 ? "<span class='update-plugins rsssl-update-count'><span class='update-count'>$count</span></span>":'';
				    $menu[$index][0] = $menu_title . $update_count;
			    }

		    }

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
        // Only show general text if there are other tabs as well
        if (defined('rsssl_pro_version')) {
           $general = __("General", "really-simple-ssl");
        } else {
            $general = '';
        }

        $tabs = array(
            'configuration' => $general,
//            'settings' => __("Settings", "really-simple-ssl"),
//            'debug' => __("Debug", "really-simple-ssl")
        );

        $tabs = apply_filters("rsssl_tabs", $tabs);

        echo '<div class="nav-tab-wrapper">';

        ?>
            <div class="rsssl-logo-container">
                <div id="rsssl-logo"><img height="50px" src="<?php echo rsssl_url?>/assets/logo-really-simple-ssl.png" alt="review-logo"></div>
            </div>
        <?php
        foreach ($tabs as $tab => $name) {
            $class = ($tab == $current) ? ' nav-tab-active' : '';
            echo "<a class='nav-tab$class' href='?page=rlrsssl_really_simple_ssl&tab=$tab'>$name</a>";
        }
//        echo '</div>';
        ?>
        <div class="header-links">
            <div class="documentation">
                <a href="https://really-simple-ssl.com/knowledge-base" target="_blank"><?php _e("Documentation", "really-simple-ssl");?></a>
            </div>
            <?php if (!defined('rsssl_pro_version')) { ?>
            <div class="header-upsell">
                <a href="<?php echo $this->pro_url ?>" target="_blank">
<!--                    <button class="button button-rsssl-primary donate">--><?php //_e("Upgrade", "really-simple-ssl");?><!--</button>-->
                    <div class="header-upsell-pro"><?php _e("PRO", "really-simple-ssl"); ?></div>
                </a>
            </div>
            <?php } ?>
        </div>
        </div>
        <?php
    }


    /**
     * Get array of notices
     * - condition: function returning boolean, if notice should be shown or not
     * - callback: function, returning boolean or string, with multiple possible answers, and resulting messages and icons
     *
     * @return array
     */


    public function get_notices_list()
    {
        $defaults = array(
            'condition' => array(),
            'callback' => false,
        );

        $enable = __("Enable", "really-simple-ssl");
	    $dismiss = __("dismiss", "really-simple-ssl");
	    $curl_error = get_transient('rsssl_curl_error');

	    if (RSSSL()->rsssl_server->uses_htaccess()) {
		    $redirect_plusone = true;
	    } else {
	        $redirect_plusone = false;
        }

        $reload_https_url = esc_url_raw("https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);

        $notices = array(
	            'ssl_detected' => array(
                'callback' => 'rsssl_ssl_detected',
                'score' => 30,
                'output' => array(
                    'fail' => array(
                        'msg' =>__('Failed activating SSL.', 'really-simple-ssl'),
                        'icon' => 'warning'
                    ),
                    'no-ssl-detected' => array(
                        'msg' => sprintf(__("No SSL detected. See our guide on how to %sget a free SSL certificate%s. If you do have an SSL certificate, try to reload this page over https by clicking this link: %sReload over https.%s", "really-simple-ssl"), '<a target="_blank" href="https://really-simple-ssl.com/knowledge-base/how-to-install-a-free-ssl-certificate-on-your-wordpress-cpanel-hosting/">', '</a>', '<a href="' .$reload_https_url . '">' , '</a>'),
                        'icon' => 'warning'
                    ),
                    'ssl-detected' => array(
                        'msg' => __('An SSL certificate was detected on your site.', 'really-simple-ssl'),
                        'icon' => 'success'
                    ),
                ),
            ),

            'google_analytics' => array(
                'callback' => 'rsssl_google_analytics_notice',
                'condition' => array('rsssl_ssl_enabled', 'rsssl_ssl_activation_time_no_longer_then_3_days_ago'),
                'score' => 5,
                'output' => array(
                        'ga' => array(
                            'msg' => sprintf(__("Don't forget to change your settings in Google Analytics and Webmaster tools. %sMore info%s", "really-simple-ssl"), '<a target="_blank" href="https://really-simple-ssl.com/knowledge-base/how-to-setup-google-analytics-and-google-search-consolewebmaster-tools/">', '</a>')
                                . " " . __("or", "really-simple-ssl")
                                . "<span class='rsssl-dashboard-dismiss' data-dismiss_type='google_analytics'><a href='#' class='rsssl-dismiss-text rsssl-close-warning'>$dismiss</a></span>",
                            'icon' => 'open',
                            'dismissible' => true,
                        ),
                ),
            ),

            'ssl_enabled' => array(
                'callback' => 'rsssl_ssl_enabled_notice',
                'score' => 30,
                'output' => array(
                    'ssl-enabled' => array(
                        'msg' =>__('SSL is enabled on your site.', 'really-simple-ssl'),
                        'icon' => 'success'
                    ),
                    'ssl-not-enabled' => array(
                        'msg' => __('SSL is not enabled yet', 'really-simple-ssl'),
                        'icon' => 'open',
                    ),
                ),
            ),

            'mixed_content_fixer_detected' => array(
                'condition' => array('rsssl_site_has_ssl', 'rsssl_autoreplace_insecure_links', 'rsssl_ssl_enabled'),
                'callback' => 'rsssl_mixed_content_fixer_detected',
                'score' => 10,
                'output' => array(
                    'found' => array(
                        'msg' =>__('Mixed content fixer was successfully detected on the front-end', 'really-simple-ssl'),
                        'icon' => 'success'
                    ),
                    'no-response' => array(
                        'msg' => sprintf(__('Really Simple SSL has received no response from the webpage. See our knowledge base for %sinstructions on how to fix this warning%s', 'really-simple-ssl'),'<a target="_blank" href="https://really-simple-ssl.com/knowledge-base/how-to-fix-no-response-from-webpage-warning/">','</a>') . " "
                                 . __("or", "really-simple-ssl")
                                 . "<span class='rsssl-dashboard-dismiss' data-dismiss_type='mixed_content_fixer_detected'><a href='#' class='rsssl-dismiss-text rsssl-close-warning'>$dismiss</a></span>"
                                 . "<span class='rsssl-dashboard-plusone update-plugins rsssl-update-count'><span class='update-count'>1</span></span>",
                        'icon' => 'open',
                        'dismissible' => true,
                        'plusone' => true
                    ),
                    'not-found' => array(
                        'msg' => sprintf(__('The mixed content fixer is active, but was not detected on the frontpage. Please follow %sthese steps%s to check if the mixed content fixer is working.', "really-simple-ssl"),'<a target="_blank" href="https://www.really-simple-ssl.com/knowledge-base/how-to-check-if-the-mixed-content-fixer-is-active/">', '</a>' ),
                        'icon' => 'open',
                        'dismissible' => true
                    ),
                    'error' => array(
	                    'msg' =>__('Error occurred when retrieving the webpage.', 'really-simple-ssl'),
	                    'icon' => 'open',
                        'dismissible' => true
                    ),
                    'curl-error' => array(
	                    'msg' =>sprintf(__("The mixed content fixer could not be detected due to a cURL error: %s. cURL errors are often caused by an outdated version of PHP or cURL and don't affect the front-end of your site. Contact your hosting provider for a fix. %sMore information about this warning%s", 'really-simple-ssl'), "<b>" . $curl_error . "</b>", '<a target="_blank" href="https://www.really-simple-ssl.com/knowledge-base/curl-errors/">', '</a>' ),
	                    'icon' => 'open',
                        'dismissible' => true
                    ),
                ),
            ),

            'wordpress_redirect' => array(
	            'condition' => array('rsssl_wp_redirect_condition'),
	            'callback' => 'rsssl_wordpress_redirect',
                'score' => 10,
                'output' => array(
                     '301-wp-redirect' => array(
                        'msg' => __('301 redirect to https set: WordPress redirect.', 'really-simple-ssl'),
                        'icon' => 'success'
                        ),
                     'no-redirect' => array(
                         'msg' => __('No 301 redirect is set. Enable the WordPress 301 redirect in the settings to get a 301 permanent redirect.', 'really-simple-ssl'),
                         'icon' => 'open'
                     ),
                )
            ),

            'check_redirect' => array(
	            'condition' => array('rsssl_ssl_enabled' , 'rsssl_htaccess_redirect_allowed', 'rsssl_no_multisite'),
	            'callback' => 'rsssl_check_redirect',
                'score' => 10,
	            'output' => array(
                    'htaccess-redirect-set' => array(
                        'msg' =>__('301 redirect to https set: .htaccess redirect.', 'really-simple-ssl'),
                        'icon' => 'success'
                    ),
                    //generate an enable link to highlight the setting, setting name is same as array key
                    $enable_link = $this->generate_enable_link($setting_name = 'wp-redirect-to-htaccess', $type='free'),
                    'wp-redirect-to-htaccess' => array(
                        'msg' => __('WordPress 301 redirect enabled. We recommend to enable the 301 .htaccess redirect option on your specific setup.', 'really-simple-ssl') . " "
                                 . "<span><a href=$enable_link>$enable</a></span>" . " "
                                 . __("or", "really-simple-ssl")
                                 . "<span class='rsssl-dashboard-dismiss' data-dismiss_type='check_redirect'><a href='#' class='rsssl-dismiss-text rsssl-close-warning'>$dismiss</a></span>"
                                 . "<span class='rsssl-dashboard-plusone update-plugins rsssl-update-count'><span class='update-count'>1</span></span>",
                        'icon' => 'open',
                        'plusone' => $redirect_plusone,
                        'dismissible' => true
                    ),
                    'no-redirect-set' => array(
                        'msg' => __('Enable a .htaccess redirect or WordPress redirect in the settings to create a 301 redirect.', 'really-simple-ssl'),
                        'icon' => 'open',
                        'dismissible' => false
                    ),
                    'htaccess-not-writeable' => array(
                        'msg' => __('.htaccess is not writable. Set 301 WordPress redirect, or set the .htaccess manually if you want to redirect in .htaccess.', 'really-simple-ssl'),
                        'icon' => 'warning',
                        'dismissible' => true
                    ),
                    'htaccess-cannot-be-set' => array(
                        'msg' => __('Https redirect cannot be set in the .htaccess file. Set the .htaccess redirect manually or enable the WordPress 301 redirect in the settings.', 'really-simple-ssl'),
                        'icon' => 'warning',
                        'dismissible' => true
                    ),
                    'default' => array(
                        'msg' => __('No 301 redirect is set. Enable the WordPress 301 redirect in the settings to get a 301 permanent redirect.', 'really-simple-ssl'),
                        'icon' => 'warning',
                        'dismissible' => true
                    ),
                ),
            ),

            'elementor' => array(
	            'condition' => array('rsssl_uses_elementor' , 'rsssl_ssl_activation_time_no_longer_then_3_days_ago' ,'rsssl_does_not_use_pro'),
	            'callback' => 'rsssl_elementor_notice',
	            'score' => 5,
	            'output' => array(
		            'elementor-notice' => array(
			            'msg' => sprintf(__("Your site uses Elementor. This can require some additional steps before getting the secure lock. %sSee our guide for detailed instructions%s ", "really-simple-ssl"), '<a target="_blank" href="https://really-simple-ssl.com/knowledge-base/how-to-fix-mixed-content-in-elementor-after-moving-to-ssl/">', '</a>')
			                     . __("or", "really-simple-ssl")
			                     . "<span class='rsssl-dashboard-dismiss' data-dismiss_type='elementor'><a href='#' class='rsssl-dismiss-text rsssl-close-warning'>$dismiss</a></span>",
			            'icon' => 'open',
			            'dismissible' => true
		            ),
	            ),
            ),

            'divi' => array(
	            'condition' => array('rsssl_uses_divi' , 'rsssl_ssl_activation_time_no_longer_then_3_days_ago'),
	            'callback' => 'rsssl_elementor_notice',
	            'score' => 5,
	            'output' => array(
		            'elementor-notice' => array(
			            'msg' => sprintf(__("Your site uses Divi. This can require some additional steps before getting the secure lock. %sSee our guide for detailed instructions%s ", "really-simple-ssl"), '<a target="_blank" href="https://really-simple-ssl.com/knowledge-base/mixed-content-when-using-divi-theme/">', '</a>')
			                     . __("or", "really-simple-ssl")
			                     . "<span class='rsssl-dashboard-dismiss' data-dismiss_type='divi'><a href='#' class='rsssl-dismiss-text rsssl-close-warning'>$dismiss</a></span>",
			            'icon' => 'open',
			            'dismissible' => true
		            ),
	            ),
            ),

            'hsts_enabled' => array(
                'condition' => array('rsssl_no_multisite'),
                'callback' => 'rsssl_hsts_enabled',
                'score' => 5,
                'output' => array(
                    'contains-hsts' => array(
                        'msg' =>__('HTTP Strict Transport Security was enabled.', 'really-simple-ssl'),
                        'icon' => 'success'
                    ),
                    'no-hsts' => array(
                        'msg' => sprintf(__('%sHTTP Strict Transport Security%s is not enabled %s(Read more)%s', "really-simple-ssl"), '<a href="https://en.wikipedia.org/wiki/HTTP_Strict_Transport_Security" target="_blank">', '</a>', '<a target="_blank" href="' . $this->pro_url . '">', '</a>'),
                        'icon' => 'premium'
                    ),
                ),
            ),

            'secure_cookies_set' => array(
                'callback' => 'rsssl_secure_cookies_set',
                'score' => 5,
                'output' => array(
                    'set' => array(
                        'msg' =>__('Secure cookies set', 'really-simple-ssl'),
                        'icon' => 'success'
                    ),
                    'not-set' => array(
                        'msg' => sprintf(__("Secure cookie settings not enabled (%sRead more%s) ", "really-simple-ssl"), '<a target="_blank" href="' . $this->pro_url .'">', '</a>'),
                        'icon' => 'premium'
                    ),
                ),
            ),

            'mixed_content_scan' => array(
                'callback' => 'rsssl_scan_upsell',
                'score' => 5,
                'output' => array(
                    'upsell' => array(
                        'msg' => sprintf(__("No mixed content scan performed (%sRead more%s) ", "really-simple-ssl"), '<a target="_blank" href="' . $this->pro_url .'">', '</a>'),
                        'icon' => 'premium'
                    ),
                ),
            ),
        );

        $notices = apply_filters('rsssl_notices', $notices);
        foreach ($notices as $id => $notice) {
            $notices[$id] = wp_parse_args($notice, $defaults);
        }

        return $notices;
    }

    /**
     * @return int
     *
     * Calculate the percentage completed in the dashboard progress section
     * Determine max score by adding $notice['score'] to the $max_score variable
     * Determine actual score by adding $notice['score'] of each item with a 'success' output to $actual_score
     *
     * @since 4.0
     *
     */

    public function get_score_percentage() {

        if (wp_doing_ajax()) {
            if (!isset($_POST['token']) || (!wp_verify_nonce($_POST['token'], 'rsssl_nonce'))) {
                return;
            }

            if (!isset($_POST["action"]) && $_POST["action"] ==! 'rsssl_get_updated_percentage') return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return 0;
        }

        $max_score = 0;
        $actual_score = 0;

        $notices = $this->get_notices_list();

            foreach ( $notices as $id => $notice ) {

                $condition = true;
                if ( get_option( "rsssl_" . $id . "_dismissed" ) ) {
                    continue;
                }

                $condition_functions = $notice['condition'];
                foreach ( $condition_functions as $func ) {
                    $condition = $func();
                    if ( ! $condition ) {
                        break;
                    }
                }

                if ( $condition ) {
                    // Only items matching condition will show in the dashboard. Only use these to determine max count.
                    $max_score = $max_score + intval($notice['score']);
                    $func    = $notice['callback'];
                    $output  = $func();

                    $success = ( isset( $notice['output'][ $output ]['icon'] )
                        && ( $notice['output'][ $output ]['icon']
                            === 'success' ) ) ? true : false;

                    if ( $success) {
                        // If the output is success, task is completed. Add to actual count.
                        $actual_score = $actual_score + intval( $notice['score'] );
                    }
                }
            }
            $score = $actual_score / $max_score;
            $score = $score * 100;

            $score = intval( round( $score ) );

            if (wp_doing_ajax()) {
                wp_die( $score );
            }

            return $score;
    }

	/**
	 * @param $setting_name
	 *
	 * @return string
     *
     * Generate an enable link for the specific setting, redirects to settings page and highlights the setting.
     *
	 */

    public function generate_enable_link($setting_name, $type)
    {
        if ($type == 'free') {
            return add_query_arg(array("page" => "rlrsssl_really_simple_ssl", "highlight" => "$setting_name"), admin_url("options-general.php"));
        } elseif ($type == 'premium') {
            return add_query_arg(array("page" => "rlrsssl_really_simple_ssl", "tab" => "premium", "highlight" => "$setting_name"), admin_url("options-general.php"));

        }
    }

	/**
	 * @param $id
	 * @param $notice
     *
     * Generate a notice row in the configuration dashboard tab
     *
     * @since 3.2
     *
	 */

    private function notice_row($id, $notice){
        if (!current_user_can('manage_options')) return;

        //check condition
        if (!empty($notice['condition']) ) {
            $condition_functions = $notice['condition'];

            foreach ($condition_functions as $func) {
                $condition = $func();
                if (!$condition) return;
            }
        }

        $func = $notice['callback'];
        $output = $func();

        if (!isset($notice['output'][$output])) {
            return;
        }

        $msg = $notice['output'][$output]['msg'];
        $icon_type = $notice['output'][$output]['icon'];

        if (get_option("rsssl_".$id."_dismissed")) return;

        // Do not show completed tasks if remaining tasks are selected.
        if ($icon_type === 'success' && !get_option('rsssl_all_tasks') && get_option('rsssl_remaining_tasks')) return;

        //call_user_func_array(array($classInstance, $methodName), $arg1, $arg2, $arg3);
        $icon = $this->icon($icon_type);
        $dismiss = (isset($notice['output'][$output]['dismissible']) && $notice['output'][$output]['dismissible']) ? $this->rsssl_dismiss_button() : '';

        ?>
        <tr>
            <td><?php echo $icon?></td><td class="rsssl-table-td-main-content"><?php echo $msg?></td>
            <td class="rsssl-dashboard-dismiss" data-dismiss_type="<?php echo $id?>"><?php echo $dismiss?></td>
        </tr>
        <?php
    }

	/**
	 *
     * Reset the plusone count transient
     *
     * @since 3.2
     *
	 */

    public function reset_plusone_cache(){
        delete_transient('rsssl_plusone_count');
    }

    /**
     *
     * Reset the open task count transient
     *
     * @since 4.0
     *
     */

    public function reset_open_remaining_task_cache(){
        delete_transient('rsssl_open_task_count');
        delete_transient('rsssl_remaining_task_count');
    }

	/**
	 * @return int|mixed
     *
     * Count the plusones
     *
     * @since 3.2
	 */

	public function count_plusones() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return 0;
		}
		$count = get_transient( 'rsssl_plusone_count' );
		if ( $count === false ) {
			$count = 0;

			$options = get_option( 'rlrsssl_options' );

			$notices = $this->get_notices_list();
			foreach ( $notices as $id => $notice ) {
				$condition = true;
				if ( get_option( "rsssl_" . $id . "_dismissed" ) ) {
					continue;
				}

				$condition_functions = $notice['condition'];
				foreach ( $condition_functions as $func ) {
					$condition = $func();
					if ( ! $condition ) {
						break;
					}
				}

				if ( $condition ) {
					$func    = $notice['callback'];
					$output  = $func();
					$success = ( isset( $notice['output'][ $output ]['icon'] )
					             && ( $notice['output'][ $output ]['icon']
					                  === 'success' ) ) ? true : false;

					if ( ( isset( $notice['output'][ $output ]['dismissible'] )
					       && $notice['output'][ $output ]['dismissible']
					       && ( $options['dismiss_all_notices'] !== false ) )
					) {
						update_option( 'rsssl_' . $id . '_dismissed', true );
						continue;
					}

					//&& notice not dismissed
					if ( ! $success
					     && isset( $notice['output'][ $output ]['plusone'] )
					     && $notice['output'][ $output ]['plusone']
					) {
						$count ++;
					}
				}
			}
			set_transient( 'rsssl_plusone_count', $count, 'WEEK_IN_SECONDS' );
		}
		return $count;
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

	    // Replace free blocks with pro if Really Simple SSL Pro is active
	    if (!defined('rsssl_pro_version')) {
            $four = array(
                'title' => __("Support forum", "really-simple-ssl"),
                'secondary_header_item' => '',
                'content' => $this->get_support_forum_block(),
                'footer' => $this->get_system_status_footer(),
                'type' => 'tasks',
                'class' => 'half-height',
                'can_hide' => true,
            );
            $five = array(
                'title' => __("Our plugins", "really-simple-ssl"),
                'secondary_header_item' => $this->generate_secondary_our_plugins_header(),
                'content' => $this->generate_other_plugins(),
                'footer' => '',
                'class' => 'half-height no-border no-background upsell-grid-container',
                'type' => 'plugins',
                'can_hide' => false,
            );
        } else {
            $four = array(
                'title' => __("Support form", "really-simple-ssl"),
                'secondary_header_item' => '',
                'content' => RSSSL_PRO()->rsssl_support->support_form(),
                'footer' => RSSSL_PRO()->rsssl_support->support_form_footer(),
                'class' => 'regular support-form',
                'type' => 'support',
                'can_hide' => true,
            );
            $five = array(
                'title' => __("Premium settings", "really-simple-ssl"),
                'secondary_header_item' => $this->generate_secondary_settings_header_item(),
                'content' => RSSSL_PRO()->rsssl_premium_options->add_pro_settings_page(),
                'footer' => RSSSL_PRO()->rsssl_premium_options->add_pro_settings_footer(),
                'type' => 'settings',
                'class' => 'rsssl-premium-settings half-height',
                'can_hide' => true,
            );
        }

		$grid_items = array(
			1 => array(
				'title' => __("Your progress", "really-simple-ssl"),
				'secondary_header_item' => $this->generate_secondary_progress_header_item(),
				'content' => $this->generate_progress(),
				'footer' => $this->generate_progress_footer(),
				'class' => 'regular rsssl-progress',
				'type' => 'all',
				'can_hide' => true,

			),
			2 => array(
				'title' => __("Settings", "really-simple-ssl"),
                'secondary_header_item' => $this->generate_secondary_settings_header_item(),
                'content' => $this->generate_settings(),
				'footer' => $this->generate_settings_footer(),
				'class' => 'small settings',
				'type' => 'settings',
				'can_hide' => true,
			),
            3 => array(
                'title' => __("Tips & Tricks", "really-simple-ssl"),
                'secondary_header_item' => '',
                'content' => $this->generate_tips_tricks(),
                'footer' => $this->generate_tips_tricks_footer(),
                'class' => 'small',
                'type' => 'popular',
                'can_hide' => true,
            ),
            4 => $four,
			5 => $five,
		);
		return $grid_items;
	}

    /**
     * @return false|string
     *
     * Generate a secondary header item for the progress block
     */

	public function generate_secondary_progress_header_item() {
	    ob_start();
        ?>
        <div class="rsssl-secondary-header-item">
            <?php $all_task_count = $this->get_all_task_count(); ?>
            <div class="all-task-text">
                <input type="checkbox" class="rsssl-task-toggle" id="rsssl-all-tasks" name="rsssl_all_tasks" <?php if (get_option('rsssl_all_tasks') ) echo "checked"?>>
                <label for="rsssl-all-tasks"><?php _e( "All tasks", "really-simple-ssl" ); ?></label>
            </div>
            <div class="all-task-count">
                <?php
                echo " " . "(" . $all_task_count . ")";
                ?> </div>
            <?php
            $open_task_count = $this->get_remaining_tasks_count();
            if ($open_task_count ==! 0) {?>
                <div class="open-task-text">
                    <input type="checkbox" class="rsssl-task-toggle" id="rsssl-remaining-tasks" name="rsssl_remaining_tasks" <?php if (get_option('rsssl_remaining_tasks') ) echo "checked"?>>
                    <label for="rsssl-remaining-tasks"><?php _e( "Remaining tasks", "really-simple-ssl" ); ?></label>
                </div>
                <div class="open-task-count">
                    <?php
                    echo " " . "(" . $open_task_count . ")";
                    ?> </div> <?php
            }
            ?>
        </div>
        <?php
        $content = ob_get_clean();
        return $content;
    }

    /**
     * Save the task toggle option
     * @since 4.0
     */

    public function update_task_toggle_option() {

        if (!isset($_POST['token']) || (!wp_verify_nonce($_POST['token'], 'rsssl_nonce'))) {
            return;
        }

        if (!isset($_POST["action"]) && $_POST["action"] ==! 'rsssl_update_task_toggle_option') return;

        if (!isset($_POST['alltasks']) || (!isset($_POST['remainingtasks']) ) ) return;

        if ($_POST['alltasks'] === 'checked') {
            update_option('rsssl_all_tasks', true);
        } else {
            update_option('rsssl_all_tasks', false);
        }

        if ($_POST['remainingtasks'] === 'checked') {
            update_option('rsssl_remaining_tasks', true);
        } else {
            update_option('rsssl_remaining_tasks', false);
        }

        wp_die();
    }

    public function get_all_task_count() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return 0;
        }

//        $count = get_transient( 'rsssl_all_task_count' );
        $count = false;
        if ( $count === false ) {
            $count = 0;

            $notices = $this->get_notices_list();
            foreach ( $notices as $id => $notice ) {
                $condition = true;

                $condition_functions = $notice['condition'];
                foreach ( $condition_functions as $func ) {
                    $condition = $func();
                    if ( ! $condition ) {
                        break;
                    }
                }

                if ( $condition ) {
                    $func    = $notice['callback'];
                    $output  = $func();

                    if ( isset( $notice['output'][ $output ]['icon'] )
                        && ( $notice['output'][ $output ]['icon'] == 'open'
                        || $notice['output'][ $output ]['icon'] == 'premium'
                        || $notice['output'][ $output ]['icon'] == 'warning'
                        || $notice['output'][ $output ]['icon'] == 'success'
                    ) ) {
                        $count ++;
                    }
                }
            }
            set_transient( 'rsssl_all_task_count', $count, 'DAY_IN_SECONDS' );
        }
        if (wp_doing_ajax()) {
            wp_die($count);
        }

        return $count;
    }

    /**
     * @return int|mixed
     *
     * Get the remaining open task count, shown in the progress header
     *
     */

    public function get_remaining_tasks_count() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return 0;
        }

        if (wp_doing_ajax()) {
            if (!isset($_POST['token']) || (!wp_verify_nonce($_POST['token'], 'rsssl_nonce'))) {
                return;
            }

            if (!isset($_POST["action"]) && $_POST["action"] ==! 'rsssl_get_updated_percentage') return;

            // When invoked via AJAX the count should be updated, therefore clear cache
            $this->reset_open_remaining_task_cache();

        }

        $count = get_transient( 'rsssl_remaining_task_count' );

        if ( $count === false ) {
            $count = 0;

            $notices = $this->get_notices_list();
            foreach ( $notices as $id => $notice ) {
                $condition = true;
                if ( get_option( "rsssl_" . $id . "_dismissed" ) ) {
                    continue;
                }

                $condition_functions = $notice['condition'];
                foreach ( $condition_functions as $func ) {
                    $condition = $func();
                    if ( ! $condition ) {
                        break;
                    }
                }

                if ( $condition ) {
                    $func    = $notice['callback'];
                    $output  = $func();
                    $success = ( isset( $notice['output'][ $output ]['icon'] )
                        && ( $notice['output'][ $output ]['icon']
                            === 'success' ) ) ? true : false;

                    if ( ! $success
                        && isset( $notice['output'][ $output ]['icon'] )
                        && ( $notice['output'][ $output ]['icon'] == 'open'
                        || $notice['output'][ $output ]['icon'] == 'premium'
                        || $notice['output'][ $output ]['icon'] == 'warning'
                    ) ) {
                        $count ++;
                    }
                }
            }
            set_transient( 'rsssl_remaining_task_count', $count, 'DAY_IN_SECONDS' );
        }

        if (wp_doing_ajax()) {
            wp_die($count);
        }

        return $count;
    }

    /**
     * Generate the progress block on plugin dashboard
     *
     * @since 4.0
     *
     */

	public function generate_progress() {

	    $percentage_completed = $this->get_score_percentage();

		$element = $this->get_template('progress.php', rsssl_path . 'grid/');
		return str_replace(
            array(
                '{percentage_completed}',

            ),
            array(
                $percentage_completed,
            )
            , $element);
    }

    /**
     * @return string|string[]
     * Generate the dashboard progress block footer
     *
     * @since 4.0
     *
     */

    public function generate_progress_footer() {

        if ($this->ssl_enabled) {
            $ssl_enabled = "rsssl-dot-success";
            $ssl_text = __("SSL Activated", "really-simple-ssl");
        } else {
            $ssl_enabled = "rsssl-dot-error";
            $ssl_text = __("SSL Not activated", "really-simple-ssl");
        }

        if ($this->has_301_redirect()) {
            $redirect_301 = "rsssl-dot-success";
        } else {
            $redirect_301 = "rsssl-dot-error";
        }

        $button_text = '';
        $text = '';

        if (!defined('rsssl_pro_version')) {
            $button_text = __("Go PRO!", "really-simple-ssl");
            $text = "<a href='$this->pro_url' target='_blank' class='button button-rsssl-primary upsell'>$button_text</a>";
        }

        $items = array(
            1 => array(
                'class' => 'footer-left',
                'dot_class' => '',
                'text' => $text,
            ),
            2 => array(
                'class' => '',
                'dot_class' => $ssl_enabled,
                'text' => $ssl_text,
            ),
            3 => array(
                'class' => '',
                'dot_class' => $redirect_301,
                'text' => __("301 Redirect", "really-simple-ssl"),
            ),
        );
        $id = 'rsssl-progress-footer';
        $container = $this->get_template('footer-container.php', rsssl_path . 'grid/');
        $element = $this->get_template('footer-element.php', rsssl_path . 'grid/');
        $output = '';
        foreach ($items as $item) {
            $output .= str_replace(array(
                '{class}',
                '{dot_class}',
                '{text}',
            ), array(
                $item['class'],
                $item['dot_class'],
                $item['text'],
            ), $element);
        }
        return str_replace(array('{id}', '{content}'), array($id, $output), $container);

    }

    /**
     * @return false|string
     *
     * Generate secondary header item in plugin dashboard settings block
     * @since 4.0
     *
     */

    public function generate_secondary_settings_header_item() {
        ob_start();
        ?>
            <div class="rsssl-secondary-header-item">
                <div class="rsssl-save-settings-feedback" style="display: none;">
                    <?php _e("Save settings" , "really-simple-ssl") ?>
                </div>
            </div>
        <?php
        $content = ob_get_clean();
        return $content;
    }

    /**
     * @return false|string
     * Generate the settings block in plugin dashboard
     *
     * @since 4.0
     */

    public function generate_settings() {
	    ob_start();
	    ?>
        <div class="rsssl-settings">
                <?php
                settings_fields('rlrsssl_options');
                do_settings_sections('rlrsssl');
                ?>
        </div>
	    <?php
        $content = ob_get_clean();
        return $content;
    }

    /**
     * @return false|string
     * Generate the settings footer in plugin dashboard
     *
     * @since 4.0
     *
     */

    public function generate_settings_footer() {
	    ob_start();
	    ?>
        <input class="button button-rsssl-secondary rsssl-button-save" name="Submit" type="submit"
               value="<?php echo __("Save", "really-simple-ssl"); ?>"/>
        <?php
        $content = ob_get_clean();
        return $content;
    }

    /**
     * @return false|string
     * Get system status footer in support forums block
     *
     * @since 4.0
     */

    public function get_system_status_footer() {
        ob_start();
        ?>
        <a href="<?php echo trailingslashit(rsssl_url).'system-status.php' ?>" class="button button-rsssl-secondary rsssl-wide-button"><?php _e("Download system status", "really-simple-ssl")?></a>
        <div id="rsssl-feedback"></div>
        <div class="rsssl-system-status-footer-info">
            <span class="system-status-info"><?php echo "<b>" . __("Server type:", "really-simple-ssl") . "</b> " . RSSSL()->rsssl_server->get_server(); ?></span>
            <span class="system-status-info"><?php echo "<b>" . __("SSL type:", "really-simple-ssl") . "</b> " . $this->ssl_type; ?></span>
        </div>
        <?php
        $content = ob_get_clean();
        return $content;
    }

    /**
     * @return string|string[]
     * Get the tips and tricks block in plugin dashboard
     *
     * @since 4.0
     */

	public function generate_tips_tricks()
	{
		$items = array(
			1 => array(
				'content' => __("Improve security by enabling HTTP Strict Transport security on your site", "really-simple-ssl"),
				'link'    => 'https://really-simple-ssl.com/hsts-http-strict-transport-security-good/',
//                'condition' => $this->does_not_use_hsts(),
			),
			2 => array(
				'content' => __("Extensive scan for your back-end", "really-simple-ssl"),
				'link' => "$this->pro_url",
                'condition' => '',
			),
			3 => array(
				'content' => __("Add security headers for improved security", "really-simple-ssl"),
				'link' => 'https://really-simple-ssl.com/new-security-headers-for-really-simple-ssl-pro-coming-up/',
                'condition' => '',
			),
		);
		$container = $this->get_template('tipstricks-container.php', rsssl_path . 'grid/');
		$element = $this->get_template('tipstricks-element.php', rsssl_path . 'grid/');
		$output = '';
		foreach ($items as $item) {
			$output .= str_replace(array(
				'{link}',
				'{content}',
			), array(
				$item['link'],
				$item['content'],
			), $element);
		}
		return str_replace(array('{content}'), array($output), $container);
	}

	public function generate_tips_tricks_footer() {
	    ob_start();
	    ?>
        <a href="https://really-simple-ssl.com/knowledge-base-overview/" target="_blank"><button class="button button-rsssl-secondary"><?php _e("Documentation", "really-simple-ssl"); ?> </button></a>
        <?php
	    $contents = ob_get_clean();
	    return $contents;
    }

    /**
     * @return string|string[]
     * Generate the support forum block in plugin dashboard
     *
     * @since 4.0
     */

    public function get_support_forum_block() {
        $items = array(
            1 => array(
                'content' => __("General Issues", "really-simple-ssl"),
                'link'    => 'https://really-simple-ssl.com/forums/forum/general-issues/',
            ),
            2 => array(
                'content' => __("Redirect loops", "really-simple-ssl"),
                'link'    => 'https://really-simple-ssl.com/forums/forum/redirect-loops/',
            ),
            3 => array(
                'content' => __("Multisite", "really-simple-ssl"),
                'link'    => 'https://really-simple-ssl.com/forums/forum/multisite/',
            ),
            4 => array(
                'content' => __("Really Simple SSL Pro", "really-simple-ssl"),
                'link'    => 'https://really-simple-ssl.com/forums/forum/really-simple-ssl-pro/',
            ),
            5 => array(
                'content' => __("Mixed Content", "really-simple-ssl"),
                'link'    => 'https://really-simple-ssl.com/forums/forum/mixed-content-site/',
            ),
            6 => array(
                'content' => __("Elementor", "really-simple-ssl"),
                'link'    => 'https://really-simple-ssl.com/knowledge-base/how-to-fix-mixed-content-in-elementor-after-moving-to-ssl/',
            ),
        );

        $container = $this->get_template('support-forums-container.php', rsssl_path . 'grid/');
        $element = $this->get_template('support-forums-element.php', rsssl_path . 'grid/');
        $output = '';
        foreach ($items as $item) {
            $output .= str_replace(array(
                '{link}',
                '{content}',
            ), array(
                $item['link'],
                $item['content'],
            ), $element);
        }
        return str_replace(array('{content}'), array($output), $container);

    }

    /**
     * @return false|string
     * Generate the support forum block footer in plugin dashboard
     *
     * @since 4.0
     */
    public function get_support_forum_block_footer() {
        ob_start();
        ?>
        <a href="https://really-simple-ssl.com/forums/" target="_blank" class="button button-rsssl-secondary"><?php _e("View all" , "really-simple-ssl");?></a>
        <?php
        $content = ob_get_clean();
        return $content;
    }

    /**
     * @return false|string
     * Generate the RSP logo in
     */
    public function generate_secondary_our_plugins_header() {
        ob_start();
	    ?>
        <div class="rsp-image"><img width=170px" src="<?php echo rsssl_url?>/assets/really-simple-plugins.png" alt="really-simple-plugins-logo"></div>
        <?php
	    $content = ob_get_clean();
        return $content;
    }

	/**
	 * @return string
     * Generate the other plugins container
	 */

    public function generate_other_plugins()
    {

        $items = array(
            1 => array(
                'title' => '<div class="wpsi-red rsssl-bullet"></div>',
                'content' => __("WP Search Insights - Track searches on your website"),
                'class' => 'wpsi',
                'constant_free' => 'wpsi_plugin',
                'constant_premium' => 'wpsi_pro_plugin',
                'website' => 'https://wpsearchinsights.com/pro',
                'search' => 'WP+Search+Insights',
                'link' => 'https://wordpress.org/plugins/wp-search-insights/',
            ),
            2 => array(
                'title' => '<div class="cmplz-blue rsssl-bullet"></div>',
                'content' => __("Complianz Privacy Suite - Consent Management as it should be ", "really-simple-ssl"),
                'class' => 'cmplz',
                'constant_free' => 'cmplz_plugin',
                'constant_premium' => 'cmplz_premium',
                'website' => 'https://complianz.io/pricing',
                'search' => 'complianz',
                'link' => 'https://wordpress.org/plugins/complianz-gdpr/',
            ),
            3 => array(
                'title' => '<div class="zip-pink rsssl-bullet"></div>',
                'content' => __("Zip Recipes - Beautiful recipes optimized for Google ", "really-simple-ssl"),
                'class' => 'zip',
                'constant_free' => 'ZRDN_PLUGIN_BASENAME',
                'constant_premium' => 'ZRDN_PREMIUM',
                'website' => 'https://ziprecipes.net/premium/',
                'search' => 'zip+recipes+recipe+maker+really+simple+plugins',
                'link' => 'https://wordpress.org/plugins/zip-recipes/',
                ),
        );

        $element = $this->get_template('upsell-element.php', rsssl_path . 'grid/');
        $output = '';
        foreach ($items as $item) {
            $output .= str_replace(array(
                '{title}',
                '{content}',
                '{status}',
                '{class}',
                '{controls}',
                '{link}'
            ), array(
                $item['title'],
                $item['content'],
                $this->get_status_link($item),
                $item['class'],
                '',
                $item['link'],
            ), $element);
        }

        return '<div>'.$output.'</div>';
    }

    /**
     * Get status link for plugin, depending on installed, or premium availability
     * @param $item
     *
     * @return string
     */

    public function get_status_link($item){
        if (defined($item['constant_free']) && defined($item['constant_premium'])) {
            $status = __("Installed", "really-simple-ssl");
        } elseif (defined($item['constant_free']) && !defined($item['constant_premium'])) {
            $link = $item['website'];
            $text = __('Upgrade to pro', 'really-simple-ssl');
            $status = "<a href=$link>$text</a>";
        } else {
            $link = admin_url() . "plugin-install.php?s=".$item['search']."&tab=search&type=term";
            $text = __('Install', 'really-simple-ssl');
            $status = "<a href=$link>$text</a>";
        }
        return $status;
    }

    public function settings_page()
    {
        if (!current_user_can($this->capability)) return;

        add_action('admin_print_footer_scripts', array($this, 'insert_dismiss_settings_script'));

        if (isset ($_GET['tab'])) $this->admin_tabs($_GET['tab']); else $this->admin_tabs('configuration');
        if (isset ($_GET['tab'])) $tab = $_GET['tab']; else $tab = 'configuration';

        ?>
        <div class="rsssl-container">
            <div class="rsssl-main"><?php

                switch ($tab) {
                case 'configuration' :

                /*
                    First tab, general
                 */

                $grid_items = $this->general_grid();

                $container = $this->get_template('grid-container.php', rsssl_path . 'grid/');
                $element = $this->get_template('grid-element.php', rsssl_path . 'grid/');
                $output = '';

                foreach ($grid_items as $index => $grid_item) {
                    if (!$grid_item['footer']) {
                        $grid_item['footer'] = '';
                    }

                    // Add form if type is settings
                    if (isset($grid_item['type']) && $grid_item['type'] == 'settings') {
                        $output .= '<form action="options.php" method="post">';
                        $output .= str_replace(array('{class}', '{title}', '{secondary_header_item}', '{content}', '{footer}'), array($grid_item['class'], $grid_item['title'], $grid_item['secondary_header_item'], $grid_item['content'], $grid_item['footer']), $element);
                        $output .= '</form>';
                    } else {
                        $output .= str_replace(array('{class}', '{title}', '{secondary_header_item}', '{content}', '{footer}'), array($grid_item['class'], $grid_item['title'], $grid_item['secondary_header_item'], $grid_item['content'], $grid_item['footer']), $element);
                    }

                }

                echo str_replace('{content}', $output, $container);

                do_action("rsssl_configuration_page");

                }
                //possibility to hook into the tabs.
                do_action("show_tab_{$tab}");
                ?>
            </div><!-- end main-->
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

    public function icon($type)
    {
        if ($type == 'success') {
            return "<span class='rsssl-progress-status rsssl-success'>Completed</span>";
        } elseif ($type == "warning") {
            return "<span class='rsssl-progress-status rsssl-warning'>Warning</span>";
        } elseif ($type == "open") {
            return "<span class='rsssl-progress-status rsssl-open'>Open</span>";
        } elseif ($type == "premium") {
	        return "<span class='rsssl-progress-status rsssl-premium'>Premium</span>";
        }
    }

    /**
     *
     * Add a dismiss button which will dismiss the nearest <tr>. Used on 'Configuration' dashboard page
     *
     * @since 3.1.6
     *
     */

    public function rsssl_dismiss_button()
    {
         return '<button type="button" class="close">
                <span class="rsssl-close-warning-x">X</span>
            </button>';
    }

    /**
     * @param $args
     *
     * @since 3.0
     *
     * Generate the HTML for the settings page sidebar
     *
     */

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

        /*
         * load if this is the SSL settings page
         */

        if ( $hook != $rsssl_admin_page) return;

        if (is_rtl()) {
            wp_register_style('rlrsssl-css', trailingslashit(rsssl_url) . 'css/main-rtl.min.css', "", rsssl_version);
            wp_register_style('rsssl-grid', trailingslashit(rsssl_url) . 'grid/css/grid-rtl.min.css', "", rsssl_version);
        } else {
	        wp_register_style('rlrsssl-css', trailingslashit(rsssl_url) . 'css/main.min.css', "", time());
            wp_register_style('rsssl-grid', trailingslashit(rsssl_url) . 'grid/css/grid.min.css', "", time());
        }


        wp_register_style('rsssl-scrollbar', trailingslashit(rsssl_url) . 'includes/simple-scrollbar.css', "", rsssl_version);
        wp_enqueue_style('rsssl-scrollbar');

	    wp_enqueue_style('rlrsssl-css');
	    wp_enqueue_style('rsssl-grid');

        wp_register_script('rsssl',
            trailingslashit(rsssl_url)
            . 'js/scripts.js', array("jquery"), rsssl_version);
        wp_enqueue_script('rsssl');

        wp_localize_script('rsssl', 'rsssl',
            array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'token'   => wp_create_nonce( 'rsssl_nonce'),
                'copied_text' => __("Copied!", "really-simple-ssl"),
                'finished_text' => __("Basic SSL configuration finished! Improve your score with ", "really-simple-ssl"),
            )
        );

        wp_register_script('rsssl-scrollbar',
            trailingslashit(rsssl_url)
            . 'includes/simple-scrollbar.js', array("jquery"), rsssl_version);
        wp_enqueue_script('rsssl-scrollbar');
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
	    if ($this->is_settings_page()) {
		    add_action( 'admin_head', array( $this, 'highlight_js' ) );
	    }

        register_setting('rlrsssl_options', 'rlrsssl_options', array($this, 'options_validate'));

	    // Show a dismiss review
	    if (!$this->dismiss_review_notice && !$this->review_notice_shown && get_option('rsssl_activation_timestamp') && get_option('rsssl_activation_timestamp') < strtotime("-1 month")) {
            $help_tip = RSSSL()->rsssl_help->get_help_tip(__("Enable this option to dismiss the review notice.", "really-simple-ssl"), $return=true);
            add_settings_field('id_dismiss_review_notice', $help_tip . "<div class='rsssl-settings-text'>" . __("Dismiss review notice", "really-simple-ssl"), array($this, 'get_option_dismiss_review_notice'), 'rlrsssl', 'rlrsssl_settings');
        }

	    add_settings_section('rlrsssl_settings', __("Settings", "really-simple-ssl"), array($this, 'section_text'), 'rlrsssl');

	    $help_tip = RSSSL()->rsssl_help->get_help_tip(__("In most cases you need to leave this enabled, to prevent mixed content issues on your site.", "really-simple-ssl"), $return=true);
	    add_settings_field('id_autoreplace_insecure_links', $help_tip . "<div class='rsssl-settings-text'>" . __("Mixed content fixer", "really-simple-ssl"), array($this, 'get_option_autoreplace_insecure_links'), 'rlrsssl', 'rlrsssl_settings');

        //only show option to enable or disable mixed content and redirect when SSL is detected
        if ($this->ssl_enabled) {
	        $help_tip = RSSSL()->rsssl_help->get_help_tip(__("Enable this if you want to use the internal WordPress 301 redirect. Needed on NGINX servers, or if the .htaccess redirect cannot be used.", "really-simple-ssl"), $return=true);
	        add_settings_field('id_wp_redirect', $help_tip . "<div class='rsssl-settings-text'>" . __("Enable WordPress 301 redirect", "really-simple-ssl"), array($this, 'get_option_wp_redirect'), 'rlrsssl', 'rlrsssl_settings', ['class' => 'rsssl-settings-row'] );

            //when enabled networkwide, it's handled on the network settings page
            if (RSSSL()->rsssl_server->uses_htaccess() && (!is_multisite() || !RSSSL()->rsssl_multisite->ssl_enabled_networkwide)) {
	            $help_tip = RSSSL()->rsssl_help->get_help_tip(__("A .htaccess redirect is faster. Really Simple SSL detects the redirect code that is most likely to work (99% of websites), but this is not 100%. Make sure you know how to regain access to your site if anything goes wrong!", "really-simple-ssl"), $return=true);
	            add_settings_field('id_htaccess_redirect', $help_tip . "<div class='rsssl-settings-text'>" . __("Enable 301 .htaccess redirect", "really-simple-ssl"), array($this, 'get_option_htaccess_redirect'), 'rlrsssl', 'rlrsssl_settings');
            }

//            $help_tip = RSSSL()->rsssl_help->get_help_tip(__("Enable this option to get debug info in the debug tab.", "really-simple-ssl"), $return=true);
//	        add_settings_field('id_javascript_redirect', $help_tip . "<div class='rsssl-settings-text'>" . __("Enable Javascript redirection to SSL", "really-simple-ssl"), array($this, 'get_option_javascript_redirect'), 'rlrsssl', 'rlrsssl_settings');
        }

//        add_settings_field('id_debug', __("Debug", "really-simple-ssl"), array($this, 'get_option_debug'), 'rlrsssl', 'rlrsssl_settings');
        //on multisite this setting can only be set networkwide
        if (RSSSL()->rsssl_server->uses_htaccess() && !is_multisite()) {
	        $help_tip = RSSSL()->rsssl_help->get_help_tip(__("If you want to customize the Really Simple SSL .htaccess, you need to prevent Really Simple SSL from rewriting it. Enabling this option will do that.", "really-simple-ssl"), $return=true);
	        add_settings_field('id_do_not_edit_htaccess', $help_tip . "<div class='rsssl-settings-text'>" . __("Stop editing the .htaccess file", "really-simple-ssl"), array($this, 'get_option_do_not_edit_htaccess'), 'rlrsssl', 'rlrsssl_settings');
        }

	    $help_tip = RSSSL()->rsssl_help->get_help_tip(__("If this option is set to true, the mixed content fixer will fire on the init hook instead of the template_redirect hook. Only use this option when you experience problems with the mixed content fixer.\"", "really-simple-ssl"), $return=true);
        add_settings_field('id_switch_mixed_content_fixer_hook', $help_tip . "<div class='rsssl-settings-text'>" . __("Use alternative method to fix mixed content", "really-simple-ssl"), array($this, 'get_option_switch_mixed_content_fixer_hook'), 'rlrsssl', 'rlrsssl_settings');
	    $help_tip = RSSSL()->rsssl_help->get_help_tip(__("Enable this option to permanently dismiss all +1 notices in the 'Your progress' tab", "really-simple-ssl"), $return=true);
	    add_settings_field('id_dismiss_all_notices', $help_tip . "<div class='rsssl-settings-text'>" .  __("Dismiss all Really Simple SSL notices", "really-simple-ssl"), array($this, 'get_option_dismiss_all_notices'), 'rlrsssl', 'rlrsssl_settings');
//        add_settings_field('id_deactivate_keep_ssl', __("Deactivate plugin and keep SSL", "really-simple-ssl"), array($this, 'get_option_deactivate_keep_ssl'), 'rlrsssl', 'rlrsssl_settings', ["class" => "rsssl-deactivate-keep-ssl"]);

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
        $newinput['dismiss_review_notice'] = $this->dismiss_review_notice;

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

	    if (!empty($input['dismiss_all_notices']) && $input['dismiss_all_notices'] == '1') {
		    $newinput['dismiss_all_notices'] = TRUE;
	    } else {
		    $newinput['dismiss_all_notices'] = FALSE;
	    }

        if (!empty($input['dismiss_review_notice']) && $input['dismiss_review_notice'] == '1') {
            $newinput['dismiss_review_notice'] = TRUE;
        } else {
            $newinput['dismiss_review_notice'] = FALSE;
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
//        RSSSL()->rsssl_help->get_help_tip(__("This is a fallback you should only use if other redirection methods do not work.", "really-simple-ssl"));
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
        <label class="rsssl-switch" id="rsssl-maybe-highlight-wp-redirect-to-htaccess">
            <input id="rlrsssl_options" name="rlrsssl_options[htaccess_redirect]" size="40" value="1"
                   type="checkbox" <?php checked(1, $this->htaccess_redirect, true) ?> />
            <span class="rsssl-slider rsssl-round"></span>
        </label>
        <?php
        echo $comment;

        if ($this->uses_htaccess_conf()) {
            $htaccess_file = "htaccess.conf (/conf/htaccess.conf/)";
        } else {
	        $htaccess_file = ".htaccess";
        }

        if ($this->htaccess_redirect && (!is_writable($this->htaccess_file()) || !$this->htaccess_test_success)) {
//            echo "<br><br>";
//            if (!is_writable($this->htaccess_file())) _e("The $htaccess_file file is not writable. Add these lines to your htaccess manually, or set 644 writing permissions.", "really-simple-ssl");
//            if (!$this->htaccess_test_success) _e("The .htaccess redirect rules that were selected by this plugin failed in the test. The following redirect rules were tested:", "really-simple-ssl");
//            echo "<br><br>";
            if ($this->ssl_type != "NA") {
//                $manual = true;
//                $rules = $this->get_redirect_rules($manual);
//
//                $arr_search = array("<", ">", "\n");
//                $arr_replace = array("&lt", "&gt", "<br>");
//                $rules = str_replace($arr_search, $arr_replace, $rules);
//
//                ?>
<!--                <code>-->
<!--                    --><?php //echo $rules; ?>
<!--                </code>-->
<!--                --><?php
            } else {
                _e("The plugin could not detect any possible redirect rule.", "really-simple-ssl");
            }
        }

        //on multisite, the .htaccess do not edit option is not available
        if (!is_multisite()) {
            if ($this->do_not_edit_htaccess) {
//                _e("If the setting 'do not edit htaccess' is enabled, you can't change this setting.", "really-simple-ssl");
            } elseif (!$this->htaccess_redirect) {
//htaccess_redirect                $link_start = '<a target="_blank" href="https://really-simple-ssl.com/knowledge-base/remove-htaccess-redirect-site-lockout/">';
//                $link_end = '</a>';
//                printf(
//                    __('Before you enable this, make sure you know how to %1$sregain access%2$s to your site in case of a redirect loop.', 'really-simple-ssl'),
//                    $link_start,
//                    $link_end
//                );
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
    }

	/**
	 *
     * Get the option to dismiss all Really Simple SSL notices
     *
     * @since 3.2
     *
     * @access public
     *
	 */

	public function get_option_dismiss_all_notices()
	{
		?>
        <label class="rsssl-switch">
            <input id="rlrsssl_options" name="rlrsssl_options[dismiss_all_notices]" size="40" value="1"
                   type="checkbox" <?php checked(1, $this->dismiss_all_notices, true) ?> />
            <span class="rsssl-slider rsssl-round"></span>
        </label>
		<?php
	}

    /**
     *
     * Add a button and thickbox to deactivate the plugin while keeping SSL
     *
     * @since 3.0
     *
     * @access public
     *
     */


    public function get_option_deactivate_keep_ssl()
    {

        ?>
<!--        <style>-->
<!--            #TB_ajaxContent {-->
<!--                text-align: center !important;-->
<!--            }-->
<!--            #TB_window {-->
<!--                height: 370px !important;-->
<!--            }-->
<!--        </style>-->
<!--        <div><input class="thickbox button" title="" type="button" style="display: block; float: left;" alt="#TB_inline?-->
<!--        height=370&width=400&inlineId=deactivate_keep_ssl" value="--><?php //echo __('Deactivate Plugin and keep SSL', 'really-simple-ssl'); ?><!--"/></div>-->
<!--        <div id="deactivate_keep_ssl" style="display: none;">-->
<!--            <h1 style="margin: 10px 0; text-align: center;">--><?php //_e("Are you sure?", "really-simple-ssl") ?><!--</h1>-->
<!--            <h2 style="margin: 20px 0; text-align: left;">--><?php //_e("Deactivating the plugin while keeping SSL will do the following:", "really-simple-ssl") ?><!--</h2>-->
<!--            <ul style="text-align: left; font-size: 1.2em;">-->
<!--                <li>--><?php //_e("* The mixed content fixer will stop working", "really-simple-ssl") ?><!--</li>-->
<!--                <li>--><?php //_e("* The WordPress 301 and Javascript redirect will stop working", "really-simple-ssl") ?><!--</li>-->
<!--                <li>--><?php //_e("* Your site address will remain https://", "really-simple-ssl") ?><!-- </li>-->
<!--                <li>--><?php //_e("* The .htaccess redirect will remain active", "really-simple-ssl") ?><!--</li>-->
<!--                --><?php //_e("Deactivating the plugin via the plugins overview will revert the site back to http://.", "really-simple-ssl") ?>
<!--            </ul>-->
<!---->
<!--            <script>-->
<!--                jQuery(document).ready(function ($) {-->
<!--                    $('#rsssl_close_tb_window').click(tb_remove);-->
<!--                });-->
<!--            </script>-->
<!--            --><?php
//            $token = wp_create_nonce('rsssl_deactivate_plugin');
//            $deactivate_keep_ssl_link = admin_url("options-general.php?page=rlrsssl_really_simple_ssl&action=uninstall_keep_ssl&token=" . $token);
//
//            ?>
<!--            <a class="button rsssl-button-deactivate-keep-ssl" href="--><?php //add_thickbox() ?>
<!--                --><?php //echo $deactivate_keep_ssl_link ?><!--">--><?php //_e("I'm sure I want to deactivate", "really-simple-ssl") ?>
<!--            </a>-->
<!--            &nbsp;&nbsp;-->
<!--            <a class="button" href="#" id="rsssl_close_tb_window">--><?php //_e("Cancel", "really-simple-ssl") ?><!--</a>-->
<!---->
<!---->
<!--        </div>-->
        <?php
        RSSSL()->rsssl_help->get_help_tip(__("Clicking this button will deactivate the plugin while keeping your site on SSL. The WordPress 301 redirect, Javascript redirect and mixed content fixer will stop working. The site address will remain https:// and the .htaccess redirect will remain active. Deactivating the plugin via the plugins overview will revert the site back to http://.", "really-simple-ssl"));

    }

    /**
     * Since 3.3.2
     */

    public function get_option_dismiss_review_notice() {
        ?>
        <label class="rsssl-switch">
            <input id="rlrsssl_options" name="rlrsssl_options[dismiss_review_notice]" size="40" value="1"
                   type="checkbox" <?php checked(1, $this->dismiss_review_notice, true) ?> />
            <span class="rsssl-slider rsssl-round"></span>
        </label>
        <?php
    }

	/**
	 *
     * Mixed content fixer option
     *
	 */

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
                $premium_link = "<a target='_blank' href='$this->pro_url'>" . __('Premium Support', 'really-simple-ssl') . '</a>';
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

	/**
	 * @return mixed|string
     *
     * Retrieve the contents of the test page
     *
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
     */

    public function htaccess_file() {
        if ($this->uses_htaccess_conf()) {
            $htaccess_file = realpath(dirname(ABSPATH) . "/conf/htaccess.conf");
        } else {
            $htaccess_file = $this->ABSpath . ".htaccess";
        }

        return $htaccess_file;
    }

	/**
	 *
     * Insert script to highlight option after dashboard click
     *
     * @since 3.2
     *
     * @access public
     *
	 */

    public function highlight_js(){
        ?>
        <script>
            jQuery(document).ready(function ($) {
                'use strict';
                <?php
                    if (isset($_GET['highlight'])) {
	                    $setting_name = sanitize_text_field( $_GET['highlight'] );
	                    echo "var setting_name = '$setting_name'" . ";";
                    }
                ?>

                $(function() {
                    if(typeof setting_name !== 'undefined' && setting_name != '') {
                        if (document.location.href.indexOf('&highlight=' + setting_name) > -1) {
                            $('#rsssl-maybe-highlight-' + setting_name).closest('tr').addClass('rsssl-highlight');
                        }
                    }
                });
            });
        </script>
    <?php
    }

	/**
	 *
     * Determine whether or not to remove the &highlight= parameter from URL
     *
     * @since 3.2
     *
     * @access public
     *
	 */

    public function maybe_remove_highlight_from_url() {

	    $http_referrer = isset($_POST['_wp_http_referer']) ? $_POST['_wp_http_referer'] : false;
	    if ($http_referrer && strpos( $http_referrer, "&highlight=" ) ) {
		    $url = add_query_arg( array(
			    "page" => "rlrsssl_really_simple_ssl",
			    "tab"  => "settings"
		    ), admin_url( "options-general.php" ) );
		    wp_safe_redirect( $url );
		    exit;
	    }
    }

	public function get_template($file, $path = rsssl_path, $args = array())
	{

		$file = trailingslashit($path) . 'templates/' . $file;
		$theme_file = trailingslashit(get_stylesheet_directory()) . dirname(rsssl_path) . $file;

		if (file_exists($theme_file)) {
			$file = $theme_file;
		}

		if (strpos($file, '.php') !== false) {
			ob_start();
			require $file;
			$contents = ob_get_clean();
		} else {
			$contents = file_get_contents($file);
		}

		return $contents;
	}
} //class closure

/**
 * Wrapper functions for dashboard notices()
 * @return string
 */

if (!function_exists('rsssl_mixed_content_fixer_detected')) {
	function rsssl_mixed_content_fixer_detected() {
		return RSSSL()->really_simple_ssl->mixed_content_fixer_detected();
	}
}

if (!function_exists('rsssl_site_has_ssl')) {
	function rsssl_site_has_ssl() {
		return RSSSL()->really_simple_ssl->site_has_ssl;
	}
}

if (!function_exists('rsssl_autoreplace_insecure_links')) {
	function rsssl_autoreplace_insecure_links() {
		return RSSSL()->really_simple_ssl->autoreplace_insecure_links;
	}
}

if (!function_exists('rsssl_ssl_enabled')) {
    function rsssl_ssl_enabled() {
        if ( RSSSL()->really_simple_ssl->ssl_enabled ) {
            return true;
        } else {
            return false;
        }
    }
}

if (!function_exists('rsssl_ssl_enabled_notice')) {
	function rsssl_ssl_enabled_notice() {
		if ( RSSSL()->really_simple_ssl->ssl_enabled ) {
			return 'ssl-enabled';
		} else {
			return 'ssl-not-enabled';
		}
	}
}

if (!function_exists('rsssl_ssl_detected')) {
	function rsssl_ssl_detected() {
		if ( ! RSSSL()->really_simple_ssl->wpconfig_ok() ) {
			return 'fail';
		}
		if ( ! RSSSL()->really_simple_ssl->site_has_ssl ) {
			return 'no-ssl-detected';
		}
		if ( RSSSL()->rsssl_certificate->is_valid() ) {
			return 'ssl-detected';
		}

		return 'ssl-detected';
	}
}

if (!function_exists('rsssl_check_redirect')) {
	function rsssl_check_redirect() {
		if ( ! RSSSL()->really_simple_ssl->has_301_redirect() ) {
			return 'no-redirect-set';
		}
		if ( RSSSL()->really_simple_ssl->has_301_redirect() && RSSSL()->rsssl_server->uses_htaccess() && RSSSL()->really_simple_ssl->htaccess_contains_redirect_rules() ) {
			return 'htaccess-redirect-set';
		}
		if ( RSSSL()->really_simple_ssl->has_301_redirect() && RSSSL()->really_simple_ssl->wp_redirect && RSSSL()->rsssl_server->uses_htaccess() && ! RSSSL()->really_simple_ssl->htaccess_redirect ) {
			return 'wp-redirect-to-htaccess';
		}
		if ( RSSSL()->rsssl_server->uses_htaccess() && ( ! is_multisite() || ! RSSSL()->rsssl_multisite->is_per_site_activated_multisite_subfolder_install() ) ) {
			if ( ! is_writable( RSSSL()->really_simple_ssl->htaccess_file() ) ) {
				return 'htaccess-not-writeable';
			} else {
				return 'htaccess-cannot-be-set';
			}
		} else {
			return 'default';
		}
	}
}

if (!function_exists('rsssl_hsts_enabled')) {
	function rsssl_hsts_enabled() {
		if ( RSSSL()->really_simple_ssl->contains_hsts() ) {
			return 'contains-hsts';
		} else {
			return 'no-hsts';
		}
	}
}

if (!function_exists('rsssl_secure_cookies_set')) {
	function rsssl_secure_cookies_set() {
		if ( RSSSL()->really_simple_ssl->contains_secure_cookie_settings() ) {
			return 'set';
		} else {
			return 'not-set';
		}
	}
}

if (!function_exists('rsssl_scan_upsell')) {
	function rsssl_scan_upsell() {
		return 'upsell';
	}
}

if (!function_exists('rsssl_htaccess_redirect_allowed')) {
	function rsssl_htaccess_redirect_allowed() {
		return RSSSL()->really_simple_ssl->htaccess_redirect_allowed();
	}
}

// Non-prefixed for backwards compatibility
if (!function_exists('uses_elementor')) {
	function uses_elementor() {
		if ( defined( 'ELEMENTOR_VERSION' ) || defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			return true;
		} else {
			return false;
		}
	}
}

if (!function_exists('rsssl_uses_elementor')) {
	function rsssl_uses_elementor() {
		if ( defined( 'ELEMENTOR_VERSION' ) || defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			return true;
		} else {
			return false;
		}
	}
}

if (!function_exists('rsssl_uses_divi')) {
	function rsssl_uses_divi() {
		if ( defined( 'ET_CORE_PATH' ) ) {
			return true;
		} else {
			return false;
		}
	}
}

if (!function_exists('rsssl_ssl_activation_time_no_longer_then_3_days_ago')) {
	function rsssl_ssl_activation_time_no_longer_then_3_days_ago() {

		$activation_time             = get_option( 'rsssl_activation_timestamp' );
		$three_days_after_activation = $activation_time + 3 * DAY_IN_SECONDS;

		if ( time() < $three_days_after_activation ) {
			return true;
		} else {
			return false;
		}
	}
}

if (!function_exists('rsssl_elementor_notice')) {
	function rsssl_elementor_notice() {
		return 'elementor-notice';
	}
}

if (!function_exists('rsssl_wp_redirect_condition')) {
	function rsssl_wp_redirect_condition() {
		if ( RSSSL()->really_simple_ssl->has_301_redirect() && RSSSL()->really_simple_ssl->wp_redirect && ! RSSSL()->really_simple_ssl->htaccess_redirect ) {
			return true;
		} else {
			return false;
		}
	}
}

if (!function_exists('rsssl_wordpress_redirect')) {
	function rsssl_wordpress_redirect() {
		if ( RSSSL()->really_simple_ssl->has_301_redirect() && RSSSL()->really_simple_ssl->wp_redirect ) {
			return '301-wp-redirect';
		} else {
			return 'no-redirect';
		}
	}
}

if (!function_exists('rsssl_no_multisite')) {
	function rsssl_no_multisite() {
		if ( ! is_multisite() ) {
			return true;
		} else {
			return false;
		}
	}
}

if (!function_exists('rsssl_does_not_use_pro')) {
	function rsssl_does_not_use_pro() {
		if ( ! defined("rsssl_pro_version") ) {
		    // Does not use RSSSL pro
			return true;
		} else {
			return false;
		}
	}
}

if (!function_exists('rsssl_google_analytics_notice')) {
    function rsssl_google_analytics_notice() {
        return 'ga';
    }
}