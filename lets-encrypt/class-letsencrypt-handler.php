<?php
defined('ABSPATH') or die("you do not have access to this page!");

require_once rsssl_path . '/lets-encrypt/vendor/autoload.php';
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
		add_action( 'rsssl_lets_encrypt_grid', array($this, 'wizard') );
		add_action( 'rsssl_lets_encrypt_grid', array($this, 'wizard') );
		add_action( 'rsssl_le_installation_step', array( $this, 'installation_progress' ), 10, 1 );
		add_action( 'wp_ajax_rsssl_installation_progress', array($this, 'get_installation_progress'));
		add_action( 'rsssl_before_save_lets-encrypt_option', array( $this, 'before_save_wizard_option' ), 10, 4 );

		$this->installation_sequence = array(
            'accept_terms',
            'challenge_directory',
            'key_directory',
            'account',
            'bundle',
            'installation',
        );

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

        $this->get_account();
        $this->subjects = $this->get_subjects();
		self::$_this = $this;
	}

	static function this() {
		return self::$_this;
	}

	public function before_save_wizard_option(
		$fieldname, $fieldvalue, $prev_value, $type
	) {

		//only run when changes have been made
		if ( $fieldvalue === $prev_value ) {
			return;
		}

		if ($fieldname==='accept_le_terms'){
		    if ($fieldvalue) {
			    $this->progress_add('accept_terms');
            } else {
		        $this->progress_remove('accept_terms');
            }
        }
	}

	public function get_installation_progress(){
		$error   = false;
		$action = '';
		$response = '';
		$status = 'none';
		if ( ! is_user_logged_in() ) {
			$error = true;
		}

		if ( !isset($_GET['step']) ) {
			$error = true;
		}

		if (!$error) {
		    $step = intval($_GET['step']);
        }

		if (!$error) {
		    switch ($step) {
			    case 4:
				    $status = $this->create_bundle_or_renew();
				    $response = $status;

				    if ($response === 'success' ) {
					    $response = __( 'Successfully created bundle', "really-simple-ssl" );
					    $action   = 'finalize';
				    } else if ( $response === 'success_renewed' ) {
                        $response = __("Certificate already installed. It has been renewed if necessary.","really-simple-ssl");
                        $action   = 'finalize';
				    } else {
					    $status = 'failed';
					    $error = true;
					    error_log($response);


					    if (strpos($response, 'Error creating new order') !== false ) {
						    error_log("found needle order");
						    $action = 'stop';
					    }
					    //if we're ready for the bundle, restart the process on failure.
                        else if ($this->is_ready_for('bundle')) {
						    $action = 'restart';
					    } else {
						    $action = 'stop';
					    }
				    }

				    break;
                case 5:
	                $status = $this->install_certificate();
	                $response = $status;
	                if ($response === 'success') {
		                $response = __('Successfully installed certificate',"really-simple-ssl");
		                $action = 'finalize';
	                } else {
	                    error_log("json response stop");
		                $action = 'stop';
		                $error = true;
	                }
	                break;
                default:
                    $response = 'not allowed input';
            }
		}

		$out = array(
			'success' => ! $error,
            'message' => $response,
            'action' => $action,
            'status' => $status,
		);

		die( json_encode( $out ) );
    }

	/**
     *
	 * @param $step
	 */

	public function installation_progress($step){
	    ?>
            <script>
                jQuery(document).ready(function ($) {
                    'use strict';
                    var progress = 0;
                    var step = $('input[name=step]').val();
                    if ( step ==4 || step ==5 ) {
                        $('.rsssl_letsencrypt_container').removeClass('rsssl-hidden');
                        rsssl_process_installation_step();
                    }
                    function rsssl_process_installation_step() {
                        console.log("start process bar");
                        //set up a counter to slowly increment the progress value until we get a response.
                        window.rsssl_interval = setInterval(function () {
                            if (progress > 50) {
                                progress += 1;
                            } else {
                                progress += 5;
                            }
                            rsssl_set_progress();
                        }, 2000);

                        $.ajax({
                            type: "GET",
                            url: rsssl_wizard.admin_url,
                            dataType: 'json',
                            data: ({
                                step: step,
                                action: 'rsssl_installation_progress'
                            }),
                            success: function (response) {
                                console.log("response");
                                console.log(response);
                                var msg = response.message;
                                //if this is the bundle step, keep progress below 50
                                //the installation was not successful yet
                                rsssl_set_status(response.status);
                                if (response.action === 'finalize' ) {
                                    window.rsssl_interval = setInterval(function() {
                                        progress +=5;
                                        rsssl_set_progress(msg);
                                    }, 100 );
                                    console.log("start callback");
                                } else if (response.action === 'restart' ) {
                                    progress = 0;
                                    window.rsssl_interval = setInterval(function() {
                                        progress = 0;
                                        rsssl_set_progress(msg, true);
                                    }, 1000 );

                                } else if (response.action === 'stop'){
                                    progress = 0;
                                    rsssl_stop_progress(msg);
                                }


                            },
                            error: function(response) {
                                console.log("error");
                                console.log(response);
                                rsssl_stop_progress(response.responseText);
                            }
                        });
                    }

                    function rsssl_set_status(status){
                        if (status)
                        if ($('.'+status).length) {
                            $('.'+status).removeClass('rsssl-hidden');
                        }
                    }

                    function rsssl_stop_progress( msg ){
                        console.log(progress);
                        $('.rsssl-installation-progress').css('width',progress + '%');
                        clearInterval(window.rsssl_interval);
                        if (typeof msg !== "undefined") {
                            $('.rsssl_installation_message').html(msg);
                        }
                    }

                    function rsssl_set_progress(msg , restart_on_100){
                        if ( progress>=100 ) progress=100;
                        console.log(progress);

                        $('.rsssl-installation-progress').css('width',progress + '%');

                        if ( progress == 100 ) {
                            clearInterval(window.rsssl_interval);
                            if (typeof msg !== "undefined") {
                                $('.rsssl_installation_message').html(msg);
                            }
                            if (typeof restart_on_100 !=='undefined' && restart_on_100){
                                progress = 0;
                                $('.rsssl_installation_message').html('<?php _e("Not succeeded yet. Please let the system retry, or come back to this page later")?>');

                                rsssl_process_installation_step();
                            }
                        }


                    }
                });


            </script>
            <div class="rsssl_letsencrypt_container field-group rsssl-hidden">
                <div class="rsssl-field">
                    <div class="rsssl_installation_message"></div>
                    <div class=" rsssl-wizard-progress-bar">
                        <div class="rsssl-wizard-progress-bar-value rsssl-installation-progress" style="width:0"></div>
                    </div>
                </div>
            </div>

            <?php
    }

	/**
	 * Get or create an account
	 */
    public function get_account(){

	    $account_email = $this->account_email();
        $error = false;

        if ( is_email($account_email) ) {
            error_log("set up account");
	        try {
		        $this->account
			        = ! Account::exists( $account_email ) ?
			        Account::create( $account_email ) :
			        Account::get( $account_email );
	        } catch(Exception $e) {
		        error_log(print_r($e, true));
	        }
        } else {
            error_log("no email set");
	        $error = true;
        }

        if (!$error) {
	        $this->progress_add('account');
        } else {
	        $this->progress_remove('account');
        }
    }


	/**
     * Authorize the order
	 * @return string|void
	 */

    public function create_bundle_or_renew(){
	    //check if the required order was created
	    $order = $bundle_completed = false;
	    $response = 'success';

	    if ($this->is_ready_for('bundle')) {
		    if ( ! Order::exists( $this->account, $this->subjects ) ) {
			    error_log("order does not exist yet");
			    try {
				    $order = Order::create( $this->account, $this->subjects );
			    } catch(Exception $e) {
				    $response = $this->get_error($e);
				    error_log(print_r($e, true));
			    }
		    } else {
			    //order exists already
			    $order = Order::get( $this->account, $this->subjects );
		    }

		    if ( $order ) {
			    if ( $order->isCertificateBundleAvailable() ) {
				    try {
					    $order->enableAutoRenewal();
					    $response         = 'success_renewed';
					    $bundle_completed = true;
				    } catch ( Exception $e ) {
					    error_log( print_r( $e, true ) );
					    $response         = $this->get_error( $e );
					    $bundle_completed = false;
				    }
			    } else {
				    try {
					    if ( $order->authorize( Order::CHALLENGE_TYPE_HTTP ) ) {
						    $order->finalize();
					    }
				    } catch ( Exception $e ) {
					    error_log( print_r( $e, true ) );
					    $response = $this->get_error( $e );
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
							    error_log( "not all files" );
							    $bundle_completed = false;
						    }
					    }


				    } catch ( Exception $e ) {
					    error_log( print_r( $e, true ) );
					    $response = $this->get_error( $e );
				    }
			    }
		    }
	    } else {
		    $response = sprintf(__('Steps not completed: %s', "really-simple-ssl"), implode(", ",$this->get_not_completed_steps('bundle')) );
	    }

	    if ( $bundle_completed ){
		    $this->progress_add('bundle');
	    } else {
		    $this->progress_remove('bundle');
	    }

	    return $response;
    }

	/**
     * Instantiate our installer, and run it.
     *
	 * @return string
	 */
	public function install_certificate(){
		$response = 'success';
		if ($this->is_ready_for('installation')) {
		    try {
			    if (rsssl_cpanel_api_supported()){
				    error_log("is cpanel");
				    require_once( rsssl_path . 'lets-encrypt/cPanel/cPanel.php' );
				    $username = rsssl_get_value('cpanel_username');
				    $password = rsssl_get_value('cpanel_password');
				    $cpanel_host = rsssl_get_value('cpanel_host');
				    $cpanel = new rsssl_cPanel($cpanel_host, $username, $password);
				    $domains = RSSSL_LE()->letsencrypt_handler->get_subjects();
				    $response_arr = array();
				    if (is_array($domains) && count($domains)>0) {
					    foreach ($domains as $domain ) {
						    $response_arr[] = $cpanel->installSSL($domain);
					    }
				    }

				    foreach ($response_arr as $response ) {
				        if ($response !== 'success' ) {
					        error_log("response err".$response);

					        return $response;
				        }
				    }
				    error_log("success response");

				    return 'success';

			    } else {

				    error_log("not cpanel");
				    $response = 'not-ready';

			    }
		    } catch (Exception $e) {
		        error_log(print_r($e, true));
			    $response = 'not-ready';
		    }
		} else {
			$response = 'not-ready';
		}
		return $response;

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
	 * @return mixed|void
	 * Let's Encrypt wizard
	 */

	public function wizard() {
		?>
		<div class="wrap">
			<?php RSSSL_LE()->wizard->wizard( 'lets-encrypt' );  ?>
		</div>
		<?php
	}

	/**
     * Get list of common names on the certificate
	 * @return array
	 */
	public function get_subjects(){
		$subjects = array();
		$domain_no_www = rsssl_get_non_www_domain();
	    $subjects[] = $domain_no_www;
	    if (rsssl_get_value('include_www')) {
		    $subjects[] = 'www.'.rsssl_get_value('domain');
	    }
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
            $this->progress_add('challenge_directory');
			return $root_directory . '.well-known/acme-challenge';
		} else {
			$this->progress_remove('challenge_directory');
			return false;
		}
	}

	/**
	 * @param string $item
	 */
	public function progress_add($item){
		$progress = get_option("rsssl_le_installation_progress", array() );
		if (!in_array($item, $progress)){
		    $progress[] = $item;
			update_option("rsssl_le_installation_progress", $progress );
		}
	}

	/**
	 * @param string $item
	 */
	public function progress_remove($item){
		$progress = get_option("rsssl_le_installation_progress", array());
		if (in_array($item, $progress)){
		    $index = array_search($item, $progress);
		    unset($progress[$index]);
			update_option("rsssl_le_installation_progress", $progress);
		}
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

	private function get_not_completed_steps($item){
		$sequence = $this->installation_sequence;
		//drop all statuses after $item. We only need to know if all previous ones have been completed
		$index = array_search($item, $sequence);
		$sequence = array_slice($sequence, 0, $index, true);
		$not_completed = array();
		$finished = get_option("rsssl_le_installation_progress", array());
		error_log(print_r($finished,true));
		foreach ($sequence as $status ) {
			if (!in_array($status, $finished)) {
				$not_completed[] = $status;
			}
		}

        return $not_completed;
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
			$this->progress_add('key_directory');
			return $parent_directory . 'ssl/keys';
		} else {
			$this->progress_remove('key_directory');

			return false;
		}
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

	private function cleanup_error_message($msg){
		return str_replace(array(
			'Refer to sub-problems for more information.',
			'Error creating new order ::',
		), '', $msg);
    }


}
