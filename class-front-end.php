<?php
defined('ABSPATH') or die("you do not have acces to this page!");

if ( ! class_exists( 'rsssl_front_end' ) ) {
  class rsssl_front_end {
    private static $_this;
    public $force_ssl_without_detection     = FALSE;
    public $site_has_ssl                    = FALSE;
    public $javascript_redirect             = TRUE;
    public $autoreplace_insecure_links      = TRUE;
    public $http_urls                       = array();

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
   * Mixed content replacement when ssl is true and fixer is enabled.
   *
   * @since  2.2
   *
   * @access public
   *
   */

  public function force_ssl() {
    if ($this->javascript_redirect) add_action('wp_print_scripts', array($this,'force_ssl_with_javascript'));

    if (($this->site_has_ssl || $this->force_ssl_without_detection) && $this->autoreplace_insecure_links) {
      add_action('template_include', array($this, 'replace_insecure_links'), 0);
    }
  }


  /**
   * Creates an array of insecure links that should be https and an array of secure links to replace with
   *
   * @since  2.0
   *
   * @access public
   *
   */

  public function build_url_list() {
    $home_no_www  = str_replace ( "://www." , "://" , get_option('home'));
    $home_yes_www = str_replace ( "://" , "://www." , $home_no_www);

    $this->http_urls = array(
        str_replace ( "https://" , "http://" , $home_yes_www),
        str_replace ( "https://" , "http://" , $home_no_www),
        "src='http://",
        'src="http://',
    );
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

    if ($this->autoreplace_insecure_links || is_admin()) {
      $this->build_url_list();
    }
  }

  /**
   * Just before the page is sent to the visitor's browser, all homeurl links are replaced with https.
   *
   * @since  1.0
   *
   * @access public
   *
   */

   public function replace_insecure_links($template) {
     if ($this->ssl_enabled && ($this->site_has_ssl || $this->force_ssl_without_detection) && $this->autoreplace_insecure_links) {
       ob_start(array($this, 'end_buffer_capture'));  // Start Page Buffer
     }
     return $template;
   }

   /**
    * Just before the page is sent to the visitor's browser, all homeurl links are replaced with https.
    *
    * @since  1.0
    *
    * @access public
    *
    */

  public function end_buffer_capture($buffer) {
    $search_array = apply_filters('rlrsssl_replace_url_args', $this->http_urls);
    $ssl_array = str_replace ( "http://" , "https://", $search_array);
    //now replace these links
    $buffer = str_replace ($search_array , $ssl_array , $buffer);

    //replace all http links except hyperlinks
    //all tags with src attr are already fixed by str_replace
    $pattern = array(
      '/url\([\'"]?\K(http:\/\/)(?=[^)]+)/i',
      '/<link .*?href=[\'"]\K(http:\/\/)(?=[^\'"]+)/i',
      '/<meta property="og:image" .*?content=[\'"]\K(http:\/\/)(?=[^\'"]+)/i',
      //'/<(?:img|iframe) .*?src=[\'"]\K(http:\/\/)(?=[^\'"]+)/i',
      //'/<script [^>]*?src=[\'"]\K(http:\/\/)(?=[^\'"]+)/i',
    );
    $buffer = preg_replace($pattern, 'https://', $buffer);
    $buffer = $buffer.'<!-- rs-ssl -->';

    return apply_filters("rsssl_fixer_output", $buffer);;
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
    if ($this->site_has_ssl || $this->force_ssl_without_detection) {
        ?>
        <script>
        if (document.location.protocol != "https:") {
            document.location = document.URL.replace(/^http:/i, "https:");
        }
        </script>
        <?php
      }
  }

}}
