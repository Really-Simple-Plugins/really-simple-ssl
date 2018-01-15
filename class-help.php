<?php
defined('ABSPATH') or die("you do not have access to this page!");
if ( ! class_exists( 'rsssl_help' ) ) {
  class rsssl_help {
    private static $_this;

  function __construct() {
    if ( isset( self::$_this ) )
        wp_die( sprintf( __( '%s is a singleton class and you cannot create a second instance.','really-simple-ssl' ), get_class( $this ) ) );

    self::$_this = $this;
  }

  static function this() {
    return self::$_this;
  }

  public function get_help_tip($str){
    ?>
    <span class="rsssl-tooltip-right tooltip-right" data-rsssl-tooltip="<?php echo $str?>">
      <span class="dashicons dashicons-editor-help"></span>
    </span>
    <?php

  }




}//class closure
} //if class exists closure
