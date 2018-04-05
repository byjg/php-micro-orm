<?php

namespace Test;

use ByJG\MicroOrm\ConnectionManager;
use PHPUnit\Framework\TestCase;

class ConnectionManagerTest extends TestCase
{
    /**
     * @var ConnectionManager
     */
    protected $object;

    public function setUp()
    {
        $this->object = new ConnectionManager();
    }

    public function tearDown()
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

    public function testAddConnection()
    {
        $dbDrive1 = $this->object->addConnection("sqlite:///tmp/a.db");
        $dbDrive2 = $this->object->addConnection("sqlite:///tmp/b.db");
        $dbDrive3 = $this->object->addConnection("sqlite:///tmp/a.db");
        $dbDrive4 = $this->object->addConnection("sqlite:///tmp/b.db");

        $this->assertSame($dbDrive1, $dbDrive3);
        $this->assertSame($dbDrive2, $dbDrive4);
        $this->assertNotSame($dbDrive1, $dbDrive2);

        $this->assertEquals(2, $this->object->count());
    }

    public function testBeginTransaction()
    {
        $this->object->addConnection("sqlite:///tmp/c.db");
        $this->object->addConnection("sqlite:///tmp/d.db");

        $this->object->beginTransaction();
        $this->object->commitTransaction();

        $this->assertEquals(2, $this->object->count());
    }

    /**
     * @throws \ByJG\MicroOrm\Exception\TransactionException
     * @expectedException \ByJG\MicroOrm\Exception\TransactionException
     */
    public function testBeginTransactionError()
    {
        $this->object->addConnection("sqlite:///tmp/a.db");
        $this->object->addConnection("sqlite:///tmp/d.db");

        $this->assertEquals(2, $this->object->count());

        $this->object->beginTransaction();
        $this->object->beginTransaction();
    }

    /**
     * @throws \ByJG\MicroOrm\Exception\TransactionException
     * @expectedException \ByJG\MicroOrm\Exception\TransactionException
     */
    public function testRollbackTransactionError()
    {
        $this->object->addConnection("sqlite:///tmp/c.db");
        $this->assertEquals(1, $this->object->count());
        $this->object->rollbackTransaction();
    }

    /**
     * @throws \ByJG\MicroOrm\Exception\TransactionException
     * @expectedException \ByJG\MicroOrm\Exception\TransactionException
     */
    public function testCommitTransactionError()
    {
        $this->object->addConnection("sqlite:///tmp/d.db");
        $this->assertEquals(1, $this->object->count());
        $this->object->commitTransaction();
    }
}
