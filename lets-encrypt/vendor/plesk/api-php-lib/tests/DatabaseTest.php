<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskXTest;

use PleskXTest\Utility\PasswordProvider;

class DatabaseTest extends TestCase
{
    /** @var \PleskX\Api\Struct\Webspace\Info */
    private static $webspace;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::$webspace = static::_createWebspace();
    }

    public function testCreate()
    {
        $database = $this->_createDatabase([
            'webspace-id' => static::$webspace->id,
            'name' => 'test1',
            'type' => 'mysql',
            'db-server-id' => 1,
        ]);
        static::$_client->database()->delete('id', $database->id);
    }

    public function testCreateUser()
    {
        $database = $this->_createDatabase([
            'webspace-id' => static::$webspace->id,
            'name' => 'test1',
            'type' => 'mysql',
            'db-server-id' => 1,
        ]);
        $user = $this->_createUser([
            'db-id' => $database->id,
            'login' => 'test_user1',
            'password' => PasswordProvider::STRONG_PASSWORD,
        ]);
        static::$_client->database()->deleteUser('id', $user->id);
        static::$_client->database()->delete('id', $database->id);
    }

    public function testUpdateUser()
    {
        $database = $this->_createDatabase([
            'webspace-id' => static::$webspace->id,
            'name' => 'test1',
            'type' => 'mysql',
            'db-server-id' => 1,
        ]);
        $user = $this->_createUser([
            'db-id' => $database->id,
            'login' => 'test_user1',
            'password' => PasswordProvider::STRONG_PASSWORD,
        ]);
        $updatedUser = static::$_client->database()->updateUser([
            'id' => $user->id,
            'login' => 'test_user2',
            'password' => PasswordProvider::STRONG_PASSWORD,
        ]);
        $this->assertEquals(true, $updatedUser);
        static::$_client->database()->deleteUser('id', $user->id);
        static::$_client->database()->delete('id', $database->id);
    }

    public function testGetById()
    {
        $database = $this->_createDatabase([
            'webspace-id' => static::$webspace->id,
            'name' => 'test1',
            'type' => 'mysql',
            'db-server-id' => 1,
        ]);

        $db = static::$_client->database()->get('id', $database->id);
        $this->assertEquals('test1', $db->name);
        $this->assertEquals('mysql', $db->type);
        $this->assertEquals(static::$webspace->id, $db->webspaceId);
        $this->assertEquals(1, $db->dbServerId);

        static::$_client->database()->delete('id', $database->id);
    }

    public function testGetAllByWebspaceId()
    {
        $db1 = $this->_createDatabase([
            'webspace-id' => static::$webspace->id,
            'name' => 'test1',
            'type' => 'mysql',
            'db-server-id' => 1,
        ]);
        $db2 = $this->_createDatabase([
            'webspace-id' => static::$webspace->id,
            'name' => 'test2',
            'type' => 'mysql',
            'db-server-id' => 1,
        ]);
        $databases = static::$_client->database()->getAll('webspace-id', static::$webspace->id);
        $this->assertEquals('test1', $databases[0]->name);
        $this->assertEquals('test2', $databases[1]->name);
        $this->assertEquals(static::$webspace->id, $databases[0]->webspaceId);
        $this->assertEquals(1, $databases[1]->dbServerId);

        static::$_client->database()->delete('id', $db1->id);
        static::$_client->database()->delete('id', $db2->id);
    }

    public function testGetUserById()
    {
        $database = $this->_createDatabase([
            'webspace-id' => static::$webspace->id,
            'name' => 'test1',
            'type' => 'mysql',
            'db-server-id' => 1,
        ]);

        $user = $this->_createUser([
            'db-id' => $database->id,
            'login' => 'test_user1',
            'password' => PasswordProvider::STRONG_PASSWORD,
        ]);

        $dbUser = static::$_client->database()->getUser('id', $user->id);
        $this->assertEquals('test_user1', $dbUser->login);
        $this->assertEquals($database->id, $dbUser->dbId);

        static::$_client->database()->deleteUser('id', $user->id);
        static::$_client->database()->delete('id', $database->id);
    }

    public function testGetAllUsersByDbId()
    {
        $db1 = $this->_createDatabase([
            'webspace-id' => static::$webspace->id,
            'name' => 'test1',
            'type' => 'mysql',
            'db-server-id' => 1,
        ]);
        $db2 = $this->_createDatabase([
            'webspace-id' => static::$webspace->id,
            'name' => 'test2',
            'type' => 'mysql',
            'db-server-id' => 1,
        ]);
        $user1 = $this->_createUser([
            'db-id' => $db1->id,
            'login' => 'test_user1',
            'password' => PasswordProvider::STRONG_PASSWORD,
        ]);

        $user2 = $this->_createUser([
            'db-id' => $db1->id,
            'login' => 'test_user2',
            'password' => PasswordProvider::STRONG_PASSWORD,
        ]);

        $user3 = $this->_createUser([
            'db-id' => $db2->id,
            'login' => 'test_user3',
            'password' => PasswordProvider::STRONG_PASSWORD,
        ]);

        $dbUsers = static::$_client->database()->getAllUsers('db-id', $db1->id);
        $this->assertEquals(2, count($dbUsers));
        $this->assertEquals('test_user1', $dbUsers[0]->login);
        $this->assertEquals('test_user2', $dbUsers[1]->login);

        static::$_client->database()->deleteUser('id', $user1->id);
        static::$_client->database()->deleteUser('id', $user2->id);
        static::$_client->database()->deleteUser('id', $user3->id);
        static::$_client->database()->delete('id', $db1->id);
        static::$_client->database()->delete('id', $db2->id);
    }

    public function testDelete()
    {
        $database = $this->_createDatabase([
            'webspace-id' => static::$webspace->id,
            'name' => 'test1',
            'type' => 'mysql',
            'db-server-id' => 1,
        ]);
        $result = static::$_client->database()->delete('id', $database->id);
        $this->assertTrue($result);
    }

    public function testDeleteUser()
    {
        $database = $this->_createDatabase([
            'webspace-id' => static::$webspace->id,
            'name' => 'test1',
            'type' => 'mysql',
            'db-server-id' => 1,
        ]);
        $user = $this->_createUser([
            'db-id' => $database->id,
            'login' => 'test_user1',
            'password' => PasswordProvider::STRONG_PASSWORD,
        ]);

        $result = static::$_client->database()->deleteUser('id', $user->id);
        $this->assertTrue($result);
        static::$_client->database()->delete('id', $database->id);
    }

    /**
     * @param array $params
     *
     * @return \PleskX\Api\Struct\Database\Info
     */
    private function _createDatabase(array $params)
    {
        $database = static::$_client->database()->create($params);
        $this->assertIsInt($database->id);
        $this->assertGreaterThan(0, $database->id);

        return $database;
    }

    /**
     * @param array $params
     *
     * @return \PleskX\Api\Struct\Database\UserInfo
     */
    private function _createUser(array $params)
    {
        $user = static::$_client->database()->createUser($params);
        $this->assertIsInt($user->id);
        $this->assertGreaterThan(0, $user->id);

        return $user;
    }
}
