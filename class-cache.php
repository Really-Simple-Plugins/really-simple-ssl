<?php

defined('ABSPATH') or die("you do not have access to this page!");
if ( ! class_exists( 'rsssl_cache' ) ) {
  class rsssl_cache {
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
    if (!rsssl_user_can_manage()) return;

    add_action( 'admin_head', array($this,'maybe_flush_w3tc_cache'));
    add_action( 'admin_head', array($this,'maybe_flush_wp_optimize_cache'));
	add_action( 'admin_head', array($this,'maybe_flush_litespeed_cache'));
	add_action( 'admin_head', array($this,'maybe_flush_hummingbird_cache'));
    add_action( 'admin_head', array($this,'maybe_flush_fastest_cache'));
	add_action( 'admin_head', array($this,'maybe_flush_autoptimize_cache'));
    add_action( 'admin_head', array($this,'maybe_flush_wp_rocket'));
	add_action( 'admin_head', array($this,'maybe_flush_cache_enabler'));
	add_action( 'admin_head', array($this,'maybe_flush_wp_super_cache'));
  }

  public function maybe_flush_w3tc_cache() {
	  if (!rsssl_user_can_manage()) return;

	  if ( function_exists('w3tc_flush_all') ) {
        w3tc_flush_all();
      }
  }

  public function maybe_flush_wp_optimize_cache() {
	  if (!rsssl_user_can_manage()) return;

	  if ( function_exists('wpo_cache_flush') ) {
		  wpo_cache_flush();
	  }
  }

  public function maybe_flush_litespeed_cache() {
	  if (!rsssl_user_can_manage()) return;

	  if ( class_exists('LiteSpeed') ) {
		  Litespeed\Purge::purge_all();
	  }
  }

  public function maybe_flush_hummingbird_cache() {
	  if (!rsssl_user_can_manage()) return;

	  if ( is_callable( array('Hummingbird\WP_Hummingbird', 'flush_cache') ) ) {
		  Hummingbird\WP_Hummingbird::flush_cache();
	  }
  }

  public function maybe_flush_fastest_cache() {
	  if (!rsssl_user_can_manage()) return;

	  if( class_exists('WpFastestCache') ) {
		  // Non-static cannot be called statically ::
	      (new WpFastestCache)->deleteCache();
      }
  }

  public function maybe_flush_autoptimize_cache() {
	  if (!rsssl_user_can_manage()) return;

	  if ( class_exists('autoptimizeCache') ) {
		  autoptimizeCache::clearall();
	  }
  }

  public function maybe_flush_wp_rocket() {
	  if (!rsssl_user_can_manage()) return;

	  if ( function_exists('rocket_clean_domain') ) {
		  rocket_clean_domain();
	  }
  }

  public function maybe_flush_cache_enabler() {
	  if (!rsssl_user_can_manage()) return;

	  if ( class_exists('Cache_Enabler') ) {
	    Cache_Enabler::clear_complete_cache();
	  }
  }

  public function maybe_flush_wp_super_cache() {
	  if (!rsssl_user_can_manage()) return;

	  if ( function_exists( 'wp_cache_clear_cache' ) ) {
		  wp_cache_clear_cache();
	  }
  }

}//class closure
}
