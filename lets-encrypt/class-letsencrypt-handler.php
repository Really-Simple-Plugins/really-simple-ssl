<?php
defined('ABSPATH') or die("you do not have access to this page!");

require_once rsssl_le_path . 'vendor/autoload.php';
use LE_ACME2\Account;
use LE_ACME2\Authorizer\AbstractDNSWriter;
use LE_ACME2\Authorizer\DNS;
use LE_ACME2\Authorizer\HTTP;
use LE_ACME2\Connector\Connector;
use LE_ACME2\Order;
use LE_ACME2\Utilities\Certificate;
use LE_ACME2\Utilities\Logger;

class rsssl_letsencrypt_handler {

	private static $_this;
	/**
	 * Account object
	 * @var bool|LE_ACME2\Account
	 */
	public $account = false;
	public $challenge_directory = false;
	public $key_directory = false;
	public $certs_directory = false;
    public $subjects = array();

	function __construct() {

		if ( isset( self::$_this ) ) {
			wp_die( sprintf( __( '%s is a singleton class and you cannot create a second instance.', 'really-simple-ssl' ), get_class( $this ) ) );
		}

		//loading of these hooks is stricter. The class can be used in the notices, which are needed on the generic dashboard
		//These functionality is not needed on the dashboard, so should only be loaded in strict circumstances
		if ( rsssl_letsencrypt_generation_allowed( true ) ) {
			add_action( 'rsssl_after_save_field', array( $this, 'after_save_field' ), 10, 4 );
			add_action( 'admin_init', array( $this, 'maybe_add_htaccess_exclude'));
			add_action( 'admin_init', array( $this, 'maybe_create_htaccess_directories'));

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

			if ( !rsssl_get_option( 'disable_ocsp' ) ) {
				Certificate::enableFeatureOCSPMustStaple();
			}

			Order::setPreferredChain('ISRG Root X1');
			$this->subjects = $this->get_subjects();
			$this->verify_dns();
		}

		self::$_this = $this;
	}

	static function this() {
		return self::$_this;
	}

