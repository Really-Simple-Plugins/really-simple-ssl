<?php

require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'autoload.php'; //Path to composer autoload

// Config the desired paths
\LE_ACME2\Account::setCommonKeyDirectoryPath('/etc/ssl/le-storage/');
\LE_ACME2\Authorizer\HTTP::setDirectoryPath('/var/www/acme-challenges/');

// General configs
\LE_ACME2\Connector\Connector::getInstance()->useStagingServer(true);
\LE_ACME2\Utilities\Logger::getInstance()->setDesiredLevel(\LE_ACME2\Utilities\Logger::LEVEL_INFO);

// Optional configs
//\LE_ACME2\Utilities\Certificate::enableFeatureOCSPMustStaple();
//\LE_ACME2\Order::setPreferredChain(\LE_ACME2\Order::IDENTRUST_ISSUER_CN);

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

if($order->shouldStartAuthorization(\LE_ACME2\Order::CHALLENGE_TYPE_HTTP)) {
    // Do some pre-checks, f.e. external dns checks - not required
}

if($order->authorize(\LE_ACME2\Order::CHALLENGE_TYPE_HTTP)) {
    $order->finalize();
}

if($order->isCertificateBundleAvailable()) {

    $bundle = $order->getCertificateBundle();
    $order->enableAutoRenewal();

    // Revoke certificate
    // $order->revokeCertificate($reason = 0);
}