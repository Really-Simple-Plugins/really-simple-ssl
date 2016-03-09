<?php
defined('ABSPATH') or die("you do not have acces to this page!");

  class rsssl_admin extends rsssl_front_end {

  private static $_this;

  //wpconfig fixing variables @TODO: convert to error array
  //true when siteurl and homeurl are defined in wp-config and can't be changed
  public $wpconfig_siteurl_not_fixed          = FALSE;
  public $no_server_variable                  = FALSE;
  public $errors                              = Array();

  public $do_wpconfig_loadbalancer_fix        = FALSE;
  public $ssl_enabled                         = FALSE;

  //multisite variables
  public $set_rewriterule_per_site          = FALSE;
  public $sites                             = Array(); //for multisite, list of all activated sites.

  //general settings
  public $capability                        = 'manage_options';

  public $ssl_test_page_error;
  public $htaccess_test_success             = FALSE;

  public $plugin_dir                        = "really-simple-ssl";
  public $plugin_filename                   = "rlrsssl-really-simple-ssl.php";
  public $ABSpath;

  public $do_not_edit_htaccess              = FALSE;
  public $htaccess_warning_shown            = FALSE;
  public $wpmu_subfolder_warning_shown      = FALSE;
  public $ssl_success_message_shown         = FALSE;
  public $hsts                              = FALSE;
  public $debug							                = TRUE;
  public $debug_log;

  public $plugin_conflict                   = ARRAY();
  public $plugin_url;
  public $plugin_version;
  public $plugin_db_version;
  public $plugin_upgraded;
  public $mixed_content_fixer_status        =0;

  public $ssl_redirect_set_in_htaccess      = FALSE;
  public $settings_changed                  = FALSE;
  public $ssl_type                          = "NA";
                                            //possible values:
                                            //"NA":     test page did not return valid response
                                            //"SERVER-HTTPS-ON"
                                            //"SERVER-HTTPS-1"
                                            //"SERVERPORT443"
                                            //"LOADBALANCER"
                                            //"CDN"
  private $ad = false;
  private $pro_url = "https://www.really-simple-ssl.com/pro";

  function __construct() {
    if ( isset( self::$_this ) )
        wp_die( sprintf( __( '%s is a singleton class and you cannot create a second instance.','really-simple-ssl' ), get_class( $this ) ) );

    self::$_this = $this;

    $this->get_options();
    $this->get_admin_options();
    $this->ABSpath = $this->getABSPATH();
    $this->get_plugin_version();
    $this->get_plugin_upgraded(); //call always, otherwise db version will not match anymore.

    register_activation_hook(  dirname( __FILE__ )."/".$this->plugin_filename, array($this,'activate') );
    register_deactivation_hook(dirname( __FILE__ )."/".$this->plugin_filename, array($this,'deactivate') );
  }

  static function this() {
    return self::$_this;
  }

  /**
   * Initializes the admin class
   *
   * @since  2.2
   *
   * @access public
   *
   */

  public function init() {
    global $rsssl_cache;
    $is_on_settings_page = $this->is_settings_page();

    if ($this->set_rewriterule_per_site) $this->build_domain_list();
    $this->get_plugin_url();//has to be after the domain list was built.

    /*
      Detect configuration when:
      - SSL activation just confirmed.
      - on settings page
      - No SSL detected -> check again
    */

    if ($this->clicked_activate_ssl() || !$this->ssl_enabled || !$this->site_has_ssl || $is_on_settings_page) {
      $this->detect_configuration();

      if (!$this->wpconfig_ok()) {
        //if we were to activate ssl, this could result in a redirect loop. So warn first.
        //$this->site_has_ssl = false;
        add_action("admin_notices", array($this, 'show_notice_wpconfig_needs_fixes'));
        $this->ssl_enabled = false;
        $this->save_options();
      } elseif ($this->ssl_enabled) {
        add_action('init',array($this,'configure_ssl'),20);
        add_action('admin_init', array($rsssl_cache,'flush'),40);
      }
    }

    //when no ssl is detected, and not enabled by user, ask for activation.
    if (!$this->ssl_enabled) {
      $this->trace_log("ssl not enabled, show notice");
      add_action("admin_notices", array($this, 'show_notice_activate_ssl'),10);
    } else {
      $this->trace_log("ssl already enabled");
    }

    add_action('plugins_loaded', array($this,'check_plugin_conflicts'),30);

    //add the settings page for the plugin
    add_action('admin_menu', array($this,'setup_admin_page'),30);

    //check if the uninstallfile is safely renamed to php.
    $this->check_for_uninstall_file();

    //callbacks for the ajax dismiss buttons
    add_action('wp_ajax_dismiss_htaccess_warning', array($this,'dismiss_htaccess_warning_callback') );
    add_action('wp_ajax_dismiss_wpmu_subfolder_warning', array($this,'dismiss_wpmu_subfolder_warning_callback') );
    add_action('wp_ajax_dismiss_success_message', array($this,'dismiss_success_message_callback') );

    //handle notices
    add_action('admin_notices', array($this,'show_notices'));

  }


  /*
    checks if the user just clicked the "activate ssl" button.
  */

  private function clicked_activate_ssl() {
    if (isset($_POST['rsssl_do_activate_ssl']) && isset( $_POST['rsssl_nonce'] ) && wp_verify_nonce( $_POST['rsssl_nonce'], 'rsssl_nonce' ) ) {
      $this->ssl_enabled=true;
      $this->save_options();
      return true;
    }
    return false;
  }

public function wpconfig_ok(){
  if (($this->do_wpconfig_loadbalancer_fix || $this->no_server_variable || $this->wpconfig_siteurl_not_fixed) && !$this->wpconfig_is_writable() ) {
    return false;
  } else {
    return true;
  }
}

  /*
      This message is shown when no ssl is not enabled by the user yet
  */

  public function show_notice_activate_ssl(){
    if (!$this->wpconfig_ok()) return;

    if (!$this->site_has_ssl) {  ?>
      <div id="message" class="error fade notice activate-ssl">
      <p><?php _e("No SSL was detected. If you do have an ssl certificate, try to change your current url in the browser address bar to https.","really-simple-ssl");?></p>

    <?php } else {?>

    <div id="message" class="updated fade notice activate-ssl">
      <h1><?php _e("Almost ready to migrate to SSL!","really-simple-ssl");?></h1>
    <?php
  }?>
  <?php _e("Some things can't be done automatically. Before you migrate, please check for: ",'really-simple-ssl');?>
  <p>
    <ul>
      <li><?php _e('Http resources in your .css and .js files: change any http:// into //','really-simple-ssl');?></li>
      <li><?php _e('External resources in your site that cannot load on ssl: remove them or move to your own server.','really-simple-ssl');?></li>
      <li><?php _e('Urls to your server without ssl certificate: change into your own url.','really-simple-ssl');?></li>
    </ul>
  </p>
  <?php $this->show_pro(); ?>

  <?php if ($this->site_has_ssl) { ?>
      <form action="" method="post">
        <?php wp_nonce_field( 'rsssl_nonce', 'rsssl_nonce' );?>
        <input type="submit" class='button button-primary' value="Go ahead, activate SSL!" id="rsssl_do_activate_ssl" name="rsssl_do_activate_ssl">
      </form>
  <?php } ?>

  </div>
  <?php }

  /**
    * @since 2.3
    * Shows option to buy pro

  */

  public function show_pro(){
    if (!$this->ad) return;
    ?>
  <p ><?php _e('For an extensive scan of your website, with a list of items to fix, and instructions how to do it, Purchase Really Simple SSL Pro, which includes:','really-simple-ssl');?>
    <ul class="rsssl_bullets">
      <li><?php _e('Full website scan for mixed content in .css and .js files','really-simple-ssl');?></li>
      <li><?php _e('Full website scan for any resource that is loaded from another domain, and cannot load over ssl','really-simple-ssl');?></li>
      <li><?php _e('Full website scan to find external css or js files with mixed content.','really-simple-ssl');?></li>
    </ul></p>
    <a target="_blank" href="<?php echo $this->pro_url;?>" class='button button-primary'>Learn about Really Simple SSL PRO</a>
    <?php
  }

  public function wpconfig_is_writable() {
    $wpconfig_path = $this->find_wp_config_path();
    if (is_writable($wpconfig_path))
      return true;
    else
      return false;
  }

  /*
  *     Check if the uninstall file is renamed to .php
  */

  protected function check_for_uninstall_file() {
    if (file_exists(dirname( __FILE__ ) .  '/force-deactivate.php')) {
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

  public function get_admin_options(){
    //if the define is true, it overrides the db setting.
    $this->do_not_edit_htaccess = (defined( 'RLRSSSL_DO_NOT_EDIT_HTACCESS' ) &&  RLRSSSL_DO_NOT_EDIT_HTACCESS) ? TRUE : FALSE;

    $options = get_option('rlrsssl_options');
    if (isset($options)) {
      $this->hsts                               = isset($options['hsts']) ? $options['hsts'] : FALSE;
      $this->htaccess_warning_shown             = isset($options['htaccess_warning_shown']) ? $options['htaccess_warning_shown'] : FALSE;
      $this->wpmu_subfolder_warning_shown       = isset($options['wpmu_subfolder_warning_shown']) ? $options['wpmu_subfolder_warning_shown'] : FALSE;
      $this->ssl_success_message_shown          = isset($options['ssl_success_message_shown']) ? $options['ssl_success_message_shown'] : FALSE;
      $this->plugin_db_version                  = isset($options['plugin_db_version']) ? $options['plugin_db_version'] : "1.0";
      $this->debug                              = isset($options['debug']) ? $options['debug'] : FALSE;
      $this->do_not_edit_htaccess               = isset($options['do_not_edit_htaccess']) ? $options['do_not_edit_htaccess'] : $this->do_not_edit_htaccess;

    }

    if (is_multisite()) {
      //migrate options to networkwide option
      $this->migrate_options_to_network();
      $network_options = get_site_option('rlrsssl_network_options');
      if (isset($network_options)) {
        $this->set_rewriterule_per_site  = isset($network_options['set_rewriterule_per_site']) ? $network_options['set_rewriterule_per_site'] : FALSE;
      }
    }
  }


  /**
  *       @since 2.13
  *
  *       Fixes a multisite bug where networkwide options were saved in each blog locally.
  *
  **/

  private function migrate_options_to_network() {
    //for upgrade purposes, check if rewrite_rule_per_site exists for the main blog, and load that as the default one.
    $main_blog_options = get_blog_option(BLOG_ID_CURRENT_SITE, "rlrsssl_options");
    if (isset($main_blog_options) && isset($main_blog_options['set_rewriterule_per_site'])) {

        $this->set_rewriterule_per_site = $main_blog_options['set_rewriterule_per_site'];

        //now, remove this option from the array
        unset($main_blog_options["set_rewriterule_per_site"]);

        //save it back into the main site, so we on next check, this function won't run
        update_blog_option(BLOG_ID_CURRENT_SITE, 'rlrsssl_options',$main_blog_options);

        $this->save_options();
    }
  }

  /**
   * Checks if the site is multisite, and if the plugin was installed networkwide
   * If not networkwide and multisite, the htaccess rewrite should be on a per site basis.
   *
   * @since  2.1
   *
   * @access public
   *
   */

  public function detect_if_rewrite_per_site($networkwide) {
    if (is_multisite() && !$networkwide) {
        $this->trace_log("per site activation");
        $this->set_rewriterule_per_site = TRUE;
    } else {
        $this->trace_log("networkwide activated");
        $this->set_rewriterule_per_site = FALSE;
    }
    $this->save_options();
  }

  /**
   * Creates an array of all domains where the plugin is active AND ssl is active, only used for multisite.
   *
   * @since  2.1
   *
   * @access public
   *
   */

  public function build_domain_list() {
    //create list of all activated  sites with ssl
    $this->sites = array();
    $sites = wp_get_sites();
    if ($this->debug) $this->trace_log("building domain list for multiste...");
    foreach ( $sites as $site ) {
        switch_to_blog( $site[ 'blog_id' ] );
        $plugin = $this->plugin_dir."/".$this->plugin_filename;
        $options = get_option('rlrsssl_options');

        $blog_has_ssl = FALSE;
        if (isset($options)) {
          $blog_has_ssl = isset($options['site_has_ssl']) ? $options['site_has_ssl'] : FALSE;
        }

        if (is_plugin_active($plugin) && $blog_has_ssl) {
          if ($this->debug) $this->trace_log("adding: ".home_url());
          $this->sites[] = home_url();
        }
        restore_current_blog(); //switches back to previous blog, not current, so we have to do it each loop
      }

      $this->save_options();
  }

  /**
   * Workaround to use add_action after plugin activation
   *
   * @since  2.1
   *
   * @access public
   *
   */

  public function activate($networkwide) {
    $this->detect_if_rewrite_per_site($networkwide);
    $this->save_options();
    //add_option('really_simple_ssl_activated', 'activated' );
  }

  /**
   * check if the plugin was upgraded to a new version
   *
   * @since  2.1
   *
   * @access public
   *
   */

  public function get_plugin_upgraded() {
  	if ($this->plugin_db_version!=$this->plugin_version) {
  		$this->plugin_db_version = $this->plugin_version;
  		$this->plugin_upgraded = true;
      $this->save_options();
  	}
    $this->plugin_upgraded = false;
  }


  /**
   * check if the plugin was just activated
   *
   * @since  2.1
   *
   * @access public
   *
   */

  public function plugin_activated() {
	if (get_option('really_simple_ssl_activated') == 'activated') {
		delete_option( 'really_simple_ssl_activated' );
		return true;
	}
	return false;
  }

  /**
   * Log events during plugin execution
   *
   * @since  2.1
   *
   * @access public
   *
   */

  public function trace_log($msg) {
    if (!$this->debug) return;
    $this->debug_log = $this->debug_log."<br>".$msg;
    //$this->debug_log = strstr($this->debug_log,'"** Detecting configuration **"');
    error_log($msg);
  }

  /**
   * Configures the site for ssl
   *
   * @since  2.2
   *
   * @access public
   *
   */

  public function configure_ssl() {
      if (!current_user_can($this->capability)) return;
      $this->trace_log("** Configuring SSL **");
      if ($this->site_has_ssl || $this->force_ssl_without_detection) {

        //when a known ssl_type was found, test if the redirect works
        if ($this->ssl_type != "NA")
            $this->test_htaccess_redirect();

        //in a configuration of loadbalancer without a set server variable https = 0, add code to wpconfig
        if ($this->do_wpconfig_loadbalancer_fix)
            $this->wpconfig_loadbalancer_fix();

        if ($this->no_server_variable)
            $this->wpconfig_server_variable_fix();

        if ( class_exists( 'Jetpack' ) )
            $this->wpconfig_jetpack();

        $this->editHtaccess();

        if ($this->wpconfig_siteurl_not_fixed)
          $this->fix_siteurl_defines_in_wpconfig();

        $this->set_siteurl_to_ssl();
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

  public function is_settings_page() {
    parse_str($_SERVER['QUERY_STRING'], $params);
    if (array_key_exists("page", $params) && ($params["page"]=="rlrsssl_really_simple_ssl")) {
        return true;
    }
    return false;
  }



  /**
   * Retrieves the current version of this plugin
   *
   * @since  2.1
   *
   * @access public
   *
   */

  public function get_plugin_version() {
      if ( ! function_exists( 'get_plugins' ) )
          require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
      $plugin_folder = get_plugins( '/' . plugin_basename( dirname( __FILE__ ) ) );
      $this->plugin_version = $plugin_folder[$this->plugin_filename]['Version'];
  }

  /**
   * Find the path to wp-config
   *
   * @since  2.1
   *
   * @access public
   *
   */

  public function find_wp_config_path() {
    //limit nr of iterations to 20
    $i=0;
    $maxiterations = 20;
    $dir = dirname(__FILE__);
    do {
        $i++;
        if( file_exists($dir."/wp-config.php") ) {
            return $dir."/wp-config.php";
        }
    } while( ($dir = realpath("$dir/..")) && ($i<$maxiterations) );
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

  public function remove_ssl_from_siteurl_in_wpconfig() {
      $wpconfig_path = $this->find_wp_config_path();
      if (!empty($wpconfig_path)) {
        $wpconfig = file_get_contents($wpconfig_path);

        $homeurl_pos = strpos($wpconfig, "define('WP_HOME','https://");
        $siteurl_pos = strpos($wpconfig, "define('WP_SITEURL','https://");

        if (($homeurl_pos !== false) || ($siteurl_pos !== false)) {
          if (is_writable($wpconfig_path)) {
            $search_array = array("define('WP_HOME','https://","define('WP_SITEURL','https://");
            $ssl_array = array("define('WP_HOME','http://","define('WP_SITEURL','http://");
            //now replace these urls
            $wpconfig = str_replace ($search_array , $ssl_array , $wpconfig);
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

  private function check_for_siteurl_in_wpconfig(){
    $wpconfig_path = $this->find_wp_config_path();

    if (empty($wpconfig_path)) return;

    $wpconfig = file_get_contents($wpconfig_path);
    $homeurl_pattern = '/(define\(\s*\'WP_HOME\'\s*,\s*\'http\:\/\/)/';
    $siteurl_pattern = '/(define\(\s*\'WP_SITEURL\'\s*,\s*\'http\:\/\/)/';

    $this->wpconfig_siteurl_not_fixed = FALSE;
    if (preg_match($homeurl_pattern, $wpconfig) || preg_match($siteurl_pattern, $wpconfig) ) {
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

  private function fix_siteurl_defines_in_wpconfig() {
      $wpconfig_path = $this->find_wp_config_path();

      if (empty($wpconfig_path)) return;

      $wpconfig = file_get_contents($wpconfig_path);
      $homeurl_pattern = '/(define\(\s*\'WP_HOME\'\s*,\s*\'http\:\/\/)/';
      $siteurl_pattern = '/(define\(\s*\'WP_SITEURL\'\s*,\s*\'http\:\/\/)/';

      if (preg_match($homeurl_pattern, $wpconfig) || preg_match($siteurl_pattern, $wpconfig) ) {
        if (is_writable($wpconfig_path)) {
          $this->trace_log("wp config siteurl/homeurl edited.");
          $wpconfig = preg_replace($homeurl_pattern, "define('WP_HOME','https://", $wpconfig);
          $wpconfig = preg_replace($siteurl_pattern, "define('WP_SITEURL','https://", $wpconfig);
          file_put_contents($wpconfig_path, $wpconfig);
        }
        else {
          if ($this->debug) {$this->trace_log("not able to fix wpconfig siteurl/homeurl.");}
          //only when siteurl or homeurl is defined in wpconfig, and wpconfig is not writable is there a possible issue because we cannot edit the defined urls.
          $this->wpconfig_siteurl_not_fixed = TRUE;
        }
      } else {
        if ($this->debug) {$this->trace_log("no siteurl/homeurl defines in wpconfig");}
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

  public function wpconfig_has_fixes() {
    $wpconfig_path = $this->find_wp_config_path();
    if (empty($wpconfig_path)) return false;
    $wpconfig = file_get_contents($wpconfig_path);

    //only one of two fixes possible.
    if (strpos($wpconfig, "//Begin Really Simple SSL Load balancing fix")!==FALSE ) {
      return true;
    }

    if (strpos($wpconfig, "//Begin Really Simple SSL Server variable fix")!==FALSE ) {
      return true;
    }

    return false;
  }



  /**
   * When Jetpack is installed, add some code to wpconfig to make it ssl proof
   *
   * @since  2.13
   *
   * @access private
   *
   */

  private function wpconfig_jetpack() {
      $wpconfig_path = $this->find_wp_config_path();
      if (empty($wpconfig_path)) return;
      $wpconfig = file_get_contents($wpconfig_path);

      if (strpos($wpconfig, "//Begin Really Simple SSL JetPack fix")===FALSE ) {
        if (is_writable($wpconfig_path)) {
          $rule  = "\n"."//Begin Really Simple SSL JetPack fix"."\n";
          $rule .= 'define( "JETPACK_SIGNATURE__HTTPS_PORT", 80 );'."\n";
          $rule .= "//END Really Simple SSL"."\n";

          $insert_after = "<?php";
          $pos = strpos($wpconfig, $insert_after);
          if ($pos !== false) {
              $wpconfig = substr_replace($wpconfig,$rule,$pos+1+strlen($insert_after),0);
          }

          file_put_contents($wpconfig_path, $wpconfig);
          $this->trace_log("wp config jetpack fix inserted");
        } else {
          $this->trace_log("wp config jetpack fix FAILED");
        }
      } else {
        $this->trace_log("wp config jetpack fix already in place");
      }
      $this->save_options();

  }



  /**
   * In case of load balancer without server https on, add fix in wp-config
   *
   * @since  2.1
   *
   * @access public
   *
   */

  public function wpconfig_loadbalancer_fix() {
      $wpconfig_path = $this->find_wp_config_path();
      if (empty($wpconfig_path)) return;
      $wpconfig = file_get_contents($wpconfig_path);
      $this->wpconfig_loadbalancer_fix_failed = FALSE;
      //only if loadbalancer AND NOT SERVER-HTTPS-ON should the following be added. (is_ssl = false)
      if (strpos($wpconfig, "//Begin Really Simple SSL Load balancing fix")===FALSE ) {
        if (is_writable($wpconfig_path)) {
          $rule  = "\n"."//Begin Really Simple SSL Load balancing fix"."\n";
          $rule .= 'if (isset($_SERVER["HTTP_X_FORWARDED_PROTO"] ) && "https" == $_SERVER["HTTP_X_FORWARDED_PROTO"] ) {'."\n";
          $rule .= '$_SERVER["HTTPS"] = "on";'."\n";
          $rule .= "}"."\n";
          $rule .= "//END Really Simple SSL"."\n";

          $insert_after = "<?php";
          $pos = strpos($wpconfig, $insert_after);
          if ($pos !== false) {
              $wpconfig = substr_replace($wpconfig,$rule,$pos+1+strlen($insert_after),0);
          }

          file_put_contents($wpconfig_path, $wpconfig);
          if ($this->debug) {$this->trace_log("wp config loadbalancer fix inserted");}
        } else {
          if ($this->debug) {$this->trace_log("wp config loadbalancer fix FAILED");}
          $this->wpconfig_loadbalancer_fix_failed = TRUE;
        }
      } else {
        if ($this->debug) {$this->trace_log("wp config loadbalancer fix already in place, great!");}
      }
      $this->save_options();

  }

  /**
   * Checks if we are on a subfolder install. (domain.com/site1 )
   *
   * @since  2.2
   *
   * @access protected
   *
   */

  protected function is_multisite_subfolder_install() {
    if (!is_multisite()) return FALSE;
    //we check this manually, as the SUBDOMAIN_INSTALL constant of wordpress might return false for domain mapping configs
    foreach ($this->sites as $site) {
      if ($this->is_subfolder($site)) return TRUE;
    }

    return false;
  }

    /**
     * Getting Wordpress to recognize setup as being ssl when no https server variable is available
     *
     * @since  2.1
     *
     * @access public
     *
     */

    public function wpconfig_server_variable_fix() {

      $wpconfig_path = $this->find_wp_config_path();
      if (empty($wpconfig_path)) return;
      $wpconfig = file_get_contents($wpconfig_path);
      //$this->wpconfig_server_variable_fix_failed = FALSE;

      //check permissions
      if (!is_writable($wpconfig_path)) {
        if ($this->debug) $this->trace_log("wp-config.php not writable");
        //$this->wpconfig_server_variable_fix_failed = TRUE;
        return;
      }

      //when more than one blog, first remove what we have
      if (is_multisite() && !$this->is_multisite_subfolder_install() && $this->set_rewriterule_per_site && count($this->sites)>1) {
        $wpconfig = preg_replace("/\/\/Begin\s?Really\s?Simple\s?SSL.*?\/\/END\s?Really\s?Simple\s?SSL/s", "", $wpconfig);
        $wpconfig = preg_replace("/\n+/","\n", $wpconfig);
        file_put_contents($wpconfig_path, $wpconfig);
      }

      //now create new

      //check if the fix is already there
      if (strpos($wpconfig, "//Begin Really Simple SSL Server variable fix")!==FALSE ) {
          if ($this->debug) {$this->trace_log("wp config server variable fix already in place, great!");}
          return;
      }

      if ($this->debug) {$this->trace_log("Adding server variable to wpconfig");}
      $rule = $this->get_server_variable_fix_code();

      $insert_after = "<?php";
      $pos = strpos($wpconfig, $insert_after);
      if ($pos !== false) {
          $wpconfig = substr_replace($wpconfig,$rule,$pos+1+strlen($insert_after),0);
      }
      file_put_contents($wpconfig_path, $wpconfig);
      if ($this->debug) $this->trace_log("wp config server variable fix inserted");

      $this->save_options();
  }


protected function get_server_variable_fix_code(){
  if ($this->set_rewriterule_per_site && $this->is_multisite_subfolder_install()) {
      if ($this->debug) $this->trace_log("per site activation on subfolder install, wp config server variable fix skipped");
      return "";
  }

  if (is_multisite() && $this->set_rewriterule_per_site && count($this->sites)==0) {
    if ($this->debug) $this->trace_log("no sites left with ssl, wp config server variable fix skipped");
    return "";
  }

  if (is_multisite() && $this->set_rewriterule_per_site) {
    $rule  = "\n"."//Begin Really Simple SSL Server variable fix"."\n";
    foreach ($this->sites as $domain ) {
        //remove http or https.
        if ($this->debug) {$this->trace_log("getting server variable rule for:".$domain);}
        $domain = preg_replace("/(http:\/\/|https:\/\/)/","",$domain);

        //we excluded subfolders, so treat as domain
        //check only for domain without www, as the www variant is found as well with the no www search.
        $domain_no_www  = str_replace ( "www." , "" , $domain);

        $rule .= 'if ( strpos($_SERVER["HTTP_HOST"], "'.$domain_no_www.'")!==FALSE ) {'."\n";
        $rule .= '   $_SERVER["HTTPS"] = "on";'."\n";
        $rule .= '}'."\n";
    }
    $rule .= "//END Really Simple SSL"."\n";
  } else {
    $rule  = "\n"."//Begin Really Simple SSL Server variable fix"."\n";
    $rule .= '$_SERVER["HTTPS"] = "on";'."\n";
    $rule .= "//END Really Simple SSL"."\n";
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

  public function remove_wpconfig_edit() {

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
    $wpconfig = preg_replace("/\n+/","\n", $wpconfig);
    file_put_contents($wpconfig_path, $wpconfig);

    //in multisite environment, with per site activation, re-add
    if (is_multisite() && $this->set_rewriterule_per_site) {

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

  public function set_siteurl_to_ssl() {
      if ($this->debug) {$this->trace_log("converting siteurl and homeurl to https");}
      $siteurl_ssl = str_replace ( "http://" , "https://" , get_option('siteurl'));
      $homeurl_ssl = str_replace ( "http://" , "https://" , get_option('home'));
      update_option('siteurl',$siteurl_ssl);
      update_option('home',$homeurl_ssl);
  }

  /**
   * On de-activation, siteurl and homeurl are reset to http
   *
   * @since  2.0
   *
   * @access public
   *
   */

  public function remove_ssl_from_siteurl() {
    if (is_multisite()) {
      $sites = wp_get_sites();
      foreach ( $sites as $site ) {
          switch_to_blog( $site[ 'blog_id' ] );

          $siteurl_no_ssl = str_replace ( "https://" , "http://" , get_option('siteurl'));
          $homeurl_no_ssl = str_replace ( "https://" , "http://" , get_option('home'));
          update_option('siteurl',$siteurl_no_ssl);
          update_option('home',$homeurl_no_ssl);

          restore_current_blog(); //switches back to previous blog, not current, so we have to do it each loop
        }
    } else {
      $siteurl_no_ssl = str_replace ( "https://" , "http://" , get_option('siteurl'));
      $homeurl_no_ssl = str_replace ( "https://" , "http://" , get_option('home'));
      update_option('siteurl',$siteurl_no_ssl);
      update_option('home',$homeurl_no_ssl);
    }
  }

  /**
   * Save the plugin options
   *
   * @since  2.0
   *
   * @access public
   *
   */

  public function save_options() {
    //any options added here should also be added to function options_validate()
    $options = array(
      'force_ssl_without_detection'       => $this->force_ssl_without_detection,
      'site_has_ssl'                      => $this->site_has_ssl,
      'hsts'                              => $this->hsts,
      'htaccess_warning_shown'            => $this->htaccess_warning_shown,
      'wpmu_subfolder_warning_shown'      => $this->wpmu_subfolder_warning_shown,
      'ssl_success_message_shown'         => $this->ssl_success_message_shown,
      'autoreplace_insecure_links'        => $this->autoreplace_insecure_links,
      'plugin_db_version'                 => $this->plugin_db_version,
      'debug'                             => $this->debug,
      'do_not_edit_htaccess'              => $this->do_not_edit_htaccess,
      'ssl_enabled'                       => $this->ssl_enabled,
      'javascript_redirect'               => $this->javascript_redirect,
    );

    update_option('rlrsssl_options',$options);

    //save multisite options
    if (is_multisite()) {
      $network_options = array(
        'set_rewriterule_per_site'  => $this->set_rewriterule_per_site,
      );
      update_site_option('rlrsssl_network_options', $network_options);
    }
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
      load_plugin_textdomain('really-simple-ssl', FALSE, dirname(plugin_basename(__FILE__)).'/languages/');
  }

  /**
   * Handles deactivation of this plugin
   *
   * @since  2.0
   *
   * @access public
   *
   */

  public function deactivate() {
    $this->remove_ssl_from_siteurl();
    $this->remove_ssl_from_siteurl_in_wpconfig();
    $this->force_ssl_without_detection          = FALSE;
    $this->site_has_ssl                         = FALSE;
    $this->hsts                                 = FALSE;
    $this->htaccess_warning_shown               = FALSE;
    $this->wpmu_subfolder_warning_shown         = FALSE;
    $this->ssl_success_message_shown            = FALSE;
    $this->autoreplace_insecure_links           = TRUE;
    $this->do_not_edit_htaccess                 = FALSE;
    $this->javascript_redirect                  = FALSE;
    $this->ssl_enabled                          = FALSE;
    $this->save_options();

    //when on multisite, per site activation, recreate domain list for htaccess and wpconfig rewrite actions
    if (is_multisite() && $this->set_rewriterule_per_site) $this->build_domain_list();

    $this->remove_wpconfig_edit();
    $this->removeHtaccessEdit();
  }


  /**
   * Checks if we are currently on ssl protocol, but extends standard wp with loadbalancer check.
   *
   * @since  2.0
   *
   * @access public
   *
   */

  public function is_ssl_extended(){
    if(!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') {
      $loadbalancer = TRUE;
    }
    else {
      $loadbalancer = FALSE;
    }

    if (is_ssl() || $loadbalancer){
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

  public function detect_configuration() {
    global $rsssl_url;
    $this->trace_log("** Detecting configuration **"); //string used to start debug log, don't change.
    $this->trace_log("plugin version: ".$this->plugin_version);
    $old_ssl_setting = $this->site_has_ssl;
    //plugin url: domain.com/wp-content/etc
    $plugin_url = str_replace ( "http://" , "https://" , $this->plugin_url);
    $testpage_url = trailingslashit($plugin_url)."ssl-test-page.php";
    $this->trace_log("Opening testpage to check for ssl: ".$testpage_url);
    $filecontents = $rsssl_url->get_contents($testpage_url);

    if($rsssl_url->error_number!=0) {
      $errormsg = $rsssl_url->get_curl_error($rsssl_url->error_number);

      //if current page is on ssl, we can assume ssl is available, even when an errormsg was returned
      if($this->is_ssl_extended()){
        $this->trace_log("We do have ssl, but the testpage loaded with an error: ".$errormsg);
        $this->site_has_ssl = TRUE;
      } else {
        $this->site_has_ssl = FALSE;
        $this->trace_log("No ssl detected. the ssl testpage returned an error: ".$errormsg);
      }
    } else {
      $this->site_has_ssl = TRUE;
      $this->trace_log("SSL test page loaded successfully");
    }

    if ($this->site_has_ssl) {
      //check the type of ssl
      if (strpos($filecontents, "#LOADBALANCER#") !== false) {
        $this->ssl_type = "LOADBALANCER";
        //check for is_ssl()
        if ((strpos($filecontents, "#SERVER-HTTPS-ON#") === false) &&
            (strpos($filecontents, "#SERVER-HTTPS-1#") === false) &&
            (strpos($filecontents, "#SERVERPORT443#") === false)) {
          //when Loadbalancer is detected, but is_ssl would return false, we should add some code to wp-config.php
          if (!$this->wpconfig_has_fixes()) {
            $this->do_wpconfig_loadbalancer_fix = TRUE;
          }
          if ($this->debug) {$this->trace_log("No server variable detected ");}
        }
      } elseif (strpos($filecontents, "#CDN#") !== false) {
        $this->ssl_type = "CDN";
      } elseif (strpos($filecontents, "#SERVER-HTTPS-ON#") !== false) {
        $this->ssl_type = "SERVER-HTTPS-ON";
      } elseif (strpos($filecontents, "#SERVER-HTTPS-1#") !== false) {
        $this->ssl_type = "SERVER-HTTPS-1";
      } elseif (strpos($filecontents, "#SERVERPORT443#") !== false) {
        $this->ssl_type = "SERVERPORT443";
      } elseif (strpos($filecontents, "#NO KNOWN SSL CONFIGURATION DETECTED#") !== false) {
        //if we are here, SSL was detected, but without any known server variables set.
        //So we can use this info to set a server variable ourselfes.
        if (!$this->wpconfig_has_fixes()) {
          $this->no_server_variable = TRUE;
        }
        $this->trace_log("No server variable detected ");
        $this->ssl_type = "NA";
      } else {
        //no valid response, so set to NA
        $this->ssl_type = "NA";
      }
	    $this->trace_log("ssl type: ".$this->ssl_type);
    }

    $force_ssl_without_detection = $this->force_ssl_without_detection ? "TRUE" : "FALSE";
    $this->trace_log("--- force ssl: ".$force_ssl_without_detection);

    if ($old_ssl_setting != $this->site_has_ssl) {
      	//value has changed, note this so we can flush the cache later.
		    $this->trace_log("ssl setting changed...");
      	add_option('really_simple_ssl_settings_changed', 'settings_changed' );
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

  public function test_htaccess_redirect() {
    global $rsssl_url;
    if (!current_user_can($this->capability)) return;
	  if ($this->debug) {$this->trace_log("testing htaccess rules...");}
    $filecontents = "";
    $plugin_url = str_replace ( "http://" , "https://" , $this->plugin_url);
    $testpage_url = $plugin_url."testssl/";
    switch ($this->ssl_type) {
    case "LOADBALANCER":
        $testpage_url .= "loadbalancer";
        break;
    case "CDN":
        $testpage_url .= "cdn";
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
    }

    $testpage_url .= ("/ssl-test-page.html");

    $filecontents = $rsssl_url->get_contents($testpage_url);
    if (($rsssl_url->error_number==0) && (strpos($filecontents, "#SSL TEST PAGE#") !== false)) {
      $this->htaccess_test_success = TRUE;
		  if ($this->debug) {$this->trace_log("htaccess rules tested successfully.");}
    } else {
      //.htaccess rewrite rule seems to be giving problems.
      $this->htaccess_test_success = FALSE;

      if ($rsssl_url->error_number!=0) {
        $this->trace_log("htaccess rules test failed with error: ".$rsssl_url->get_curl_error($rsssl_url->error_number));
      } else {
        $this->trace_log("htaccess test rules failed.");
      }

    }

  }

  /**
   * Get the url of this plugin
   *
   * @since  2.0
   *
   * @access public
   *
   */

   public function get_plugin_url(){
     $this->plugin_url = trailingslashit(plugin_dir_url( __FILE__ ));
     //do not force to ssl yet, we need it also in non ssl situations.

     //in some case we get a relative url here, so we check that.
     //we compare to urls replaced to https, in case one of them is still on http.
 	   if (strpos(str_replace("http://","https://",$this->plugin_url),str_replace("http://","https://",home_url()))===FALSE) {
       //make sure we do not have a slash at the start
       $this->plugin_url = ltrim($this->plugin_url,"/");
       $this->plugin_url = trailingslashit(home_url()).$this->plugin_url;
     }

     //for subdomains or domain mapping situations, we have to convert the plugin_url from main site to the subdomain url.
     if (is_multisite() && ( !is_main_site(get_current_blog_id()) ) && (!$this->is_multisite_subfolder_install()) ) {
       $mainsiteurl = str_replace("http://","https://",network_site_url());

       $home = str_replace("http://","https://",home_url());
       $this->plugin_url = str_replace($mainsiteurl,home_url(), $this->plugin_url);

       //return http link if original url is http.
       if (strpos(home_url(), "https://")===FALSE) $this->plugin_url = str_replace("https://","http://",$this->plugin_url);
     }

 	   if ($this->debug) {$this->trace_log("pluginurl: ".$this->plugin_url);}
   }


  /**
   * removes the added redirect to https rules to the .htaccess file.
   *
   * @since  2.0
   *
   * @access public
   *
   */

  public function removeHtaccessEdit() {
      if(file_exists($this->ABSpath.".htaccess") && is_writable($this->ABSpath.".htaccess")){
        $htaccess = file_get_contents($this->ABSpath.".htaccess");


        //if multisite, per site activation and more than one blog remaining on ssl, remove condition for this site only
        //the domain list has been rebuilt already, so current site is already removed.
        if (is_multisite() && $this->set_rewriterule_per_site && count($this->sites)>0) {
          //remove http or https.
          $domain = preg_replace("/(http:\/\/|https:\/\/)/","",home_url());
          $pattern = "/#wpmu\srewritecond\s?".preg_quote($domain, "/")."\n.*?#end\swpmu\srewritecond\s?".preg_quote($domain, "/")."\n/s";

          //only remove if the pattern is there at all
          if (preg_match($pattern, $htaccess)) $htaccess = preg_replace($pattern, "", $htaccess);
          //now replace any remaining "or" on the last condition.
          $pattern = "/(\[OR\])(?!.*(\[OR\]|#start).*?RewriteRule)/s";
          $htaccess = preg_replace($pattern, "", $htaccess,1);

        } else {
          // remove everything
          $pattern = "/#\s?BEGIN\s?rlrssslReallySimpleSSL.*?#\s?END\s?rlrssslReallySimpleSSL/s";
          //only remove if the pattern is there at all
          if (preg_match($pattern, $htaccess)) $htaccess = preg_replace($pattern, "", $htaccess);

        }

        $htaccess = preg_replace("/\n+/","\n", $htaccess);
        file_put_contents($this->ABSpath.".htaccess", $htaccess);
        //THIS site is not redirected in htaccess anymore.
        $this->ssl_redirect_set_in_htaccess =  FALSE;
        $this->save_options();
      } else {
        $this->errors['HTACCESS_NOT_WRITABLE'] = TRUE;
        if ($this->debug) $this->trace_log("could not remove rules from htaccess, file not writable");
      }
  }

  public function contains_previous_version($htaccess) {
    $versionpos = strpos($htaccess, "rsssl_version");
    if ($versionpos===false) {
      //no version found, so old version
      return true;
    } else {
      //find closing marker of version
      $close = strpos($htaccess, "]", $versionpos);
      $version = substr($htaccess, $versionpos+14, $close-($versionpos+14));
      if ($version != $this->plugin_version) {
        return true;
      }
      else {
        return false;
      }
    }
  }

  public function contains_rsssl_rules($htaccess) {
    preg_match("/BEGIN rlrssslReallySimpleSSL/", $htaccess, $check);
    if(count($check) === 0){
      return false;
    } else {
      return true;
    }
  }

  /**
   * Checks if the hsts rule is already in the htaccess file
   * Set the hsts variable in the db accordingly
   *
   * @since  2.1
   *
   * @access public
   *
   */

  public function contains_hsts($htaccess) {
    preg_match("/Header always set Strict-Transport-Security/", $htaccess, $check);
    if(count($check) === 0){
      return false;
    } else {
      return true;
    }
  }

  /**
   * Adds redirect to https rules to the .htaccess file.
   *
   * @since  2.0
   *
   * @access public
   *
   */

  public function editHtaccess(){
      if (!current_user_can($this->capability)) return;
      //check if htacces exists and  if htaccess is writable
      //update htaccess to redirect to ssl and set redirect_set_in_htaccess
      $this->ssl_redirect_set_in_htaccess =  FALSE;

      if($this->debug) $this->trace_log("checking if .htaccess can or should be edited...");

      //does it exist?
      if (!file_exists($this->ABSpath.".htaccess")) {
        $this->trace_log(".htaccess not found.");
        return;
      }

      //check if editing is blocked.
      if ($this->do_not_edit_htaccess) {
        $this->trace_log("Edit of .htaccess blocked by setting or define 'do not edit htaccess' in really simple ssl.");
        return;
      }

      //if subfolder multisite, per site activated, exit.
      if (is_multisite() && $this->set_rewriterule_per_site && $this->is_multisite_subfolder_install()) {
          $this->trace_log("per site activation on subfolder install, adding of htaccess rules skipped");
          $this->ssl_redirect_set_in_htaccess = false;
          $this->javascript_redirect = true;
          return;
      }

      $htaccess = file_get_contents($this->ABSpath.".htaccess");
      if(!$this->contains_rsssl_rules($htaccess)){
        //really simple ssl rules not in the file, so add if writable.
        if ($this->debug) {$this->trace_log("no rules there, adding rules...");}

        if (!is_writable($this->ABSpath.".htaccess")) {
          //set the javascript redirect as fallback, because .htaccess couldn't be edited.
          $this->javascript_redirect = true;
          $this->trace_log(".htaccess not writable.");
          $this->errors["NO_REDIRECT_IN_HTACCESS"] = true;
          return;
        }

        $rules = $this->get_redirect_rules();

        //insert rules before wordpress part.
        $wptag = "# BEGIN WordPress";
        if (strpos($htaccess, $wptag)!==false) {
            $htaccess = str_replace($wptag, $rules.$wptag, $htaccess);
        } else {
            $htaccess = $htaccess.$rules;
        }

        file_put_contents($this->ABSpath.".htaccess", $htaccess);

    } elseif ((is_multisite() && $this->set_rewriterule_per_site) || ($this->hsts!=$this->contains_hsts($htaccess))) {
        /*
            Remove all rules and add new IF
            //disabled changes for version upgrade. , $this->contains_previous_version($htaccess) ||
            or the hsts option has changed, so we need to edit the htaccess anyway.
            or rewrite per site (if a site is added or removed on per site activated
            mulsite we need to rewrite even if the rules are already there.)
        */

        if ($this->debug) {$this->trace_log("per site activation or hsts option change, updating htaccess...");}

        if (!is_writable($this->ABSpath.".htaccess")) {
          if($this->debug) $this->trace_log(".htaccess not writable.");
          return;
        }

	      $htaccess = preg_replace("/#\s?BEGIN\s?rlrssslReallySimpleSSL.*?#\s?END\s?rlrssslReallySimpleSSL/s", "", $htaccess);
        $htaccess = preg_replace("/\n+/","\n", $htaccess);

        $rules = $this->get_redirect_rules();
        //insert rules before wordpress part.
        $wptag = "# BEGIN WordPress";
        if (strpos($htaccess, $wptag)!==false) {
            $htaccess = str_replace($wptag, $rules.$wptag, $htaccess);
        } else {
            $htaccess = $htaccess.$rules;
        }
        file_put_contents($this->ABSpath.".htaccess", $htaccess);
      } else {
        if ($this->debug) {$this->trace_log("rules already added in .htaccess.");}
        $this->ssl_redirect_set_in_htaccess =  TRUE;
      }
  }

  /**
  *
  *  @since 2.2
  *  Check if the mixed content fixer is functioning on the front end, by scanning the source of the homepage for the fixer comment.
  *
  */

  public function mixed_content_fixer_detected(){
    global $rsssl_url;
    //check if the mixed content fixer is active
    $web_source = $rsssl_url->get_contents(home_url());
    if ($rsssl_url->error_number!=0 || (strpos($web_source, "<!-- rs-ssl -->") === false)) {
      if ($rsssl_url->error_number!=0) $this->mixed_content_fixer_status = $rsssl_url->get_curl_error($rsssl_url->error_number);
      $this->trace_log("Check for Mixed Content detection failed");
      return false;
    } else {
      $this->trace_log("Mixed content was successfully detected on the front end.");
      return true;
    }
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
   */

  private function is_subfolder($domain) {
      //remove slashes of the http(s)
      $domain = preg_replace("/(http:\/\/|https:\/\/)/","",$domain);
      if (strpos($domain,"/")!==FALSE) {
        return true;
      }

      return false;
  }

  /**
   * Create redirect rules for the .htaccess.
   *
   * @since  2.1
   *
   * @access public
   *
   */

  public function get_redirect_rules($manual=false) {
      if (!current_user_can($this->capability)) return;

      //only add the redirect rules when a known type of ssl was detected. Otherwise, we use https.
      $rule="\n";

      $rule .= "# BEGIN rlrssslReallySimpleSSL rsssl_version[".$this->plugin_version."]\n";

      //if the htaccess test was successfull, and we know the redirectype, edit
      if ($manual || ($this->htaccess_test_success && ($this->ssl_type != "NA"))) {
        //set redirect_set_in_htaccess to true, because we are now making a redirect rule.
        if (!$manual) {
          $this->ssl_redirect_set_in_htaccess = TRUE;
        }

        $rule .= "<IfModule mod_rewrite.c>"."\n";
        $rule .= "RewriteEngine on"."\n";

        //select rewrite condition based on detected type of ssl
        if ($this->ssl_type == "SERVER-HTTPS-ON") {
            $rule .= "RewriteCond %{HTTPS} !=on [NC]"."\n";
        } elseif ($this->ssl_type == "SERVER-HTTPS-1") {
            $rule .= "RewriteCond %{HTTPS} !=1"."\n";
        } elseif ($this->ssl_type == "SERVERPORT443") {
           $rule .= "RewriteCond %{SERVER_PORT} !443"."\n";
        } elseif ($this->ssl_type == "LOADBALANCER") {
            $rule .="RewriteCond %{HTTP:X-Forwarded-Proto} !https"."\n";
        } elseif ($this->ssl_type == "CDN") {
            $rule .= "RewriteCond %{HTTP:X-Forwarded-SSL} !on"."\n";
        }

        //if multisite, and NOT subfolder install (checked for in the detec_config function)
        //, add a condition so it only applies to sites where plugin is activated
        if (is_multisite() && $this->set_rewriterule_per_site) {
          //disable hsts, because other sites on the network would be forced on ssl as well
          $this->hsts = FALSE;
          $this->trace_log("multisite, per site activation");

          foreach ($this->sites as $domain ) {
              $this->trace_log("adding condition for:".$domain);

              //remove http or https.
              $domain = preg_replace("/(http:\/\/|https:\/\/)/","",$domain);
              //We excluded subfolders, so treat as domain

              $domain_no_www  = str_replace ( "www." , "" , $domain);
              $domain_yes_www = "www.".$domain_no_www;

              $rule .= "#wpmu rewritecond ".$domain."\n";
              $rule .= "RewriteCond %{HTTP_HOST} ^".preg_quote($domain_no_www, "/")." [OR]"."\n";
              $rule .= "RewriteCond %{HTTP_HOST} ^".preg_quote($domain_yes_www, "/")." [OR]"."\n";
              $rule .= "#end wpmu rewritecond ".$domain."\n";

          }

          //now remove last [OR] if at least on one site the plugin was activated, so we have at lease one condition
          if (count($this->sites)>0) {
            $rule = strrev(implode("", explode(strrev("[OR]"), strrev($rule), 2)));
          }
        } else {
          if ($this->debug) {$this->trace_log("single site or networkwide activation");}
        }
        $rule .= "RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]"."\n";

        //$rule .= "RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]"."\n";
        $rule .= "</IfModule>"."\n";
      } else {
        $this->ssl_redirect_set_in_htaccess = FALSE;
      }

      if ($this->hsts && !is_multisite()) {
        //owasp security best practice https://www.owasp.org/index.php/HTTP_Strict_Transport_Security
        $rule .= "<IfModule mod_headers.c>"."\n";
        $rule .= "Header always set Strict-Transport-Security 'max-age=31536000' env=HTTPS"."\n";
        $rule .= "</IfModule>"."\n";
      }

      $rule .= "# END rlrssslReallySimpleSSL"."\n";

      $rule = preg_replace("/\n+/","\n", $rule);
      return $rule;
    }



/**
*     Show warning when wpconfig could not be fixed
*
*     @since 2.2
*
*/

public function show_notice_wpconfig_needs_fixes(){ ?>
  <div id="message" class="error fade notice">
  <h1><?php echo __("System detection encountered issues","really-simple-ssl");?></h1>

  <?php if ($this->wpconfig_siteurl_not_fixed) { ?>
    <p>
      <?php echo __("A definition of a siteurl or homeurl was detected in your wp-config.php, but the file is not writable.","really-simple-ssl");?>
    </p>
    <p><?php echo __("Set your wp-config.php to writable and reload this page.", "really-simple-ssl");?></p>
  <?php }
  if ($this->do_wpconfig_loadbalancer_fix) { ?>
      <p><?php echo __("Your wp-config.php has to be edited, but is not writable.","really-simple-ssl");?></p>
      <p><?php echo __("Because your site is behind a loadbalancer and is_ssl() returns false, you should add the following line of code to your wp-config.php.","really-simple-ssl");?>

    <br><br><code>
        //Begin Really Simple SSL Load balancing fix <br>
        if (isset($_SERVER["HTTP_X_FORWARDED_PROTO"] ) &amp;&amp; "https" == $_SERVER["HTTP_X_FORWARDED_PROTO"] ) {<br>
        &nbsp;&nbsp;$_SERVER["HTTPS"] = "on";<br>
      }<br>
        //END Really Simple SSL
    </code><br>
    </p>
    <p><?php echo __("Or set your wp-config.php to writable and reload this page.", "really-simple-ssl");?></p>
    <?php
  }

  if ( $this->no_server_variable ) {
    ?>
      <p><?php echo __('Because your server does not pass a variable with which Wordpress can detect SSL, Wordpress may create redirect loops on SSL.','really-simple-ssl');?></p>
      <p><?php echo __("Set your wp-config.php to writable and reload this page.", "really-simple-ssl");?></p>
    <?php
  }
  ?>

</div>
<?php
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
  /*
      show a notice when the .htaccess file does not contain redirect rules
  */

  if (!$this->htaccess_warning_shown && isset($this->errors["NO_REDIRECT_IN_HTACCESS"])) {
        add_action('admin_print_footer_scripts', array($this, 'insert_dismiss_htaccess'));
        ?>
        <div id="message" class="error fade notice is-dismissible rlrsssl-htaccess">
          <p>
            <?php echo __("Your .htaccess did not contain the Really Simple SSL redirect to https, and could not be written, so a javascript redirect is currently added. For SEO purposes it is advisable to use .htaccess redirects. Set the .htaccess file to writable and visit the settings page to write it again, or copy the code lines from the settings page.","really-simple-ssl");?>
            <a href="options-general.php?page=rlrsssl_really_simple_ssl">Settings</a>
          </p>
        </div>
        <?php
  }

  if (isset($this->errors["DEACTIVATE_FILE_NOT_RENAMED"])) {
    ?>
    <div id="message" class="error fade notice is-dismissible rlrsssl-fail">
      <h1>
        <?php _e("Major security issue!","really-simple-ssl");?>
      </h1>
      <p>
    <?php _e("The 'force-deactivate.php' file has to be renamed to .txt. Otherwise your ssl can be deactived by anyone on the internet.","really-simple-ssl");?>
    </p>
    <a href="options-general.php?page=rlrsssl_really_simple_ssl"><?php echo __("Check again","really-simple-ssl");?></a>
    </div>
    <?php
  }

  /*
    encourage network wide for subfolder install.
  */

  if (is_multisite() && $this->set_rewriterule_per_site && $this->is_multisite_subfolder_install()) {
    //with no server variables, the website could get into redirect loops.
    if ($this->no_server_variable) {
      ?>
        <div id="message" class="error fade notice">
          <p>
            <?php _e('You run a Multisite installation with subfolders, which prevents this plugin from fixing your missing server variable in the wp-config.php.','really-simple-ssl');?>
            <?php _e('Because the $_SERVER["HTTPS"] variable is not set, your website may experience redirect loops.','really-simple-ssl');?>
            <?php _e('Activate networkwide to fix this.','really-simple-ssl');?>
          </p>
        </div>
      <?php
    } elseif (!$this->wpmu_subfolder_warning_shown) {
      //otherwise, the htaccess cannot be fixed.
      add_action('admin_print_footer_scripts', array($this, 'insert_dismiss_wpmu_subfolder_warning'));
    ?>
      <div id="message" class="error fade notice is-dismissible rlrsssl-wpmu-subfolder-warning">
        <p>
          <?php _e('You run a Multisite installation with subfolders, which prevents this plugin from handling the .htaccess.','really-simple-ssl');?>
          <?php _e('Because the domain is the same on all sites, it would be better to activate SSL on all your sites.','really-simple-ssl');?>
        </p>
      </div>
    <?php
    }
  }

  /*
      SSL success message
  */

  if ($this->ssl_enabled && $this->site_has_ssl && !$this->ssl_success_message_shown) {
        add_action('admin_print_footer_scripts', array($this, 'insert_dismiss_success'));
        ?>
        <div id="message" class="updated fade notice is-dismissible rlrsssl-success">
          <p>
            <?php echo __("SSL was detected and successfully activated!","really-simple-ssl");?>
          </p>
        </div>
        <?php
  }

  //some notices for ssl situations
  if ($this->site_has_ssl || $this->force_ssl_without_detection) {
      if (sizeof($this->plugin_conflict)>0) {
        //pre Woocommerce 2.5
        if (isset($this->plugin_conflict["WOOCOMMERCE_FORCEHTTP"]) && $this->plugin_conflict["WOOCOMMERCE_FORCEHTTP"] && isset($this->plugin_conflict["WOOCOMMERCE_FORCESSL"]) && $this->plugin_conflict["WOOCOMMERCE_FORCESSL"]) {
          ?>
          <div id="message" class="error fade notice"><p>
          <?php _e("Really Simple SSL has a conflict with another plugin.","really-simple-ssl");?><br>
          <?php _e("The force http after leaving checkout in Woocommerce will create a redirect loop.","really-simple-ssl");?><br>
          <a href="admin.php?page=wc-settings&tab=checkout"><?php _e("Show me this setting","really-simple-ssl");?></a>
          </p></div>
          <?php
        }

        if (isset($this->plugin_conflict["YOAST_FORCE_REWRITE_TITLE"]) && $this->plugin_conflict["YOAST_FORCE_REWRITE_TITLE"]) {
            ?>
            <div id="message" class="error fade notice"><p>
            <?php _e("Really Simple SSL has a conflict with another plugin.","really-simple-ssl");?><br>
            <?php _e("The force rewrite titles option in Yoast SEO prevents Really Simple SSL plugin from fixing mixed content.","really-simple-ssl");?><br>
            <a href="admin.php?page=wpseo_titles"><?php _e("Show me this setting","really-simple-ssl");?></a>

            </p></div>
            <?php
          }
      }
    }
}

  /**
   * Insert some ajax script to dismis the ssl success message, and stop nagging about it
   *
   * @since  2.0
   *
   * @access public
   *
   */

public function insert_dismiss_success() {
  $ajax_nonce = wp_create_nonce( "really-simple-ssl-dismiss" );
  ?>
  <script type='text/javascript'>
    jQuery(document).ready(function($) {
      $(".rlrsssl-success.notice.is-dismissible").on("click", ".notice-dismiss", function(event){
            var data = {
              'action': 'dismiss_success_message',
              'security': '<?php echo $ajax_nonce; ?>'
            };

            $.post(ajaxurl, data, function(response) {

            });
        });
    });
  </script>
  <?php
}
/**
 * Insert some ajax script to dismis the htaccess failed fail message, and stop nagging about it
 *
 * @since  2.0
 *
 * @access public
 *
 */

public function insert_dismiss_htaccess() {
  $ajax_nonce = wp_create_nonce( "really-simple-ssl" );
  ?>
  <script type='text/javascript'>
    jQuery(document).ready(function($) {
        $(".rlrsssl-htaccess.notice.is-dismissible").on("click", ".notice-dismiss", function(event){
              var data = {
                'action': 'dismiss_htaccess_warning',
                'security': '<?php echo $ajax_nonce; ?>'
              };
              $.post(ajaxurl, data, function(response) {

              });
          });
    });
  </script>
  <?php
}

/**
 * Insert some ajax script to dismis the ssl fail message, and stop nagging about it
 *
 * @since  2.0
 *
 * @access public
 *
 */

public function insert_dismiss_wpmu_subfolder_warning() {
  $ajax_nonce = wp_create_nonce( "really-simple-ssl" );
  ?>
  <script type='text/javascript'>
    jQuery(document).ready(function($) {
        $(".rlrsssl-wpmu-subfolder-warning.notice.is-dismissible").on("click", ".notice-dismiss", function(event){
              var data = {
                'action': 'dismiss_wpmu_subfolder_warning',
                'security': '<?php echo $ajax_nonce; ?>'
              };
              $.post(ajaxurl, data, function(response) {

              });
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

public function dismiss_success_message_callback() {
  //nonce check fails if url is changed to ssl. 
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

public function dismiss_htaccess_warning_callback() {
  check_ajax_referer( 'really-simple-ssl', 'security' );
  $this->htaccess_warning_shown = TRUE;
  $this->save_options();
  wp_die(); // this is required to terminate immediately and return a proper response
}

/**
 * Process the ajax dismissal of the wpmu subfolder message
 *
 * @since  2.1
 *
 * @access public
 *
 */

public function dismiss_wpmu_subfolder_warning_callback() {
  global $wpdb;
  check_ajax_referer( 'really-simple-ssl', 'security' );
  $this->wpmu_subfolder_warning_shown = TRUE;
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

public function add_settings_page() {
  if (!current_user_can($this->capability)) return;
  $admin_page = add_options_page(
    __("SSL settings","really-simple-ssl"), //link title
    __("SSL","really-simple-ssl"), //page title
    $this->capability, //capability
    'rlrsssl_really_simple_ssl', //url
    array($this,'settings_page')); //function

    // Adds my_help_tab when my_admin_page loads
    add_action('load-'.$admin_page, array($this,'admin_add_help_tab'));

}

  /**
   * Admin help tab
   *
   * @since  2.0
   *
   * @access public
   *
   */

public function admin_add_help_tab() {
    $screen = get_current_screen();
    // Add my_help_tab if current screen is My Admin Page
    $screen->add_help_tab( array(
        'id'	=> "really-simple-ssl-documentation",
        'title'	=> __("Documentation","really-simple-ssl"),
        'content'	=> '<p>' . __("On <a href='https://www.really-simple-ssl.com'>www.really-simple-ssl.com</a> you can find a lot of articles and documentation about installing this plugin, and installing SSL in general.","really-simple-ssl") . '</p>',
    ) );
}

  /**
   * Create tabs on the settings page
   *
   * @since  2.1
   *
   * @access public
   *
   */

  public function admin_tabs( $current = 'homepage' ) {
      $tabs = array(
        'configuration' => __("Configuration","really-simple-ssl"),
        'settings'=>__("Settings","really-simple-ssl"),
        'debug' => __("Debug","really-simple-ssl")
      );

      $tabs = apply_filters("rsssl_tabs", $tabs);

      echo '<h2 class="nav-tab-wrapper">';

      foreach( $tabs as $tab => $name ){
          $class = ( $tab == $current ) ? ' nav-tab-active' : '';
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

public function settings_page() {
  if (!current_user_can($this->capability)) return;

  if ( isset ( $_GET['tab'] ) ) $this->admin_tabs($_GET['tab']); else $this->admin_tabs('configuration');
  if ( isset ( $_GET['tab'] ) ) $tab = $_GET['tab']; else $tab = 'configuration';

  switch ( $tab ){
      case 'configuration' :

      /*
              First tab, configuration
      */

      ?>
        <h2><?php echo __("Detected setup","really-simple-ssl");?></h2>
        <table class="really-simple-ssl-table">

          <?php if ($this->site_has_ssl) { ?>
          <tr>
            <td><?php echo $this->ssl_enabled ? $this->img("success") : $this->img("error");?></td>
            <td><?php
                  if ($this->ssl_enabled) {
                    _e("SSL is enabled on your site.","really-simple-ssl")."&nbsp;";
                  } else {
                    _e("SSL iss not enabled yet","really-simple-ssl")."&nbsp;";
                  }
                ?>
              </td><td></td>
          </tr>
          <?php }
          /* check if the mixed content fixer is working */
          if ($this->ssl_enabled && $this->autoreplace_insecure_links && $this->site_has_ssl) { ?>
          <tr>
            <td><?php echo $this->mixed_content_fixer_detected() ? $this->img("success") : $this->img("error");?></td>
            <td><?php
                  if ($this->mixed_content_fixer_detected()) {
                    _e("Mixed content fixer was successfully detected on the front-end","really-simple-ssl")."&nbsp;";
                  } elseif ($this->mixed_content_fixer_status!=0) {
                    _e("The mixed content is activated, but the frontpage could not be loaded for verification. The following error was returned: ","really-simple-ssl")."&nbsp;";
                    echo $this->mixed_content_fixer_status;
                  } else {
                    _e("The mixed content is activated, but was not detected on the frontpage. Please check if you have mixed content, which could indicate a plugin conflict.","really-simple-ssl")."&nbsp;";
                  }
                ?>
              </td><td></td>
          </tr>
          <?php } ?>
          <tr>
            <td><?php echo $this->site_has_ssl ? $this->img("success") : $this->img("error");?></td>
            <td><?php
                    if ( !$this->wpconfig_ok())  {
                      _e("Failed activating SSL","really-simple-ssl")."&nbsp;";
                    } elseif (!$this->site_has_ssl) {
                      if (!$this->force_ssl_without_detection)
                         _e("No SSL detected.","really-simple-ssl")."&nbsp;";
                      else
                         _e("No SSL detected, but SSL is forced.","really-simple-ssl")."&nbsp;";
                    }
                    else {
                      //ssl detected, no problems!
                      _e("An SSL certificate was detected on your site. ","really-simple-ssl");
                    }
                ?>
              </td><td></td>
          </tr>
          <?php if($this->ssl_enabled && ($this->site_has_ssl || $this->force_ssl_without_detection)) { ?>
          <tr>
            <td>
              <?php echo ($this->ssl_redirect_set_in_htaccess || $this->do_not_edit_htaccess) ? $this->img("success") :$this->img("warning");?>
            </td>
            <td>
            <?php
                if($this->ssl_redirect_set_in_htaccess) {
                 _e("https redirect set in .htaccess","really-simple-ssl");
              } elseif ($this->do_not_edit_htaccess) {
                 _e("Editing of .htaccess is blocked in Really Simple ssl settings, so you're in control of the .htaccess file.","really-simple-ssl");
              } else {
                 if (!is_writable($this->ABSpath.".htaccess")) {
                   _e("Https redirect was set in javascript because the .htaccess was not writable. Set manually if you want to redirect in .htaccess.","really-simple-ssl");
                 } elseif($this->set_rewriterule_per_site && $this->is_multisite_subfolder_install()) {
                   _e("Https redirect was set in javascript because you have activated per site on a multiste subfolder install. Install networkwide to set the .htaccess redirect.","really-simple-ssl");
                 } else {
                   _e("Https redirect was set in javascript because the htaccess redirect rule could not be verified. Set manually if you want to redirect in .htaccess.","really-simple-ssl");
                }
                 if ($this->ssl_type!="NA" && !($this->set_rewriterule_per_site && $this->is_multisite_subfolder_install())) {
                    $manual = true;
                    $rules = $this->get_redirect_rules($manual);
                    echo "&nbsp;";
                    $arr_search = array("<",">","\n");
                    $arr_replace = array("&lt","&gt","<br>");
                    $rules = str_replace($arr_search, $arr_replace, $rules);
                    _e("Try to add these rules above the wordpress lines in your .htaccess. If it doesn't work, just remove them again.","really-simple-ssl");
                     ?>
                     <br><br><code>
                         <?php echo $rules; ?>
                       </code>
                     <?php
                  }
              }
            ?>
            </td><td></td>
          </tr>

          <?php
          //HSTS on per site activated multisite is not possible.
          if (!(is_multisite() && $this->set_rewriterule_per_site)) {
          ?>
          <tr>
            <td>
              <?php echo $this->hsts ? $this->img("success") :$this->img("warning");?>
            </td>
            <td>
            <?php
              if($this->hsts) {
                 _e("HTTP Strict Transport Security was set in the .htaccess","really-simple-ssl");
              } else {
                 _e("HTTP Strict Transport Security was not set in your .htaccess. Do this only if your setup is fully working, and only when you do not plan to revert to http.","really-simple-ssl");
                 ?>
                <br>
                <a href="https://en.wikipedia.org/wiki/HTTP_Strict_Transport_Security" target="_blank"><?php _e("More info about HSTS","really-simple-ssl");?></a>
                 <?php
              }
            ?>
            </td><td><a href="?page=rlrsssl_really_simple_ssl&tab=settings"><?php _e("Manage settings","really-simple-ssl");?></a></td>
          </tr>

          <?php
              }
          }
          ?>

        </table>
        <?php do_action("rsssl_configuration_page");?>
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

        <input class="button button-primary" name="Submit" type="submit" value="<?php echo __("Save","really-simple-ssl"); ?>" />
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
        echo "<h2>".__("Log for debugging purposes","really-simple-ssl")."</h2>";
        echo "<p>".__("Send me a copy of these lines if you have any issues. The log will be erased when debug is set to false","really-simple-ssl")."</p>";
        echo "<div class='debug-log'>";
        echo $this->debug_log;
        echo "</div>";
        $this->debug_log.="<br><b>-----------------------</b>";
        $this->save_options();
      }
      else {
        _e("To view results here, enable the debug option in the settings tab.","really-simple-ssl");
      }

       ?>
    </div>
    <?php
    break;
  }
  //possibility to hook into the tabs.
  do_action("show_tab_{$tab}");
     ?>
<?php
}

  /**
   * Returns a succes, error or warning image for the settings page
   *
   * @since  2.0
   *
   * @access public
   *
   * @param string $type the type of image
   *
   * @return html string
   */

public function img($type) {
  if ($type=='success') {
    return "<img class='icons' src='".$this->plugin_url."img/check-icon.png' alt='success'>";
  } elseif ($type=="error") {
    return "<img class='icons' src='".$this->plugin_url."img/cross-icon.png' alt='error'>";
  } else {
    return "<img class='icons' src='".$this->plugin_url."img/warning-icon.png' alt='warning'>";
  }
}

  /**
   * Add some css for the settings page
   *
   * @since  2.0
   *
   * @access public
   *
   */

public function enqueue_assets(){
  wp_register_style( 'rlrsssl-css', $this->plugin_url . 'css/main.css');
  wp_enqueue_style( 'rlrsssl-css');

}

  /**
   * Initialize admin errormessage, settings page
   *
   * @since  2.0
   *
   * @access public
   *
   */

public function setup_admin_page(){
  if (current_user_can($this->capability)) {

    add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    add_action('admin_init', array($this, 'load_translation'),20);
    add_action('rsssl_configuration_page', array($this, 'configuration_page_more'));

    //settings page, from creation and settings link in the plugins page
    add_action('admin_menu', array($this, 'add_settings_page'),40);
    add_action('admin_init', array($this, 'create_form'),40);

    $plugin = $this->plugin_dir."/".$this->plugin_filename;
    add_filter("plugin_action_links_$plugin", array($this,'plugin_settings_link'));
  }
}

public function configuration_page_more(){
  if (!$this->ad) return;
  if (!$this->site_has_ssl) {
    $this->show_pro();
  } else { ?>
    <p><?php _e('Still having issues with mixed content? Try scanning your site with Really Simple SSL Pro. ', "really-simple-ssl")?><a href="<?php echo $this->pro_url?>">Get Pro</a></p>
  <?php }
}

  /**
   * Create the settings page form
   *
   * @since  2.0
   *
   * @access public
   *
   */

public function create_form(){

      register_setting( 'rlrsssl_options', 'rlrsssl_options', array($this,'options_validate') );
      add_settings_section('rlrsssl_settings', __("Settings","really-simple-ssl"), array($this,'section_text'), 'rlrsssl');
      add_settings_field('id_do_not_edit_htaccess', __("Stop editing the .htaccess file","really-simple-ssl"), array($this,'get_option_do_not_edit_htaccess'), 'rlrsssl', 'rlrsssl_settings');

      //only show option to enable or disable mixed content and redirect when ssl is detected
      if($this->site_has_ssl || $this->force_ssl_without_detection) {
        add_settings_field('id_autoreplace_insecure_links', __("Auto replace mixed content","really-simple-ssl"), array($this,'get_option_autoreplace_insecure_links'), 'rlrsssl', 'rlrsssl_settings');
        add_settings_field('id_javascript_redirect', __("Enable javascript redirection to ssl","really-simple-ssl"), array($this,'get_option_javascript_redirect'), 'rlrsssl', 'rlrsssl_settings');
      }

      if($this->site_has_ssl && file_exists($this->ABSpath.".htaccess")) {
        add_settings_field('id_hsts', __("Turn HTTP Strict Transport Security on","really-simple-ssl"), array($this,'get_option_hsts'), 'rlrsssl', 'rlrsssl_settings');
      }

      if(!$this->site_has_ssl) {
        //no sense in showing force or ignore warning options when ssl is detected: everything should work fine
        add_settings_field('id_force_ssl_without_detection', __("Force SSL without detection","really-simple-ssl"), array($this,'get_option_force_ssl_withouth_detection'), 'rlrsssl', 'rlrsssl_settings');
      }

      add_settings_field('id_debug', __("Debug","really-simple-ssl"), array($this,'get_option_debug'), 'rlrsssl', 'rlrsssl_settings');
    }

  /**
   * Insert some explanation above the form
   *
   * @since  2.0
   *
   * @access public
   *
   */

public function section_text() {
  ?>
  <p>
  <?php
    if ($this->site_has_ssl || $this->force_ssl_without_detection)
      _e('By unchecking the \'auto replace mixed content\' checkbox you can test if your site can run without this extra functionality. Uncheck, empty your cache when you use one, and go to the front end of your site. You should then check if you have mixed content errors, by clicking on the lock icon in the addres bar.','really-simple-ssl');
    else {
      _e('The force ssl without detection option can be used when the ssl was not detected, but you are sure you have ssl.','really-simple-ssl');
    }

    if ($this->site_has_ssl && is_multisite() && $this->set_rewriterule_per_site) {
      _e('The HSTS option is not available for per site activated ssl, as it would force other sites over ssl as well.','really-simple-ssl');
    }
  ?>
  </p>
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

public function options_validate($input) {
  //fill array with current values, so we don't lose any
  $newinput = array();
  $newinput['site_has_ssl']                       = $this->site_has_ssl;
  $newinput['ssl_success_message_shown']          = $this->ssl_success_message_shown;
  $newinput['htaccess_warning_shown']             = $this->htaccess_warning_shown;
  $newinput['wpmu_subfolder_warning_shown']       = $this->wpmu_subfolder_warning_shown;
  $newinput['plugin_db_version']                  = $this->plugin_db_version;
  $newinput['set_rewriterule_per_site']           = $this->set_rewriterule_per_site;
  $newinput['ssl_enabled']                        = $this->ssl_enabled;


  if (!empty($input['hsts']) && $input['hsts']=='1') {
    $newinput['hsts'] = TRUE;
  } else {
    $newinput['hsts'] = FALSE;
  }

  if (!empty($input['javascript_redirect']) && $input['javascript_redirect']=='1') {
    $newinput['javascript_redirect'] = TRUE;
  } else {
    $newinput['javascript_redirect'] = FALSE;
  }

  if (!empty($input['force_ssl_without_detection']) && $input['force_ssl_without_detection']=='1') {
    $newinput['force_ssl_without_detection'] = TRUE;
  } else {
    $newinput['force_ssl_without_detection'] = FALSE;
  }

  if (!empty($input['autoreplace_insecure_links']) && $input['autoreplace_insecure_links']=='1') {
    $newinput['autoreplace_insecure_links'] = TRUE;
  } else {
    $newinput['autoreplace_insecure_links'] = FALSE;
  }

  if (!empty($input['debug']) && $input['debug']=='1') {
    $newinput['debug'] = TRUE;
  } else {
    $newinput['debug'] = FALSE;
    $this->debug_log = "";
  }

  if (!empty($input['do_not_edit_htaccess']) && $input['do_not_edit_htaccess']=='1') {
    $newinput['do_not_edit_htaccess'] = TRUE;
  } else {
    $newinput['do_not_edit_htaccess'] = FALSE;
  }

  //if autoreplace value is changed, flush the cache
  if (($newinput['autoreplace_insecure_links']!= $this->autoreplace_insecure_links)) {
 	 add_option('really_simple_ssl_settings_changed', 'settings_changed' );
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

public function get_option_debug() {
$options = get_option('rlrsssl_options');
echo '<input id="rlrsssl_options" name="rlrsssl_options[debug]" size="40" type="checkbox" value="1"' . checked( 1, $this->debug, false ) ." />";
}

  /**
   * Insert option into settings form
   * @since  2.0
   *
   * @access public
   *
   */

public function get_option_hsts() {
  $options = get_option('rlrsssl_options');
  $disabled = (!is_writable($this->ABSpath.".htaccess") || (is_multisite() && $this->set_rewriterule_per_site) || $this->do_not_edit_htaccess) ? "disabled" : "";

  echo '<input id="rlrsssl_options" name="rlrsssl_options[hsts]" onClick="return confirm(\''.__("Are you sure? Your visitors will keep going to a https site for a year after you turn this off.","really-simple-ssl").'\');" size="40" type="checkbox" '.$disabled.' value="1"' . checked( 1, $this->hsts, false ) ." />";
  if (is_multisite() && $this->set_rewriterule_per_site) _e("On multisite with per site activation, activating HSTS is not possible","really-simple-ssl");
  if ($this->do_not_edit_htaccess || !is_writable($this->ABSpath.".htaccess")) _e("You cannot use this option when .htaccess is not writable, or 'stop editing the .htaccess file' is enabled.","really-simple-ssl");

}

/**
 * Insert option into settings form
 * @since  2.2
 *
 * @access public
 *
 */

public function get_option_javascript_redirect() {
$options = get_option('rlrsssl_options');
echo '<input id="rlrsssl_options" name="rlrsssl_options[javascript_redirect]" size="40" type="checkbox" value="1"' . checked( 1, $this->javascript_redirect, false ) ." />";
}

/**
 * Insert option into settings form
 *
 * @since  2.0
 *
 * @access public
 *
 */

public function get_option_force_ssl_withouth_detection() {
$options = get_option('rlrsssl_options');
echo '<input id="rlrsssl_options" onClick="return confirm(\''.__("Are you sure you have an SSL certifcate? Forcing ssl on a non-ssl site can break your site.","really-simple-ssl").'\');" name="rlrsssl_options[force_ssl_without_detection]" size="40" type="checkbox" value="1"' . checked( 1, $this->force_ssl_without_detection, false ) ." />";
}

/**
 * Insert option into settings form
 *
 * @since  2.0
 *
 * @access public
 *
 */

public function get_option_do_not_edit_htaccess() {
  $options = get_option('rlrsssl_options');
  echo '<input id="rlrsssl_options" name="rlrsssl_options[do_not_edit_htaccess]" size="40" type="checkbox" value="1"' . checked( 1, $this->do_not_edit_htaccess, false ) ." />";
  if (!$this->do_not_edit_htaccess && !is_writable($this->ABSpath.".htaccess")) _e(".htaccess is currently not writable.","really-simple-ssl");
}

/**
 * Insert option into settings form
 *
 * @since  2.1
 *
 * @access public
 *
 */

public function get_option_autoreplace_insecure_links() {
  $options = get_option('rlrsssl_options');
  echo "<input id='rlrsssl_options' name='rlrsssl_options[autoreplace_insecure_links]' size='40' type='checkbox' value='1'" . checked( 1, $this->autoreplace_insecure_links, false ) ." />";
}
    /**
     * Add settings link on plugins overview page
     *
     * @since  2.0
     *
     * @access public
     *
     */

public function plugin_settings_link($links) {
  $settings_link = '<a href="options-general.php?page=rlrsssl_really_simple_ssl">'.__("Settings","really-simple-ssl").'</a>';
  array_unshift($links, $settings_link);
  return $links;
}

public function check_plugin_conflicts() {
  //Yoast conflict only occurs when mixed content fixer is active
  if ($this->autoreplace_insecure_links && defined('WPSEO_VERSION') ) {
    if ($this->debug) {$this->trace_log("Detected Yoast seo plugin");}
    $wpseo_options  = get_option("wpseo_titles");
    $forcerewritetitle = isset($wpseo_options['forcerewritetitle']) ? $wpseo_options['forcerewritetitle'] : FALSE;
    if ($forcerewritetitle) {
      $this->plugin_conflict["YOAST_FORCE_REWRITE_TITLE"] = TRUE;
      if ($this->debug) {$this->trace_log("Force rewrite titles set in Yoast plugin, which prevents really simple ssl from replacing mixed content");}
    } else {
      if ($this->debug) {$this->trace_log("No conflict issues with Yoast SEO detected");}
    }
  }

  //not necessary anymore after woocommerce 2.5
  if (class_exists('WooCommerce') && defined( 'WOOCOMMERCE_VERSION' ) && version_compare( WOOCOMMERCE_VERSION, '2.5', '<' ) ) {
    $woocommerce_force_ssl_checkout = get_option("woocommerce_force_ssl_checkout");
    $woocommerce_unforce_ssl_checkout = get_option("woocommerce_unforce_ssl_checkout");
    if (isset($woocommerce_force_ssl_checkout) && $woocommerce_force_ssl_checkout!="no") {
      $this->plugin_conflict["WOOCOMMERCE_FORCESSL"] = TRUE;
    }

    //setting force ssl in certain pages with woocommerce will result in redirect errors.
    if (isset($woocommerce_unforce_ssl_checkout) && $woocommerce_unforce_ssl_checkout!="no") {
      $this->plugin_conflict["WOOCOMMERCE_FORCEHTTP"] = TRUE;
      if ($this->debug) {$this->trace_log("Force HTTP when leaving the checkout set in woocommerce, disable this setting to prevent redirect loops.");}
    }
  }

}



/**
 * Get the absolute path the the www directory of this site, where .htaccess lives.
 *
 * @since  2.0
 *
 * @access public
 *
 */

public function getABSPATH(){
 $path = ABSPATH;
 if($this->is_subdirectory_install()){
   $siteUrl = site_url();
   $homeUrl = home_url();
   $diff = str_replace($homeUrl, "", $siteUrl);
   $diff = trim($diff,"/");
     $pos = strrpos($path, $diff);
     if($pos !== false){
       $path = substr_replace($path, "", $pos, strlen($diff));
       $path = trim($path,"/");
       $path = "/".$path."/";
     }
   }
   return $path;
 }

 /**
  * Find if this wordpress installation is installed in a subdirectory
  *
  * @since  2.0
  *
  * @access protected
  *
  */

protected function is_subdirectory_install(){
   if(strlen(site_url()) > strlen(home_url())){
     return true;
   }
   return false;
}

} //class closure
