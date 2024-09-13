<?php

namespace LE_ACME2\Utilities;
defined('ABSPATH') or die();

class RequestSigner {

    /**
     * Generates a JSON Web Key signature to attach to the request.
     *
     * @param array 	$payload		The payload to add to the signature.
     * @param string	$url 			The URL to use in the signature.
     * @param string    $nonce
     * @param string 	$privateKeyDir  The directory to get the private key from. Default to the account keys directory given in the constructor. (optional)
     * @param string 	$privateKeyFile The private key to sign the request with. Defaults to 'private.pem'. (optional)
     *
     * @return array	Returns an array containing the signature.
     */
    public static function JWK(array $payload, string $url, string $nonce, string $privateKeyDir, string $privateKeyFile = 'private.pem') : array {

        Logger::getInstance()->add(Logger::LEVEL_DEBUG, 'JWK sign request for ' . $url, $payload);

        $privateKey = @openssl_pkey_get_private(file_get_contents($privateKeyDir . $privateKeyFile));
        $details = @openssl_pkey_get_details($privateKey);

        $protected = [
            "alg" => "RS256",
            "jwk" => [
                "kty" => "RSA",
                "n" => Base64::UrlSafeEncode($details["rsa"]["n"]),
                "e" => Base64::UrlSafeEncode($details["rsa"]["e"]),
            ],
            "nonce" => $nonce,
            "url" => $url
        ];

        $payload64 = Base64::UrlSafeEncode(str_replace('\\/', '/', json_encode($payload)));
        $protected64 = Base64::UrlSafeEncode(json_encode($protected));

        openssl_sign($protected64.'.'.$payload64, $signed, $privateKey, "SHA256");
        $signed64 = Base64::UrlSafeEncode($signed);

        $data = array(
            'protected' => $protected64,
            'payload' => $payload64,
            'signature' => $signed64
        );

        return $data;
    }

    /**
     * Generates a JSON Web Key signature to attach to the request.
     *
     * @param array 	$payload		The payload to add to the signature.
     * @param string	$url 			The URL to use in the signature.
     * @param string    $nonce
     * @param string 	$privateKeyDir  The directory to get the private key from. Default to the account keys directory given in the constructor. (optional)
     * @param string 	$privateKeyFile The private key to sign the request with. Defaults to 'private.pem'. (optional)
     *
     * @return string	Returns a JSON encoded string containing the signature.
     */
    public static function JWKString(array $payload, string $url, string $nonce, string $privateKeyDir, string $privateKeyFile = 'private.pem') : string {

        $jwk = self::JWK($payload, $url, $nonce, $privateKeyDir, $privateKeyFile);
        return json_encode($jwk);
    }

    /**
     * Generates a Key ID signature to attach to the request.
     *
     * @param array|null 	$payload		The payload to add to the signature.
     * @param string	$kid			The Key ID to use in the signature.
     * @param string	$url 			The URL to use in the signature.
     * @param string    $nonce
     * @param string 	$privateKeyDir  The directory to get the private key from.
     * @param string 	$privateKeyFile The private key to sign the request with. Defaults to 'private.pem'. (optional)
     *
     * @return string	Returns a JSON encoded string containing the signature.
     */
    public static function KID(?array $payload, string $kid, string $url, string $nonce, string $privateKeyDir, string $privateKeyFile = 'private.pem') : string {

        Logger::getInstance()->add(Logger::LEVEL_DEBUG, 'KID sign request for ' . $url, $payload);

        $privateKey = openssl_pkey_get_private(file_get_contents($privateKeyDir . $privateKeyFile));
        // TODO: unused - $details = openssl_pkey_get_details($privateKey);

        $protected = [
            "alg" => "RS256",
            "kid" => $kid,
            "nonce" => $nonce,
            "url" => $url
        ];

        Logger::getInstance()->add(Logger::LEVEL_DEBUG, 'KID: ready to sign request for: ' . $url, $protected);

        $payload = $payload === null ? "" : str_replace('\\/', '/', json_encode($payload));

        $payload64 = Base64::UrlSafeEncode($payload);
        $protected64 = Base64::UrlSafeEncode(json_encode($protected));

        openssl_sign($protected64.'.'.$payload64, $signed, $privateKey, "SHA256");
        $signed64 = Base64::UrlSafeEncode($signed);

        $data = [
            'protected' => $protected64,
            'payload' => $payload64,
            'signature' => $signed64
        ];

        return json_encode($data);
    }
}