<?php

/**
 * @return void
 * Update option to disable user enumeration
 */
function rsssl_disable_user_enumeration() {
    update_option('rsssl_disable_user_enumeration', true );
}

/**
 * @return void
 * Update option to enable user enumeration
 */
function rsssl_enable_user_enumeration() {
    update_option('rsssl_disable_user_enumeration', false );
}