<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

if ( ! function_exists('rsssl_test_trace' ) ) {
    function rsssl_test_trace()
    {

//        if (!get_transient('rsssl_trace_allowed')) {

            if (function_exists('curl_init')) {

				$url = site_url();
                $ch = curl_init();

	            curl_setopt($ch, CURLOPT_URL, $url );
//	            curl_setopt($ch, CURLOPT_HEADER, 1);
	            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'TRACE');
	            curl_setopt($ch, CURLOPT_TIMEOUT, 3); //timeout in seconds

                $result = curl_exec($ch);
                if (curl_errno($ch)) {
					error_log("Err");
                    return 'error';
                }
                curl_close($ch);
	            error_log(print_r($result, true));

            }
//        }
    }
}

if ( ! function_exists('rsssl_test_stack' ) ) {
    function rsssl_test_stack()
    {

       // if ( ! get_transient( 'rsssl_stack_allowed' ) ) {

            if (function_exists('curl_init')) {

	            $url = site_url();
	            $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, $url );
	            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'OPTIONS');

	            $result = curl_exec($ch);
				error_log(print_r($result, true));
	            if (curl_errno($ch)) {
		            echo 'Error:' . curl_error($ch);
	            }
	            curl_close($ch);
				exit;

            }

			set_transient('rsssl_stack_allowed', $response_code, DAY_IN_SECONDS);
        //}
    }
}

rsssl_test_stack();


if ( ! function_exists('rsssl_disable_http_methods' ) ) {
    function rsssl_disable_http_methods()
    {

	    if ( rsssl_get_server() == 'apache' ) {
			//RewriteCond %{REQUEST_METHOD} ^(TRACE|STACK)
			//	RewriteRule .* - [F]
		}

	    if ( rsssl_get_server() == 'nginx' ) {
			//	    add_header Allow "GET, POST, HEAD" always;
			//if ( $request_method !~ ^(GET|POST|HEAD)$ ) {
			//	    return 405;
	    }

    }
}