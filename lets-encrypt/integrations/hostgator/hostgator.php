<?php
defined( 'ABSPATH' ) or die();

/**
 * On hostgator, we don't have the cpanel api, so remove these steps.
 * @param $add
 *
 * @return false
 */
function rsssl_hostgator_drop_actions($add){
	return false;
}
add_filter('rssl_le_add_cpanel_steps', 'rsssl_hostgator_drop_actions');