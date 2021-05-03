<?php

namespace LE_ACME2\Utilities;

class KeyGenerator {

    /**
     * Generates a new RSA keypair and saves both keys to a new file.
     *
     * @param string	$directory		The directory in which to store the new keys.
     * @param string	$privateKeyFile	The filename for the private key file.
     * @param string	$publicKeyFile  The filename for the public key file.
     */
    public static function RSA(string $directory, string $privateKeyFile = 'private.pem', string $publicKeyFile = 'public.pem') {

        $res = openssl_pkey_new([
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
            "private_key_bits" => 4096,
        ]);

        if(!openssl_pkey_export($res, $privateKey))
            throw new \RuntimeException("RSA keypair export failed!");

        $details = openssl_pkey_get_details($res);

        file_put_contents($directory . $privateKeyFile, $privateKey);
        file_put_contents($directory . $publicKeyFile, $details['key']);

        if(PHP_MAJOR_VERSION < 8) {
            // deprecated after PHP 8.0.0 and not needed anymore
            openssl_pkey_free($res);
        }
    }

    /**
     * Generates a new EC prime256v1 keypair and saves both keys to a new file.
     *
     * @param string	$directory		The directory in which to store the new keys.
     * @param string	$privateKeyFile	The filename for the private key file.
     * @param string	$publicKeyFile  The filename for the public key file.
     */
    public static function EC(string $directory, string $privateKeyFile = 'private.pem', string $publicKeyFile = 'public.pem') {

        if (version_compare(PHP_VERSION, '7.1.0') == -1)
            throw new \RuntimeException("PHP 7.1+ required for EC keys");

        $res = openssl_pkey_new([
            "private_key_type" => OPENSSL_KEYTYPE_EC,
            "curve_name" => "prime256v1",
        ]);

        if(!openssl_pkey_export($res, $privateKey))
            throw new \RuntimeException("EC keypair export failed!");

        $details = openssl_pkey_get_details($res);

        file_put_contents($directory . $privateKeyFile, $privateKey);
        file_put_contents($directory . $publicKeyFile, $details['key']);

        if(PHP_MAJOR_VERSION < 8) {
            // deprecated after PHP 8.0.0 and not needed anymore
            openssl_pkey_free($res);
        }
    }
}