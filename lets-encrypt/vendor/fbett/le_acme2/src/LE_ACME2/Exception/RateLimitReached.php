<?php

namespace LE_ACME2\Exception;

class RateLimitReached extends AbstractException {

    public function __construct(string $request, string $detail) {
        parent::__construct(
            "Invalid response received for request (" . $request . "): " .
            "rate limit reached - " . $detail
        );
    }
}