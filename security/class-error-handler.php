<?php
defined( 'ABSPATH' ) or die();

class rsssl_error_handler {
	private static $_this;
	public function __construct() {
		if ( isset( self::$_this ) ) {
			wp_die( 'you cannot create a second instance.' ) ;
		}
		self::$_this = $this;
	}

	public function get(){
		return get_option('rsssl_errors', []);
	}
	public function add($error){
		$errors = get_option('rsssl_errors', []);
		if ( ! in_array( $error, $errors, true ) ) {
			$errors[] = $error;
			update_option('rsssl_errors', $errors, false);
		}
	}

	public function delete($error){
		$errors = get_option('rsssl_errors', []);
		if ( in_array( $error, $errors, true ) ) {
			$index = array_search($error, $errors);
			if ( false !== $index ) {
				unset($errors[$index]);
				update_option('rsssl_errors', $errors, false);
			}
		}
	}

}