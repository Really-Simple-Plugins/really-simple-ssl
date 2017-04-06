<?php

defined('ABSPATH') or die("you do not have acces to this page!");

if ( ! class_exists( 'rsssl_multisite' ) ) {
  class rsssl_multisite {
    private static $_this;

    public $option_group = "rsssl_network_options";
    public $page_slug = "really-simple-ssl";
    public $section = "rsssl_network_options_section";
    public $wp_redirect;
    public $htaccess_redirect;
    public $ssl_enabled_networkwide;
    public $do_not_edit_htaccess;
    public $selected_networkwide_or_per_site;

  function __construct() {
    if ( isset( self::$_this ) )
        wp_die( sprintf( __( '%s is a singleton class and you cannot create a second instance.','really-simple-ssl' ), get_class( $this ) ) );

    self::$_this = $this;


    $this->load_options();
    register_activation_hook(  dirname( __FILE__ )."/".rsssl_plugin, array($this,'activate') );
    add_filter("admin_url", array($this, "check_protocol_multisite"), 20, 3 );

    add_action("plugins_loaded", array($this, "init"), 10, 0);
    add_action("plugins_loaded", array($this, "process_networkwide_choice"), 20, 0);

    add_action('network_admin_menu', array( &$this, 'add_multisite_menu' ) );
    add_action('network_admin_edit_rsssl_update_network_settings',  array($this,'update_network_options'));

  }

  static function this() {
    return self::$_this;
  }

  public function init(){
    if (!$this->selected_networkwide_or_per_site) {
      add_action('network_admin_notices', array($this, 'show_notice_activate_networkwide'), 10);
    }
  }

  public function load_options(){
    $options = get_site_option('rlrsssl_network_options');

    $this->wp_redirect  = isset($options["wp_redirect"]) ? $options["wp_redirect"] : false;
    $this->htaccess_redirect = isset($options["htaccess_redirect"]) ? $options["htaccess_redirect"] : false;
    $this->ssl_enabled_networkwide = isset($options["ssl_enabled_networkwide"]) ? $options["ssl_enabled_networkwide"] : false;
    $this->do_not_edit_htaccess = isset($options["do_not_edit_htaccess"]) ? $options["do_not_edit_htaccess"] : false;
    $this->selected_networkwide_or_per_site = isset($options["selected_networkwide_or_per_site"]) ? $options["selected_networkwide_or_per_site"] : false;

  }


  /**
   * On plugin activation, we can check if it is networkwide or not.
   *
   * @since  2.1
   *
   * @access public
   *
   */

  public function activate($networkwide) {
    //if networkwide, we ask, if not, we set it as selected.
    if (!$networkwide) {
        $this->selected_networkwide_or_per_site = true;
        $this->ssl_enabled_networkwide = false;
        $this->save_options();
    }

  }





  /*

      Add network menu for SSL
      Only when plugin is network activated.

  */

  public function add_multisite_menu(){
    if (!$this->plugin_network_wide_active()) return;


    register_setting( $this->option_group, 'rsssl_options');
    add_settings_section('rsssl_network_settings', __("Settings","really-simple-ssl"), array($this,'section_text'), $this->page_slug);

    global $really_simple_ssl;
    if ($really_simple_ssl->site_has_ssl) {

      add_settings_field('id_ssl_enabled_networkwide', __("Enable SSL", "really-simple-ssl"), array($this,'get_option_enable_multisite'), $this->page_slug, 'rsssl_network_settings');

      if ($this->selected_networkwide_or_per_site) {
        add_settings_field('id_301_redirect', __("Enable WordPress 301 redirection to SSL for all SSL sites","really-simple-ssl"), array($this,'get_option_301_redirect'), $this->page_slug, 'rsssl_network_settings');

        add_settings_field('id_htaccess_redirect', __("Enable htacces redirection to SSL on the network","really-simple-ssl"), array($this,'get_option_htaccess_redirect'), $this->page_slug, 'rsssl_network_settings');
        add_settings_field('id_do_not_edit_htaccess', __("Stop editing the .htaccess file","really-simple-ssl"), array($this,'get_option_do_not_edit_htaccess'), $this->page_slug, 'rsssl_network_settings');

      }
    }
    global $rsssl_network_admin_page;
    $rsssl_network_admin_page = add_submenu_page('settings.php', "SSL", "SSL", 'manage_options', $this->page_slug, array( &$this, 'multisite_menu_page' ) );
  }

  /*
      Shows the content of the multisite menu page
  */



  public function section_text(){
    global $really_simple_ssl;
    if (!$really_simple_ssl->site_has_ssl) {
      ?>
      <p>
        <?php _e("No SSL was detected. If you do have an ssl certificate, try to reload this page over https by clicking this link:","really-simple-ssl");?>&nbsp;<a href="<?php echo $current_url?>"><?php _e("reload over https.","really-simple-ssl");?></a>
        <?php _e("You can check your certificate on","really-simple-ssl");?>&nbsp;<a target="_blank" href="https://www.ssllabs.com/ssltest/">Qualys SSL Labs</a>
      </p>
      <?php
    } else {
      _e("Below you can set the multisite options for Really Simple SSL","really-simple-ssl");
    }
  }

  public function get_option_enable_multisite(){
    $this->ssl_enabled_networkwide;
    $this->selected_networkwide_or_per_site;

      ?>
      <select name="rlrsssl_network_options[ssl_enabled_networkwide]">
        <?php if (!$this->selected_networkwide_or_per_site) {?>
        <option value="-1" <?php if (!$this->selected_networkwide_or_per_site) echo "selected";?>><?php _e("No selection was made", "really-simple-ssl")?>
        <?php }?>
        <option value="1" <?php if ($this->ssl_enabled_networkwide) echo "selected";?>><?php _e("networkwide", "really-simple-ssl")?>
        <option value="0" <?php if (!$this->ssl_enabled_networkwide) echo "selected";?>><?php _e("per site", "really-simple-ssl")?>
      </select>
      <?php

      //echo '<input id="rlrsssl_options" name="rlrsssl_network_options[ssl_enabled_networkwide]" size="40" type="checkbox" value="1"' . checked( 1, $this->ssl_enabled_networkwide, false ) ." />";
      rsssl_help::this()->get_help_tip(__("Select to enable SSL networkwide or per site.", "really-simple-ssl"));
  }

  public function get_option_htaccess_redirect(){
      echo '<input id="rlrsssl_options" name="rlrsssl_network_options[htaccess_redirect]" size="40" type="checkbox" value="1"' . checked( 1, $this->htaccess_redirect, false ) ." />";

      if($this->ssl_enabled_networkwide) {
        rsssl_help::this()->get_help_tip(__("Enable this if you want to redirect ALL websites to SSL using .htaccess", "really-simple-ssl"));
      } else {
        rsssl_help::this()->get_help_tip(__("Enable this if you want to redirect SSL websites using .htaccess. ", "really-simple-ssl"));
      }
  }

  public function get_option_301_redirect(){
      echo '<input id="rlrsssl_options" name="rlrsssl_network_options[wp_redirect]" size="40" type="checkbox" value="1"' . checked( 1, $this->wp_redirect, false ) ." />";
      if($this->ssl_enabled_networkwide) {
      rsssl_help::this()->get_help_tip(__("Enable this if you want to use the internal WordPress 301 redirect for ALL websites. Needed on NGINX servers, or if the .htaccess redirect cannot be used.", "really-simple-ssl"));
  }   else {
      rsssl_help::this()->get_help_tip(__("Enable this if you want to use the internal WordPress 301 redirect for SSL websites. Needed on NGINX servers, or if the .htaccess redirect cannot be used.", "really-simple-ssl"));

  }
}

  public function get_option_do_not_edit_htaccess(){
      echo '<input id="rlrsssl_options" name="rlrsssl_network_options[do_not_edit_htaccess]" size="40" type="checkbox" value="1"' . checked( 1, $this->do_not_edit_htaccess, false ) ." />";
      rsssl_help::this()->get_help_tip(__("Enable this if you want to block the htaccess file from being edited.", "really-simple-ssl"));
  }


/**
 * Displays the options page. The big difference here is where you post the data
 * because, unlike for normal option pages, there is nowhere to process it by
 * default so we have to create our own hook to process the saving of our options.
 */

 public function multisite_menu_page() {
   if (isset($_GET['updated'])): ?>
   <div id="message" class="updated notice is-dismissible"><p><?php _e('Options saved.') ?></p></div>
  <?php endif; ?>
  <div class="wrap">
    <h1><?php _e('Really Simple SSL multisite options', 'really-simple-ssl'); ?></h1>
    <form method="POST" action="edit.php?action=rsssl_update_network_settings">
      <?php

          settings_fields($this->option_group);
          do_settings_sections($this->page_slug);

          submit_button();
        // settings_fields('rsssl_network_settings_page');
        // do_settings_sections('rsssl_network_settings_page');
        // submit_button();
      ?>
      <?php //wp_nonce_field( 'rsssl_save_network_settings', 'rsssl_save_network_settings_nonce' ); ?>
    </form>
  </div>
<?php
}


/**
 * Save network settings
 */

 public function update_network_options() {
  check_admin_referer($this->option_group.'-options');

  if (isset($_POST["rlrsssl_network_options"])) {
    $options = array_map(array($this, "sanitize_boolean"), $_POST["rlrsssl_network_options"]);
    $options["selected_networkwide_or_per_site"] = true;

    $this->wp_redirect  = isset($options["wp_redirect"]) ? $options["wp_redirect"] : false;
    $this->htaccess_redirect = isset($options["htaccess_redirect"]) ? $options["htaccess_redirect"] : false;
    $this->ssl_enabled_networkwide = isset($options["ssl_enabled_networkwide"]) ? $options["ssl_enabled_networkwide"] : false;
    $this->do_not_edit_htaccess = isset($options["do_not_edit_htaccess"]) ? $options["do_not_edit_htaccess"] : false;

    $this->selected_networkwide_or_per_site = isset($options["selected_networkwide_or_per_site"]) ? $options["selected_networkwide_or_per_site"] : false;
  }

  $this->save_options();

  if ($this->ssl_enabled_networkwide) {
    //enable SSL on all  sites on the network
    $this->activate_ssl_networkwide();
  } else {
    //if we switch to per page, we deactivate SSL on all pages first.
    $sites = $this->get_sites_bw_compatible();
    global $really_simple_ssl;
    foreach ( $sites as $site ) {
      $this->switch_to_blog_bw_compatible($site);
      $really_simple_ssl->deactivate_ssl();
      restore_current_blog(); //switches back to previous blog, not current, so we have to do it each loop
    }
  }


  // At last we redirect back to our options page.
  wp_redirect(add_query_arg(array('page' => $this->page_slug, 'updated' => 'true'), network_admin_url('settings.php')));
  exit;
}

  public function sanitize_boolean($n)
{
  return boolval($n);
}


/**
 * Give the user an option to activate network wide or not.
 * Needs to be called after detect_configuration function
 *
 * @since  2.3
 *
 * @access public
 *
 */

 public function show_notice_activate_networkwide(){
  global $really_simple_ssl;
  //if no SSL was detected, don't activate it yet.
  if (!$really_simple_ssl->site_has_ssl) {
    global $wp;
    $current_url = "https://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]
    ?>
    <div id="message" class="error fade notice activate-ssl">
    <p><?php _e("No SSL was detected. If you do have an ssl certificate, try to reload this page over https by clicking this link:","really-simple-ssl");?>&nbsp;<a href="<?php echo $current_url?>"><?php _e("reload over https.","really-simple-ssl");?></a>
      <?php _e("You can check your certificate on","really-simple-ssl");?>&nbsp;<a target="_blank" href="https://www.ssllabs.com/ssltest/">Qualys SSL Labs</a>
    </p>
  </div>
  <?php }


  if ($really_simple_ssl->site_has_ssl) {
    if (is_main_site(get_current_blog_id()) && $really_simple_ssl->wpconfig_ok()) {
      ?>
      <div id="message" class="updated fade notice activate-ssl">
        <h1><?php _e("Choose your preferred setup","really-simple-ssl");?></h1>
      <p>
        <form action="" method="post">
          <?php wp_nonce_field( 'rsssl_nonce', 'rsssl_nonce' );?>
          <input type="submit" class='button button-primary' value="<?php _e("Activate SSL networkwide","really-simple-ssl");?>" id="rsssl_do_activate_ssl_networkwide" name="rsssl_do_activate_ssl_networkwide">
          <input type="submit" class='button button-primary' value="<?php _e("Activate SSL per site","really-simple-ssl");?>" id="rsssl_do_activate_ssl_per_site" name="rsssl_do_activate_ssl_per_site">
        </form>
      </p>
      <p>
        <?php _e("Networkwide activation does not check if a site has an SSL certificate. It just migrates all sites to SSL.","really-simple-ssl");?>
      </p>
      </div>
      <?php
    }
  }


}



/*

    Check if the plugin is network activated.

*/

  public function plugin_network_wide_active(){

    if ( is_plugin_active_for_network( 'really-simple-ssl/rlrsssl-really-simple-ssl.php') ){
      return true;
    } else {
      return false;
    }
  }



  public function process_networkwide_choice(){
    if (!$this->plugin_network_wide_active()) return;

    if ( isset($_POST['rsssl_do_activate_ssl_networkwide'])) {
      $this->selected_networkwide_or_per_site = true;
      $this->ssl_enabled_networkwide = true;
      $this->wp_redirect = true;
      $this->save_options();

      //enable SSL on all  sites on the network
      $this->activate_ssl_networkwide();

    }

    if (isset($_POST['rsssl_do_activate_ssl_per_site'])) {
      $this->selected_networkwide_or_per_site = true;
      $this->ssl_enabled_networkwide = false;
      $this->save_options();
    }
  }


  public function save_options(){
    $options = get_site_option("rlrsssl_network_options");
    if (!is_array($options)) $options = array();

    $options["selected_networkwide_or_per_site"] = $this->selected_networkwide_or_per_site;
    $options["ssl_enabled_networkwide"] = $this->ssl_enabled_networkwide;
    $options["wp_redirect"] = $this->wp_redirect;
    $options["htaccess_redirect"] = $this->htaccess_redirect;
    $options["do_not_edit_htaccess"] = $this->do_not_edit_htaccess;
    update_site_option("rlrsssl_network_options", $options);
  }


  public function activate_ssl_networkwide(){
    global $really_simple_ssl;

    //set all sites as enabled
    $sites = $this->get_sites_bw_compatible();

    foreach ( $sites as $site ) {
      $this->switch_to_blog_bw_compatible($site);
      $really_simple_ssl->activate_ssl();
      restore_current_blog(); //switches back to previous blog, not current, so we have to do it each loop
    }

  }




  //change deprecated function depending on version.

  public function get_sites_bw_compatible(){
    global $wp_version;
    $sites = ($wp_version >= 4.6 ) ? get_sites() : wp_get_sites();
    return $sites;
  }

  /*
        The new get_sites function returns an object.

  */

  public function switch_to_blog_bw_compatible($site){
    global $wp_version;
    if ($wp_version >= 4.6 ) {
      switch_to_blog( $site->blog_id );
    } else {
      switch_to_blog( $site[ 'blog_id' ] );
    }
  }

  public function deactivate(){

    $options = get_site_option("rlrsssl_network_options");
    $options["selected_networkwide_or_per_site"] = false;
    $options["wp_redirect"] = false;
    $options["htaccess_redirect"] = false;
    $options["do_not_edit_htaccess"] = false;

    unset($options["ssl_enabled_networkwide"]);
    update_site_option("rlrsssl_network_options", $options);

    $sites = $this->get_sites_bw_compatible();
    global $really_simple_ssl;
    foreach ( $sites as $site ) {
      $this->switch_to_blog_bw_compatible($site);
      $really_simple_ssl->deactivate_ssl();
      restore_current_blog(); //switches back to previous blog, not current, so we have to do it each loop
    }

}


/**
* filters the get_admin_url function to correct the false https urls wordpress returns for non ssl websites.
*
* @since 2.3.10
*
*/

public function check_protocol_multisite($url, $path, $blog_id){
  if (is_multisite() && !$this->ssl_enabled_networkwide) {
    $options = get_blog_option($blog_id, "rlrsssl_options");

    if ($options && isset($options)) {
      $site_has_ssl = isset($options['site_has_ssl']) ? $options['site_has_ssl'] : FALSE;
      $ssl_enabled = isset($options['ssl_enabled']) ? $options['ssl_enabled'] : $site_has_ssl;
      if (!$ssl_enabled) {
        $url = str_replace("https://","http://",$url);
      }
    }
  }
  return $url;
}


public function show_notices(){
  /*
    when a server variable is not passed, redirect loops could result
  */

  if (is_multisite() && !$this->ssl_enabled_networkwide && $this->selected_networkwide_or_per_site && $this->is_multisite_subfolder_install()) {
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
    }
  }
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
  $is_subfolder = FALSE;
  $sites = $this->get_sites_bw_compatible();
  foreach ( $sites as $site ) {
    $this->switch_to_blog_bw_compatible($site);
    if ($this->is_subfolder(home_url())) {
      $is_subfolder=TRUE;
    }
    restore_current_blog(); //switches back to previous blog, not current, so we have to do it each loop
    if ($is_subfolder) return true;
  }

  return $is_subfolder;
}


public function is_per_site_activated_multisite_subfolder_install() {
  global $rsssl_multisite;
  if (is_multisite() && $this->is_multisite_subfolder_install() && !$this->ssl_enabled_networkwide){
    return true;
  }

  return false;
}


} //class closure
}
