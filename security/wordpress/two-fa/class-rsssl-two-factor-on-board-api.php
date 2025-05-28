<?php
/**
 * Handles the API routes for the two-factor authentication onboarding process.
 * This class is responsible for handling the API routes for the two-factor authentication onboarding process.
 * It registers the routes and handles the requests.
 *
 * @package REALLY_SIMPLE_SSL
 * @subpackage Security\WordPress\Two_Fa
 */

namespace RSSSL\Security\WordPress\Two_Fa;
use RSSSL\Security\WordPress\Two_Fa\Controllers\Rsssl_Base_Controller;
use RSSSL\Security\WordPress\Two_Fa\Providers\Rsssl_Provider_Loader;
use RSSSL\Security\WordPress\Two_Fa\Providers\Rsssl_Two_Factor_Provider_Interface;

/**
 * Registers API routes for the application.
 * This class is responsible for registering the API routes for the two-factor authentication onboarding process.
 * It registers the routes and handles the requests.
 *
 * @package REALLY_SIMPLE_SSL
 * @subpackage Security\WordPress\Two_Fa
 */
class Rsssl_Two_Factor_On_Board_Api {

	/**
	 * The namespace for the API routes.
	 *
	 * @package reallysimplessl/v1/two_fa
	 */
	public const NAMESPACE = 'really-simple-security/v1/two-fa/v2';

	/**
	 * Initializes the object and registers API routes.
	 *
	 * @return void
	 */
	public function __construct() {
        // get the correct loader
        $loader = Rsssl_Provider_Loader::get_loader();

        new Rsssl_Base_Controller('really-simple-security', 'v1', 'v2');

        foreach ($loader::available_providers() as $provider ) {
            /** @var Rsssl_Two_Factor_Provider_Interface $provider */
            $provider::start_controller('really-simple-security', 'v1', 'v2');
        }
	}
}
