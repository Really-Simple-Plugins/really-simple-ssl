<?php

namespace LE_ACME2\Response\Order;
defined('ABSPATH') or die();

use LE_ACME2\Response\AbstractResponse;

class GetCertificate extends AbstractResponse {

    protected $_pattern = '~(-----BEGIN\sCERTIFICATE-----[\s\S]+?-----END\sCERTIFICATE-----)~i';


    public function getCertificate() : string {

        if(preg_match_all($this->_pattern, $this->_raw->body, $matches))  {

            return $matches[0][0];
        }

        throw new \RuntimeException('Preg_match_all has returned false - invalid pattern?');
    }

    public function getIntermediate() : string {

        if(preg_match_all($this->_pattern, $this->_raw->body, $matches))  {

            $result = '';

            for($i=1; $i<count($matches[0]); $i++)  {

                $result .= "\n" . $matches[0][$i];
            }
            return $result;
        }

        throw new \RuntimeException('Preg_match_all has returned false - invalid pattern?');
    }

    /**
     * @return string[]
     */
    public function getAlternativeLinks() : array {

        $result = [];

        foreach($this->_raw->header as $line) {
            $matches = [];
            preg_match_all('/^link: <(.*)>;rel="alternate"$/', $line, $matches);

            if(isset($matches[1][0])) {
                $result[] = $matches[1][0];
            }
        }

        return $result;
    }
}