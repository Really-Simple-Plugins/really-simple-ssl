<?php
defined('ABSPATH') or die("you do not have access to this page!");

require_once rsssl_le_path . 'vendor/autoload.php';
use LE_ACME2\Account;
use LE_ACME2\Authorizer\HTTP;
use LE_ACME2\Connector\Connector;
use LE_ACME2\Order;
use LE_ACME2\Utilities\Logger;

class rsssl_letsencrypt_handler {

	private static $_this;
	public $account = false;
	public $challenge_directory = false;
	public $key_directory = false;
	public $certs_directory = false;
    public $subjects = array();
    public $installation_sequence;
	function __construct() {

		if ( isset( self::$_this ) ) {
			wp_die( sprintf( __( '%s is a singleton class and you cannot create a second instance.', 'really-simple-ssl' ), get_class( $this ) ) );
		}
		add_action( 'rsssl_before_save_lets-encrypt_option', array( $this, 'before_save_wizard_option' ), 10, 4 );
		add_action( 'rsssl_le_activation', array( $this, 'cleanup_on_ssl_activation'));
		add_action( 'rsssl_le_activation', array( $this, 'plugin_activation_actions'));
		add_filter( 'rsssl_notices', array( $this, 'get_notices_list'), 30, 1 );

		$this->installation_sequence = array_column( RSSSL_LE()->config->steps['lets-encrypt'], 'id');
		$this->key_directory = $this->key_directory();
		$this->challenge_directory = $this->challenge_directory();
		$this->certs_directory = $this->certs_directory();

		// Config the desired paths
        if ( $this->key_directory ) {
	        Account::setCommonKeyDirectoryPath( $this->key_directory );
        }

        if ( $this->challenge_directory ) {
	        HTTP::setDirectoryPath( $this->challenge_directory );
        }

		// General configs
		Connector::getInstance()->useStagingServer( false );
		Logger::getInstance()->setDesiredLevel( Logger::LEVEL_DISABLED );

//		// Optional configs
//		//\LE_ACME2\Utilities\Certificate::enableFeatureOCSPMustStaple();
//		\LE_ACME2\Order::setPreferredChain(\LE_ACME2\Order::IDENTRUST_ISSUER_CN);
        $this->subjects = $this->get_subjects();
		self::$_this = $this;
	}

	static function this() {
		return self::$_this;
	}

	/**
	 * Show notice if certificate needs to be renewed.
	 *
	 * @param array $notices
	 *
	 * @return array
	 */
	public function get_notices_list($notices) {
		if ( RSSSL_LE()->letsencrypt_handler->generated_by_rsssl() ) {
			$valid = RSSSL()->rsssl_certificate->is_valid();
			//we have now renewed the cert info transient
			$certinfo = get_transient('rsssl_certinfo');
			$end_date = isset($certinfo['validTo_time_t']) ? $certinfo['validTo_time_t'] : false;

			//if the certificate expires within the grace period, allow renewal
			//e.g. expiry date 30 may, now = 10 may => grace period 9 june.
			$expiry_date = date( get_option('date_format'), $end_date );
			$renew_link = rsssl_settings_page();
			$link_open = '<a href="'.$renew_link.'" target="_blank">';


			$notices['certificate_renewal'] = array(
				'condition' => array( 'rsssl_ssl_enabled' ),
				'callback'  => 'RSSSL_LE()->letsencrypt_handler->certificate_about_to_expire',
				'score'     => 10,
				'output'    => array(
					'false'     => array(
						'msg'  => sprintf( __( "Your certificate is valid to: %s", "really-simple-ssl-pro" ), $expiry_date ),
						'icon' => 'success'
					),
					'true' => array(
						'msg'  => sprintf( __( "Your certificate will expire on %s. You can renew it %shere%s.", "really-simple-ssl-pro" ), $expiry_date, $link_open, '</a>' ),
						'icon' => 'open',
						'plusone' => true,
					),
				),
			);

			$notices['certificate_installation'] = array(
				'condition' => array( 'rsssl_ssl_enabled' ),
				'callback'  => 'RSSSL_LE()->letsencrypt_handler->installation_failed',
				'score'     => 10,
				'output'    => array(
					'true' => array(
						'msg'  => sprintf( __( "The automatic installation of your certificate has failed. Please check your credentials, and retry the %sinstallation%s.", "really-simple-ssl-pro" ), '<a href="'.rsssl_settings_page().'">', '</a>' ),
						'icon' => 'open',
						'plusone' => true,
					),
				),
			);
		}

		return $notices;
	}

