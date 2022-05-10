<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

/**
 * @return bool
 *
 * Check if this function can be accessed in the /uploads/ folder
 */
function rsssl_test_code_execution()
{
    return true;
}
