<?php

declare(strict_types=1);

namespace ReallySimplePlugins\RSS\Core\Traits;

trait HasNonces
{
    /**
     * Method for verifying the nonce
     * @param mixed $nonce Preferably string, not type-casted to prevent errors
     */
    protected function verifyNonce($nonce, string $action = 'rss_core_nonce'): bool
    {
        if (is_string($nonce) === false) {
            return false;
        }

        return (bool) wp_verify_nonce(sanitize_text_field(wp_unslash($nonce)), $action);
    }
}