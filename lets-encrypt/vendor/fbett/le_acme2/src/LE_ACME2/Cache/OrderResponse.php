<?php
namespace LE_ACME2\Cache;
defined('ABSPATH') or die();

use LE_ACME2\Connector;
use LE_ACME2\Order;
use LE_ACME2\Request;
use LE_ACME2\Response;
use LE_ACME2\Exception;
use LE_ACME2\Utilities;
use LE_ACME2\SingletonTrait;

class OrderResponse extends AbstractKeyValuableCache {

    use SingletonTrait;

    private const _FILE = 'CacheResponse';
    private const _DEPRECATED_FILE = 'DirectoryNewOrderResponse';

    private $_responses = [];

    public function exists(Order $order) : bool {

        $cacheFile = $order->getKeyDirectoryPath() . self::_FILE;
        $deprecatedCacheFile = $order->getKeyDirectoryPath() . self::_DEPRECATED_FILE;

        return file_exists($cacheFile) || file_exists($deprecatedCacheFile);
    }

    /**
     * @param Order $order
     * @return Response\Order\AbstractOrder
     * @throws Exception\InvalidResponse
     * @throws Exception\RateLimitReached
     */
    public function get(Order $order): Response\Order\AbstractOrder {

        $accountIdentifier = $this->_getObjectIdentifier($order->getAccount());
        $orderIdentifier = $this->_getObjectIdentifier($order);

        if(!isset($this->_responses[$accountIdentifier])) {
            $this->_responses[$accountIdentifier] = [];
        }

        if(array_key_exists($orderIdentifier, $this->_responses[$accountIdentifier])) {
            return $this->_responses[ $accountIdentifier ][ $orderIdentifier ];
        }
        $this->_responses[ $accountIdentifier ][ $orderIdentifier ] = null;

        $cacheFile = $order->getKeyDirectoryPath() . self::_FILE;
        $deprecatedCacheFile = $order->getKeyDirectoryPath() . self::_DEPRECATED_FILE;

        if(file_exists($deprecatedCacheFile) && !file_exists($cacheFile)) {
            rename($deprecatedCacheFile, $cacheFile);
        }

        if(file_exists($cacheFile)) {

            $rawResponse = Connector\RawResponse::getFromString(file_get_contents($cacheFile));

            $response = new Response\Order\Create($rawResponse);

            if(
                $response->getStatus() != Response\Order\AbstractOrder::STATUS_VALID
            ) {

                Utilities\Logger::getInstance()->add(
                    Utilities\Logger::LEVEL_DEBUG,
                    get_class() . '::' . __FUNCTION__ . ' (cache did not satisfy, status "' . $response->getStatus() . '")'
                );

                $request = new Request\Order\Get($order, $response);
                $response = $request->getResponse();
                $this->set($order, $response);
                return $response;
            }

            Utilities\Logger::getInstance()->add(
                Utilities\Logger::LEVEL_DEBUG,
                get_class() . '::' . __FUNCTION__ .  ' (from cache, status "' . $response->getStatus() . '")'
            );

            $this->_responses[$accountIdentifier][$orderIdentifier] = $response;

            return $response;
        }

        throw new \RuntimeException(
            self::_FILE . ' could not be found for order: ' .
            '- Path: ' . $order->getKeyDirectoryPath() . PHP_EOL .
            '- Subjects: ' . var_export($order->getSubjects(), true) . PHP_EOL
        );
    }

    public function set(Order $order, Response\Order\AbstractOrder $response = null) : void {

        $accountIdentifier = $this->_getObjectIdentifier($order->getAccount());
        $orderIdentifier = $this->_getObjectIdentifier($order);

        $filePath = $order->getKeyDirectoryPath() . self::_FILE;

        if($response === null) {

            unset($this->_responses[$accountIdentifier][$orderIdentifier]);

            if(file_exists($filePath)) {
                unlink($filePath);
            }

            return;
        }

        $this->_responses[$accountIdentifier][$orderIdentifier] = $response;
        file_put_contents($filePath, $response->getRaw()->toString());
    }
}