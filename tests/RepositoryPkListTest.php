<?php

namespace Tests;

use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\Repository;
use Override;
use PHPUnit\Framework\TestCase;
use Tests\Model\Items;

class RepositoryPkListTest extends TestCase
{
    /**
     * @var Repository
     */
    protected Repository $repository;

    #[Override]
    public function setUp(): void
    {
        $dbDriver = ConnectionUtil::getConnection("testmicroorm");
        $itemsMapper = new Mapper(Items::class, 'items', ['storeid', 'itemid']);
        $this->repository = new Repository(DatabaseExecutor::using($dbDriver), $itemsMapper);

        $this->repository->getExecutor()->execute('CREATE TABLE items (
            storeid INTEGER,
            itemid INTEGER,
            qty INTEGER, 
            PRIMARY KEY (storeid, itemid)
          );'
        );
        $this->repository->getExecutor()->execute("insert into items (storeid, itemid, qty) values (1, 1, 10)");
        $this->repository->getExecutor()->execute("insert into items (storeid, itemid, qty) values (1, 2, 15)");
        $this->repository->getExecutor()->execute("insert into items (storeid, itemid, qty) values (2, 1, 20)");
    }

    #[Override]
    public function tearDown(): void
    {
        $this->repository->getExecutor()->execute('drop table if exists items;');
    }

    public function testGet()
    {
        $items = $this->repository->get([1, 1]);
        $this->assertEquals(1, $items->getStoreId());
        $this->assertEquals(1, $items->getItemId());
        $this->assertEquals(10, $items->getQty());

        $items = $this->repository->get([1, 2]);
        $this->assertEquals(1, $items->getStoreId());
        $this->assertEquals(2, $items->getItemId());
        $this->assertEquals(15, $items->getQty());

        $items = $this->repository->get([2, 1]);
        $this->assertEquals(2, $items->getStoreId());
        $this->assertEquals(1, $items->getItemId());
        $this->assertEquals(20, $items->getQty());
    }

    public function testUpdate()
    {
        $items = $this->repository->get([1, 1]);
        $items->setQty(100);
        $this->repository->save($items);


        $items = $this->repository->get([1, 1]);
        $this->assertEquals(100, $items->getQty());
    }

    public function testInsert()
    {
        $items = new Items();
        $items->setStoreId(5);
        $items->setItemId(3);
        $items->setQty(200);
        $retItem = $this->repository->save($items);
        $this->assertEquals(5, $retItem->getStoreId());

        $items = $this->repository->get([5, 3]);
        $this->assertEquals(200, $items->getQty());
    }
}