	/**
	 * Check if we have an installation failed state.
	 * @return bool
	 */
	public function installation_failed(){
		$installation_active = get_option("rsssl_le_start_installation");
		$installation_failed = get_option("rsssl_installation_error");

		return $installation_active && $installation_failed;
	}

	public function plugin_activation_actions(){
		if (get_option('rsssl_activated_plugin')) {
			//do some actions

			delete_option('rsssl_activated_plugin');
		}
	}

	/**
	 * Cleanup. If user did not consent to storage, all password fields should be removed on activation, unless they're needed for renewals
	 */
	public function cleanup_on_ssl_activation(){
		if (!current_user_can('manage_options')) return;
		$delete_credentials = !rsssl_get_value('store_credentials');
		if ( !$this->certificate_automatic_install_possible() || !$this->certificate_install_required() || $delete_credentials ) {
			$fields = RSSSL_LE()->config->fields;
			$fields = array_filter($fields, function($i){
				return isset( $i['type'] ) && $i['type'] === 'password';
			});
			$options = get_option( 'rsssl_options_lets-encrypt' );
			foreach ($fields as $fieldname => $field ) {
				unset($options[$fieldname]);
			}
			update_option( 'rsssl_options_lets-encrypt', $options );
		}
	}

	public function before_save_wizard_option(
		$fieldname, $fieldvalue, $prev_value, $type
	) {
		rsssl_progress_add('domain');
		//only run when changes have been made
		if ( $fieldvalue === $prev_value ) {
			return;
		}

		if ($fieldname==='accept_le_terms'){
		    if (!$fieldvalue) {
		        rsssl_progress_remove('domain');
            }
        }

		if ($fieldname==='other_host_type'){
			if ( !rsssl_do_local_lets_encrypt_generation() ) {
				rsssl_progress_add('directories');
				rsssl_progress_add('generation');
			}
		}

		if ( $fieldname==='email' ){
		    if ( !is_email($fieldvalue) ) {
		        rsssl_progress_remove('domain');
            }
        }
	}

	/**
     * Test for localhost usage
	 * @return RSSSL_RESPONSE
	 */
    public function localhost_used(){
	    if ( strpos(site_url(), 'localhost')!==false ) {
		    rsssl_progress_remove('system-status');
		    $action = 'stop';
		    $status = 'error';
		    $message = __("It is not possible to install Let's Encrypt on a localhost environment.", "really-simple-ssl" );
	    } else {
		    $action = 'continue';
		    $status = 'success';
		    $message = __("Your domain meets the requirements for Let's Encrypt.", "really-simple-ssl" );
	    }
	    return new RSSSL_RESPONSE($status, $action, $message);
    }

	/**
	 * Get certificate installation URL
	 * @return RSSSL_RESPONSE
	 */

    public function search_ssl_installation_url(){
    	//start with most generice, then more specific if possible.
	    $url = 'https://really-simple-ssl.com/install-ssl-certificate';
	    $host = 'enter-your-dashboard-url-here';

	    if (function_exists('wp_get_direct_update_https_url') && !empty(wp_get_direct_update_https_url())) {
		    $url = wp_get_direct_update_https_url();
	    }

	    if ( rsssl_is_cpanel() ) {
		    $cpanel = new rsssl_cPanel();
		    $host = $cpanel->cpanel_host;
		    $url = $cpanel->ssl_installation_url;
	    }

	    $hosting_company = rsssl_get_other_host();
	    if ( $hosting_company && $hosting_company !== 'none' ) {
		    $hosting_specific_link = RSSSL_LE()->config->hosts[$hosting_company]['ssl_installation_link'];
		    if ($hosting_specific_link) {
			    $url = str_replace('{host}', $host, $hosting_specific_link);
		    }
	    }

	    $action = 'continue';
	    $status = 'warning';
	    $message = __("Your server requires some manual actions to install the certificate.", "really-simple-ssl").' '.
	               sprintf(__("Please follow this %slink%s to proceed.", "really-simple-ssl"), '<a target="_blank" href="'.$url.'">', '</a>');

	    return new RSSSL_RESPONSE($status, $action, $message);
    }

