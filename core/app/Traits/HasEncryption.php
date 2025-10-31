<?php
namespace ReallySimplePlugins\RSS\Core\Traits;

trait HasEncryption
{
    /**
     * Encrypt a string with a prefix. If the prefix is already there, it's
     * already encrypted.
     */
    public function maybeEncryptPrefixed(string $data, string $prefix = 'rsssl_'): string
    {
        if (strpos($data, $prefix) === 0) {
            return $data;
        }

        $data = $this->encrypt($data);
        return $prefix . $data;
    }

    /**
     * Decrypt data if prefixed. If not prefixed, return the data, as it is
     * already decrypted.
     */
    public function maybeDecryptPrefixed(string $data, string $prefix = 'rsssl_', string $deprecatedKey = ''): string
    {
        if ( strpos($data, $prefix) !== 0 ) {
            return $data;
        }

        $data = substr($data, strlen($prefix));
        return $this->decrypt($data, 'string', $deprecatedKey);
    }

    /**
     * Encrypt the given data
     *
     * @param array|string $data
     * @param string $type The $data type that was given ('string' or 'array')
     */
    public function encrypt($data, string $type = 'string'): string
    {
        $key = $this->getEncryptionKey();

        if ('array' === strtolower($type)) {
            $data = serialize($data);
        }

        $dataIsEmpty = (strlen(trim($data)) === 0);
        $functionsDoNotExists = (
            function_exists('openssl_random_pseudo_bytes') === false
            || function_exists('openssl_cipher_iv_length') === false
            || function_exists('openssl_encrypt') === false
        );

        if ($dataIsEmpty || $functionsDoNotExists) {
            return '';
        }

        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }

    /**
     * Decrypt the given data
     *
     * @param mixed $data The data to decrypt
     * @param string $type The type of data to return ('string' or 'array')
     * @return array|string Either array or string, based on the $type
     */
    public function decrypt($data, string $type = 'string', string $deprecatedKey = '')
    {
        $fallbackValue = (strtolower($type) === 'string' ? '' : []);
        $key = !empty($deprecatedKey) ? $deprecatedKey : $this->getEncryptionKey();

        // If $data is empty, return appropriate empty value based on type
        if (empty($data)) {
            return $fallbackValue;
        }

        // If $data is not a string (i.e., it's already an array), return as is
        if (!is_string($data)) {
            return $data;
        }

        if (!function_exists('openssl_decrypt')) {
            return $fallbackValue;
        }

        $decoded = base64_decode($data);
        if (false === $decoded) {
            return $fallbackValue;
        }

        if (strpos($decoded, '::') !== false) {
            [$encrypted_data, $iv] = explode('::', $decoded, 2);
        } else {
            // Deprecated method, for backwards compatibility (license decryption)
            $ivlength = openssl_cipher_iv_length('aes-256-cbc');
            $iv = substr($decoded, 0, $ivlength);
            $encrypted_data = substr($decoded, $ivlength);
        }

        $decrypted_data = openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);

        if ('array' === strtolower($type)) {
            $unserialized_data = @unserialize($decrypted_data);
            return (is_array($unserialized_data)) ? $unserialized_data : [];
        }

        return $decrypted_data;
    }

    /**
     * Method is used to fetch the encryption key. Used in the encryption and
     * decryption processes. The key is a constant stored in the wp-config
     * or a key stored in the database.
     */
    private function getEncryptionKey(): string
    {
        return defined('RSSSL_KEY') ? RSSSL_KEY : get_site_option('rsssl_main_key', '');
    }
}