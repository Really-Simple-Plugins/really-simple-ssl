<?php

namespace LE_ACME2\Exception;

class OpenSSLException extends AbstractException {

	public function __construct(string $function) {

		$errors = [];
		while(($error = openssl_error_string()) !== false) {
			$errors[] = $error;
		}

		parent::__construct(
			$function . ' failed - error messages: ' . var_export($errors, true)
		);
	}
}