    /**
     * Test for localhost usage
	 * @return RSSSL_RESPONSE
	 */
    public function certificate_status(){
	    delete_transient('rsssl_certinfo');
	    if ( RSSSL()->rsssl_certificate->is_valid() ) {
	    	//we have now renewed the cert info transient
		    $certinfo = get_transient('rsssl_certinfo');
		    $end_date = isset($certinfo['validTo_time_t']) ? $certinfo['validTo_time_t'] : false;
		    $grace_period = strtotime('+30 days');

		    //if the certificate expires within the grace period, allow renewal
		    //e.g. expiry date 30 may, now = 10 may => grace period 9 june.
		    if ( $grace_period > $end_date ) {
			    $action = 'continue';
			    $status = 'error';
			    $message = __("Your certificate will expire within 30 days.", "really-simple-ssl" ).' '.__("Continue to renew.", "really-simple-ssl" );   ;
		    } else {
			    $action = 'stop';
			    $status = 'error';
			    $message = __("You already have a valid SSL certificate.", "really-simple-ssl" );
		    }

	    } else {
		    $action = 'continue';
		    $status = 'error';
		    $message = __("SSL certificate not valid. Please continue to generate your own certificate.", "really-simple-ssl" );
	    }
	    return new RSSSL_RESPONSE($status, $action, $message);
    }

	public function certificate_about_to_expire(){
		$valid = RSSSL()->rsssl_certificate->is_valid();
		//we have now renewed the cert info transient
		$certinfo = get_transient('rsssl_certinfo');
		$end_date = isset($certinfo['validTo_time_t']) ? $certinfo['validTo_time_t'] : false;
		$thirty_days_time = strtotime('+30 days');
		if ( $thirty_days_time < $end_date ) {
			return false;
		} else {
			return true;
		}
	}

    /**
     * Test for server software
	 * @return RSSSL_RESPONSE
	 */

	public function server_software(){
	    $action = 'continue';
	    $status = 'warning';
	    $message = __("The server software was not recognized. The generated certificate will need to be installed manually.", "really-simple-ssl" );

        if (rsssl_is_cpanel()) {
	        $status = 'success';
	        $message = __("CPanel recognized. Possibly the certificate can be installed automatically.", "really-simple-ssl" );
        }

        if (rsssl_is_plesk()) {
	        $status = 'success';
	        $message = __("Plesk recognized. Possibly the certificate can be installed automatically.", "really-simple-ssl" );
        }

		return new RSSSL_RESPONSE($status, $action, $message);
    }

	/**
	 * Check if CURL is available
	 *
	 * @return RSSSL_RESPONSE
	 */

    public function curl_exists(){
	    if(function_exists('curl_init') === false){
		    $action = 'stop';
		    $status = 'error';
		    $message = __("The PHP function CURL is not available on your server, which is required. Please contact your hosting company.", "really-simple-ssl" );
	    } else {
		    $action = 'continue';
		    $status = 'success';
		    $message = __("The PHP function CURL has successfully been detected.", "really-simple-ssl" );
	    }

	    return new RSSSL_RESPONSE($status, $action, $message);
    }

    /**
     * Test for server software
	 * @return RSSSL_RESPONSE
	 */
	public function system_check(){
	    $action = 'continue';
	    $status = 'error';
	    $message = __("Your system does not meet the minimum requirements.", "really-simple-ssl" );

        if (rsssl_is_cpanel()) {
	        $action = 'continue';
	        $status = 'success';
	        $message = __("CPanel recognized. Possibly the certificate can be installed automatically.", "really-simple-ssl" );
        }

		return new RSSSL_RESPONSE($status, $action, $message);
    }


	/**
	 * Get or create an account
	 * @return RSSSL_RESPONSE
	 */
    public function get_account(){
	    $account_email = $this->account_email();
        if ( is_email($account_email) ) {
	        try {
		        $this->account
			        = ! Account::exists( $account_email ) ?
			        Account::create( $account_email ) :
			        Account::get( $account_email );
		        $status = 'success';
		        $action = 'continue';
		        $message = __("Successfully retrieved account", "really-simple-ssl");
	        } catch(Exception $e) {
		        error_log(print_r($e, true));
		        $response = $this->get_error($e);
		        $status = 'error';
		        $action = 'retry';
		        $message = $response;
	        }
        } else {
            error_log("no email set");
	        $status = 'error';
	        $action = 'stop';
	        $message = __("The email address was not set. Please set the email address",'really-simple-ssl');
        }
	    return new RSSSL_RESPONSE($status, $action, $message);
    }


	/**
     * Authorize the order
	 * @return string|void
	 */

