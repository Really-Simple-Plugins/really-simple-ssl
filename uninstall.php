<?php
// If uninstall is not called from WordPress, exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

delete_all_options('rlrsssl_options');
delete_all_options('rlrsssl_network_options');

function delete_all_options($option_name) {
  delete_option( $option_name );
  // For site options in Multisite
  delete_site_option( $option_name );
}
