<?php

namespace LE_ACME2;

use LE_ACME2\Request;
use LE_ACME2\Response;

use LE_ACME2\Cache;
use LE_ACME2\Authorizer;
use LE_ACME2\Exception;
use LE_ACME2\Utilities;

class Order extends AbstractKeyValuable {

    const CHALLENGE_TYPE_HTTP = 'http-01';
    const CHALLENGE_TYPE_DNS = 'dns-01';

    /**
     * @deprecated
     * @param $directoryPath
     */
    public static function setHTTPAuthorizationDirectoryPath(string $directoryPath) {

        Authorizer\HTTP::setDirectoryPath($directoryPath);
    }

    CONST IDENTRUST_ISSUER_CN = 'DST Root CA X3';

    /** @var string|null $_preferredChain */
    private static $_preferredChain = null;

    public static function setPreferredChain(string $issuerCN = null) {
        self::$_preferredChain = $issuerCN;
    }

    protected $_account;
    protected $_subjects;

    public function __construct(Account $account, array $subjects) {

        array_map(function($subject) {

            if(preg_match_all('~(\*\.)~', $subject) > 1)
                throw new \RuntimeException('Cannot create orders with multiple wildcards in one domain.');

        }, $subjects);

        $this->_account = $account;
        $this->_subjects = $subjects;

        $this->_identifier = $this->_getAccountIdentifier($account) . DIRECTORY_SEPARATOR .
            'order_' . md5(implode('|', $subjects));

        Utilities\Logger::getInstance()->add(
            Utilities\Logger::LEVEL_INFO,
            get_class() . '::' . __FUNCTION__ .  ' "' . implode(':', $this->getSubjects()) . '"'
        );

        Utilities\Logger::getInstance()->add(
            Utilities\Logger::LEVEL_DEBUG,
            get_class() . '::' . __FUNCTION__ .  ' path: ' . $this->getKeyDirectoryPath()
        );
    }

    public function getAccount() : Account {
        return $this->_account;
    }

    public function getSubjects() : array {

        return $this->_subjects;
    }

    /**
     * @param Account $account
     * @param array $subjects
     * @param string $keyType
     * @return Order
     * @throws Exception\AbstractException
     */
    public static function create(Account $account, array $subjects, string $keyType = self::KEY_TYPE_RSA) : Order {

        $order = new self($account, $subjects);
        return $order->_create($keyType, false);
    }

    /**
     * @param $keyType
     * @param bool $ignoreIfKeysExist
     * @return Order
     * @throws Exception\AbstractException
     */
    protected function _create(string $keyType, bool $ignoreIfKeysExist = false) : Order {

        $this->_initKeyDirectory($keyType, $ignoreIfKeysExist);

        $request = new Request\Order\Create($this);

        try {
            $response = $request->getResponse();

            Cache\OrderResponse::getInstance()->set($this, $response);
            return $this;

        } catch(Exception\AbstractException $e) {
            $this->_clearKeyDirectory();
            throw $e;
        }
    }

    public static function exists(Account $account, array $subjects) : bool {

        $order = new self($account, $subjects);
        return Cache\OrderResponse::getInstance()->exists($order);
    }

    public static function get(Account $account, array $subjects) : Order {

        $order = new self($account, $subjects);

        if(!self::exists($account, $subjects))
            throw new \RuntimeException('Order does not exist');

        return $order;
    }

    /** @var Authorizer\AbstractAuthorizer|Authorizer\HTTP|null $_authorizer  */
    protected $_authorizer = null;

    /**
     * @param $type
     * @return Authorizer\AbstractAuthorizer|Authorizer\HTTP|null
     * @throws Exception\InvalidResponse
     * @throws Exception\RateLimitReached
     * @throws Exception\ExpiredAuthorization
     */
    protected function _getAuthorizer(string $type) : Authorizer\AbstractAuthorizer {

        if($this->_authorizer === null) {

            if($type == self::CHALLENGE_TYPE_HTTP) {
                $this->_authorizer = new Authorizer\HTTP($this->_account, $this);
            } else if($type == self::CHALLENGE_TYPE_DNS) {
                $this->_authorizer = new Authorizer\DNS($this->_account, $this);
            } else {
                throw new \RuntimeException('Challenge type not implemented');
            }
        }
        return $this->_authorizer;
    }

    /**
     * The Authorization has expired, so we clean the complete order to restart again on the next call
     */
    protected function _clearAfterExpiredAuthorization() {

        Utilities\Logger::getInstance()->add(
            Utilities\Logger::LEVEL_INFO,
            get_class() . '::' . __FUNCTION__ . ' "Will clear after expired authorization'
        );

        $this->clear();
    }