    public function create_bundle_or_renew(){
	    $attempt_count = intval(get_transient('rsssl_le_generate_attempt_count'));
	    $attempt_count++;
	    set_transient('rsssl_le_generate_attempt_count', $attempt_count, 2 * HOUR_IN_SECONDS);
	    if ($attempt_count>20){
		    delete_option("rsssl_le_start_renewal");
		    $status = 'error';
		    $action = 'stop';
		    $message = __("The certificate generation was rate limited. Please try again later.",'really-simple-ssl');
	        return new RSSSL_RESPONSE($status, $action, $message);
	    }

	    //check if the required order was created
	    $order = $bundle_completed = false;

	    if ($this->is_ready_for('generation')) {
		    $this->get_account();

		    if ( ! Order::exists( $this->account, $this->subjects ) ) {
			    try {
				    $order = Order::create( $this->account, $this->subjects );
				    $status = 'success';
				    $action = 'continue';
				    $message = __("Order successfully created.",'really-simple-ssl');
			    } catch(Exception $e) {
				    $response = $this->get_error($e);
				    error_log(print_r($e, true));
				    $status = 'error';
				    $action = 'retry';
				    $message = $response;
			    }
		    } else {
			    //order exists already
			    $status = 'success';
			    $action = 'continue';
			    $message = __("Order exists.",'really-simple-ssl');
			    $order = Order::get( $this->account, $this->subjects );
		    }

		    if ( $order ) {
			    if ( $order->isCertificateBundleAvailable() ) {
				    try {
					    $order->enableAutoRenewal();
					    $status = 'success';
					    $action = 'continue';
					    $message = __("Successfully renewed certificate.",'really-simple-ssl');
					    $bundle_completed = true;
				    } catch ( Exception $e ) {
					    error_log( print_r( $e, true ) );
					    $status = 'error';
					    $action = 'retry';
					    $message = $this->get_error( $e );
					    $bundle_completed = false;
				    }
			    } else {
				    try {
					    if ( $order->authorize( Order::CHALLENGE_TYPE_HTTP ) ) {
						    $order->finalize();
					    }
				    } catch ( Exception $e ) {
					    error_log( print_r( $e, true ) );
				    }

				    try {
					    if ( $order->isCertificateBundleAvailable() ) {
						    error_log( "cert bundle available" );
						    $bundle_completed   = true;
						    $success_cert       = $success_intermediate = $success_private = false;
						    $bundle             = $order->getCertificateBundle();
						    $pathToPrivateKey   = $bundle->path . $bundle->private;
						    $pathToCertificate  = $bundle->path . $bundle->certificate;
						    $pathToIntermediate = $bundle->path . $bundle->intermediate;

						    if ( file_exists( $pathToPrivateKey ) ) {
							    $success_private = true;
							    update_option( 'rsssl_private_key_path', $pathToPrivateKey );
						    }
						    if ( file_exists( $pathToCertificate ) ) {
							    $success_cert = true;
							    update_option( 'rsssl_certificate_path', $pathToCertificate );
						    }

						    if ( file_exists( $pathToIntermediate ) ) {
							    $success_intermediate = true;
							    update_option( 'rsssl_intermediate_path', $pathToIntermediate );
						    }

						    if ( ! $success_cert || ! $success_private || ! $success_intermediate ) {
							    $bundle_completed = false;
						    }

						    if ( $bundle_completed ) {
							    $status = 'success';
							    $action = 'continue';
							    $message = __("Successfully generated certificate.",'really-simple-ssl');
						    } else {
							    $status = 'error';
							    $action = 'retry';
							    $message = __("Bundle not available yet...",'really-simple-ssl');
						    }

					    } else {
						    $status = 'error';
						    $action = 'retry';
						    $message = __("Bundle not available yet...",'really-simple-ssl');
					    }


				    } catch ( Exception $e ) {
					    error_log( print_r( $e, true ) );
					    $status = 'success';
					    $action = 'continue';
					    $message = $this->get_error( $e );
				    }
			    }
		    }
	    } else {
		    $status = 'error';
		    $action = 'stop';
		    $message = sprintf(__('Steps not completed: %s', "really-simple-ssl"), implode(", ",$this->get_not_completed_steps('generation')) );
	    }

	    if ( $bundle_completed ){
		    rsssl_progress_add('generation');
		    update_option('rsssl_le_certificate_generated_by_rsssl', true);
		    delete_option("rsssl_le_start_renewal");
	    } else {
		    rsssl_progress_remove('generation');
	    }

	    return new RSSSL_RESPONSE($status, $action, $message);
    }

	/**
     * If a bundle generation is completed, this value is set to true.
	 * @return bool
	 */
    public function generated_by_rsssl(){
	    return get_option('rsssl_le_certificate_generated_by_rsssl')!==false;
    }


	public function certificate_automatic_install_possible(){

		$install_method = get_option('rsssl_le_certificate_installed_by_rsssl');

		//if it was never auto installed, we probably can't autorenew.
		if ($install_method === false ) {
			return false;
		} else {
			return false;
		}
    }

