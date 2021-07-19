<?php
namespace LE_ACME2Tests;
defined('ABSPATH') or die();

use LE_ACME2\Exception\InvalidResponse;

/**
 * @covers \LE_ACME2\Account
 */
class AccountTest extends AbstractTest {
    
    private $_commonKeyDirectoryPath;

    private $_email;

    public function __construct() {
        parent::__construct();

        $this->_commonKeyDirectoryPath = TestHelper::getInstance()->getTempPath() . 'le-storage/';

        $this->_email = 'le_acme2_php_client@test.com';
    }

    public function testNonExistingCommonKeyDirectoryPath() {

        $this->assertTrue(\LE_ACME2\Account::getCommonKeyDirectoryPath() === null);

        $notExistingPath = TestHelper::getInstance()->getTempPath() . 'should-not-exist/';

        $this->expectException(\RuntimeException::class);

        \LE_ACME2\Account::setCommonKeyDirectoryPath($notExistingPath);
    }

    public function testCommonKeyDirectoryPath() {

        if(!file_exists($this->_commonKeyDirectoryPath)) {
            mkdir($this->_commonKeyDirectoryPath);
        }

        \LE_ACME2\Account::setCommonKeyDirectoryPath($this->_commonKeyDirectoryPath);

        $this->assertTrue(
            \LE_ACME2\Account::getCommonKeyDirectoryPath() === $this->_commonKeyDirectoryPath
        );
    }

    public function testNonExisting() {

        if(\LE_ACME2\Account::exists($this->_email)) {
            $this->markTestSkipped('Skipped: Account does already exist');
        }

        $this->assertTrue(!\LE_ACME2\Account::exists($this->_email));

        $this->expectException(\RuntimeException::class);
        \LE_ACME2\Account::get($this->_email);
    }

    public function testCreate() {

        if(\LE_ACME2\Account::exists($this->_email)) {
            // Skipping account modification tests, when the account already exists
            // to reduce the LE api usage while developing
            TestHelper::getInstance()->setSkipAccountModificationTests(true);
            $this->markTestSkipped('Account modifications skipped: Account does already exist');
        }

        $this->assertTrue(!\LE_ACME2\Account::exists($this->_email));

        $account = \LE_ACME2\Account::create($this->_email);
        $this->assertTrue(is_object($account));
        $this->assertTrue($account->getEmail() === $this->_email);

        $account = \LE_ACME2\Account::get($this->_email);
        $this->assertTrue(is_object($account));

        $result = $account->getData();
        $this->assertTrue($result->getStatus() === \LE_ACME2\Response\Account\AbstractAccount::STATUS_VALID);
    }

    public function testInvalidCreate() {

        if(TestHelper::getInstance()->shouldSkipAccountModificationTests()) {
            $this->expectNotToPerformAssertions();
            return;
        }

        $this->expectException(InvalidResponse::class);
        $this->expectExceptionMessage(
            'Invalid response received: ' .
            'urn:ietf:params:acme:error:invalidEmail' .
            ' - ' .
            'Error creating new account :: invalid contact domain. Contact emails @example.org are forbidden'
        );
        \LE_ACME2\Account::create('test@example.org');
    }

    public function testModification() {

        if(TestHelper::getInstance()->shouldSkipAccountModificationTests()) {
            $this->expectNotToPerformAssertions();
            return;
        }

        $account = \LE_ACME2\Account::get($this->_email);
        $this->assertTrue(is_object($account));

        $keyDirectoryPath = $account->getKeyDirectoryPath();
        $newEmail = 'new-' . $this->_email;

        // An email from example.org is not allowed
        $result = $account->update('test@example.org');
        $this->assertTrue($result === false);

        $result = $account->update($newEmail);
        $this->assertTrue($result === true);

        $this->assertTrue($account->getKeyDirectoryPath() !== $keyDirectoryPath);
        $this->assertTrue(file_exists($account->getKeyDirectoryPath()));

        $result = $account->update($this->_email);
        $this->assertTrue($result === true);

        $result = $account->changeKeys();
        $this->assertTrue($result === true);
    }

    public function testDeactivation() {

        if(TestHelper::getInstance()->shouldSkipAccountModificationTests()) {
            $this->expectNotToPerformAssertions();
            return;
        }

        $account = \LE_ACME2\Account::get($this->_email);
        $this->assertTrue(is_object($account));

        $result = $account->deactivate();
        $this->assertTrue($result === true);

        // The account is already deactivated
        $result = $account->deactivate();
        $this->assertTrue($result === false);

        // The account is already deactivated
        $result = $account->changeKeys();
        $this->assertTrue($result === false);

        // The account is already deactivated
        $this->expectException(\LE_ACME2\Exception\InvalidResponse::class);
        $account->getData();
    }

    public function testCreationAfterDeactivation() {

        if(TestHelper::getInstance()->shouldSkipAccountModificationTests()) {
            $this->expectNotToPerformAssertions();
            return;
        }

        $account = \LE_ACME2\Account::get($this->_email);
        $this->assertTrue(is_object($account));

        system('rm -R ' . $account->getKeyDirectoryPath());
        $this->assertTrue(!\LE_ACME2\Account::exists($this->_email));

        $account = \LE_ACME2\Account::create($this->_email);
        $this->assertTrue(is_object($account));
    }

    public function test() {

        $account = \LE_ACME2\Account::get($this->_email);
        $this->assertTrue(is_object($account));
    }
}