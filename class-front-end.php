<?php
defined('ABSPATH') or die("you do not have acces to this page!");

if ( ! class_exists( 'rsssl_front_end' ) ) {
  class rsssl_front_end {
    private static $_this;
    public $force_ssl_without_detection     = FALSE;
    public $site_has_ssl                    = FALSE;
    public $javascript_redirect             = TRUE;
    public $autoreplace_insecure_links      = TRUE;

  function __construct() {
    if ( isset( self::$_this ) )
        wp_die( sprintf( __( '%s is a singleton class and you cannot create a second instance.','really-simple-ssl' ), get_class( $this ) ) );

    self::$_this = $this;

    $this->get_options();

  }

  static function this() {
    return self::$_this;
  }

  /**
   * Javascript redirect, when ssl is true.
   *
   * @since  2.2
   *
   * @access public
   *
   */

   public function force_ssl() {
     if ($this->ssl_enabled && ($this->site_has_ssl || $this->force_ssl_without_detection) ) {
       if ($this->javascript_redirect) add_action('wp_print_scripts', array($this,'force_ssl_with_javascript'));
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

  public function get_options(){
    $options = get_option('rlrsssl_options');
    if (isset($options)) {
      $this->force_ssl_without_detection  = isset($options['force_ssl_without_detection']) ? $options['force_ssl_without_detection'] : FALSE;
      $this->site_has_ssl                 = isset($options['site_has_ssl']) ? $options['site_has_ssl'] : FALSE;
      $this->autoreplace_insecure_links   = isset($options['autoreplace_insecure_links']) ? $options['autoreplace_insecure_links'] : TRUE;
      $this->ssl_enabled                  = isset($options['ssl_enabled']) ? $options['ssl_enabled'] : $this->site_has_ssl;
      $this->javascript_redirect          = isset($options['javascript_redirect']) ? $options['javascript_redirect'] : TRUE;
    }
  }


  /**
   * Adds some javascript to redirect to https.
   *
   * @since  1.0
   *
   * @access public
   *
   */

  public function force_ssl_with_javascript() {
      ?>
      <script>
      if (document.location.protocol != "https:") {
          document.location = document.URL.replace(/^http:/i, "https:");
      }
      </script>
      <?php
  }

}}