    public function clear() {
        Cache\OrderResponse::getInstance()->set($this, null);
        $this->_clearKeyDirectory();
    }

    /**
     * @return bool
     * @param $type
     * @throws Exception\InvalidResponse
     * @throws Exception\RateLimitReached
     */
    public function shouldStartAuthorization(string $type) : bool {

        try {
            return $this->_getAuthorizer($type)->shouldStartAuthorization();
        } catch(Exception\ExpiredAuthorization $e) {

            $this->_clearAfterExpiredAuthorization();

            return false;
        }
    }

    /**
     * @param $type
     * @return bool
     * @throws Exception\InvalidResponse
     * @throws Exception\RateLimitReached
     * @throws Exception\AuthorizationInvalid
     */
    public function authorize(string $type) : bool {

        try {
            $authorizer = $this->_getAuthorizer($type);
            $authorizer->progress();

            return $authorizer->hasFinished();
        } catch(Exception\ExpiredAuthorization $e) {

            $this->_clearAfterExpiredAuthorization();

            return false;
        }
    }

    /**
     * @throws Exception\InvalidResponse
     * @throws Exception\RateLimitReached
     */
    public function finalize() {

        if(!is_object($this->_authorizer) || !$this->_authorizer->hasFinished()) {

            throw new \RuntimeException('Not all challenges are valid. Please check result of authorize() first!');
        }

        Utilities\Logger::getInstance()->add(
            Utilities\Logger::LEVEL_INFO,
            get_class() . '::' . __FUNCTION__ . ' "Will finalize'
        );

        $orderResponse = Cache\OrderResponse::getInstance()->get($this);

        if(
            $orderResponse->getStatus() == Response\Order\AbstractOrder::STATUS_PENDING /* DEPRECATED AFTER JULI 5TH 2018 */ ||
            $orderResponse->getStatus() == Response\Order\AbstractOrder::STATUS_READY   // ACME draft-12 Section 7.1.6
        ) {

            $request = new Request\Order\Finalize($this, $orderResponse);
            $orderResponse = $request->getResponse();
            Cache\OrderResponse::getInstance()->set($this, $orderResponse);
        }

        if($orderResponse->getStatus() == Response\Order\AbstractOrder::STATUS_VALID) {

            $request = new Request\Order\GetCertificate($this, $orderResponse);
            $response = $request->getResponse();

            $certificate = $response->getCertificate();
            $intermediate = $response->getIntermediate();

            //$certificateInfo = openssl_x509_parse($certificate);
            //$certificateValidToTimeTimestamp = $certificateInfo['validTo_time_t'];
            $intermediateInfo = openssl_x509_parse($intermediate);

            if(self::$_preferredChain !== null) {
                Utilities\Logger::getInstance()->add(
                    Utilities\Logger::LEVEL_INFO,
                    'Preferred chain is set: ' . self::$_preferredChain
                );
            }

            $found = false;
            if(self::$_preferredChain !== null && $intermediateInfo['issuer']['CN'] != self::$_preferredChain) {

                Utilities\Logger::getInstance()->add(
                    Utilities\Logger::LEVEL_INFO,
                    'Default certificate does not satisfy preferred chain, trying to fetch alternative'
                );

                foreach($response->getAlternativeLinks() as $link) {

                    $request = new Request\Order\GetCertificate($this, $orderResponse, $link);
                    $response = $request->getResponse();

                    $alternativeCertificate = $response->getCertificate();
                    $alternativeIntermediate = $response->getIntermediate();

                    $intermediateInfo = openssl_x509_parse($intermediate);
                    if($intermediateInfo['issuer']['CN'] != self::$_preferredChain) {
                        continue;
                    }

                    $found = true;

                    $certificate = $alternativeCertificate;
                    $intermediate = $alternativeIntermediate;

                    break;
                }

                if(!$found) {
                    Utilities\Logger::getInstance()->add(
                        Utilities\Logger::LEVEL_INFO,
                        'Preferred chain could not be satisfied, returning default chain'
                    );
                }
            }

            $this->_saveCertificate($certificate, $intermediate);
        }
    }

