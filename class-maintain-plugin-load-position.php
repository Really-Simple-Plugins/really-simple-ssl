<?php
defined('ABSPATH') or die("you do not have acces to this page!");

class rsssl_maintain_plugin_position {

private static $_this;

function __construct() {
  if ( isset( self::$_this ) )
      wp_die( sprintf( __( '%s is a singleton class and you cannot create a second instance.','really-simple-ssl' ), get_class( $this ) ) );

  self::$_this = $this;

  add_action( 'admin_init', array( $this, 'maintainPluginLoadPosition') );

}

static function this() {
  return self::$_this;
}

/**
 * Sets this plugin to be the first loaded of all the plugins.
 */

public function maintainPluginLoadPosition() {
  $sBaseFile = plugin_basename( __FILE__ );
  $nLoadPosition = $this->getActivePluginLoadPosition( $sBaseFile );
  if ( $nLoadPosition > 1 ) {
    $this->setActivePluginLoadPosition( $sBaseFile, 0 );
  }
}

/**
 * @param string $sPluginFile
 * @return int
 */
public function getActivePluginLoadPosition( $sPluginFile ) {
  $sOptionKey = is_multisite() ? 'active_sitewide_plugins' : 'active_plugins';
  $aActive = get_option( $sOptionKey );
  $nPosition = -1;
  if ( is_array( $aActive ) ) {
    $nPosition = array_search( $sPluginFile, $aActive );
    if ( $nPosition === false ) {
      $nPosition = -1;
    }
  }
  return $nPosition;
}

/**
 * @param string $sPluginFile
 * @param int $nDesiredPosition
 */
public function setActivePluginLoadPosition( $sPluginFile, $nDesiredPosition = 0 ) {

  $aActive = $this->setArrayValueToPosition( get_option( 'active_plugins' ), $sPluginFile, $nDesiredPosition );
  update_option( 'active_plugins', $aActive );

  if ( is_multisite() ) {
    $aActive = $this->setArrayValueToPosition( get_option( 'active_sitewide_plugins' ), $sPluginFile, $nDesiredPosition );
    update_option( 'active_sitewide_plugins', $aActive );
  }
}

/**
 * @param array $aSubjectArray
 * @param mixed $mValue
 * @param int $nDesiredPosition
 * @return array
 */
public function setArrayValueToPosition( $aSubjectArray, $mValue, $nDesiredPosition ) {

  if ( $nDesiredPosition < 0 || !is_array( $aSubjectArray ) ) {
    return $aSubjectArray;
  }

  $nMaxPossiblePosition = count( $aSubjectArray ) - 1;
  if ( $nDesiredPosition > $nMaxPossiblePosition ) {
    $nDesiredPosition = $nMaxPossiblePosition;
  }

  $nPosition = array_search( $mValue, $aSubjectArray );
  if ( $nPosition !== false && $nPosition != $nDesiredPosition ) {

    // remove existing and reset index
    unset( $aSubjectArray[ $nPosition ] );
    $aSubjectArray = array_values( $aSubjectArray );

    // insert and update
    // http://stackoverflow.com/questions/3797239/insert-new-item-in-array-on-any-position-in-php
    array_splice( $aSubjectArray, $nDesiredPosition, 0, $mValue );
  }

  return $aSubjectArray;
}
}
