<?php

namespace RSSSL\Security\WordPress\Two_Fa\Controllers;

use Exception;
use RSSSL\Security\WordPress\Two_Fa\Models\Rsssl_Request_Parameters;
use RSSSL\Security\WordPress\Two_Fa\Providers\Rsssl_Provider_Loader;
use RSSSL\Security\WordPress\Two_Fa\Providers\Rsssl_Two_Factor_Provider;
use WP_REST_Request;
use WP_REST_Response;

final class Rsssl_Base_Controller extends Rsssl_Abstract_Controller
{
    protected const METHOD = 'POST';
    protected const FEATURE_ROUTE = '/two-fa';

    protected string $namespace;


    public function __construct($namespace, $version, $featureVersion)
    {
        parent::__construct($namespace, $version, $featureVersion);
        add_action('rest_api_init', array($this, 'register_api_routes'));
    }

	/**
	 * Registers the REST API routes for the base controller.
	 *
	 * @return void
	 * @throws Exception
	 */
    public function register_api_routes(): void
    {
        $this->route($this->namespace,
            self::METHOD,
            'do_not_ask_again',
            array($this, 'disable_two_fa_for_user'),
	        null,
            $this->build_args(array('user_id', 'login_nonce'), array('redirect_to'))
        );
        $this->route($this->namespace,
            self::METHOD,
            'skip_onboarding',
            array($this, 'skip_onboarding'),
	        null,
            $this->build_args(array('user_id', 'login_nonce'), array('redirect_to'))
        );
    }

    /**
     * Disables two-factor authentication for the user.
     *
     * @param WP_REST_Request $request The REST request object.
     *
     * @return WP_REST_Response The REST response object.
     */
    public function disable_two_fa_for_user( WP_REST_Request $request ): WP_REST_Response {
        $parameters = new Rsssl_Request_Parameters( $request );

        try {
           $user = $this->check_login_and_get_user($parameters->user_id, $parameters->login_nonce);
        } catch (Exception $e) {
            return new WP_REST_Response(['error' => $e->getMessage()], 403);
        }

        $loader = Rsssl_Provider_Loader::get_loader();
        // We get all the available providers for the user.
        foreach ($loader::get_providers() as $provider ) {
            /**
             * Set the user status to disable.
             *
             * @var Rsssl_Two_Factor_Provider $provider
             */
            $provider::set_user_status( $user->ID, 'disabled' );
        }

        // Finally we redirect the user to the redirect_to page.
        return $this->authenticate_and_redirect( $user->ID, $parameters->redirect_to );
    }

    /**
     * Skips the onboarding process for the user.
     *
     * @param WP_REST_Request $request The REST request object.
     *
     * @return WP_REST_Response The REST response object.
     */
    public function skip_onboarding( WP_REST_Request $request ): WP_REST_Response {
        $parameters = new Rsssl_Request_Parameters( $request );

        try {
            $user = $this->check_login_and_get_user($parameters->user_id, $parameters->login_nonce);
        } catch (Exception $e) {
            return new WP_REST_Response(['error' => $e->getMessage()], 403);
        }

        return $this->authenticate_and_redirect( $user->ID, $parameters->redirect_to );
    }
}