	/**
	 * Check if the certificate can be installed automatically.
	 *
	 *     	// we can only instal if the certificate is up to date
	if ($this->certificate_needs_renewal()) {
	return false;
	}
	 */

    public function certificate_install_required(){

    	$install_method = get_option('rsssl_le_certificate_installed_by_rsssl');
    	$hosting_company = rsssl_get_other_host();
    	if ( in_array($install_method, RSSSL_LE()->config->no_installation_renewal_needed) || in_array($hosting_company, RSSSL_LE()->config->no_installation_renewal_needed)) {
    		return false;
	    }

        return false;
    }

	/**
     * Check if the certificate needs renewal.
     *
	 * @return bool
	 */
    public function certificate_needs_renewal(){

	    $cert_file = get_option('rsssl_certificate_path');
	    $certificate = file_get_contents($cert_file);
	    $certificateInfo = openssl_x509_parse($certificate);
	    $valid_to = $certificateInfo['validTo_time_t'];
	    $in_30_days = strtotime( "+30 days" );
	    if ( $in_30_days > $valid_to ) {
	        return true;
        } else {
	        return false;
	    }
    }


	/**
     * Get account email
	 * @return string
	 */
	public function account_email(){
	    //don't use the default value: we want users to explicitly enter a value
	    return rsssl_get_value('email_address', false);
    }
	/**
     * Get terms accepted
	 * @return bool
	 */
	public function terms_accepted(){
	    //don't use the default value: we want users to explicitly enter a value
	    return rsssl_get_value('accept_le_terms', false);
    }



	/**
     * Change the email address in an account
	 * @param $new_email
	 */

    public function update_account( $new_email ){
	    if (!$this->account) return;

	    try {
	        $this->account->update($new_email);
        } catch (Exception $e) {
            error_log("Lets encrypt email update failed");
            error_log(print_r($e, true));
        }
    }

	/**
     * Get list of common names on the certificate
	 * @return array
	 */
	public function get_subjects(){
		$subjects = array();
		$domain = rsssl_get_domain();
		$subjects[] = $domain;

		//don't offer aliasses for subdomains
		if ( !rsssl_is_subdomain() ) {
			//main is www.
			if ( strpos( $domain, 'www.' ) !== false ) {
				$alias_domain = str_replace( 'www.', '', $domain );
			} else {
				$alias_domain = 'www.'.$domain;
			}
			if (rsssl_get_value( 'include_alias' )) {
				$subjects[] = $alias_domain;
			}
		}
		error_log(print_r($subjects, true));
	    return $subjects;
	}

	/**
	 * @param LE_ACME2\Exception\InvalidResponse Object $e
	 *
	 * @return string
	 */
	public function get_error_from_le($e){
	    $message = $e->getMessage();
	    $message1 = json_decode($message);
		error_log(print_r($message1,true));

		return $message;
	}

	/**
     * Check if we're ready for the next step.
	 * @param string $item
	 *
	 * @return array | bool
	 */
	public function is_ready_for($item) {
        if (empty($this->get_not_completed_steps($item))){
            return true;
        } else{
            return false;
        }
	}



	/**
	 * Catch errors
	 *
	 * @since 3.0
	 *
	 * @access public
	 * @param       $errno
	 * @param       $errstr
	 * @param       $errfile
	 * @param       $errline
	 * @param array $errcontext
	 *
	 * @return bool
	 */

	public function custom_error_handling( $errno, $errstr, $errfile, $errline, $errcontext = array() ) {
		return true;
	}


	private function get_not_completed_steps($item){
		$sequence = $this->installation_sequence;
		//drop all statuses after $item. We only need to know if all previous ones have been completed
		$index = array_search($item, $sequence);
		$sequence = array_slice($sequence, 0, $index, true);
		$not_completed = array();
		$finished = get_option("rsssl_le_installation_progress", array());
		foreach ($sequence as $status ) {
			if (!in_array($status, $finished)) {
				$not_completed[] = $status;
			}
		}

        return $not_completed;
	}

	/**
	 * Test for writing permissions
	 * @return RSSSL_RESPONSE
	 */

	public function check_writing_permissions(){
		$directories_without_permissions = $this->directories_without_writing_permissions();
		$has_missing_permissions = count($directories_without_permissions)>0;

		if ( $has_missing_permissions ) {
			rsssl_progress_remove('directories');
			$action = 'stop';
			$status = 'error';
			$message = __("The following directories do not have the necessary writing permissions.", "really-simple-ssl" )."&nbsp;".__("Set permissions to 644 to enable SSL generation.", "really-simple-ssl" );
			foreach ($directories_without_permissions as $directories_without_permission) {
				$message .= "<br> - ".$directories_without_permission;
			}
		} else {
			$action = 'continue';
			$status = 'success';
			$message = __("The required directories have the necessary writing permissions.", "really-simple-ssl" );
		}
		return new RSSSL_RESPONSE($status, $action, $message);
	}

