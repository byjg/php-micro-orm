<?php

namespace Tests;

use ByJG\AnyDataset\Db\Factory;
use ByJG\MicroOrm\Exception\TransactionException;
use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\Repository;
use ByJG\MicroOrm\TransactionManager;
use PHPUnit\Framework\TestCase;
use Tests\Model\Users;

class TransactionManagerTest extends TestCase
{
    /**
     * @var TransactionManager
     */
    protected $object;

    public function setUp(): void
    {
        $this->object = new TransactionManager();
    }

    public function tearDown(): void
    {
        $this->object->destroy();
        $this->object = null;
        if (file_exists("/tmp/a.db")) {
            unlink("/tmp/a.db");
        }
        if (file_exists("/tmp/b.db")) {
            unlink("/tmp/b.db");
        }
        if (file_exists("/tmp/c.db")) {
            unlink("/tmp/c.db");
        }
        if (file_exists("/tmp/d.db")) {
            unlink("/tmp/d.db");
        }
    }

    public function testAddConnectionError()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("The connection already exists with a different instance");

        $dbDrive1 = $this->object->addConnection("sqlite:///tmp/a.db");
        $dbDrive2 = $this->object->addConnection("sqlite:///tmp/b.db");
        $dbDrive3 = $this->object->addConnection("sqlite:///tmp/a.db");
        $dbDrive4 = $this->object->addConnection("sqlite:///tmp/b.db");
    }

    public function testAddConnection()
    {
        $dbDrive1 = $this->object->addConnection("sqlite:///tmp/a.db");
        $dbDrive2 = $this->object->addConnection("sqlite:///tmp/b.db");
        $this->object->addDbDriver($dbDrive1);
        $this->object->addDbDriver($dbDrive2);

        $this->assertNotSame($dbDrive1, $dbDrive2);

        $this->assertEquals(2, $this->object->count());
    }

    public function testAddDbDriverError()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("The connection already exists with a different instance");

        $dbDrive1 = Factory::getDbInstance("sqlite:///tmp/a.db");
        $dbDrive2 = Factory::getDbInstance("sqlite:///tmp/b.db");
        $dbDrive3 = Factory::getDbInstance("sqlite:///tmp/a.db");
        $dbDrive4 = Factory::getDbInstance("sqlite:///tmp/b.db");

        $this->object->addDbDriver($dbDrive1);
        $this->object->addDbDriver($dbDrive2);
        $this->object->addDbDriver($dbDrive3);
        $this->object->addDbDriver($dbDrive4);
    }

    public function testAddDbDriver()
    {
        $dbDrive1 = Factory::getDbInstance("sqlite:///tmp/a.db");
        $dbDrive2 = Factory::getDbInstance("sqlite:///tmp/b.db");

        $this->object->addDbDriver($dbDrive1);
        $this->object->addDbDriver($dbDrive2);
        $this->object->addDbDriver($dbDrive1);
        $this->object->addDbDriver($dbDrive2);

        $this->assertNotSame($dbDrive1, $dbDrive2);

        $this->assertEquals(2, $this->object->count());
    }

    public function testAddRepository()
    {
        $dbDriver = Factory::getDbInstance("sqlite:///tmp/a.db");

        $dbDriver->execute('create table users (
            id integer primary key  autoincrement,
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
        $this->object->addConnection("sqlite:///tmp/c.db");
        $this->object->addConnection("sqlite:///tmp/d.db");

        $this->object->beginTransaction();
        $this->object->commitTransaction();

        $this->assertEquals(2, $this->object->count());
    }

    public function testBeginTransactionTwice()
    {
        $this->expectException(TransactionException::class);
        $this->expectExceptionMessage("Transaction Already Started");

        $this->object->addConnection("sqlite:///tmp/a.db");
        $this->object->addConnection("sqlite:///tmp/d.db");

        $this->assertEquals(2, $this->object->count());

        $this->object->beginTransaction();
        $this->object->beginTransaction();
    }

    public function testRollbackWithNoTransaction()
    {
        $this->expectException(TransactionException::class);
        $this->expectExceptionMessage("There is no Active Transaction");

        $this->object->addConnection("sqlite:///tmp/c.db");
        $this->assertEquals(1, $this->object->count());
        $this->object->rollbackTransaction();
    }

    public function testCommitWithNoTransaction()
    {
        $this->expectException(TransactionException::class);
        $this->expectExceptionMessage("There is no Active Transaction");

        $this->object->addConnection("sqlite:///tmp/d.db");
        $this->assertEquals(1, $this->object->count());
        $this->object->commitTransaction();
    }

    public function testTransaction()
    {
        $dbDrive1 = Factory::getDbInstance("sqlite:///tmp/a.db");
        $dbDrive2 = Factory::getDbInstance("sqlite:///tmp/b.db");

        $dbDrive1->execute('create table users1 (
            id integer primary key  autoincrement,
            name varchar(45));'
        );
        $dbDrive2->execute('create table users2 (
            id integer primary key  autoincrement,
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
