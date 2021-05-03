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

	function __construct() {

		if ( isset( self::$_this ) ) {
			wp_die( sprintf( __( '%s is a singleton class and you cannot create a second instance.', 'really-simple-ssl' ), get_class( $this ) ) );
		}
		add_action('rsssl_lets_encrypt_grid', array($this, 'wizard'));

//		// Config the desired paths
//		Account::setCommonKeyDirectoryPath( '/etc/ssl/le-storage/' );
//		HTTP::setDirectoryPath( '/var/www/acme-challenges/' );
//
//		// General configs
//		Connector::getInstance()->useStagingServer( true );
//		Logger::getInstance()->setDesiredLevel( Logger::LEVEL_INFO );
//
//		// Optional configs
//		//\LE_ACME2\Utilities\Certificate::enableFeatureOCSPMustStaple();
//		//\LE_ACME2\Order::setPreferredChain(\LE_ACME2\Order::IDENTRUST_ISSUER_CN);
//
//		$account_email = 'test@example.org';
//
//		$account
//			= ! Account::exists( $account_email ) ?
//			Account::create( $account_email ) :
//			Account::get( $account_email );
//
//		// Update email address
//		// $account->update('new-test@example.org');
//
//		// Deactivate account
//		// Warning: It seems not possible to reactivate an account.
//		// $account->deactivate();
//
//		$subjects
//			= ['example.org', // First item will be set as common name on the certificate
//			'www.example.org'];
//
//		if ( ! Order::exists( $account, $subjects ) ) {
//
//			// Do some pre-checks, f.e. external dns checks - not required
//
//			$order
//				= Order::create( $account, $subjects );
//		}
//
//		else {
//			$order = Order::get( $account, $subjects );
//		}
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
	 * Create .well-known/acme-challenge directory if not existing
	 * @return bool
	 */
	public function manual_directory_creation_needed() {
		$root_directory = trailingslashit(ABSPATH);
		if ( ! file_exists( $root_directory . '.well-known' ) ) {
			mkdir( $root_directory . '.well-known' );
		}

		if ( ! file_exists( $root_directory . '.well-known/acme-challenge' ) ) {
			mkdir( $root_directory . '.well-known/acme-challenge' );
		}

		if ( !file_exists( $root_directory . '.well-known/acme-challenge' ) ){
			return true;
		} else {
			return false;
		}
	}

	/**
     * Create ssl/keys directory above the wp root if not existing
	 * @return bool
	 */
	public function manual_key_directory_creation_needed(){
		$root_directory = trailingslashit(ABSPATH);
		$parent_directory = dirname($root_directory);
		if ( ! file_exists( $parent_directory . 'ssl' ) ) {
			mkdir( $parent_directory . 'ssl' );
		}

		if ( ! file_exists( $parent_directory . 'ssl/keys' ) ) {
			mkdir( $parent_directory . 'ssl/keys' );
		}

		if ( !file_exists( $parent_directory . 'ssl/keys' ) ){
			return true;
		} else {
			return false;
		}
	}
}
