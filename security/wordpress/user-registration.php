<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

/**
 * Action to disable user registration
 *
 * @return bool
 */
function rsssl_disable_user_registration($value, $option) {
	return false;
}
add_filter( "option_users_can_register", 'rsssl_disable_user_registration', 999, 2 );