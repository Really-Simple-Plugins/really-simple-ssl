<?php

namespace LE_ACME2\Utilities;

use LE_ACME2\Order;
use LE_ACME2\Exception\OpenSSLException;

class Certificate {

	protected static $_featureOCSPMustStapleEnabled = false;

	public static function enableFeatureOCSPMustStaple() {
		self::$_featureOCSPMustStapleEnabled = true;
	}

	public static function disableFeatureOCSPMustStaple() {
		self::$_featureOCSPMustStapleEnabled = false;
	}

	/**
	 * @param Order $order
	 * @return string
	 * @throws OpenSSLException
	 */
	public static function generateCSR(Order $order) : string {

		$dn = [
			"commonName" => $order->getSubjects()[0]
		];

		$san = implode(",", array_map(function ($dns) {

				return "DNS:" . $dns;
			}, $order->getSubjects())
		);

		$configFilePath = $order->getKeyDirectoryPath() . 'csr_config';

		$config = 'HOME = .
			RANDFILE = ' . $order->getKeyDirectoryPath() . '.rnd
			[ req ]
			default_bits = 4096
			default_keyfile = privkey.pem
			distinguished_name = req_distinguished_name
			req_extensions = v3_req
			[ req_distinguished_name ]
			countryName = Country Name (2 letter code)
			[ v3_req ]
			basicConstraints = CA:FALSE
			subjectAltName = ' . $san . '
			keyUsage = nonRepudiation, digitalSignature, keyEncipherment';

		if(self::$_featureOCSPMustStapleEnabled) {
			$config .= PHP_EOL . 'tlsfeature=status_request';
		}

		file_put_contents($configFilePath, $config);

		$privateKey = openssl_pkey_get_private(
			file_get_contents($order->getKeyDirectoryPath() . 'private.pem')
		);

		if($privateKey === false) {
			throw new OpenSSLException('openssl_pkey_get_private');
		}

		$csr = openssl_csr_new(
			$dn,
			$privateKey,
			[
				'config' => $configFilePath,
				'digest_alg' => 'sha256'
			]
		);

		if($csr === false) {
			throw new OpenSSLException('openssl_csr_new');
		}

		if(!openssl_csr_export($csr, $csr)) {
			throw new OpenSSLException('openssl_csr_export');
		}

		unlink($configFilePath);

		return $csr;
	}
}