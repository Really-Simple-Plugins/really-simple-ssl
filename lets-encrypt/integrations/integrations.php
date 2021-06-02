<?php defined( 'ABSPATH' ) or die();
$other_host = rsssl_get_other_host();
if (file_exists( rsssl_le_path . "integrations/$other_host/$other_host.php" )) {
	require_once( rsssl_le_path . "integrations/$other_host/$other_host.php" );
}
if (file_exists( rsssl_le_path . "integrations/$other_host/functions.php" )){
	require_once( rsssl_le_path . "integrations/$other_host/functions.php" );
}

if ( rsssl_is_cpanel() ) {
	require_once( rsssl_le_path . 'integrations/cpanel/cpanel.php' );
} else if ( rsssl_is_plesk() ) {
	require_once( rsssl_le_path . 'integrations/plesk/plesk.php' );
} else if ( rsssl_is_directadmin() ) {
	require_once( rsssl_le_path . 'integrations/directadmin/directadmin.php' );
}

