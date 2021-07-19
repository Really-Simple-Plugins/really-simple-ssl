<?php

namespace LE_ACME2\Utilities;
defined('ABSPATH') or die();

class Base64 {

    /**
     * Encodes a string input to a base64 encoded string which is URL safe.
     *
     * @param string	$input 	The input string to encode.
     * @return string	Returns a URL safe base64 encoded string.
     */
    public static function UrlSafeEncode(string $input) : string {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    /**
     * Decodes a string that is URL safe base64 encoded.
     *
     * @param string	$input	The encoded input string to decode.
     * @return string	Returns the decoded input string.
     */
    public static function UrlSafeDecode(string $input) : string {

        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }
}