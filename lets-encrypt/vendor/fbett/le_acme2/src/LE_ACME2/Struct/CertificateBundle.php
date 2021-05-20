<?php

namespace LE_ACME2\Struct;

class CertificateBundle {

    public $path;
    public $private;
    public $certificate;
    public $intermediate;
    public $expireTime;

    public function __construct(string $path, string $private, string $certificate, string $intermediate, int $expireTime) {

        $this->path = $path;
        $this->private = $private;
        $this->certificate = $certificate;
        $this->intermediate = $intermediate;
        $this->expireTime = $expireTime;
    }
}