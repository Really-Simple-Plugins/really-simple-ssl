<?php

declare(strict_types=1);

namespace ReallySimplePlugins\RSS\Core\Services;

/**
 * Business logic related to the site certificate
 * @todo Move RSSSL()->certificate methods here after full refactor.
 */
final class CertificateService
{
    /**
     * Method returns true if the site certificate is valid. False otherwise.
     */
    public function isValid(): bool
    {
        return RSSSL()->certificate->is_valid();
    }

    /**
     * Method returns true if the certificate detection failed prior to calling
     * this method. It uses the transient 'rsssl_certinfo' for the detection.
     */
    public function detectionFailed(): bool
    {
        return RSSSL()->certificate->detection_failed();
    }
}