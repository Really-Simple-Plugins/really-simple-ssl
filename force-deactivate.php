<?php
/*
* Deactivation page to simple deactivate the plugin when backend is not accessible anymore
* To deactivate:
*       1) rename this file to force-deactivate.php
*       2) Go in your browser to www.yourdomain.com/wp-content/plugins/really-simple-ssl/force-deactivate.php.
*       3) IMPORTANT!!!! Rename this file back to .txt
*/

?>
<html>
<body>

<?php
  # No need for the template engine
  define( 'WP_USE_THEMES', false );

  #find the base path
  define( 'BASE_PATH', find_wordpress_base_path()."/" );

  # Load WordPress Core
  require_once( BASE_PATH.'wp-load.php' );
  require_once(ABSPATH.'wp-admin/includes/plugin.php');
  $core_plugin = 'really-simple-ssl/rlrsssl-really-simple-ssl.php';

  if (!is_plugin_active($core_plugin) ) {
    echo "<h1>Really Simple SSL is already deactivated!</h1>";
    exit;
  }

  #Load plugin functionality
  require_once( dirname( __FILE__ ) .  '/class-front-end.php' );
  require_once( dirname( __FILE__ ) .  '/class-admin.php' );

  $really_simple_ssl = new rsssl_admin();
   if (is_multisite()) {
     require_once( dirname( __FILE__ ) .  '/class-multisite.php' );
     $rsssl_multisite = new rsssl_multisite();
   }

   $step = 1;
   echo "<h1>Force deactivation of Really Simple SSL</h1>";
   echo $step.". Resetting options"."<br>";
   $networkwide = is_multisite();
   $really_simple_ssl->deactivate($networkwide);
   $step++;

   //add feedback on writable files.
   if (count($really_simple_ssl->errors)>0) {
     echo $step.". Errors occured while deactivating:<ul>";
    $step++;
     foreach($really_simple_ssl->errors as $errorname=>$error) {
       echo "<li>".$errorname."</li>";
     }
     echo "</ul>";
     echo "Errors while removing the really simple ssl lines from your wp-config.php and .htacces, wich you can normally find in your webroot."."<br><br>";
   }

   echo $step.". Deactivating plugin"."<br>";
   rl_deactivate_plugin($really_simple_ssl->plugin_dir."/".$really_simple_ssl->plugin_filename);

   $step++;
   echo $step.". Completed with <b>".count($really_simple_ssl->errors)."</b> error(s)"."<br>";




function rl_remove_plugin_from_array($plugin, $current) {
  $key = array_search( $plugin, $current );
  if ( false !== $key ) {
    $do_blog = true;
    unset( $current[ $key ] );
  }
  return $current;
}

function rl_deactivate_plugin( $plugin ) {
  $plugin = plugin_basename( trim( $plugin ) );

	if ( is_multisite() ) {

		$network_current = get_site_option( 'active_sitewide_plugins', array() );
    if ( is_plugin_active_for_network( $plugin ) ) { unset( $network_current[ $plugin ] );}
    update_site_option( 'active_sitewide_plugins', $network_current );

    //remove plugin one by one on each site
    $sites = wp_get_sites();
    foreach ( $sites as $site ) {
        switch_to_blog( $site[ 'blog_id' ] );

        $current = get_option( 'active_plugins', array() );
        $current = rl_remove_plugin_from_array($plugin, $current);
        update_option('active_plugins', $current);

        restore_current_blog(); //switches back to previous blog, not current, so we have to do it each loop
      }

} else {
  $current = get_option( 'active_plugins', array() );
  $current = rl_remove_plugin_from_array($plugin, $current);
  update_option('active_plugins', $current);
}









	update_option('active_plugins', $current);



}

function find_wordpress_base_path() {
  $dir = dirname(__FILE__);
  do {
      //it is possible to check for other files here
      if( file_exists($dir."/wp-config.php") ) {
          return $dir;
      }
  } while( $dir = realpath("$dir/..") );
  return null;
}

?>
</body>
</html>
