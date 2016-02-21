<?php
defined('ABSPATH') or die("you do not have acces to this page!");

class rlrsssl_cache {
  private
       $capability  = 'manage_options';

  /**
   * Flushes the cache for popular caching plugins to prevent mixed content errors
   * When .htaccess is changed, all traffic should flow over https, so clear cache when possible.
   * supported: W3TC, WP fastest Cache, Zen Cache
   *
   * @since  2.0
   *
   * @access public
   *
   */

  public function flush() {
    if (!current_user_can($this->capability)) return;

    if (get_option('really_simple_ssl_settings_changed') == 'settings_changed') {
      delete_option( 'really_simple_ssl_settings_changed');
      add_action( 'shutdown', array($this,'flush_w3tc_cache'));
      add_action( 'shutdown', array($this,'flush_fastest_cache'));
      add_action( 'shutdown', array($this,'flush_zen_cache'));
      add_action( 'shutdown', array($this,'flush_wp_rocket'));
    }
  }

  public function flush_w3tc_cache() {
    if( class_exists('W3_Plugin_TotalCacheAdmin') )
    {
      if (function_exists('w3tc_flush_all')) {
        w3tc_flush_all();
      }
    }
  }

  public function flush_fastest_cache() {
    if(class_exists('WpFastestCache') )
    {
      $GLOBALS["wp_fastest_cache"]->deleteCache(TRUE);
    }
  }

  public function flush_zen_cache() {
    if (class_exists('\\zencache\\plugin') )
    {
      $GLOBALS['zencache']->clear_cache();
    }
  }

  public function flush_wp_rocket() {
    if (function_exists("rocket_clean_domain")) {
      rocket_clean_domain();
    }
  }

}