	/**
	 * If we're on apache, add a line to the .htaccess so the acme challenge directory won't get blocked.
	 */
	public function maybe_add_htaccess_exclude(){

		if (!rsssl_user_can_manage()) {
			return;
		}

		if ( !RSSSL()->server->uses_htaccess() ) {
			return;
		}

		$htaccess_file = RSSSL()->admin->htaccess_file();
		if ( !file_exists($htaccess_file) ) {
			return;
		}

		if ( !is_writable($htaccess_file) ) {
			return;
		}

		$htaccess = file_get_contents( $htaccess_file );

		//if it's already inserted, skip.
		if ( strpos($htaccess, 'Really Simple SSL LETS ENCRYPT') !== FALSE ) {
			return;
		}

		$htaccess = preg_replace("/#\s?BEGIN\s?Really Simple SSL LETS ENCRYPT.*?#\s?END\s?Really Simple SSL LETS ENCRYPT/s", "", $htaccess);
		$htaccess = preg_replace("/\n+/", "\n", $htaccess);

		$rules = '#BEGIN Really Simple SSL LETS ENCRYPT'."\n";
		$rules .= 'RewriteRule ^.well-known/(.*)$ - [L]'."\n";
		$rules .= '#END Really Simple SSL LETS ENCRYPT'."\n";
		$htaccess = $rules . $htaccess;
		file_put_contents($htaccess_file, $htaccess);

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

	/**
	 * Cleanup. If user did not consent to storage, all password fields should be removed on activation, unless they're needed for renewals
	 *
	 * @return bool
	 */
	public function cleanup_on_ssl_activation(){
		if ( !current_user_can('manage_security') ) {
			return false;
		}
		$delete_credentials = !rsssl_get_option('store_credentials');
		$le_fields = rsssl_le_add_fields([]);
		if ( !$this->certificate_automatic_install_possible() || !$this->certificate_install_required() || $delete_credentials ) {
			$le_fields = array_filter($le_fields, function($i){
				return isset( $i['type'] ) && $i['type'] === 'password';
			});
			$options = get_option( 'rsssl_options' );
			foreach ($le_fields as $index => $field ) {
				unset($options[$field['id']]);
			}
			update_option( 'rsssl_options', $options, false );
		}
		return true;
	}

	/**
	 * some custom actions after a field has been saved
	 * @param string $fieldname
	 * @param mixed $fieldvalue
	 * @param mixed $prev_value
	 * @param string $type
	 *
	 * @return void
	 */
	public function after_save_field(
		$fieldname, $fieldvalue, $prev_value, $type
	) {
		rsssl_progress_add('domain');
		//only run when changes have been made
		if ( $fieldvalue === $prev_value ) {
			return;
		}

		if ( $fieldname==='other_host_type' ){
			if ( !rsssl_do_local_lets_encrypt_generation() ) {
				rsssl_progress_add('directories');
				rsssl_progress_add('generation');
				rsssl_progress_add('dns-verification');
			}
		}

		if ( $fieldname==='email' ){
		    if ( !is_email($fieldvalue) ) {
		        rsssl_progress_remove('domain');
            }
        }
	}

	/**
     * Test for localhost or subfolder usage
	 * @return RSSSL_RESPONSE
	 */
    public function check_domain(){
	    $details = parse_url(site_url());
	    $path = isset($details['path']) ? $details['path'] : '';
        if ( strpos(site_url(), 'localhost')!==false ) {
	        rsssl_progress_remove( 'system-status' );
	        $action  = 'stop';
	        $status  = 'error';
	        $message = __( "It is not possible to install Let's Encrypt on a localhost environment.", "really-simple-ssl" );
        } else if (is_multisite() && get_current_blog_id() !== get_main_site_id() ) {
		    rsssl_progress_remove('system-status');
		    $action = 'stop';
		    $status = 'error';
		    $message = __("It is not possible to install Let's Encrypt on a subsite. Please go to the main site of your website.", "really-simple-ssl" );
	    } else if ( strlen($path)>0 ) {
		    rsssl_progress_remove('system-status');
		    $action = 'stop';
		    $status = 'error';
		    $message = __("It is not possible to install Let's Encrypt on a subfolder configuration.", "really-simple-ssl" ).rsssl_le_read_more('https://really-simple-ssl.com/install-ssl-on-subfolders');
	    } elseif ( rsssl_caa_record_prevents_le() ) {
		    $action = 'stop';
		    $status = 'error';
		    $message = __("Please adjust the CAA records via your DNS provider to allow Let’s Encrypt SSL certificates", "really-simple-ssl" ).rsssl_le_read_more('https://really-simple-ssl.com/instructions/edit-dns-caa-records-to-allow-lets-encrypt-ssl-certificates/');
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
    	//start with most generic, then more specific if possible.
	    $url = 'https://really-simple-ssl.com/install-ssl-certificate';
	    $host = 'enter-your-dashboard-url-here';

	    if (function_exists('wp_get_direct_update_https_url') && !empty(wp_get_direct_update_https_url())) {
		    $url = wp_get_direct_update_https_url();
	    }

	    if ( rsssl_is_cpanel() ) {
		    $cpanel = new rsssl_cPanel();
		    $host = $cpanel->host;
		    $url = $cpanel->ssl_installation_url;
	    } else if ( rsssl_is_plesk() ) {
		    $plesk = new rsssl_plesk();
		    $host = $plesk->host;
		    $url = $plesk->ssl_installation_url;
	    } else if ( rsssl_is_directadmin() ) {
		    $directadmin = new rsssl_directadmin();
		    $host = $directadmin->host;
		    $url = $directadmin->ssl_installation_url;
	    }

	    $hosting_company = rsssl_get_other_host();
	    if ( $hosting_company && $hosting_company !== 'none' ) {
		    $hosting_specific_link = RSSSL_LE()->hosts->hosts[$hosting_company]['ssl_installation_link'];
		    if ($hosting_specific_link) {
			    $site = trailingslashit( str_replace(array('https://','http://', 'www.'),'', site_url()) );
			    if ( strpos($hosting_specific_link,'{host}') !==false && empty($host) ) {
			    	$url = '';
			    } else {
				    $url = str_replace(array('{host}', '{domain}'), array($host, $site), $hosting_specific_link);
			    }
		    }
	    }

	    $action = 'continue';
	    $status = 'warning';
	    $message = rsssl_get_manual_instructions_text($url);
		$output = $url;
	    return new RSSSL_RESPONSE($status, $action, $message, $output );
    }

    /**
     * Test for localhost usage
	 * @return RSSSL_RESPONSE
	 */
    public function certificate_status(){
	    delete_transient('rsssl_certinfo');
	    if ( RSSSL()->certificate->is_valid() ) {
	    	//we have now renewed the cert info transient
		    $certinfo = get_transient('rsssl_certinfo');
		    $end_date = isset($certinfo['validTo_time_t']) ? $certinfo['validTo_time_t'] : false;
		    $grace_period = strtotime('+'.rsssl_le_manual_generation_renewal_check.' days');
		    $expiry_date = !empty($end_date) ? date( get_option('date_format'), $end_date ) : __("(unknown)","really-simple-ssl");
		    //if the certificate expires within the grace period, allow renewal
		    //e.g. expiry date 30 may, now = 10 may => grace period 9 june.
		    if ( $grace_period > $end_date ) {
			    $action = 'continue';
			    $status = 'success';
			    $message = sprintf(__("Your certificate will expire on %s.", "really-simple-ssl" ).' '.__("Continue to renew.", "really-simple-ssl" ), $expiry_date);   ;
		    } else {
			    $action = 'continue';
			    $status = 'error';
			    $message = __("You already have a valid SSL certificate.", "really-simple-ssl" );
		    }

	    } else {
		    $action = 'continue';
		    $status = 'success';
		    $message = __("SSL certificate should be generated and installed.", "really-simple-ssl" );
	    }
	    return new RSSSL_RESPONSE($status, $action, $message);
    }

	/**
	 * Check if the certifiate is to expire in max rsssl_le_manual_generation_renewal_check days.
	 * Used in notices list
	 * @return bool
	 */

	public function certificate_about_to_expire(){
		$about_to_expire = RSSSL()->certificate->about_to_expire();
		if ( !$about_to_expire ) {
			//if the certificate is valid, stop any attempt to renew.
			delete_option('rsssl_le_start_renewal');
			delete_option('rsssl_le_start_installation');
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
	    $message = __("The Hosting Panel software was not recognized. Depending on your hosting provider, the generated certificate may need to be installed manually.", "really-simple-ssl" );

        if ( rsssl_is_cpanel() ) {
	        $status = 'success';
	        $message = __("CPanel recognized. Possibly the certificate can be installed automatically.", "really-simple-ssl" );
        } else if ( rsssl_is_plesk() ) {
	        $status = 'success';
	        $message = __("Plesk recognized. Possibly the certificate can be installed automatically.", "really-simple-ssl" );
        } else if ( rsssl_is_directadmin() ) {
			$status = 'success';
			$message = __("DirectAdmin recognized. Possibly the certificate can be installed automatically.", "really-simple-ssl" );
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
		    $message = __("The PHP function CURL is not available on your server, which is required. Please contact your hosting provider.", "really-simple-ssl" );
	    } else {
		    $action = 'continue';
		    $status = 'success';
		    $message = __("The PHP function CURL has successfully been detected.", "really-simple-ssl" );
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
		        $response = $this->get_error($e);
		        $status = 'error';
		        $action = 'retry';
		        if ( strpos($response, 'invalid contact domain')) {
		        	$action = 'stop';
		        	$response = __("The used domain for your email address is not allowed.","really-simple-ssl").'&nbsp;'.
			                    sprintf(__("Please change your email address %shere%s and try again.", "really-simple-ssl"),'<a href="'.rsssl_letsencrypt_wizard_url().'&step=2'.'">','</a>');
		        }

		        $message = $response;
	        }
        } else {
	        $status = 'error';
	        $action = 'stop';
	        $message = __("The email address was not set. Please set the email address",'really-simple-ssl');
        }
	    return new RSSSL_RESPONSE($status, $action, $message);
    }

	/**
	 * @return RSSSL_RESPONSE
	 */
    public function get_dns_token(){
	    if ( rsssl_is_ready_for('dns-verification') ) {
		    $use_dns        = rsssl_dns_verification_required();
		    $challenge_type = $use_dns ? Order::CHALLENGE_TYPE_DNS : Order::CHALLENGE_TYPE_HTTP;
		    if ( $use_dns ) {
			    try {
				    $this->get_account();
				    $dnsWriter = new class extends AbstractDNSWriter {
					    public function write( Order $order, string $identifier, string $digest ): bool {
						    $tokens                = get_option( 'rsssl_le_dns_tokens', [] );
						    $tokens[ $identifier ] = $digest;
						    update_option( "rsssl_le_dns_tokens", $tokens, false );
						    rsssl_progress_add( 'dns-verification' );

						    //return false, as we will continue later on.
						    return false;
					    }
				    };
				    DNS::setWriter( $dnsWriter );
				    $response = $this->get_order();
				    $order = $response->output;
				    $response->output = false;

				    if ( $order ) {
					    try {
						    if ( $order->authorize( $challenge_type ) ) {
							    $response = new RSSSL_RESPONSE(
								    'success',
								    'continue',
								    __( "Token successfully retrieved.", 'really-simple-ssl' ),
								    $this->get_dns_tokens()
							    );
						    } else {
							    if ( get_option( 'rsssl_le_dns_tokens' ) ) {
								    $response = new RSSSL_RESPONSE(
									    'success',
									    'continue',
									    __( "Token successfully retrieved.", 'really-simple-ssl' ),
									    $this->get_dns_tokens()
								    );
							    } else {
								    $response = new RSSSL_RESPONSE(
									    'error',
									    'retry',
									    __( "Token not received yet.", 'really-simple-ssl' )
								    );
							    }

						    }
					    } catch ( Exception $e ) {
						    $error = $this->get_error( $e );
						    if ( strpos( $error, 'No challenge found with given type')!==false ) {
							    //Maybe it was first set to HTTP challenge. retry after clearing the order.
							    $order->clear();
						    } else if (strpos($error, 'Order has status "invalid"')!==false) {
							    $order->clear();
							    $error = __("The order is invalid, possibly due to too many failed authorization attempts. Please start at the previous step.","really-simple-ssl");
						    } else
						    //fixing a plesk bug
						    if ( strpos($error, 'No order for ID ') !== FALSE){
							    $error .= '&nbsp;'.__("Order ID mismatch, regenerate order.","really-simple-ssl");
							    $order->clear();
							    rsssl_progress_remove('dns-verification');
							    $error .= '&nbsp;'.__("If you entered your DNS records before, they need to be changed.","really-simple-ssl");
						    }
						    $response = new RSSSL_RESPONSE(
							    'error',
							    'retry',
							    $error
						    );
					    }
				    }
			    } catch ( Exception $e ) {
				    rsssl_progress_remove( 'dns-verification' );
				    $response = $this->get_error( $e );
					$response = new RSSSL_RESPONSE(
						'error',
						'retry',
						$response
					);
			    }
		    } else {
			    $response = new RSSSL_RESPONSE(
				    'error',
				    'stop',
				    __( "Configured for HTTP challenge", 'really-simple-ssl' )
			    );
		    }
	    } else {
		    rsssl_progress_remove( 'dns-verification' );
		    $response = new RSSSL_RESPONSE(
			    'error',
			    'stop',
			    $this->not_completed_steps_message('dns-verification')
		    );
	    }
	    return $response;
    }

	/**
	 * @return array
	 */
	public function get_dns_tokens(){
		$tokens = get_option( 'rsssl_le_dns_tokens', [] );
		$output = [];
		foreach ($tokens as $domain => $token ) {
			$output[] = [
				'domain' => $domain,
				'token' => $token,
			];
		}
		return $output;
	}

	/**
	 * Check DNS txt records.
	 * @return RSSSL_RESPONSE
	 */

	public function verify_dns(){
		if ( rsssl_is_ready_for('generation') ) {
			update_option('rsssl_le_dns_records_verified', false, false );

			$tokens = get_option('rsssl_le_dns_tokens');
			if ( !$tokens) {
				$status = 'error';
				$action = 'stop';
				$message = __('Token not generated. Please complete the previous step.',"really-simple-ssl");
				return new RSSSL_RESPONSE($status, $action, $message);
			}
			foreach ($tokens as $identifier => $token){
				if (strpos($identifier, '*') !== false) continue;
				set_error_handler(array($this, 'custom_error_handling'));

				$response = dns_get_record( "_acme-challenge.$identifier", DNS_TXT );
				restore_error_handler();
				if ( isset($response[0]['txt']) ){
					if ($response[0]['txt'] === $token) {
						$response = new RSSSL_RESPONSE(
							'success',
							'continue',
							sprintf(__('Successfully verified DNS records', "really-simple-ssl"), "_acme-challenge.$identifier")
						);
						update_option('rsssl_le_dns_records_verified', true, false );
					} else {
						$action = get_option('rsssl_skip_dns_check') ? 'continue' : 'stop';
						$response = new RSSSL_RESPONSE(
							'error',
							$action,
							sprintf(__('The DNS response for %s was %s, while it should be %s.', "really-simple-ssl"), "_acme-challenge.$identifier", $response[0]['txt'], $token )
						);
						break;
					}
				} else {
					$action = get_option('rsssl_skip_dns_check') ? 'continue' : 'stop';
					$response = new RSSSL_RESPONSE(
						'warning',
						$action,
						sprintf(__('Could not verify TXT record for domain %s', "really-simple-ssl"), "_acme-challenge.$identifier")
					);
				}
			}

		} else {
			$response = new RSSSL_RESPONSE(
				'error',
				'stop',
				$this->not_completed_steps_message('dns-verification')
			);
		}

		return $response;
	}

	/**
	 * Clear an existing order
	 */
	public function clear_order(){
		$this->get_account();
		if ( $this->account ) {
			$response = $this->get_order();
			$order = $response->output;
			if ( $order ) {
				$order->clear();
			}
		}
	}

	/**
     * Authorize the order
	 * @return RSSSL_RESPONSE
	 */

    public function create_bundle_or_renew(){
	    $bundle_completed = false;
    	$use_dns = rsssl_dns_verification_required();
	    $attempt_count = (int) get_transient( 'rsssl_le_generate_attempt_count' );
	    if ( $attempt_count>5 ){
		    delete_option("rsssl_le_start_renewal");
		    $message = __("The certificate generation was rate limited for 10 minutes because the authorization failed.",'really-simple-ssl');
		    if ($use_dns){
			    $message .= '&nbsp;'.__("Please double check your DNS txt record.",'really-simple-ssl');
		    }
		    return new RSSSL_RESPONSE(
			    'error',
			    'stop',
			    $message
		    );
	    }

	    if ( !get_option('rsssl_skip_dns_check') ) {
		    if ( $use_dns && ! get_option( 'rsssl_le_dns_records_verified' ) ) {
			    return new RSSSL_RESPONSE(
				    'error',
				    'stop',
				    __( "DNS records were not verified yet. Please complete the previous step.", 'really-simple-ssl' )
			    );
	        }
	    }

	    if (rsssl_is_ready_for('generation') ) {
		    $this->get_account();
			if ( $use_dns ) {
				$dnsWriter = new class extends AbstractDNSWriter {
					public function write( Order $order, string $identifier, string $digest): bool {
						$status = false;
						if ( get_option('rsssl_le_dns_tokens') ) {
							$status = true;
						}
						return $status;
					}
				};
				DNS::setWriter($dnsWriter);
			}

		    $response = $this->get_order();
			$order = $response->output;
		    $response->output = false;

		    if ( $order ) {
			    if ( $order->isCertificateBundleAvailable() ) {

				    try {
					    $order->enableAutoRenewal();
					    $response = new RSSSL_RESPONSE(
						    'success',
						    'continue',
						    __("Certificate already generated. It was renewed if required.",'really-simple-ssl')
					    );
					    $bundle_completed = true;
				    } catch ( Exception $e ) {
					    $response = new RSSSL_RESPONSE(
						    'error',
						    'retry',
						    $this->get_error( $e )
					    );
					    $bundle_completed = false;
				    }
			    } else {
				    $finalized = false;
			    	$challenge_type = $use_dns ? Order::CHALLENGE_TYPE_DNS : Order::CHALLENGE_TYPE_HTTP;
				    try {
					    if ( $order->authorize( $challenge_type ) ) {
						    $order->finalize();
						    $this->reset_attempt();
						    $finalized = true;
					    } else {
							$this->count_attempt();
						    $response = new RSSSL_RESPONSE(
							    'error',
							    'retry',
							    __('Authorization not completed yet.',"really-simple-ssl")
						    );
						    $bundle_completed = false;
					    }
				    } catch ( Exception $e ) {
					    $this->count_attempt();
					    $message = $this->get_error( $e );
					    $response = new RSSSL_RESPONSE(
						    'error',
						    'stop',
						    $message
					    );

					    if (strpos($message, 'Order has status "invalid"')!==false) {
					    	$order->clear();
						    $response->message = __("The order is invalid, possibly due to too many failed authorization attempts. Please start at the previous step.","really-simple-ssl");
					        if ($use_dns) {
					        	rsssl_progress_remove('dns-verification');
						        $response->message .= '&nbsp;'.__("As your order will be regenerated, you'll need to update your DNS text records.","really-simple-ssl");
					        }
					    } else {
					    	//if OCSP is not disabled yet, and the order status is not invalid, we disable ocsp, and try again.
					    	if ( !rsssl_get_option( 'disable_ocsp' ) ) {
							    rsssl_update_option( 'disable_ocsp', true );
							    $response->action = 'retry';
							    $response->status = 'warning';
							    $response->message = __("OCSP not supported, the certificate will be generated without OCSP.","really-simple-ssl");
						    }
					    }
				    }

					if ($finalized) {
					    try {
						    if ( $order->isCertificateBundleAvailable() ) {
							    $bundle_completed   = true;
							    $success_cert       = $success_intermediate = $success_private = false;
							    $bundle             = $order->getCertificateBundle();
							    $pathToPrivateKey   = $bundle->path . $bundle->private;
							    $pathToCertificate  = $bundle->path . $bundle->certificate;
							    $pathToIntermediate = $bundle->path . $bundle->intermediate;

							    if ( file_exists( $pathToPrivateKey ) ) {
								    $success_private = true;
								    update_option( 'rsssl_private_key_path', $pathToPrivateKey, false );
							    }
							    if ( file_exists( $pathToCertificate ) ) {
								    $success_cert = true;
								    update_option( 'rsssl_certificate_path', $pathToCertificate, false );
							    }

							    if ( file_exists( $pathToIntermediate ) ) {
								    $success_intermediate = true;
								    update_option( 'rsssl_intermediate_path', $pathToIntermediate, false );
							    }

							    if ( ! $success_cert || ! $success_private || ! $success_intermediate ) {
								    $bundle_completed = false;
							    }

							    if ( $bundle_completed ) {
								    $response = new RSSSL_RESPONSE(
									    'success',
									    'continue',
									    __("Successfully generated certificate.",'really-simple-ssl')
								    );
							    } else {
								    $response = new RSSSL_RESPONSE(
									    'error',
									    'retry',
									    __("Files not created yet...",'really-simple-ssl')
								    );
							    }

						    } else {
							    $response = new RSSSL_RESPONSE(
								    'error',
								    'retry',
								    __("Bundle not available yet...",'really-simple-ssl')
							    );
						    }
					    } catch ( Exception $e ) {
						    $response = new RSSSL_RESPONSE(
							    'error',
							    'retry',
							    $this->get_error( $e )
						    );
				        }
					}
			    }
		    }
	    } else {
		    $response = new RSSSL_RESPONSE(
		    	'error',
			    'stop',
			    $this->not_completed_steps_message('generation')
		    );
	    }

	    if ( $bundle_completed ){
		    rsssl_progress_add('generation');
		    update_option('rsssl_le_certificate_generated_by_rsssl', true, false);
		    delete_option("rsssl_le_start_renewal");
	    } else {
		    rsssl_progress_remove('generation');
	    }

	    return $response;
    }

	/**
	 * Get the order object
	 *
	 * @return RSSSL_RESPONSE
	 */
    public function get_order(){

		#if we don't have an account, try to retrieve it
		if ( !$this->account ) {
			$this->get_account();
		}

		#still no account, then exit
		if ( !$this->account ) {
			return new RSSSL_RESPONSE(
				'error',
				'retry',
				__( "Failed retrieving account.", 'really-simple-ssl' )
			);
		}

	    if ( ! Order::exists( $this->account, $this->subjects ) ) {
		    try {
			    $response = new RSSSL_RESPONSE(
				    'success',
				    'continue',
				    __("Order successfully created.",'really-simple-ssl')
			    );
			    $response->output = Order::create( $this->account, $this->subjects );

		    } catch(Exception $e) {
			    $response = new RSSSL_RESPONSE(
				    'error',
				    'retry',
				    $this->get_error($e)
			    );
		    }
	    } else {
		    //order exists already
		    $response = new RSSSL_RESPONSE(
			    'success',
			    'continue',
			    __( "Order successfully retrieved.", 'really-simple-ssl' )
		    );
		    $response->output = Order::get( $this->account, $this->subjects );
	    }

	    return $response;
    }

	/**
	 * Keep track of certain request counts, to prevent rate limiting by LE
	 */
    public function count_attempt(){
	    $attempt_count = intval(get_transient('rsssl_le_generate_attempt_count'));
	    $attempt_count++;
	    set_transient('rsssl_le_generate_attempt_count', $attempt_count, 10 * MINUTE_IN_SECONDS);
    }

	public function reset_attempt(){
		delete_transient('rsssl_le_generate_attempt_count');
	}



	/**
	 * Check if SSL generation renewal can be handled automatically
	 * @return bool
	 */
    public function ssl_generation_can_auto_renew(){
	    if ( rsssl_get_option('verification_type')==='dns' && !get_option('rsssl_le_dns_configured_by_rsssl') ) {
		    return false;
	    } else {
		    return true;
	    }
    }

	/**
	 * Check if it's possible to autorenew
	 * @return bool
	 */
	public function certificate_automatic_install_possible(){

		$install_method = get_option('rsssl_le_certificate_installed_by_rsssl');

		//if it was never auto installed, we probably can't autorenew.
		if ($install_method === false ) {
			return false;
		} else {
			return true;
		}
    }

	/**
	 * Check if the manual renewal should start.
	 *
	 * @return bool
	 */
    public function should_start_manual_installation_renewal(){
	    if ( !$this->should_start_manual_ssl_generation() && get_option( "rsssl_le_start_installation" ) ) {
			return true;
	    }
	    return false;
    }

	public function should_start_manual_ssl_generation(){
		return get_option( "rsssl_le_start_renewal" );
	}

	/**
	 * Only used if
	 * - SSL generated by RSSSL
	 * - certificate is about to expire
	 *
	 * @return string
	 */
	public function certificate_renewal_status_notice(){
    	if ( !RSSSL_LE()->letsencrypt_handler->ssl_generation_can_auto_renew()){
		    return 'manual-generation';
	    }

    	if ( $this->certificate_install_required() &&
	         $this->certificate_automatic_install_possible() &&
	         $this->installation_failed()
	    ){
    		return 'automatic-installation-failed';
	    }

    	if ( $this->certificate_install_required() && !$this->certificate_automatic_install_possible() ) {
    		return 'manual-installation';
	    }

    	return 'automatic';
	}

	/**
	 * Check if the certificate has to be installed on each renewal
	 * defaults to true.
	 *
	 */

    public function certificate_install_required(){
	    $install_method = get_option('rsssl_le_certificate_installed_by_rsssl');
	    $hosting_company = rsssl_get_other_host();
	    if ( in_array($install_method, RSSSL_LE()->hosts->no_installation_renewal_needed) || in_array($hosting_company, RSSSL_LE()->hosts->no_installation_renewal_needed)) {
		    return false;
	    }

        return true;
    }

	/**
     * Check if the certificate needs renewal.
     *
	 * @return bool
	 */
    public function cron_certificate_needs_renewal(){

	    $cert_file = get_option('rsssl_certificate_path');
	    if ( empty($cert_file) ) {
	    	return false;
	    }

	    $certificate = file_get_contents($cert_file);
	    $certificateInfo = openssl_x509_parse($certificate);
	    $valid_to = $certificateInfo['validTo_time_t'];
	    $in_expiry_days = strtotime( "+".rsssl_le_cron_generation_renewal_check." days" );
	    if ( $in_expiry_days > $valid_to ) {
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
	    return rsssl_get_option('email_address' );
    }
	/**
     * Get terms accepted
	 * @return RSSSL_RESPONSE
	 */

	public function terms_accepted(){
	    //don't use the default value: we want users to explicitly enter a value
	    $accepted =  rsssl_get_option('accept_le_terms');
		if ( $accepted ) {
			$status = 'success';
			$action = 'continue';
			$message = __("Terms & Conditions are accepted.",'really-simple-ssl');
		} else {
			$status = 'error';
			$action = 'stop';
			$message = __("The Terms & Conditions were not accepted. Please accept in the general settings.",'really-simple-ssl');
		}

		return new RSSSL_RESPONSE($status, $action, $message);
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
            //error_log(print_r($e, true));
        }
    }

	/**
     * Get list of common names on the certificate
	 * @return array
	 */
	public function get_subjects(){
		$subjects = array();
		$domain = rsssl_get_domain();
		$root = str_replace( 'www.', '', $domain );;
		$subjects[] = $domain;
		//don't offer aliasses for subdomains
		if ( !rsssl_is_subdomain() ) {
			if (rsssl_get_option( 'include_alias' )) {
				//main is www.
				if ( strpos( $domain, 'www.' ) !== false ) {
					$alias_domain = $root;
				} else {
					$alias_domain = 'www.'.$root;
				}
				$subjects[] = $alias_domain;
			}
		}

		if ( rsssl_wildcard_certificate_required() ) {
			$domain = rsssl_get_domain();
			//in theory, the main site of a subdomain setup can be a www. domain. But we have to request a certificate without the www.
			$domain   = str_replace( 'www.', '', $domain );
			$subjects = array(
				$domain,
				'*.' . $domain,
			);
		}

	    return apply_filters('rsssl_le_subjects', $subjects);
	}

	/**
     * Check if we're ready for the next step.
	 * @param string $item
	 *
	 * @return array | bool
	 */
	public function is_ready_for($item) {
		if ( !rsssl_do_local_lets_encrypt_generation() ) {
			rsssl_progress_add('directories');
			rsssl_progress_add('generation');
			rsssl_progress_add('dns-verification');
		}

		if ( !rsssl_dns_verification_required() ) {
			rsssl_progress_add('dns-verification');
		}

		if (empty(rsssl_get_not_completed_steps($item))){
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

	public function not_completed_steps_message($step){
		$not_completed_steps = rsssl_get_not_completed_steps($step);
		$nice_names = array();
		$steps = rsssl_le_steps();
		foreach ($not_completed_steps as $not_completed_step ) {
			$index = array_search($not_completed_step, array_column( $steps, 'id'));
			$nice_names[] = $steps[$index]['title'];
		}
		return sprintf(__('Please complete the following step(s) first: %s', "really-simple-ssl"), implode(", ", $nice_names) );
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
	 * Verify if a host has been selected, and if so, if this host supports LE, or if it's already active
	 */
	public function check_host(){
		$action = 'continue';
		$status = 'success';
		$message = __("We have not detected any known hosting limitations.", "really-simple-ssl" );
		$host = rsssl_get_other_host();
		if ( $host === 'none' ) $host = false;
		if ( isset(RSSSL_LE()->hosts->hosts[$host]) ){
			if ( RSSSL_LE()->hosts->hosts[$host]['free_ssl_available'] === 'paid_only' ) {
				$action = 'stop';
				$status = 'error';
				$message = sprintf(__("According to our information, your hosting provider does not allow any kind of SSL installation, other then their own paid certificate. For an alternative hosting provider with SSL, see this %sarticle%s.","really-simple-ssl"), '<a target="_blank" href="https://really-simple-ssl.com/hosting-providers-with-free-ssl">', '</a>');
			}

			if ( RSSSL_LE()->hosts->hosts[$host]['free_ssl_available'] === 'activated_by_default' ) {
				$url = RSSSL_LE()->hosts->hosts[$host]['ssl_installation_link'];
				$action = 'continue';
				$status = 'error';
				$message = sprintf(__("According to our information, your hosting provider supplies your account with an SSL certificate by default. Please contact your %shosting support%s if this is not the case.","really-simple-ssl"), '<a target="_blank" href="'.$url.'">', '</a>').'&nbsp'.
				       __("After completing the installation, you can let Really Simple SSL automatically configure your site for SSL by using the 'Activate SSL' button.","really-simple-ssl");
			}
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
		$action = 'stop';
		$status = 'error';
		$message = __("The key directory is not created yet.", "really-simple-ssl" );
		//this option is set in the key_dir function, so we need to check it now.
		if ( !get_option('rsssl_create_folders_in_root')) {
			$action = 'retry';
			$message = __("Trying to create directory in root of website.", "really-simple-ssl" );
		}

		if ( !$this->key_directory() ) {
			rsssl_progress_remove('directories');
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

	public function directories_without_writing_permissions( ){
		$required_folders = array(
			$this->key_directory,
			$this->certs_directory,
		);

		if ( !rsssl_dns_verification_required() ) {
			$required_folders[] = $this->challenge_directory;
		}

		$no_writing_permissions = array();
		foreach ($required_folders as $required_folder){
			if (!$this->directory_has_writing_permissions( $required_folder )) {
				$no_writing_permissions[] = $required_folder;
			}
		}

		return $no_writing_permissions;
	}

	/**
	 * Check if a directory has writing permissions
	 * @param string $directory
	 *
	 * @return bool
	 */
	public function directory_has_writing_permissions( $directory ){
		set_error_handler(array($this, 'custom_error_handling'));
		$test_file = fopen( $directory . "/really-simple-ssl-permissions-check.txt", "w" );
		if ( !$test_file ) {
			return false;
		}

		fwrite($test_file, 'file to test writing permissions for Really Simple SSL');
		fclose( $test_file );
		restore_error_handler();
		if (!file_exists($directory . "/really-simple-ssl-permissions-check.txt")) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Check if the challenage directory is reachable over the http protocol
	 * @return RSSSL_RESPONSE
	 */

	public function challenge_directory_reachable(){
		$file_content = false;
		$status_code = __('no response','really-simple-ssl');
		//make sure we request over http, otherwise the request might fail if the url is already https.
		$url = str_replace('https://', 'http://', site_url('.well-known/acme-challenge/really-simple-ssl-permissions-check.txt'));

		$error_message = sprintf(__( "Could not reach challenge directory over %s.", "really-simple-ssl"), '<a target="_blank" href="'.$url.'">'.$url.'</a>');
		$test_string = 'Really Simple SSL';
		$folders = $this->directories_without_writing_permissions();
		if ( !$this->challenge_directory() || count($folders) !==0 ) {
			$status  = 'error';
			$action  = 'stop';
			$message = __( "Challenge directory not writable.", "really-simple-ssl");
			return new RSSSL_RESPONSE($status, $action, $message);
		}

		$response       = wp_remote_get( $url );
		if ( is_array( $response ) ) {
			$status_code       = wp_remote_retrieve_response_code( $response );
			$file_content = wp_remote_retrieve_body( $response );
		}

		if ( $status_code !== 200 ) {
			if (get_option('rsssl_skip_challenge_directory_request')) {
				$status  = 'warning';
				$action = 'continue';
				$message = $error_message.' '.sprintf( __( "Error code %s.", "really-simple-ssl" ), $status_code );
			} else {
				$status  = 'error';
				$action = 'stop';
				$message = $error_message.' '.sprintf( __( "Error code %s.", "really-simple-ssl" ), $status_code );
				rsssl_progress_remove('directories');
			}
		} else {
			if ( ! is_wp_error( $response ) && ( strpos( $file_content, $test_string ) !== false ) ) {
				$status  = 'success';
				$action  = 'continue';
				$message = __( "Successfully verified alias domain.", "really-simple-ssl" );
				set_transient('rsssl_alias_domain_available', 'available', 30 * MINUTE_IN_SECONDS );
			} else {
				$status  = 'error';
				$action  = 'stop';
				$message = $error_message;
				rsssl_progress_remove('directories');
			}
		}

		return new RSSSL_RESPONSE($status, $action, $message);
	}

	/**
	 * Check if exists, create .well-known/acme-challenge directory if not existing
	 * @return bool|string
	 */
	public function challenge_directory() {
		$root_directory = trailingslashit(ABSPATH);
		if ( ! file_exists( $root_directory . '.well-known' ) ) {
			mkdir( $root_directory . '.well-known', 0755 );
		}

		if ( ! file_exists( $root_directory . '.well-known/acme-challenge' ) ) {
			mkdir( $root_directory . '.well-known/acme-challenge', 0755 );
		}

		if ( file_exists( $root_directory . '.well-known/acme-challenge' ) ){
			return $root_directory . '.well-known/acme-challenge';
		} else {
			return false;
		}
	}

	/**
	 * Check if exists, create ssl/certs directory above the wp root if not existing
	 * @return bool|string
	 */
	public function certs_directory(){
		$directory = $this->get_directory_path();
		if ( ! file_exists( $directory . 'ssl' ) ) {
			mkdir( $directory . 'ssl', 0755 );
		}

		if ( ! file_exists( $directory . 'ssl/certs' ) ) {
			mkdir( $directory . 'ssl/certs', 0755 );
		}

		if ( file_exists( $directory . 'ssl/certs' ) ){
			return $directory . 'ssl/certs';
		} else {
			return false;
		}
	}

	/**
	 * Get path to location where to create the directories.
	 * @return string
	 */
	public function get_directory_path(){
		$root_directory = trailingslashit(ABSPATH);
		if ( get_option('rsssl_create_folders_in_root') ) {
			if ( !get_option('rsssl_ssl_dirname') ) {
				$token = str_shuffle ( time() );
				update_option('rsssl_ssl_dirname', $token, false );
			}
			if ( ! file_exists( $root_directory . get_option('rsssl_ssl_dirname') ) ) {
				mkdir( $root_directory . get_option('rsssl_ssl_dirname'), 0755 );
			}
			return $root_directory . trailingslashit( get_option('rsssl_ssl_dirname') );
		} else {
			return trailingslashit(dirname($root_directory));
		}
	}

	/**
     * Check if exists, create ssl/keys directory above the wp root if not existing
	 * @return bool|string
	 */

	public function key_directory(){
		$directory = $this->get_directory_path();
		try {
			$openbasedir_restriction = $this->openbasedir_restriction($directory);
			if ( !$openbasedir_restriction ) {
				if ( ! file_exists( $directory . 'ssl' ) && is_writable( $directory ) ) {
					mkdir( $directory . 'ssl', 0755 );
				}

				if ( ! file_exists( $directory . 'ssl/keys' ) && is_writable( $directory . 'ssl' ) ) {
					mkdir( $directory . 'ssl/keys', 0755 );
				}
			}

			if ( !$openbasedir_restriction && file_exists( $directory . 'ssl/keys' ) ) {
				return $directory . 'ssl/keys';
			} else {
				//if creating the folder has failed, we're on apache, and can write to these folders, we create a root directory.
				$challenge_dir           = $this->challenge_directory;
				$has_writing_permissions = $this->directory_has_writing_permissions( $challenge_dir );
				//we're guessing that if the challenge dir has writing permissions, the new dir will also have it.
				if ( RSSSL()->server->uses_htaccess() && $has_writing_permissions ) {
					update_option( 'rsssl_create_folders_in_root', true, false );
				}

				return false;
			}
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Check for openbasedir restrictions
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	private function openbasedir_restriction( string $path): bool {

		// Default error handler is required
		set_error_handler(null);

		// Clean last error info.
		error_clear_last();

		// Testing...
		@file_exists($path);

		// Restore previous error handler
		restore_error_handler();

		// Return `true` if error has occurred
		return ($error = error_get_last()) && $error['message'] !== '__clean_error_info';
	}


	/**
	 * Clear the keys directory, used in reset function
	 * @since 5.0
	 */

	public function clear_keys_directory() {

		if (!rsssl_user_can_manage()) {
			return;
		}

		$dir = $this->key_directory();
		$this->delete_files_directories_recursively( $dir );

	}

	/**
	 * @param $dir
	 * Delete files and directories recursively. Used to clear the order from keys directory
	 * @since 5.0.11
	 */

	private function delete_files_directories_recursively( $dir ) {

		if ( strpos( $dir, 'ssl/keys' ) !== false ) {
			foreach ( glob( $dir . '/*' ) as $file ) {
				if ( is_dir( $file ) ) {
					$this->delete_files_directories_recursively( $file );
				} else {
					unlink( $file );
				}
			}
			rmdir( $dir );
		}
	}

	public function maybe_create_htaccess_directories(){
		if (!rsssl_user_can_manage()) {
			return;
		}

		if ( !RSSSL()->server->uses_htaccess() ) {
			return;
		}

		if ( !get_option('rsssl_create_folders_in_root') ) {
			return;
		}

		if ( !empty($this->get_directory_path()) ) {
			$this->write_htaccess_dir_file( $this->get_directory_path().'ssl/.htaccess' ,'ssl');
		}

		if ( !empty($this->key_directory()) ) {
			$this->write_htaccess_dir_file( trailingslashit($this->key_directory()).'.htaccess' ,'key');
		}
		if ( !empty($this->certs_directory()) ) {
			$this->write_htaccess_dir_file( trailingslashit($this->certs_directory()).'.htaccess' ,'certs');
		}
	}

	public function write_htaccess_dir_file($path, $type){
		$htaccess = '<ifModule mod_authz_core.c>' . "\n"
		            . '    Require all denied' . "\n"
		            . '</ifModule>' . "\n"
		            . '<ifModule !mod_authz_core.c>' . "\n"
		            . '    Deny from all' . "\n"
		            . '</ifModule>';
		insert_with_markers($path, 'Really Simple SSL LETS ENCRYPT', $htaccess);

		$htaccess = file_get_contents( $path );
		if ( strpos($htaccess, 'deny from all') !== FALSE ) {
			update_option('rsssl_htaccess_file_set_'.$type, true, false);
			return;
		}
	}

	/**
	 * Check if it's a subdomain multisite
	 * @return RSSSL_RESPONSE
	 */
	public function is_subdomain_setup(){
		if ( !is_multisite() ) {
			$is_subdomain = false;
		} else {
			if ( defined('SUBDOMAIN_INSTALL') && SUBDOMAIN_INSTALL ) {
				$is_subdomain = true;
			} else {
				$is_subdomain = false;
			}
		}

		if ($is_subdomain) {
			$status  = 'error';
			$action  = 'stop';
			$message = sprintf(__("This is a multisite configuration with subdomains. You should generate a wildcard certificate on the root domain.",'really-simple-ssl'), '<a href="https://really-simple-ssl.com/pro/?mtm_campaign=error&mtm_kwd=multisite&mtm_source=free&mtm_medium=letsencrypt&mtm_content=upgrade" target="_blank">','</a>');
			rsssl_progress_remove('system-status');
		} else {
			$status  = 'success';
			$action  = 'continue';
			$message = __("No subdomain setup detected.","really-simple-ssl");
		}

		return new RSSSL_RESPONSE($status, $action, $message);
	}

	/**
	 * Check if we're about to create a wilcard certificate
	 * @return bool
	 */

	public function is_wildcard(){
		$subjects = $this->get_subjects();
		$is_wildcard = false;
		foreach ($subjects as $domain ) {
			if ( strpos($domain, '*') !== false ) {
				$is_wildcard = true;
			}
		}

		return $is_wildcard;
	}

	/**
	 * Check if the alias domain is available
	 *
	 * @return RSSSL_RESPONSE
	 */
	public function alias_domain_available(){
		if ( rsssl_is_subdomain() ) {
			return new RSSSL_RESPONSE('success', 'continue',__("Alias domain check is not relevant for a subdomain","really-simple-ssl"));
		}
		//write a test file to the uploads directory
		$uploads    = wp_upload_dir();
		$upload_dir = trailingslashit($uploads['basedir']);
		$upload_url = trailingslashit($uploads['baseurl']);
		$file_content = false;
		$status_code = __('no response','really-simple-ssl');
		$domain = rsssl_get_domain();

		if ( strpos( $domain, 'www.' ) !== false ) {
			$is_www = true;
			$alias_domain = str_replace( 'www.', '', $domain );
		} else {
			$is_www = false;
			$alias_domain = 'www.'.$domain;
		}

		if ( $is_www ) {
			$message =  __("Please check if the non www version of your site also points to this website.", "really-simple-ssl" );
		} else {
			$message = __("Please check if the www version of your site also points to this website.", "really-simple-ssl" );
		}
		$error_message = __( "Could not verify alias domain.", "really-simple-ssl") .' '. $message.' '. __( "If this is not the case, don't add this alias to your certificate.", "really-simple-ssl");
		//get cached status first.
		$cached_status = get_transient('rsssl_alias_domain_available');
		if ( $cached_status ) {
			if ( $cached_status === 'available' ) {
				$status  = 'success';
				$action  = 'continue';
				$message = __( "Successfully verified alias domain.", "really-simple-ssl" );
			} else {
				$status  = 'warning';
				$action  = 'continue';
				$message = $error_message;
			}
			return new RSSSL_RESPONSE($status, $action, $message);
		}

		if ( ! file_exists( $upload_dir . 'rsssl' ) ) {
			mkdir( $upload_dir . 'rsssl', 0755 );
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
			set_transient('rsssl_alias_domain_available', 'not-available', 30 * MINUTE_IN_SECONDS );
			$alias_test_url = str_replace( $domain, $alias_domain, $test_url );
			//always over http:
			$alias_test_url = str_replace('https://','http://', $alias_test_url);
			$response       = wp_remote_get( $alias_test_url );
			if ( is_array( $response ) ) {
				$status_code       = wp_remote_retrieve_response_code( $response );
				$file_content = wp_remote_retrieve_body( $response );
			}

			if ( $status_code !== 200 ) {
				$status  = 'warning';
				$action  = 'continue';
				$message = $error_message;
				if (intval($status_code) != 0 ) {
					$message .= ' '.sprintf( __( "Error code %s", "really-simple-ssl" ), $status_code );
				}
			} else {
				if ( ! is_wp_error( $response ) && ( strpos( $file_content, $test_string ) !== false ) ) {
					//make sure we only set this value once, during first setup.
					if ( !get_option('rsssl_initial_alias_domain_value_set') ) {
						rsssl_update_option('include_alias', true);
						update_option('rsssl_initial_alias_domain_value_set', true, false);
					}
					$status  = 'success';
					$action  = 'continue';
					$message = __( "Successfully verified alias domain.", "really-simple-ssl" );
					set_transient('rsssl_alias_domain_available', 'available', 30 * MINUTE_IN_SECONDS );
				} else {
					$status  = 'warning';
					$action  = 'continue';
					$message = $error_message;
				}
			}
		}

		return new RSSSL_RESPONSE($status, $action, $message);
	}

	/**
     * Get string error from error message.
	 * @param mixed|LE_ACME2\Exception\InvalidResponse $e
	 *
	 * @return string
	 */
	private function get_error($e){
		$is_raw_response = false;
		if (method_exists($e, 'getRawResponse') && isset($e->getRawResponse()->body['detail'])) {
	    	$is_raw_response = true;
		    $error = $e->getRawResponse()->body['detail'];
		    //check for subproblems
		    if (isset($e->getRawResponse()->body['subproblems'])){
			    $error .= '<ul>';
		        foreach($e->getRawResponse()->body['subproblems'] as $index => $problem) {
			        $error .= '<li>'. $this->cleanup_error_message($e->getRawResponse()->body['subproblems'][$index]['detail']).'</li>';
		        }
			    $error .= '</ul>';
		    }

	    } else {
	        $error = $e->getMessage();
	    }


	    $max = strpos($error, 'CURL response');
	    if ($max===false) {
	    	$max = 200;
	    }
	    if (!$is_raw_response){
		    $error = substr( $error, 0, $max);
	    }
	    return $error;

	}

	/**
	 * Generic SSL cert installation function
	 *
	 * @return RSSSL_RESPONSE
	 */
	public function cron_renew_installation() {
		$install_method = get_option('rsssl_le_certificate_installed_by_rsssl');
		$data = explode(':', $install_method );

		$server = isset($data[0]) ? $data[0] : false;
		$type = isset($data[1]) ? $data[1] : false;

		$attempt_count = (int) get_transient( 'rsssl_le_install_attempt_count' );
		$attempt_count++;
		set_transient('rsssl_le_install_attempt_count', $attempt_count, DAY_IN_SECONDS);
		if ( $attempt_count>10 ){
			delete_option("rsssl_le_start_installation");
			$status = 'error';
			$action = 'stop';
			$message = __("The certificate installation was rate limited. Please try again later.",'really-simple-ssl');
			return new RSSSL_RESPONSE($status, $action, $message);
		}

		if (rsssl_is_ready_for('installation')) {
			try {
				if ( $server === 'cpanel' ) {
					if ($type==='default') {
						$response = rsssl_install_cpanel_default();
					} else if ( function_exists('rsssl_shell_installSSL') ) {
						$response = rsssl_shell_installSSL();
					} else {
						//in case of auto ssl.
						$response = new RSSSL_RESPONSE('error', 'stop', '');
						delete_option( "rsssl_le_start_installation" );
					}

					if ( $response->status === 'success' ) {
						delete_option( "rsssl_le_start_installation" );
					}
					return $response;
				} else if ( $server === 'plesk') {
					$response = rsssl_plesk_install();
					if ( $response->status === 'success' ) {
						delete_option( "rsssl_le_start_installation" );
					}
					return $response;
				} else {
					$status = 'error';
					$action = 'stop';
					$message = __("Not recognized server.", "really-simple-ssl");
				}
			} catch (Exception $e) {
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
	 * Decode a string
	 * @param $string
	 *
	 * @return string
	 */
    public function decode($string){
		if ( !wp_doing_cron() && !rsssl_user_can_manage() ) {
			return '';
		}

		if ( strpos( $string , 'rsssl_') !== FALSE ) {
			$key = get_site_option( 'rsssl_key' );
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

}