	/**
	 * Test for directory
	 * @return RSSSL_RESPONSE
	 */

	public function check_challenge_directory(){
		if ( !$this->challenge_directory() ) {
			rsssl_progress_remove('directories');
			$action = 'stop';
			$status = 'error';
			$message = __("The challenge directory is not created yet.", "really-simple-ssl" );
		} else {
			$action = 'continue';
			$status = 'success';
			$message = __("The challenge directory was successfully created.", "really-simple-ssl" );
		}
		return new RSSSL_RESPONSE($status, $action, $message);
	}
	/**
	 * Test for directory
	 * @return RSSSL_RESPONSE
	 */

	public function check_key_directory(){
		if ( !$this->key_directory() ) {
			rsssl_progress_remove('directories');
			$action = 'stop';
			$status = 'error';
			$message = __("The key directory is not created yet.", "really-simple-ssl" );
		} else {
			$action = 'continue';
			$status = 'success';
			$message = __("The key directory was successfully created.", "really-simple-ssl" );
		}
		return new RSSSL_RESPONSE($status, $action, $message);
	}

	/**
	 * Test for directory
	 * @return RSSSL_RESPONSE
	 */

	public function check_certs_directory(){
		if ( !$this->certs_directory() ) {
			rsssl_progress_remove('directories');
			$action = 'stop';
			$status = 'error';
			$message = __("The certs directory is not created yet.", "really-simple-ssl" );
		} else {
			$action = 'continue';
			$status = 'success';
			$message = __("The certs directory was successfully created.", "really-simple-ssl" );
		}
		return new RSSSL_RESPONSE($status, $action, $message);
	}

	/**
	 * Check if our created directories have the necessary writing permissions
	 */

	public function directories_without_writing_permissions(){
		$required_folders = array(
			$this->challenge_directory,
			$this->key_directory,
			$this->certs_directory,
		);

		$no_writing_permissions = array();
		foreach ($required_folders as $required_folder){
			set_error_handler(array($this, 'custom_error_handling'));
			$test_file = fopen( $required_folder . "/really-simple-ssl-permissions-check.txt", "w" );
			fclose( $test_file );
			restore_error_handler();
			if (!file_exists($required_folder . "/really-simple-ssl-permissions-check.txt")) {
				$no_writing_permissions[] = $required_folder;
			}
		}

		return $no_writing_permissions;
	}

	/**
	 * Check if exists, create .well-known/acme-challenge directory if not existing
	 * @return bool|string
	 */
	public function challenge_directory() {
		$root_directory = trailingslashit(ABSPATH);
		if ( ! file_exists( $root_directory . '.well-known' ) ) {
			mkdir( $root_directory . '.well-known' );
		}

		if ( ! file_exists( $root_directory . '.well-known/acme-challenge' ) ) {
			mkdir( $root_directory . '.well-known/acme-challenge' );
		}

		if ( file_exists( $root_directory . '.well-known/acme-challenge' ) ){
			return $root_directory . '.well-known/acme-challenge';
		} else {
			return false;
		}
	}

	/**
     * Check if exists, create ssl/keys directory above the wp root if not existing
	 * @return bool|string
	 */
	public function key_directory(){
		$root_directory = trailingslashit(ABSPATH);
		$parent_directory = trailingslashit(dirname($root_directory));
		if ( ! file_exists( $parent_directory . 'ssl' ) ) {
			mkdir( $parent_directory . 'ssl' );
		}

		if ( ! file_exists( $parent_directory . 'ssl/keys' ) ) {
			mkdir( $parent_directory . 'ssl/keys' );
		}

		if ( file_exists( $parent_directory . 'ssl/keys' ) ){
			return $parent_directory . 'ssl/keys';
		} else {
			return false;
		}
	}

