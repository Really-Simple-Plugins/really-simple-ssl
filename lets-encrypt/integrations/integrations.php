<?php defined( 'ABSPATH' ) or die();

$other_host = rsssl_get_other_host();
error_log($other_host);
if (file_exists( rsssl_le_path . "integrations/$other_host/$other_host.php" )){
	error_log("file exists");
	require_once( rsssl_le_path . "integrations/$other_host/$other_host.php" );
	require_once( rsssl_le_path . "integrations/$other_host/functions.php" );
} else if ( rsssl_is_plesk() ) {
	require_once( rsssl_le_path . 'integrations/plesk/plesk.php' );
	require_once( rsssl_le_path . 'integrations/plesk/functions.php' );
} else if ( rsssl_cpanel_api_supported() ) {
	require_once( rsssl_le_path . 'integrations/cpanel/cpanel.php' );
	require_once( rsssl_le_path . 'integrations/cpanel/functions.php' );
}

