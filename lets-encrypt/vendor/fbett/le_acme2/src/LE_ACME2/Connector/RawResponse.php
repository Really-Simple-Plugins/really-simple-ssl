<?php

namespace LE_ACME2\Connector;
defined('ABSPATH') or die();

class RawResponse {

    /** @var string */
    public $request;

    /** @var array */
    public $header;

    /** @var array|string */
    public $body;

    public function init(string $method, string $url, string $response, int $headerSize) {

        $header = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        $body_json = json_decode($body, true);

        $this->request = $method . ' ' . $url;

        $this->header = array_map(function($line) {
            return trim($line);
        }, explode("\n", $header));

        $this->body = $body_json === null ? $body : $body_json;
    }

    public function toString() : string {

        return serialize([
            'request' => $this->request,
            'header' => $this->header,
            'body' => $this->body,
        ]);
    }

    public static function getFromString(string $string) : self {

        $array = unserialize($string);

        $rawResponse = new self();

        $rawResponse->request = $array['request'];
        $rawResponse->header = $array['header'];
        $rawResponse->body = $array['body'];

        return $rawResponse;
    }
}