	/**
	 * Check if the alias domain is available
	 *
	 * @return RSSSL_RESPONSE
	 */
	public function alias_domain_available(){
		//write a test file to the uploads directory
		$uploads    = wp_upload_dir();
		$upload_dir = trailingslashit($uploads['basedir']);
		$upload_url = trailingslashit($uploads['baseurl']);
		$file_content = false;
		$status = 404;

		$domain = rsssl_get_domain();
		//main is www.
		if ( strpos( $domain, 'www.' ) !== false ) {
			$is_www = true;
			$alias_domain = str_replace( 'www.', '', $domain );
		} else {
			$is_www = false;
			$alias_domain = str_replace( array( 'http://', 'https://' ), array( 'http://www.', 'https://www.' ), $domain );
		}
		if ( $is_www ) {
			$message =  __("Please check if the non www version of your site also points to this website.", "really-simple-ssl" );
		} else {
			$message = __("Please check if the www version of your site also points to this website.", "really-simple-ssl" );
		}
		$error_message = __( "Could not verify alias domain.", "really-simple-ssl") .' '. $message.' '. __( "If this is not the case, dont' add this variant to your certificate.", "really-simple-ssl");

		//get cached status first.
		$cached_status = get_transient('rsssl_alias_domain_available');
		if ( $cached_status ) {
			if ( $cached_status === 'available' ) {
				$status  = 'success';
				$action  = 'continue';
				$message = __( "Successfully verified alias domain.", "really-simple-ssl" );

				//make sure we only set this value once, during first setup.
				if ( !get_option('rsssl_initial_alias_domain_value_set')) {
					RSSSL_LE()->field->save_field  ('rsssl_include_alias', true);
					update_option('rsssl_initial_alias_domain_value_set', true);
				}

			} else {
				$status  = 'warning';
				$action  = 'continue';
				$message = $error_message;
			}
			return new RSSSL_RESPONSE($status, $action, $message);
		}

		if ( ! file_exists( $upload_dir . 'rsssl' ) ) {
			mkdir( $upload_dir . 'rsssl' );
		}

		$test_string = 'file to test alias domain existence';
		$test_file = $upload_dir . 'rsssl/test.txt';
		file_put_contents($test_file, $test_string );
		$test_url = $upload_url . 'rsssl/test.txt';

		if ( ! file_exists( $test_file ) ) {
			$status = 'error';
			$action = 'stop';
			$message = __("Could not create test folder and file.", "really-simple-ssl").' '.
			           __("Please create a folder 'rsssl' in the uploads directory, with 644 permissions.", "really-simple-ssl");
		} else {
			set_transient('rsssl_alias_domain_available', 'not-available', 30 * 'MINUTE_IN_SECONDS' );
			$alias_test_url = str_replace( $domain, $alias_domain, $test_url );
			$response       = wp_remote_get( $alias_test_url );
			if ( is_array( $response ) ) {
				$status       = wp_remote_retrieve_response_code( $response );
				$file_content = wp_remote_retrieve_body( $response );
			}

			if ( $status !== 200 ) {
				$status  = 'warning';
				$action  = 'retry';
				$message = $error_message.' '.sprintf( __( "Error code %s", "really-simple-ssl" ), $status );
			} else {
				if ( ! is_wp_error( $response ) && ( strpos( $file_content, $test_string ) !== false ) ) {
					$status  = 'success';
					$action  = 'continue';
					$message = __( "Successfully verified alias domain.", "really-simple-ssl" );
					set_transient('rsssl_alias_domain_available', 'available', 30 * 'MINUTE_IN_SECONDS' );
				} else {
					$status  = 'warning';
					$action  = 'retry';
					$message = $error_message;
				}
			}
		}

		return new RSSSL_RESPONSE($status, $action, $message);
	}

	/**
	 * Check if exists, create ssl/certs directory above the wp root if not existing
	 * @return bool|string
	 */
	public function certs_directory(){
		$root_directory = trailingslashit(ABSPATH);
		$parent_directory = trailingslashit(dirname($root_directory));
		if ( ! file_exists( $parent_directory . 'ssl' ) ) {
			mkdir( $parent_directory . 'ssl' );
		}

		if ( ! file_exists( $parent_directory . 'ssl/certs' ) ) {
			mkdir( $parent_directory . 'ssl/certs' );
		}

		if ( file_exists( $parent_directory . 'ssl/certs' ) ){
			return $parent_directory . 'ssl/certs';
		} else {
			return false;
		}
	}

	/**
     * Get string error from error message.
	 * @param $e
	 *
	 * @return string
	 */
	private function get_error($e){
	    if (isset($e->getTrace()[0]['args'][0]->body['detail'])) {
		    $error = $e->getTrace()[0]['args'][0]->body['detail'];

		    //check for subproblems
		    if (isset($e->getTrace()[0]['args'][0]->body['subproblems'])){
			    $error .= '<ul>';
		        foreach($e->getTrace()[0]['args'][0]->body['subproblems'] as $index => $problem) {
			        $error .= '<li>'. $this->cleanup_error_message($e->getTrace()[0]['args'][0]->body['subproblems'][$index]['detail']).'</li>';
		        }
			    $error .= '</ul>';

		    }

	    } else {
	        $error = $e;
	    }
	    return $error;

	}

