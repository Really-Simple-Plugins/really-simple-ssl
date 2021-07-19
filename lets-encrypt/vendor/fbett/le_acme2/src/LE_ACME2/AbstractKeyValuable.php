<?php

namespace LE_ACME2;
defined('ABSPATH') or die();

use LE_ACME2\Connector\Connector;

abstract class AbstractKeyValuable {

    const KEY_TYPE_RSA = "RSA";
    const KEY_TYPE_EC = "EC";

    protected $_identifier;

    protected static $_directoryPath = null;

    public static function setCommonKeyDirectoryPath(string $directoryPath) {

        if(!file_exists($directoryPath)) {
            throw new \RuntimeException('Common Key Directory Path does not exist');
        }

        self::$_directoryPath = realpath($directoryPath) . DIRECTORY_SEPARATOR;
    }

    public static function getCommonKeyDirectoryPath() : ?string {
        return self::$_directoryPath;
    }

    protected function _getKeyDirectoryPath(string $appendix = '') : string {

        return self::$_directoryPath . $this->_identifier . $appendix . DIRECTORY_SEPARATOR;
    }

    public function getKeyDirectoryPath() : string {

        return $this->_getKeyDirectoryPath('');
    }

    protected function _initKeyDirectory(string $keyType = self::KEY_TYPE_RSA, bool $ignoreIfKeysExist = false) {

        if(!file_exists($this->getKeyDirectoryPath())) {

            mkdir($this->getKeyDirectoryPath());
        }

        if(!$ignoreIfKeysExist && (
                file_exists($this->getKeyDirectoryPath() . 'private.pem') ||
                file_exists($this->getKeyDirectoryPath() . 'public.pem')
            )
        ) {

            throw new \RuntimeException(
                'Keys exist already. Exists the ' . get_class($this) . ' already?' . PHP_EOL .
                'Path: ' . $this->getKeyDirectoryPath()
            );
        }

        if($keyType == self::KEY_TYPE_RSA) {

            Utilities\KeyGenerator::RSA(
                $this->getKeyDirectoryPath(),
                'private.pem',
                'public.pem'
            );
        } else if($keyType == self::KEY_TYPE_EC) {

            Utilities\KeyGenerator::EC(
                $this->getKeyDirectoryPath(),
                'private.pem',
                'public.pem'
            );
        } else {

            throw new \RuntimeException('Key type "' . $keyType . '" not supported.');
        }
    }

    protected function _clearKeyDirectory() {

        if(file_exists($this->getKeyDirectoryPath() . 'private.pem')) {
            unlink($this->getKeyDirectoryPath() . 'private.pem');
        }

        if(file_exists($this->getKeyDirectoryPath() . 'public.pem')) {
            unlink($this->getKeyDirectoryPath() . 'public.pem');
        }
    }

    protected function _getAccountIdentifier(Account $account) : string {

        $staging = Connector::getInstance()->isUsingStagingServer();

        return 'account_' . ($staging ? 'staging_' : 'live_') . $account->getEmail();
    }
}