<?php

namespace Tests;

use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\Repository;
use Override;
use PHPUnit\Framework\TestCase;
use Tests\Model\Items;

class RepositoryPkListTest extends TestCase
{
    /**
     * @var Mapper
     */
    protected $itemsMapper;

    /**
     * @var DbDriverInterface
     */
    protected $dbDriver;

    /**
     * @var Repository
     */
    protected $repository;

    #[Override]
    public function setUp(): void
    {
        $this->dbDriver = ConnectionUtil::getConnection("testmicroorm");

        $this->dbDriver->execute('CREATE TABLE items (
            storeid INTEGER,
            itemid INTEGER,
            qty INTEGER, 
            PRIMARY KEY (storeid, itemid)
          );'
        );
        $this->dbDriver->execute("insert into items (storeid, itemid, qty) values (1, 1, 10)");
        $this->dbDriver->execute("insert into items (storeid, itemid, qty) values (1, 2, 15)");
        $this->dbDriver->execute("insert into items (storeid, itemid, qty) values (2, 1, 20)");

        $this->itemsMapper = new Mapper(Items::class, 'items', ['storeid', 'itemid']);

        $this->repository = new Repository(DatabaseExecutor::using($this->dbDriver), $this->itemsMapper);
    }

    #[Override]
    public function tearDown(): void
    {
        $this->dbDriver->execute('drop table if exists items;');
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

    // public function testGetSelectFunction()
    // {
    //     $this->itemsMapper = new Mapper(Items::class, 'items', 'itemid');
    //     $this->repository = new Repository($this->dbDriver, $this->itemsMapper);

    //     $this->itemsMapper->addFieldMap(
    //         'name',
    //         'name',
    //         null,
    //         function ($value, $instance) {
    //             return '[' . strtoupper($value) . '] - ' . $instance->getCreatedate();
    //         }
    //     );

    //     $this->itemsMapper->addFieldMap(
    //         'year',
    //         'createdate',
    //         null,
    //         function ($value, $instance) {
    //             $date = new \DateTime($value);
    //             return $date->format('Y');
    //         }
    //     );

    //     $items = $this->repository->get(1);
    //     $this->assertEquals(1, $items->getItemId());
    //     $this->assertEquals('[JOHN DOE] - 2015-05-02', $items->getName());
    //     $this->assertEquals('2015-05-02', $items->getCreatedate());
    //     $this->assertEquals('2017', $items->getYear());

    //     $items = $this->repository->get(2);
    //     $this->assertEquals(2, $items->getItemId());
    //     $this->assertEquals('[JANE DOE] - 2017-01-04', $items->getName());
    //     $this->assertEquals('2017-01-04', $items->getCreatedate());
    //     $this->assertEquals('2017', $items->getYear());
    // }

    // public function testInsert()
    // {
    //     $items = new items();
    //     $items->setName('Bla99991919');
    //     $items->setCreatedate('2015-08-09');

    //     $this->assertEquals(null, $items->getId());
    //     $this->repository->save($items);
    //     $this->assertEquals(4, $items->getId());

    //     $items2 = $this->repository->get(4);

    //     $this->assertEquals(4, $items2->getId());
    //     $this->assertEquals('Bla99991919', $items2->getName());
    //     $this->assertEquals('2015-08-09', $items2->getCreatedate());
    // }

    // public function testInsert_beforeInsert()
    // {
    //     $items = new items();
    //     $items->setName('Bla');

    //     $this->repository->setBeforeInsert(function ($instance) {
    //         $instance['name'] .= "-add";
    //         $instance['createdate'] .= "2017-12-21";
    //         return $instance;
    //     });

    //     $this->assertEquals(null, $items->getId());
    //     $this->repository->save($items);
    //     $this->assertEquals(4, $items->getId());

    //     $items2 = $this->repository->get(4);

    //     $this->assertEquals(4, $items2->getId());
    //     $this->assertEquals('Bla-add', $items2->getName());
    //     $this->assertEquals('2017-12-21', $items2->getCreatedate());
    // }

    // public function testInsertLiteral()
    // {
    //     $items = new items();
    //     $items->setName(new Literal("X'6565'"));
    //     $items->setCreatedate('2015-08-09');

    //     $this->assertEquals(null, $items->getId());
    //     $this->repository->save($items);
    //     $this->assertEquals(4, $items->getId());

    //     $items2 = $this->repository->get(4);

    //     $this->assertEquals(4, $items2->getId());
    //     $this->assertEquals('ee', $items2->getName());
    //     $this->assertEquals('2015-08-09', $items2->getCreatedate());
    // }

    // public function testInsertKeyGen()
    // {
    //     $this->infoMapper = new Mapper(
    //         items::class,
    //         'items',
    //         'id',
    //         function () {
    //             return 50;
    //         }
    //     );
    //     $this->repository = new Repository($this->dbDriver, $this->infoMapper);

    //     $items = new items();
    //     $items->setName('Bla99991919');
    //     $items->setCreatedate('2015-08-09');
    //     $this->assertEquals(null, $items->getId());
    //     $this->repository->save($items);
    //     $this->assertEquals(50, $items->getId());

    //     $items2 = $this->repository->get(50);

    //     $this->assertEquals(50, $items2->getId());
    //     $this->assertEquals('Bla99991919', $items2->getName());
    //     $this->assertEquals('2015-08-09', $items2->getCreatedate());
    // }

    // public function testInsertUpdateFunction()
    // {
    //     $this->itemsMapper = new Mapper(itemsMap::class, 'items', 'id');
    //     $this->repository = new Repository($this->dbDriver, $this->itemsMapper);

    //     $this->itemsMapper->addFieldMap(
    //         'name',
    //         'name',
    //         function ($value, $instance) {
    //             return 'Sr. ' . $value . ' - ' . $instance->getCreatedate();
    //         }
    //     );

    //     $this->itemsMapper->addFieldMap(
    //         'year',
    //         'createdate',
    //         Mapper::doNotUpdateClosure(),
    //         function ($value, $instance) {
    //             $date = new \DateTime($value);
    //             return $date->format('Y');
    //         }
    //     );

    //     $items = new itemsMap();
    //     $items->setName('John Doe');
    //     $items->setCreatedate('2015-08-09');
    //     $items->setYear('NOT USED!');

    //     $this->assertEquals(null, $items->getId());
    //     $this->repository->save($items);
    //     $this->assertEquals(4, $items->getId());

    //     $items2 = $this->repository->get(4);

    //     $this->assertEquals(4, $items2->getId());
    //     $this->assertEquals('2015-08-09', $items2->getCreatedate());
    //     $this->assertEquals('2015', $items2->getYear());
    //     $this->assertEquals('Sr. John Doe - 2015-08-09', $items2->getName());
    // }

    // public function testUpdate()
    // {
    //     $items = $this->repository->get(1);

    //     $items->setName('New Name');
    //     $items->setCreatedate('2016-01-09');
    //     $this->repository->save($items);

    //     $items2 = $this->repository->get(1);
    //     $this->assertEquals(1, $items2->getId());
    //     $this->assertEquals('New Name', $items2->getName());
    //     $this->assertEquals('2016-01-09', $items2->getCreatedate());

    //     $items2 = $this->repository->get(2);
    //     $this->assertEquals(2, $items2->getId());
    //     $this->assertEquals('Jane Doe', $items2->getName());
    //     $this->assertEquals('2017-01-04', $items2->getCreatedate());
    // }

    // public function testUpdate_beforeUpdate()
    // {
    //     $items = $this->repository->get(1);

    //     $items->setName('New Name');

    //     $this->repository->setBeforeUpdate(function ($instance) {
    //         $instance['name'] .= "-upd";
    //         $instance['createdate'] = "2017-12-21";
    //         return $instance;
    //     });

    //     $this->repository->save($items);

    //     $items2 = $this->repository->get(1);
    //     $this->assertEquals(1, $items2->getId());
    //     $this->assertEquals('New Name-upd', $items2->getName());
    //     $this->assertEquals('2017-12-21', $items2->getCreatedate());

    //     $items2 = $this->repository->get(2);
    //     $this->assertEquals(2, $items2->getId());
    //     $this->assertEquals('Jane Doe', $items2->getName());
    //     $this->assertEquals('2017-01-04', $items2->getCreatedate());
    // }

    // public function testUpdateLiteral()
    // {
    //     $items = $this->repository->get(1);
    //     $items->setName(new Literal("X'6565'"));
    //     $this->repository->save($items);

    //     $items2 = $this->repository->get(1);

    //     $this->assertEquals(1, $items2->getId());
    //     $this->assertEquals('ee', $items2->getName());
    //     $this->assertEquals('2015-05-02', $items2->getCreatedate());
    // }

    // public function testUpdateFunction()
    // {
    //     $this->itemsMapper = new Mapper(itemsMap::class, 'items', 'id');
    //     $this->repository = new Repository($this->dbDriver, $this->itemsMapper);

    //     $this->itemsMapper->addFieldMap(
    //         'name',
    //         'name',
    //         function ($value, $instance) {
    //             return 'Sr. ' . $value;
    //         }
    //     );

    //     $this->itemsMapper->addFieldMap(
    //         'year',
    //         'createdate',
    //         Mapper::doNotUpdateClosure(),
    //         function ($value, $instance) {
    //             $date = new \DateTime($value);
    //             return $date->format('Y');
    //         }
    //     );

    //     $items = $this->repository->get(1);

    //     $items->setName('New Name');
    //     $items->setCreatedate('2016-01-09');
    //     $this->repository->save($items);

    //     $items2 = $this->repository->get(1);
    //     $this->assertEquals(1, $items2->getId());
    //     $this->assertEquals('Sr. New Name', $items2->getName());
    //     $this->assertEquals('2016-01-09', $items2->getCreatedate());
    //     $this->assertEquals('2016', $items2->getYear());

    //     $items2 = $this->repository->get(2);
    //     $this->assertEquals(2, $items2->getId());
    //     $this->assertEquals('Jane Doe', $items2->getName());
    //     $this->assertEquals('2017-01-04', $items2->getCreatedate());
    //     $this->assertEquals('2017', $items2->getYear());
    // }


    // public function testDelete()
    // {
    //     $this->repository->delete(1);
    //     $this->assertEmpty($this->repository->get(1));

    //     $items = $this->repository->get(2);
    //     $this->assertEquals(2, $items->getId());
    //     $this->assertEquals('Jane Doe', $items->getName());
    //     $this->assertEquals('2017-01-04', $items->getCreatedate());
    // }

    // public function testDeleteLiteral()
    // {
    //     $this->repository->delete(new Literal(1));
    //     $this->assertEmpty($this->repository->get(1));

    //     $items = $this->repository->get(2);
    //     $this->assertEquals(2, $items->getId());
    //     $this->assertEquals('Jane Doe', $items->getName());
    //     $this->assertEquals('2017-01-04', $items->getCreatedate());
    // }

    // public function testDelete2()
    // {
    //     $query = Updatable::getInstance()
    //         ->table($this->itemsMapper->getTable())
    //         ->where('name like :name', ['name'=>'Jane%']);

    //     $this->repository->deleteByQuery($query);

    //     $items = $this->repository->get(1);
    //     $this->assertEquals(1, $items->getId());
    //     $this->assertEquals('John Doe', $items->getName());
    //     $this->assertEquals('2015-05-02', $items->getCreatedate());

    //     $items = $this->repository->get(2);
    //     $this->assertEmpty($items);
    // }

    // public function testGetByQueryNone()
    // {
    //     $query = new Query();
    //     $query->table($this->infoMapper->getTable())
    //         ->where('iduser = :id', ['id'=>1000])
    //         ->orderBy(['property']);

    //     $infoRepository = new Repository($this->dbDriver, $this->infoMapper);
    //     $result = $infoRepository->getByQuery($query);

    //     $this->assertEquals(count($result), 0);
    // }

    // public function testGetByQueryOne()
    // {
    //     $query = new Query();
    //     $query->table($this->infoMapper->getTable())
    //         ->where('iduser = :id', ['id'=>3])
    //         ->orderBy(['property']);

    //     $infoRepository = new Repository($this->dbDriver, $this->infoMapper);
    //     $result = $infoRepository->getByQuery($query);

    //     $this->assertEquals(count($result), 1);

    //     $this->assertEquals(3, $result[0]->getId());
    //     $this->assertEquals(3, $result[0]->getIduser());
    //     $this->assertEquals('bbb', $result[0]->getValue());
    // }

    // public function testFilterInNone()
    // {
    //     $result = $this->repository->filterIn([1000, 1002]);

    //     $this->assertEquals(count($result), 0);
    // }

    // public function testFilterInOne()
    // {
    //     $result = $this->repository->filterIn(2);

    //     $this->assertEquals(count($result), 1);

    //     $this->assertEquals(2, $result[0]->getId());
    //     $this->assertEquals('Jane Doe', $result[0]->getName());
    //     $this->assertEquals('2017-01-04', $result[0]->getCreatedate());
    // }

    // public function testFilterInTwo()
    // {
    //     $result = $this->repository->filterIn([2, 3, 1000, 1001]);

    //     $this->assertEquals(count($result), 2);

    //     $this->assertEquals(2, $result[0]->getId());
    //     $this->assertEquals('Jane Doe', $result[0]->getName());
    //     $this->assertEquals('2017-01-04', $result[0]->getCreatedate());

    //     $this->assertEquals(3, $result[1]->getId());
    //     $this->assertEquals('JG', $result[1]->getName());
    //     $this->assertEquals('1974-01-26', $result[1]->getCreatedate());
    // }

    // /**
    //  * @throws \Exception
    //  */
    // public function testGetScalar()
    // {
    //     $query = new Query();
    //     $query->table($this->infoMapper->getTable())
    //         ->fields(['property'])
    //         ->where('iduser = :id', ['id'=>3]);

    //     $infoRepository = new Repository($this->dbDriver, $this->infoMapper);
    //     $result = $infoRepository->getScalar($query);

    //     $this->assertEquals('bbb', $result);
    // }

    // public function testGetByQueryMoreThanOne()
    // {
    //     $query = new Query();
    //     $query->table($this->infoMapper->getTable())
    //         ->where('iduser = :id', ['id'=>1])
    //         ->orderBy(['property']);

    //     $infoRepository = new Repository($this->dbDriver, $this->infoMapper);
    //     $result = $infoRepository->getByQuery($query);

    //     $this->assertEquals(2, $result[0]->getId());
    //     $this->assertEquals(1, $result[0]->getIduser());
    //     $this->assertEquals('ggg', $result[0]->getValue());

    //     $this->assertEquals(1, $result[1]->getId());
    //     $this->assertEquals(1, $result[1]->getIduser());
    //     $this->assertEquals('xxx', $result[1]->getValue());
    // }

    // public function testJoin()
    // {
    //     $query = new Query();
    //     $query->table($this->itemsMapper->getTable())
    //         ->fields([
    //             'items.id',
    //             'items.name',
    //             'items.createdate',
    //             'info.property'
    //         ])
    //         ->join($this->infoMapper->getTable(), 'items.id = info.iduser')
    //         ->where('items.id = :id', ['id'=>1])
    //         ->orderBy(['items.id']);

    //     $result = $this->repository->getByQuery($query, [$this->infoMapper]);

    //     $this->assertEquals(1, $result[0][0]->getId());
    //     $this->assertEquals('John Doe', $result[0][0]->getName());
    //     $this->assertEquals('2015-05-02', $result[0][0]->getCreatedate());

    //     $this->assertEquals(1, $result[1][0]->getId());
    //     $this->assertEquals('John Doe', $result[1][0]->getName());
    //     $this->assertEquals('2015-05-02', $result[1][0]->getCreatedate());

    //     // - ------------------

    //     $this->assertEmpty($result[0][1]->getIduser());
    //     $this->assertEquals('xxx', $result[0][1]->getValue());

    //     $this->assertEmpty($result[1][1]->getIduser());
    //     $this->assertEquals('ggg', $result[1][1]->getValue());

    // }

    // public function testLeftJoin()
    // {
    //     $query = new Query();
    //     $query->table($this->itemsMapper->getTable())
    //         ->fields([
    //             'items.id',
    //             'items.name',
    //             'items.createdate',
    //             'info.property'
    //         ])
    //         ->leftJoin($this->infoMapper->getTable(), 'items.id = info.iduser')
    //         ->where('items.id = :id', ['id'=>2])
    //         ->orderBy(['items.id']);

    //     $result = $this->repository->getByQuery($query, [$this->infoMapper]);

    //     $this->assertEquals(2, $result[0][0]->getId());
    //     $this->assertEquals('Jane Doe', $result[0][0]->getName());
    //     $this->assertEquals('2017-01-04', $result[0][0]->getCreatedate());

    //     // - ------------------

    //     $this->assertEmpty($result[0][1]->getIduser());
    //     $this->assertEmpty($result[0][1]->getValue());
    // }

    // public function testTop()
    // {
    //     $query = Query::getInstance()
    //         ->table($this->itemsMapper->getTable())
    //         ->top(1);

    //     $result = $this->repository->getByQuery($query);

    //     $this->assertEquals(1, $result[0]->getId());
    //     $this->assertEquals('John Doe', $result[0]->getName());
    //     $this->assertEquals('2015-05-02', $result[0]->getCreatedate());

    //     $this->assertEquals(1, count($result));
    // }

    // public function testLimit()
    // {
    //     $query = Query::getInstance()
    //         ->table($this->itemsMapper->getTable())
    //         ->limit(1, 1);

    //     $result = $this->repository->getByQuery($query);

    //     $this->assertEquals(2, $result[0]->getId());
    //     $this->assertEquals('Jane Doe', $result[0]->getName());
    //     $this->assertEquals('2017-01-04', $result[0]->getCreatedate());

    //     $this->assertEquals(1, count($result));
    // }

    // public function testQueryRaw()
    // {
    //     $query = Query::getInstance()
    //         ->fields([
    //             "name",
    //             "julianday('2020-06-28') - julianday(createdate) as days"
    //         ])
    //         ->table($this->itemsMapper->getTable())
    //         ->limit(1, 1);

    //     $result = $this->repository->getByQuery($query);

    //     $this->assertEquals(null, $result[0]->getId());
    //     $this->assertEquals('Jane Doe', $result[0]->getName());
    //     $this->assertEquals(null, $result[0]->getCreatedate());

    //     $this->assertEquals(1, count($result));

    //     $result = $this->repository->getByQueryRaw($query);
    //     $this->assertEquals([
    //         [
    //             "name" => "Jane Doe",
    //             "days" => 1271.0
    //         ]
    //     ], $result);
    // }

}
