<?php

namespace LE_ACME2\Request\Order;

use LE_ACME2\Response;
use LE_ACME2\Request\AbstractRequest;

use LE_ACME2\Connector;
use LE_ACME2\Cache;
use LE_ACME2\Exception;
use LE_ACME2\Struct;
use LE_ACME2\Utilities;

class RevokeCertificate extends AbstractRequest {

    protected $_certificateBundle;
    protected $_reason;

    public function __construct(Struct\CertificateBundle $certificateBundle, $reason) {

        $this->_certificateBundle = $certificateBundle;
        $this->_reason = $reason;
    }

    /**
     * @return Response\AbstractResponse|Response\Order\RevokeCertificate
     * @throws Exception\InvalidResponse
     * @throws Exception\RateLimitReached
     */
    public function getResponse() : Response\AbstractResponse {

        $certificate = file_get_contents($this->_certificateBundle->path . $this->_certificateBundle->certificate);
        preg_match('~-----BEGIN\sCERTIFICATE-----(.*)-----END\sCERTIFICATE-----~s', $certificate, $matches);
        $certificate = trim(Utilities\Base64::UrlSafeEncode(base64_decode(trim($matches[1]))));

        $payload = [
            'certificate' => $certificate,
            'reason' => $this->_reason
        ];

        $jwk = Utilities\RequestSigner::JWKString(
            $payload,
            Cache\DirectoryResponse::getInstance()->get()->getRevokeCert(),
            Cache\NewNonceResponse::getInstance()->get()->getNonce(),
            $this->_certificateBundle->path,
            $this->_certificateBundle->private
        );

        $result = Connector\Connector::getInstance()->request(
            Connector\Connector::METHOD_POST,
            Cache\DirectoryResponse::getInstance()->get()->getRevokeCert(),
            $jwk
        );

        return new Response\Order\RevokeCertificate($result);
    }
}