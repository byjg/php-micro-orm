<?php

namespace Tests;

use ByJG\AnyDataset\Db\Factory;
use ByJG\MicroOrm\Exception\TransactionException;
use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\Repository;
use ByJG\MicroOrm\TransactionManager;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Model\Users;

class TransactionManagerTest extends TestCase
{
    const URI = 'mysql://root:password@127.0.0.1';
    /**
     * @var TransactionManager
     */
    protected $object;

    public function setUp(): void
    {
        $this->object = new TransactionManager();

        $dbDriver = Factory::getDbInstance(self::URI);
        $dbDriver->execute('create database if not exists a;');
        $dbDriver->execute('create database if not exists b;');
        $dbDriver->execute('create database if not exists c;');
        $dbDriver->execute('create database if not exists d;');
    }

    public function tearDown(): void
    {
        $this->object->destroy();
        $this->object = null;

        $dbDriver = Factory::getDbInstance(self::URI . "/a");
        $dbDriver->execute('drop table if exists users;');
        $dbDriver->execute('drop table if exists users1;');

        $dbDriver = Factory::getDbInstance(self::URI . "/b");
        $dbDriver->execute('drop table if exists users2;');
    }

    public function testAddConnectionError()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The connection already exists with a different instance");

        $dbDrive1 = $this->object->addConnection(self::URI . "/a");
        $dbDrive2 = $this->object->addConnection(self::URI . "/b");
        $dbDrive3 = $this->object->addConnection(self::URI . "/a");
        $dbDrive4 = $this->object->addConnection(self::URI . "/b");
    }

    public function testAddConnection()
    {
        $dbDrive1 = $this->object->addConnection(self::URI . "/a");
        $dbDrive2 = $this->object->addConnection(self::URI . "/b");
        $this->object->addDbDriver($dbDrive1);
        $this->object->addDbDriver($dbDrive2);

        $this->assertNotSame($dbDrive1, $dbDrive2);

        $this->assertEquals(2, $this->object->count());
    }

    public function testAddDbDriverError()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The connection already exists with a different instance");

        $dbDrive1 = Factory::getDbInstance(self::URI . "/a");
        $dbDrive2 = Factory::getDbInstance(self::URI . "/b");
        $dbDrive3 = Factory::getDbInstance(self::URI . "/a");
        $dbDrive4 = Factory::getDbInstance(self::URI . "/b");

        $this->object->addDbDriver($dbDrive1);
        $this->object->addDbDriver($dbDrive2);
        $this->object->addDbDriver($dbDrive3);
        $this->object->addDbDriver($dbDrive4);
    }

    public function testAddDbDriver()
    {
        $dbDrive1 = Factory::getDbInstance(self::URI . "/a");
        $dbDrive2 = Factory::getDbInstance(self::URI . "/b");

        $this->object->addDbDriver($dbDrive1);
        $this->object->addDbDriver($dbDrive2);
        $this->object->addDbDriver($dbDrive1);
        $this->object->addDbDriver($dbDrive2);

        $this->assertNotSame($dbDrive1, $dbDrive2);

        $this->assertEquals(2, $this->object->count());
    }

    public function testAddRepository()
    {
        $dbDriver = Factory::getDbInstance(self::URI . "/a");

        $dbDriver->execute('create table users (
            id integer primary key  auto_increment,
            name varchar(45),
            createdate datetime);'
        );
        $dbDriver->execute("insert into users (name, createdate) values ('John Doe', '2017-01-02')");
        $dbDriver->execute("insert into users (name, createdate) values ('Jane Doe', '2017-01-04')");
        $dbDriver->execute("insert into users (name, createdate) values ('JG', '1974-01-26')");
        $userMapper = new Mapper(Users::class, 'users', 'Id');
        $repository = new Repository($dbDriver, $userMapper);

        $this->object->addRepository($repository);
        $this->assertEquals(1, $this->object->count());

        $this->object->addDbDriver($dbDriver);
        $this->assertEquals(1, $this->object->count());
    }

    public function testBeginTransaction()
    {
        $this->object->addConnection(self::URI . "/c");
        $this->object->addConnection(self::URI . "/d");

        $this->object->beginTransaction();
        $this->object->commitTransaction();

        $this->assertEquals(2, $this->object->count());
    }

    public function testBeginTransactionTwice()
    {
        $this->expectException(TransactionException::class);
        $this->expectExceptionMessage("Transaction Already Started");

        $this->object->addConnection(self::URI . "/a");
        $this->object->addConnection(self::URI . "/d");

        $this->assertEquals(2, $this->object->count());

        $this->object->beginTransaction();
        $this->object->beginTransaction();
    }

    public function testRollbackWithNoTransaction()
    {
        $this->expectException(TransactionException::class);
        $this->expectExceptionMessage("There is no Active Transaction");

        $this->object->addConnection(self::URI . "/c");
        $this->assertEquals(1, $this->object->count());
        $this->object->rollbackTransaction();
    }

    public function testCommitWithNoTransaction()
    {
        $this->expectException(TransactionException::class);
        $this->expectExceptionMessage("There is no Active Transaction");

        $this->object->addConnection(self::URI . "/d");
        $this->assertEquals(1, $this->object->count());
        $this->object->commitTransaction();
    }

    public function testTransaction()
    {
        $dbDrive1 = Factory::getDbInstance(self::URI . "/a");
        $dbDrive2 = Factory::getDbInstance(self::URI . "/b");

        $dbDrive1->execute('create table users1 (
            id integer primary key  auto_increment,
            name varchar(45));'
        );
        $dbDrive2->execute('create table users2 (
            id integer primary key  auto_increment,
            name varchar(45));'
        );

        $this->assertEquals(0, $dbDrive1->getScalar("select count(*) from users1"));
        $this->assertEquals(0, $dbDrive2->getScalar("select count(*) from users2"));


        // Create Transaction Manager
        $this->object->addDbDriver($dbDrive1);
        $this->object->addDbDriver($dbDrive2);

        // Initialize Transaction
        $this->object->beginTransaction();
        $dbDrive1->execute("insert into users1 (name) values ('John1')");
        $dbDrive2->execute("insert into users2 (name) values ('John2')");
        $this->assertEquals(1, $dbDrive1->getScalar("select count(*) from users1"));
        $this->assertEquals(1, $dbDrive2->getScalar("select count(*) from users2"));
        $this->object->rollbackTransaction();

        // After rollback, no records should be added.
        $this->assertEquals(0, $dbDrive1->getScalar("select count(*) from users1"));
        $this->assertEquals(0, $dbDrive2->getScalar("select count(*) from users2"));

        // Initialize a new Transaction
        $this->object->beginTransaction();
        $dbDrive1->execute("insert into users1 (name) values ('John1')");
        $dbDrive2->execute("insert into users2 (name) values ('John2')");
        $this->assertEquals(1, $dbDrive1->getScalar("select count(*) from users1"));
        $this->assertEquals(1, $dbDrive2->getScalar("select count(*) from users2"));
        $this->object->commitTransaction();

        // After commit, records should be added.
        $this->assertEquals(1, $dbDrive1->getScalar("select count(*) from users1"));
        $this->assertEquals(1, $dbDrive2->getScalar("select count(*) from users2"));
    }

}
