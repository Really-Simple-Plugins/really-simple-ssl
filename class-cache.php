<?php
defined('ABSPATH') or die("you do not have acces to this page!");
if ( ! class_exists( 'rsssl_cache' ) ) {
  class rsssl_cache {
    private $capability  = 'manage_options';
    private static $_this;

  function __construct() {
    if ( isset( self::$_this ) )
        wp_die( sprintf( __( '%s is a singleton class and you cannot create a second instance.','really-simple-ssl' ), get_class( $this ) ) );

    self::$_this = $this;
  }

  static function this() {
    return self::$_this;
  }

  /**
   * Flushes the cache for popular caching plugins to prevent mixed content errors
   * When .htaccess is changed, all traffic should flow over https, so clear cache when possible.
   * supported: W3TC, WP fastest Cache, Zen Cache, wp_rocket
   *
   * @since  2.0
   *
   * @access public
   *
   */

  public function flush() {
    if (!current_user_can($this->capability)) return;

    add_action( 'admin_head', array($this,'flush_w3tc_cache'));
    add_action( 'admin_head', array($this,'flush_fastest_cache'));
    add_action( 'admin_head', array($this,'flush_zen_cache'));
    
    //keep getting errors from wp-rocket.
    //add_action( 'admin_head', array($this,'flush_wp_rocket'));
  }

  public function flush_w3tc_cache() {
    if (function_exists('w3tc_flush_all')) {
      w3tc_flush_all();
    }
  }

  public function flush_fastest_cache() {
    if(class_exists('WpFastestCache') ) {
      $GLOBALS["wp_fastest_cache"]->deleteCache(TRUE);
    }
  }

  public function flush_zen_cache() {
    if (class_exists('\\zencache\\plugin') ) {
      $GLOBALS['zencache']->clear_cache();
    }
  }

  public function flush_wp_rocket() {
    if (function_exists("rocket_clean_domain")) {
      rocket_clean_domain();
    }
  }

}//class closure
}
