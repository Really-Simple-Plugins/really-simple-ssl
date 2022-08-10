<?php
defined( 'ABSPATH' ) or die();

/**
 * Test file for Really Simple SSL to check if uploads directory has code execution permissions
 *
 */


/**
 * @return bool
 *
 * Check if this function can be accessed in the /uploads/ folder
 */
function rsssl_test_code_execution() {
    return true;
}
