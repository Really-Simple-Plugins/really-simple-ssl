<?php
defined('ABSPATH') or die("you do not have acces to this page!");

if ( ! class_exists( 'rl_rsssl_front_end' ) ) {


class rl_rsssl_front_end {
  public
   $force_ssl_without_detection     = FALSE,
   $site_has_ssl                    = FALSE,
   $autoreplace_insecure_links      = TRUE;

  public function __construct()
  {
      $this->get_options();
  }


  /**
   * Moves the site to ssl, and fixes insecure links.
   *
   * @since  2.2
   *
   * @access public
   *
   */

  public function force_ssl() {

    // javascript redirect, when ssl is true.
    add_action('wp_print_scripts', array($this,'force_ssl_with_javascript'));

    // mixed content replacement when ssl is true and fixer is enabled.
    add_filter('template_include', array($this,'replace_insecure_links'));
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
    if (($this->site_has_ssl || $this->force_ssl_without_detection) && $this->autoreplace_insecure_links) {
      ob_start(array($this, 'end_buffer_capture'));  // Start Page Buffer
    }
    return $template;
  }

  /**
   * Just before the page is sent to the visitor's browser,
   * all homeurl links are replaced with protocol-independent lnks
   *
   * @since  1.0
   *
   * @access public
   *
   */

  public function end_buffer_capture($buffer) {
    //now replace all http links to protocol-independent links.
    $buffer = str_replace ("http://", "//" , $buffer);
    return $buffer;
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