	/**
	 * Generic SSL cert installation function
	 * @param $server
	 * @param $type
	 *
	 * @return RSSSL_RESPONSE
	 */
	public function cron_renew_installation($server, $type) {
		//autodetect if empty
		if (!$server) {
			$install_method = get_option('rsssl_le_certificate_installed_by_rsssl');
			$data = explode($install_method, ':');
			$server = isset($data[0]) ? $data[0] : false;
			$type = isset($data[1]) ? $data[1] : false;
		}

		$attempt_count = intval(get_transient('rsssl_le_install_attempt_count'));
		$attempt_count++;
		set_transient('rsssl_le_install_attempt_count', $attempt_count, DAY_IN_SECONDS);
		if ( $attempt_count>10 ){
			delete_option("rsssl_le_start_installation");
			$status = 'error';
			$action = 'stop';
			$message = __("The certificate installation was rate limited. Please try again later.",'really-simple-ssl');
			return new RSSSL_RESPONSE($status, $action, $message);
		}

		if (RSSSL_LE()->letsencrypt_handler->is_ready_for('installation')) {
			try {
				if ( $server === 'cpanel' ) {
					$response = rsssl_install_cpanel_default();

					if ( $response->status === 'success' ) {
						delete_option( "rsssl_le_start_installation" );
					}
				} else if ( $server === 'plesk') {
					$response = rsssl_plesk_install();
					if ( $response->status === 'success' ) {
						delete_option( "rsssl_le_start_installation" );
					}
				} else {
					$status = 'error';
					$action = 'stop';
					$message = __("Not recognized server.", "really-simple-ssl");
				}
			} catch (Exception $e) {
				error_log(print_r($e, true));
				$status = 'error';
				$action = 'stop';
				$message = __("Installation failed.", "really-simple-ssl");
			}
		} else {
			$status = 'error';
			$action = 'stop';
			$message = __("The system is not ready for the installation yet. Please run the wizard again.", "really-simple-ssl");
		}

		return new RSSSL_RESPONSE($status, $action, $message);
	}

	/**
	 * Cleanup the default message a bit
	 *
	 * @param $msg
	 *
	 * @return string|string[]
	 */
	private function cleanup_error_message($msg){
		return str_replace(array(
			'Refer to sub-problems for more information.',
			'Error creating new order ::',
		), '', $msg);
    }

	/**
	 * Encode a string
	 * @param string $string
	 * @return string
	 */

	public function encode( $string ) {
		if ( strlen(trim($string)) === 0 ) return $string;

		if (strpos( $string , 'rsssl_') !== FALSE ) {
			return $string;
		}

		$key = $this->get_key();
		if ( !$key ) {
			$key = $this->set_key();
		}

		$ivlength = openssl_cipher_iv_length('aes-256-cbc');
		$iv = openssl_random_pseudo_bytes($ivlength);
		$ciphertext_raw = openssl_encrypt($string, 'aes-256-cbc', $key, 0, $iv);
		$key = base64_encode( $iv.$ciphertext_raw );

		return 'rsssl_'.$key;
	}

	/**
	 * Decode a string
	 * @param $string
	 *
	 * @return string
	 */
    public function decode($string){
		if ( !wp_doing_cron() && !current_user_can('manage_options') ) {
			return '';
		}

		if (strpos( $string , 'rsssl_') !== FALSE ) {
			$key = $this->get_key();
			$string = str_replace('rsssl_', '', $string);

			// To decrypt, split the encrypted data from our IV
			$ivlength = openssl_cipher_iv_length('aes-256-cbc');
			$iv = substr(base64_decode($string), 0, $ivlength);
			$encrypted_data = substr(base64_decode($string), $ivlength);

			$decrypted =  openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);
			return $decrypted;
		}

		//not encoded, return
		return $string;
	}

	/**
	 * Set a new key
	 * @return string
	 */

	private function set_key(){
		update_site_option( 'rsssl_key' , time() );
		return get_site_option('rsssl_key');
	}

	/**
	 * Get a decode/encode key
	 * @return false|string
	 */

	private function get_key() {
		return get_site_option( 'rsssl_key' );
	}


}

class RSSSL_RESPONSE
{
	public $message;
	public $action;
	public $status;
	public $output;

	public function __construct($status, $action, $message, $output = false )
	{
	    $this->status = $status;
	    $this->action = $action;
	    $this->message = $message;
	    $this->output = $output;
	}

}
