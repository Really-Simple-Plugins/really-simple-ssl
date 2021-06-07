<?php

require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'autoload.php'; //Path to composer autoload

$dnsWriter = new class extends \LE_ACME2\Authorizer\AbstractDNSWriter {
    public function write(\LE_ACME2\Order $order, string $identifier, string $digest): bool {
        $status = false;
        error_log(print_r($order,true));
	    error_log("Identifier");

	    error_log(print_r($identifier,true));
	    error_log("digest");

	    error_log(print_r($digest,true));
        // Write digest to DNS system
        // return true, if the dns configuration is usable and the process should be progressed
        return $status;
    }
};


// Config the desired paths
\LE_ACME2\Account::setCommonKeyDirectoryPath('/etc/ssl/le-storage/');
\LE_ACME2\Authorizer\DNS::setWriter($dnsWriter);

$account_email = 'test@example.org';

$account = !\LE_ACME2\Account::exists($account_email) ?
    \LE_ACME2\Account::create($account_email) :
    \LE_ACME2\Account::get($account_email);

// Update email address
// $account->update('new-test@example.org');

// Deactivate account
// Warning: It seems not possible to reactivate an account.
// $account->deactivate();

$subjects = [
    'example.org', // First item will be set as common name on the certificate
    'www.example.org'
];

if(!\LE_ACME2\Order::exists($account, $subjects)) {

    // Do some pre-checks, f.e. external dns checks - not required

    $order = \LE_ACME2\Order::create($account, $subjects);
} else {
    $order = \LE_ACME2\Order::get($account, $subjects);
}

// Clear current order (in case to restart on status "invalid")
// Already received certificate bundles will not be affected
// $order->clear();

if($order->shouldStartAuthorization(\LE_ACME2\Order::CHALLENGE_TYPE_DNS)) {
    // Do some pre-checks, f.e. external dns checks - not required
}

if($order->authorize(\LE_ACME2\Order::CHALLENGE_TYPE_DNS)) {
    $order->finalize();
}

if($order->isCertificateBundleAvailable()) {

    $bundle = $order->getCertificateBundle();
    $order->enableAutoRenewal();

    // Revoke certificate
    // $order->revokeCertificate($reason = 0);
}