    private function _saveCertificate(string $certificate, string $intermediate) : void {

        $certificateInfo = openssl_x509_parse($certificate);
        $certificateValidToTimeTimestamp = $certificateInfo['validTo_time_t'];

        $path = $this->getKeyDirectoryPath() . self::BUNDLE_DIRECTORY_PREFIX . $certificateValidToTimeTimestamp . DIRECTORY_SEPARATOR;

        mkdir($path);
        rename($this->getKeyDirectoryPath() . 'private.pem', $path . 'private.pem');
        file_put_contents($path . 'certificate.crt', $certificate);
        file_put_contents($path . 'intermediate.pem', $intermediate);

        Utilities\Logger::getInstance()->add(Utilities\Logger::LEVEL_INFO, 'Certificate received');
    }

    const BUNDLE_DIRECTORY_PREFIX = 'bundle_';

    protected function _getLatestCertificateDirectory() : ?string {

        $files = scandir($this->getKeyDirectoryPath(), SORT_NUMERIC | SORT_DESC);
        foreach($files as $file) {
            if(
                substr($file, 0, strlen(self::BUNDLE_DIRECTORY_PREFIX)) == self::BUNDLE_DIRECTORY_PREFIX &&
                is_dir($this->getKeyDirectoryPath() . $file)
            ) {
                return $file;
            }
        }
        return null;
    }

    public function isCertificateBundleAvailable() : bool {

        return $this->_getLatestCertificateDirectory() !== NULL;
    }

    public function getCertificateBundle() : Struct\CertificateBundle {

        if(!$this->isCertificateBundleAvailable()) {
            throw new \RuntimeException('There is no certificate available');
        }

        $certificatePath = $this->getKeyDirectoryPath() . $this->_getLatestCertificateDirectory();

        return new Struct\CertificateBundle(
            $certificatePath . DIRECTORY_SEPARATOR,
            'private.pem',
            'certificate.crt',
            'intermediate.pem',
            self::_getExpireTimeFromCertificateDirectoryPath($certificatePath)
        );
    }

    /**
     * @param string $keyType
     * @param int|null $renewBefore Unix timestamp
     * @throws Exception\AbstractException
     */
    public function enableAutoRenewal($keyType = self::KEY_TYPE_RSA, int $renewBefore = null) {

        if($keyType === null) {
            $keyType = self::KEY_TYPE_RSA;
        }

        if(!$this->isCertificateBundleAvailable()) {
            throw new \RuntimeException('There is no certificate available');
        }

        $orderResponse = Cache\OrderResponse::getInstance()->get($this);
        if(
            $orderResponse === null ||
            $orderResponse->getStatus() != Response\Order\AbstractOrder::STATUS_VALID
        ) {
            return;
        }

        Utilities\Logger::getInstance()->add(Utilities\Logger::LEVEL_DEBUG,'Auto renewal triggered');

        $directory = $this->_getLatestCertificateDirectory();

        $expireTime = self::_getExpireTimeFromCertificateDirectoryPath($directory);

        if($renewBefore === null) {
            $renewBefore = strtotime('-30 days', $expireTime);
        }

        if($renewBefore < time()) {

            Utilities\Logger::getInstance()->add(Utilities\Logger::LEVEL_INFO,'Auto renewal: Will recreate order');

            $this->_create($keyType, true);
        }
    }

    /**
     * @param int $reason The reason to revoke the LetsEncrypt Order instance certificate.
     *                    Possible reasons can be found in section 5.3.1 of RFC5280.
     * @return bool
     * @throws Exception\RateLimitReached
     */
    public function revokeCertificate(int $reason = 0) : bool {

        if(!$this->isCertificateBundleAvailable()) {
            throw new \RuntimeException('There is no certificate available to revoke');
        }

        $bundle = $this->getCertificateBundle();

        $request = new Request\Order\RevokeCertificate($bundle, $reason);

        try {
            /* $response = */ $request->getResponse();
            rename(
                $this->getKeyDirectoryPath(),
                $this->_getKeyDirectoryPath('-revoked-' . microtime(true))
            );
            return true;
        } catch(Exception\InvalidResponse $e) {
            return false;
        }
    }

    protected static function _getExpireTimeFromCertificateDirectoryPath(string $path) {

        $stringPosition = strrpos($path, self::BUNDLE_DIRECTORY_PREFIX);
        if($stringPosition === false) {
            throw new \RuntimeException('ExpireTime not found in' . $path);
        }

        $expireTime = substr($path, $stringPosition + strlen(self::BUNDLE_DIRECTORY_PREFIX));
        if(
            !is_numeric($expireTime) ||
            $expireTime < strtotime('-10 years') ||
            $expireTime > strtotime('+10 years')
        ) {
            throw new \RuntimeException('Unexpected expireTime: ' . $expireTime);
        }
        return $expireTime;
    }
}