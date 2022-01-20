<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

if ( ! function_exists('rsssl_test_trace' ) ) {
    function rsssl_test_trace()
    {

        if (!get_transient('rsssl_trace_allowed')) {

            if (function_exists('curl_init')) {

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, '');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'TRACE');

                $result = curl_exec($ch);
                if (curl_errno($ch)) {
                    return 'error';
                }
                curl_close($ch);
            }
        }
    }
}

if ( ! function_exists('rsssl_test_stack' ) ) {
    function rsssl_test_stack()
    {

        if ( ! get_transient( 'rsssl_stack_allowed' ) ) {

            if (function_exists('curl_init')) {

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, '');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'STACK');

                $result = curl_exec($ch);
                if (curl_errno($ch)) {
                    return 'error';
                }
                curl_close($ch);

            }
        }
    }
}

if ( ! function_exists('rsssl_disable_http_methods' ) ) {
    function rsssl_disable_http_methods()
    {

        // Fix
//RewriteCond %{REQUEST_METHOD} ^(TRACE|STACK)
//	RewriteRule .* - [F]

    }
}