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
    public $account;
    public $subjects = array();
    public $installation_sequence;
	function __construct() {

		if ( isset( self::$_this ) ) {
			wp_die( sprintf( __( '%s is a singleton class and you cannot create a second instance.', 'really-simple-ssl' ), get_class( $this ) ) );
		}
		add_action('rsssl_lets_encrypt_grid', array($this, 'wizard'));
		add_action( 'rsssl_le_installation_step', array( $this, 'installation_progress' ), 10, 1 );
		add_action('wp_ajax_rsssl_installation_progress', array($this, 'get_installation_progress'));

		$this->installation_sequence = array(
            1 => 'account_created',
            2 => 'order_created',
            3 => 'bundle_created',
        );

		// Config the desired paths
        if ( $this->key_directory() ) {
	        Account::setCommonKeyDirectoryPath( $this->key_directory() );
        }

        if ( $this->challenge_directory() ) {
	        HTTP::setDirectoryPath( $this->challenge_directory() );
        }

		// General configs
		Connector::getInstance()->useStagingServer( true );
		Logger::getInstance()->setDesiredLevel( Logger::LEVEL_DISABLED );

//		// Optional configs
//		//\LE_ACME2\Utilities\Certificate::enableFeatureOCSPMustStaple();
//		//\LE_ACME2\Order::setPreferredChain(\LE_ACME2\Order::IDENTRUST_ISSUER_CN);

		$account_email = $this->account_email();

		$this->account
			= ! Account::exists( $account_email ) ?
			Account::create( $account_email ) :
			Account::get( $account_email );

        $this->subjects = $this->get_subjects();


//
//		// Clear current order (in case to restart on status "invalid")
//		// Already received certificate bundles will not be affected
//		// $order->clear();
//
//		if ( $order->shouldStartAuthorization( Order::CHALLENGE_TYPE_HTTP ) ) {
//			// Do some pre-checks, f.e. external dns checks - not required
//		}
//
//		if ( $order->authorize( Order::CHALLENGE_TYPE_HTTP ) ) {
//			$order->finalize();
//		}
//
//		if ( $order->isCertificateBundleAvailable() ) {
//
//			$bundle = $order->getCertificateBundle();
//			$order->enableAutoRenewal();
//
//			// Revoke certificate
//			// $order->revokeCertificate($reason = 0);
//		}

		self::$_this = $this;

	}

	static function this() {
		return self::$_this;
	}

	public function get_installation_progress(){
		$error   = false;
		$response = '';
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
                case 3:
	                $response = $this->get_order();
	                break;
			    case 4:
				    $response = $this->authorize();
				    break;
                default:
                    $response = 'not allowed input';
            }
		}

		$out = array(
			'success' => ! $error,
            'message' => $response,
		);

		die( json_encode( $out ) );
    }


	public function installation_progress($step){
	    $progress_steps = array(3, 4);
	    if ( !in_array($step, $progress_steps ) ) return;
	    ?>
            <script>
                jQuery(document).ready(function ($) {
                        'use strict';

                    //set up a counter to slowly increment the progress value until we get a response.
                    var progress = 0;
                    var interval = setInterval(function() {
                        if (progress>50) {
                            progress +=1;
                        } else {
                            progress +=5;

                        }
                        rsssl_set_progress(progress);
                    }, 1000);

                    var step = <?php echo $step?>;
                    $.ajax({
                        type: "GET",
                        url: rsssl_wizard.admin_url,
                        dataType: 'json',
                        data: ({
                            step: step,
                            action: 'rsssl_installation_progress'
                        }),
                        success: function (response) {
                            if (response.message === 'success' ) {
                                progress = 100;
                                rsssl_set_progress(progress);
                            } else {
                                rsssl_set_progress(100);
                                $('.rsssl_installation_message').html(response.message);
                            }
                        }
                    });

                    function rsssl_waiting_progress(){

                    }
                    function rsssl_set_progress(progress){
                        if (progress>100) progress=100;
                        if (progress==100) clearInterval(interval);
                        $('.rsssl-installation-progress').css('width',progress + '%')
                    }
                });


            </script>
            <div class="field-group">
                <div class="rsssl-field">
                    <div class="rsssl_installation_message"></div>
                    <div class=" rsssl-wizard-progress-bar">
                        <div class="rsssl-wizard-progress-bar-value rsssl-installation-progress" style="width:0"></div>
                    </div>
                </div>

            </div>

            <?php
    }

	public function get_order(){
	    //  return;

	    error_log("run this order");
		$response = 'success';
		$order = false;
        if ( ! Order::exists( $this->account, $this->subjects ) ) {
			// Do some pre-checks, f.e. external dns checks - not required
            try {
	            $order = Order::create( $this->account, $this->subjects );
            } catch(Exception $e) {
	            error_log(print_r($e, true));
	            $response = $e->getMessage();

	            $this->log_error('create_order_failed');
            }

		} else {
	        try {
		        $order = Order::get( $this->account, $this->subjects );
	        } catch(Exception $e) {
		        error_log(print_r($e, true));
		        $response = $e->getMessage();
		        $this->log_error('create_order_failed');
	        }
		}
        if ( $response==='success' && $order) {
	        $progress[] = 'order_created';
            update_option('rsssl_installation_progress', $progress);
        }

		return $response;
    }

    public function authorize(){
	    //check if the required order was created

	    $progress_array = get_option( 'rsssl_installation_progress' );
	    if (!in_array('order_created', $progress_array)){
		    $response = __("Lets encrypt order creation not completed.","really-simple-ssl");
        } else {

		    try {
			    $order = Order::get( $this->account, $this->subjects );
		    } catch (Exception $e){
			    error_log(print_r($e, true));
			    $response = $e->getMessage();
			    $this->log_error('finalize_order_failed');
		    }

		    try {
			    if ( $order->authorize( Order::CHALLENGE_TYPE_HTTP ) ) {
				    $order->finalize();
			    }
		    } catch (Exception $e){
			    error_log(print_r($e, true));
			    $response = $e->getMessage();
			    $this->log_error('finalize_order_failed');
		    }

		    if ( $order->isCertificateBundleAvailable() ) {

                //			$bundle = $order->getCertificateBundle();
                //			$order->enableAutoRenewal();

			    // Revoke certificate
			    // $order->revokeCertificate($reason = 0);
		    }
	    }
	    return $response;
    }

	public function account_email(){
	    return rsssl_get_value('email_address');
    }

	/**
     * Change the email address in an account
	 * @param $new_email
	 */

    public function update_account( $new_email ){
	    $this->clear_error('change_email_failed');
        try {
	        $this->account->update($new_email);
        } catch (Exception $e) {
            error_log("Lets encrypt email update failed");
            error_log(print_r($e, true));
            $this->log_error('change_email_failed');
        }
    }

	/**
     * Add an error to the logs
	 * @param string $error
	 */
    public function log_error($error){
	    $logs = get_transient('rsssl_letsencrypt_logs');
	    if (!is_array($logs)) {
	        $logs = array();
	    }
	    if (!in_array($error, $logs)) {
	        $logs[] = $error;
		    set_transient('rsssl_letsencrypt_logs', $logs, DAY_IN_SECONDS );
	    }
    }

	/**
     * Remove an error from the logs.
	 * @param string $error
	 */
	public function clear_error($error){
		$logs = get_transient('rsssl_letsencrypt_logs');
		if (is_array($logs) && in_array($error, $logs)) {
		    $key = array_search( $error, $logs);
		    unset($logs[$key]);
			set_transient('rsssl_letsencrypt_logs', $logs, DAY_IN_SECONDS);
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
	    $subjects[] = rsssl_get_value('domain');
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
		$parent_directory = dirname($root_directory);
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
}
