<?php

namespace Tests;

use ByJG\AnyDataset\Core\Enum\Relation;
use ByJG\AnyDataset\Core\IteratorFilter;
use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\AnyDataset\Db\Factory;
use ByJG\AnyDataset\Db\SqlStatement;
use ByJG\Cache\Psr16\ArrayCacheEngine;
use ByJG\MicroOrm\CacheQueryResult;
use ByJG\MicroOrm\Constraint\CustomConstraint;
use ByJG\MicroOrm\Constraint\RequireChangedValuesConstraint;
use ByJG\MicroOrm\DeleteQuery;
use ByJG\MicroOrm\Enum\ObserverEvent;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Exception\OrmInvalidFieldsException;
use ByJG\MicroOrm\Exception\RepositoryReadOnlyException;
use ByJG\MicroOrm\Exception\RequireChangedValuesConstraintException;
use ByJG\MicroOrm\Exception\UpdateConstraintException;
use ByJG\MicroOrm\FieldMapping;
use ByJG\MicroOrm\InsertBulkQuery;
use ByJG\MicroOrm\InsertQuery;
use ByJG\MicroOrm\Interface\ObserverProcessorInterface;
use ByJG\MicroOrm\Literal\Literal;
use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\MapperFunctions;
use ByJG\MicroOrm\ObserverData;
use ByJG\MicroOrm\ORM;
use ByJG\MicroOrm\ORMSubject;
use ByJG\MicroOrm\Query;
use ByJG\MicroOrm\Repository;
use ByJG\MicroOrm\Union;
use ByJG\MicroOrm\UpdateQuery;
use ByJG\Util\Uri;
use DateTime;
use Exception;
use Override;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Tests\Model\ActiveRecordModel;
use Tests\Model\Info;
use Tests\Model\ModelWithAttributes;
use Tests\Model\Users;
use Tests\Model\UsersMap;
use Tests\Model\UsersWithAttribute;
use Throwable;

class RepositoryTest extends TestCase
{

    const URI='sqlite:///tmp/test.db';

    /**
     * @var Mapper
     */
    protected $userMapper;

    /**
     * @var Mapper
     */
    protected $infoMapper;

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
        $this->dbDriver = Factory::getDbInstance(self::URI);

        $this->dbDriver->execute('create table users (
            id integer primary key  autoincrement,
            name varchar(45),
            createdate datetime);'
        );
        $insertBulk = InsertBulkQuery::getInstance('users', ['name', 'createdate']);
        $insertBulk->values(['name' => 'John Doe', 'createdate' => '2015-05-02']);
        $insertBulk->values(['name' => 'Jane Doe', 'createdate' => '2017-01-04']);
        $insertBulk->values(['name' => 'JG', 'createdate' => '1974-01-26']);
        $insertBulk->buildAndExecute($this->dbDriver);
        $this->userMapper = new Mapper(Users::class, 'users', 'Id');


        $this->dbDriver->execute('create table info (
            id integer primary key  autoincrement,
            iduser INTEGER,
            property number(10,2),
            created_at datetime,
            updated_at datetime,
            deleted_at datetime);'
        );
        $insertMultiple = InsertBulkQuery::getInstance('info', ['iduser', 'property']);
        $insertMultiple->values(['iduser' => 1, 'property' => 30.4]);
        $insertMultiple->values(['iduser' => 1, 'property' => 1250.96]);
        $insertMultiple->values(['iduser' => 3, 'property' => 3.5]);
        $insertMultiple->buildAndExecute($this->dbDriver);
        $this->infoMapper = new Mapper(Info::class, 'info', 'id');
        $this->infoMapper->addFieldMapping(FieldMapping::create('value')->withFieldName('property'));

        $this->repository = new Repository($this->dbDriver, $this->userMapper);
        ORMSubject::getInstance()->clearObservers();
    }

    #[Override]
    public function tearDown(): void
    {
        $uri = new Uri(self::URI);
        unlink($uri->getPath());
        ORM::clearRelationships();
    }

    public function testGet()
    {
        $users = $this->repository->get(1);
        $this->assertEquals(1, $users->getId());
        $this->assertEquals('John Doe', $users->getName());
        $this->assertEquals('2015-05-02', $users->getCreatedate());

        $users = $this->repository->get("2");
        $this->assertEquals(2, $users->getId());
        $this->assertEquals('Jane Doe', $users->getName());
        $this->assertEquals('2017-01-04', $users->getCreatedate());

        $users = $this->repository->get(new Literal(1));
        $this->assertEquals(1, $users->getId());
        $this->assertEquals('John Doe', $users->getName());
        $this->assertEquals('2015-05-02', $users->getCreatedate());
    }

    public function testGetByFilter()
    {
        $users = $this->repository->getByFilter('id = :id', ['id' => 1]);
        $this->assertCount(1, $users);
        $this->assertEquals(1, $users[0]->getId());
        $this->assertEquals('John Doe', $users[0]->getName());
        $this->assertEquals('2015-05-02', $users[0]->getCreatedate());

        $filter = new IteratorFilter();
        $filter->and('id', Relation::EQUAL, 2);
        $users = $this->repository->getByFilter($filter);
        $this->assertCount(1, $users);
        $this->assertEquals(2, $users[0]->getId());
        $this->assertEquals('Jane Doe', $users[0]->getName());
        $this->assertEquals('2017-01-04', $users[0]->getCreatedate());
    }

    public function testGetSelectFunction()
    {
        $this->userMapper = new Mapper(UsersMap::class, 'users', 'id');
        $this->repository = new Repository($this->dbDriver, $this->userMapper);

        $this->userMapper->addFieldMapping(FieldMapping::create('name')
            ->withSelectFunction(function ($value, $instance) {
                if (empty($value)) {
                    return null;
                }
                return '[' . strtoupper($value) . '] - ' . $instance["createdate"];
            })
        );

        $this->userMapper->addFieldMapping(FieldMapping::create('year')
            ->withSelectFunction(function ($value, $instance) {
                if (empty($instance["createdate"])) {
                    return null;
                }
                $date = new DateTime($instance["createdate"]);
                return intval($date->format('Y'));
            })
        );

        $users = $this->repository->get(1);
        $this->assertEquals(1, $users->getId());
        $this->assertEquals('[JOHN DOE] - 2015-05-02', $users->getName());
        $this->assertEquals('2015-05-02', $users->getCreatedate());
        $this->assertEquals(2015, $users->getYear());

        $users = $this->repository->get(2);
        $this->assertEquals(2, $users->getId());
        $this->assertEquals('[JANE DOE] - 2017-01-04', $users->getName());
        $this->assertEquals('2017-01-04', $users->getCreatedate());
        $this->assertEquals(2017, $users->getYear());
    }

    public function testBuildAndGetIterator()
    {
        $query = Query::getInstance()
            ->table('users')
            ->where('id = :id', ['id' => 1]);

        $iterator = $query->buildAndGetIterator($this->repository->getDbDriver(), null)->toArray();
        $this->assertEquals(1, count($iterator));
    }

    public function testBuildAndGetIteratorWithCache()
    {
        $query = Query::getInstance()
            ->table('users')
            ->where('id = :id', ['id' => 1]);

        $cacheEngine = new ArrayCacheEngine();

        // Sanity test
        $result = $query->buildAndGetIterator($this->repository->getDbDriver())->toArray();
        $this->assertCount(1, $result);

        $cacheObject = new CacheQueryResult($cacheEngine, "mykey", 120);

        // Get the result and save to cache
        $result = $query->buildAndGetIterator($this->repository->getDbDriver(), cache: $cacheObject)->toArray();
        $this->assertCount(1, $result);

        // Delete the record
        $deleteQuery = DeleteQuery::getInstance()
            ->table('users')
            ->where('id = :id', ['id' => 1]);
        $deleteQuery->buildAndExecute($this->repository->getDbDriver());

        // Check if query with no cache the record is not found
        $result = $query->buildAndGetIterator($this->repository->getDbDriver())->toArray();
        $this->assertCount(0, $result);

        // Check if query with cache the record is found
        $result = $query->buildAndGetIterator($this->repository->getDbDriver(), cache: $cacheObject)->toArray();
        $this->assertCount(1, $result);
    }


    public function testInsert()
    {
        $users = new Users();
        $users->setName('Bla99991919');
        $users->setCreatedate('2015-08-09');

        $this->assertEquals(null, $users->getId());
        $this->repository->save($users);
        $this->assertEquals(4, $users->getId());

        $users2 = $this->repository->get(4);

        $this->assertEquals(4, $users2->getId());
        $this->assertEquals('Bla99991919', $users2->getName());
        $this->assertEquals('2015-08-09', $users2->getCreatedate());
    }

    public function testInsertReadOnly()
    {
        $this->expectException(RepositoryReadOnlyException::class);

        $users = new Users();
        $users->setName('Bla99991919');
        $users->setCreatedate('2015-08-09');

        $this->assertEquals(null, $users->getId());
        $this->repository->setRepositoryReadOnly();
        $this->repository->save($users);
    }

    public function testInsert_beforeInsert()
    {
        $users = new Users();
        $users->setName('Bla');

        $this->repository->setBeforeInsert(function ($instance) {
            $instance['name'] .= "-add";
            $instance['createdate'] .= "2017-12-21";
            return $instance;
        });

        $this->assertEquals(null, $users->getId());
        $this->repository->save($users);
        $this->assertEquals(4, $users->getId());

        $users2 = $this->repository->get(4);

        $this->assertEquals(4, $users2->getId());
        $this->assertEquals('Bla-add', $users2->getName());
        $this->assertEquals('2017-12-21', $users2->getCreatedate());
    }

    public function testInsertLiteral()
    {
        /** @var Users $users */
        $users = $this->repository->entity([
            "name" => new Literal("X'6565'"),
            "createdate" => '2015-08-09'
        ]);

        $this->assertEquals(null, $users->getId());
        $this->repository->save($users);

        $users2 = $this->repository->get(4);

        $this->assertEquals(4, $users2->getId());
        $this->assertEquals('ee', $users2->getName());
        $this->assertEquals('2015-08-09', $users2->getCreatedate());

        $this->assertEquals($users2->getId(), $users->getId());
        $this->assertEquals($users2->getCreatedate(), $users->getCreatedate());
    }

    public function testInsertFromObject()
    {
        $insertQuery = InsertQuery::getInstance(
            $this->userMapper->getTable(),
            [
                "name" => new Literal("X'6565'"),
                "createdate" => '2015-08-09'
            ]
        );

        $sqlStatement = $insertQuery->build();

        $this->assertEquals(
            new SqlStatement("INSERT INTO users( name, createdate )  values ( X'6565', :createdate ) ", ["createdate" => "2015-08-09"]),
            $sqlStatement
        );

        $this->repository->getDbDriverWrite()->execute($sqlStatement);

        $users2 = $this->repository->get(4);

        $this->assertEquals(4, $users2->getId());
        $this->assertEquals('ee', $users2->getName());
        $this->assertEquals('2015-08-09', $users2->getCreatedate());
    }

    public function testInsertKeyGen()
    {
//        $this->infoMapper = new Mapper(
//            Users::class,
//            'users',
//            'id'
//        );
//        $this->infoMapper->withPrimaryKeySeedFunction(function ($instance) {
//            return 50;
//        });
        $this->repository = new Repository($this->dbDriver, UsersWithAttribute::class);

        $users = new Users();
        $users->setName('Bla99991919');
        $users->setCreatedate('2015-08-09');
        $this->assertEquals(null, $users->getId());
        $this->repository->save($users);
        $this->assertEquals(50, $users->getId());

        $users2 = $this->repository->get(50);

        $this->assertEquals(50, $users2->getId());
        $this->assertEquals('Bla99991919', $users2->getName());
        $this->assertEquals('2015-08-09', $users2->getCreatedate());
    }

    public function testInsertUpdateFunction()
    {
        $this->userMapper = new Mapper(UsersMap::class, 'users', 'id');
        $this->repository = new Repository($this->dbDriver, $this->userMapper);

        $this->userMapper->addFieldMapping(FieldMapping::create('name')
            ->withUpdateFunction(function ($value, $instance) {
                return 'Sr. ' . $value . ' - ' . $instance->getCreatedate();
            })
        );

        $this->userMapper->addFieldMapping(FieldMapping::create('year')
            ->withUpdateFunction(MapperFunctions::READ_ONLY)
            ->withSelectFunction(function ($value, $instance) {
                if (empty($instance["createdate"])) {
                    return null;
                }
                $date = new DateTime($instance["createdate"]);
                return intval($date->format('Y'));
            })
        );

        $users = new UsersMap();
        $users->setName('John Doe');
        $users->setCreatedate('2015-08-09');
        $users->setYear('NOT USED!');

        $this->assertEquals(null, $users->getId());
        $this->repository->save($users);
        $this->assertEquals(4, $users->getId());

        $users2 = $this->repository->get(4);

        $this->assertEquals(4, $users2->getId());
        $this->assertEquals('2015-08-09', $users2->getCreatedate());
        $this->assertEquals(2015, $users2->getYear());
        $this->assertEquals('Sr. John Doe - 2015-08-09', $users2->getName());
    }

    public function testUpdate()
    {
        $users = $this->repository->get(1);

        $users->setName('New Name');
        $users->setCreatedate('2016-01-09');
        $this->repository->save($users);

        $users2 = $this->repository->get(1);
        $this->assertEquals(1, $users2->getId());
        $this->assertEquals('New Name', $users2->getName());
        $this->assertEquals('2016-01-09', $users2->getCreatedate());

        $users2 = $this->repository->get(2);
        $this->assertEquals(2, $users2->getId());
        $this->assertEquals('Jane Doe', $users2->getName());
        $this->assertEquals('2017-01-04', $users2->getCreatedate());
    }

    public function testUpdateReadOnly()
    {
        $this->expectException(RepositoryReadOnlyException::class);

        $users = $this->repository->get(1);

        $users->setName('New Name');
        $users->setCreatedate('2016-01-09');
        $this->repository->setRepositoryReadOnly();
        $this->repository->save($users);
    }

    public function testUpdateBuildAndExecute()
    {
        $users = $this->repository->get(1);

        $this->assertEquals('John Doe', $users->getName());

        $updateQuery = UpdateQuery::getInstance()
            ->table('users')
            ->set('name', 'New Name')
            ->where('id = :id', ['id' => 1]);
        $updateQuery->buildAndExecute($this->repository->getDbDriver());

        $users = $this->repository->get(1);
        $this->assertEquals('New Name', $users->getName());
    }

    public function testInsertBuildAndExecute()
    {
        $users = $this->repository->get(4);
        $this->assertEmpty($users);

        $insertQuery = InsertQuery::getInstance()
            ->table('users')
            ->defineFields([
                'name',
                'createdate'
            ]);
        $insertQuery->buildAndExecute($this->repository->getDbDriver(), ['name' => 'inserted name', 'createdate' => '2024-09-03']);

        $users = $this->repository->get(4);
        $this->assertEquals('inserted name', $users->getName());
        $this->assertEquals('2024-09-03', $users->getCreatedate());
    }

    public function testInsertBuildAndExecute2()
    {
        $users = $this->repository->get(4);
        $this->assertEmpty($users);

        $insertQuery = InsertQuery::getInstance()
            ->table('users')
            ->set('name', 'inserted name')
            ->set('createdate', '2024-09-03')
        ;
        $insertQuery->buildAndExecute($this->repository->getDbDriver());

        $users = $this->repository->get(4);
        $this->assertEquals('inserted name', $users->getName());
        $this->assertEquals('2024-09-03', $users->getCreatedate());
    }

    public function testDeleteBuildAndExecute()
    {
        $users = $this->repository->get(1);
        $this->assertEquals('John Doe', $users->getName());

        $updateQuery = DeleteQuery::getInstance()
            ->table('users')
            ->where('id = :id', ['id' => 1]);
        $updateQuery->buildAndExecute($this->repository->getDbDriver());

        $users = $this->repository->get(1);
        $this->assertEmpty($users);
    }

    public function testUpdate_beforeUpdate()
    {
        $users = $this->repository->get(1);

        $users->setName('New Name');

        $this->repository->setBeforeUpdate(function ($instance) {
            $instance['name'] .= "-upd";
            $instance['createdate'] = "2017-12-21";
            return $instance;
        });

        $this->repository->save($users);

        $users2 = $this->repository->get(1);
        $this->assertEquals(1, $users2->getId());
        $this->assertEquals('New Name-upd', $users2->getName());
        $this->assertEquals('2017-12-21', $users2->getCreatedate());

        $users2 = $this->repository->get(2);
        $this->assertEquals(2, $users2->getId());
        $this->assertEquals('Jane Doe', $users2->getName());
        $this->assertEquals('2017-01-04', $users2->getCreatedate());
    }

    public function testUpdateLiteral()
    {
        $users = $this->repository->get(1);
        $users->setName(new Literal("X'6565'"));
        $this->repository->save($users);

        $users2 = $this->repository->get(1);

        $this->assertEquals(1, $users2->getId());
        $this->assertEquals('ee', $users2->getName());
        $this->assertEquals('2015-05-02', $users2->getCreatedate());
    }

    public function testUpdateObject()
    {
        $updateQuery = UpdateQuery::getInstance(
            [
                "id" => 1,
                "name" => new Literal("X'6565'"),
                "createdate" => '2020-01-02'
            ],
            $this->userMapper,
        );

        $sqlStatement = $updateQuery->build();
        $this->assertEquals(
            new SqlStatement("UPDATE users SET name = X'6565' , createdate = :createdate  WHERE id = :pkid", ["createdate" => "2020-01-02", "pkid" => 1]),
            $sqlStatement
        );

        $this->repository->getDbDriverWrite()->execute($sqlStatement);

        $users2 = $this->repository->get(1);

        $this->assertEquals(1, $users2->getId());
        $this->assertEquals('ee', $users2->getName());
        $this->assertEquals('2020-01-02', $users2->getCreatedate());
    }


    public function testUpdateFunction()
    {
        $this->userMapper = new Mapper(UsersMap::class, 'users', 'id');
        $this->repository = new Repository($this->dbDriver, $this->userMapper);

        $this->userMapper->addFieldMapping(FieldMapping::create('name')
            ->withUpdateFunction(function ($value, $instance) {
                return 'Sr. ' . $value;
            })
        );

        $this->userMapper->addFieldMapping(FieldMapping::create('year')
            ->withUpdateFunction(MapperFunctions::READ_ONLY)
            ->withSelectFunction(function ($value, $instance) {
                if (empty($instance["createdate"])) {
                    return null;
                }
                $date = new DateTime($instance["createdate"]);
                return intval($date->format('Y'));
            })
        );

        $users = $this->repository->get(1);

        $users->setName('New Name');
        $users->setCreatedate('2016-01-09');
        $this->repository->save($users);

        $users2 = $this->repository->get(1);
        $this->assertEquals(1, $users2->getId());
        $this->assertEquals('Sr. New Name', $users2->getName());
        $this->assertEquals('2016-01-09', $users2->getCreatedate());
        $this->assertEquals(2016, $users2->getYear());

        $users2 = $this->repository->get(2);
        $this->assertEquals(2, $users2->getId());
        $this->assertEquals('Jane Doe', $users2->getName());
        $this->assertEquals('2017-01-04', $users2->getCreatedate());
        $this->assertEquals(2017, $users2->getYear());
    }


    public function testDelete()
    {
        $this->repository->delete(1);
        $this->assertEmpty($this->repository->get(1));

        $users = $this->repository->get(2);
        $this->assertEquals(2, $users->getId());
        $this->assertEquals('Jane Doe', $users->getName());
        $this->assertEquals('2017-01-04', $users->getCreatedate());
    }

    public function testDeleteReadOnly()
    {
        $this->expectException(RepositoryReadOnlyException::class);
        $this->repository->setRepositoryReadOnly();
        $this->repository->delete(1);
    }

    public function testDeleteLiteral()
    {
        $this->repository->delete(new Literal(1));
        $this->assertEmpty($this->repository->get(1));

        $users = $this->repository->get(2);
        $this->assertEquals(2, $users->getId());
        $this->assertEquals('Jane Doe', $users->getName());
        $this->assertEquals('2017-01-04', $users->getCreatedate());
    }

    public function testDelete2()
    {
        $query = DeleteQuery::getInstance()
            ->table($this->userMapper->getTable())
            ->where('name like :name', ['name'=>'Jane%']);

        $this->repository->deleteByQuery($query);

        $users = $this->repository->get(1);
        $this->assertEquals(1, $users->getId());
        $this->assertEquals('John Doe', $users->getName());
        $this->assertEquals('2015-05-02', $users->getCreatedate());

        $users = $this->repository->get(2);
        $this->assertEmpty($users);
    }

    public function testGetByQueryNone()
    {
        $query = $this->infoMapper->getQuery()
            ->where('iduser = :id', ['id'=>1000])
            ->orderBy(['property']);

        $infoRepository = new Repository($this->dbDriver, $this->infoMapper);
        $result = $infoRepository->getByQuery($query);

        $this->assertEquals(count($result), 0);
    }

    public function testGetByQueryOne()
    {
        $query = $this->infoMapper->getQuery()
            ->where('iduser = :id', ['id'=>3])
            ->orderBy(['property']);

        $infoRepository = new Repository($this->dbDriver, $this->infoMapper);
        $result = $infoRepository->getByQuery($query);

        $this->assertEquals(count($result), 1);

        $this->assertEquals(3, $result[0]->getId());
        $this->assertEquals(3, $result[0]->getIduser());
        $this->assertEquals(3.5, $result[0]->getValue());

        // Set Zero
        $result[0]->setValue(0);
        $infoRepository->save($result[0]);

        $result = $infoRepository->getByQuery($query);
        $this->assertSame('0', (string)$result[0]->getValue());

        // Set Null
        $result[0]->setValue(null);
        $infoRepository->save($result[0]);

        $result = $infoRepository->getByQuery($query);
        $this->assertNull($result[0]->getValue());
    }

    public function testFilterInNone()
    {
        $result = $this->repository->filterIn([1000, 1002]);

        $this->assertEquals(count($result), 0);
    }

    public function testFilterInOne()
    {
        $result = $this->repository->filterIn(2);

        $this->assertEquals(count($result), 1);

        $this->assertEquals(2, $result[0]->getId());
        $this->assertEquals('Jane Doe', $result[0]->getName());
        $this->assertEquals('2017-01-04', $result[0]->getCreatedate());
    }

    public function testFilterInTwo()
    {
        $result = $this->repository->filterIn([2, 3, 1000, 1001]);

        $this->assertEquals(count($result), 2);

        $this->assertEquals(2, $result[0]->getId());
        $this->assertEquals('Jane Doe', $result[0]->getName());
        $this->assertEquals('2017-01-04', $result[0]->getCreatedate());

        $this->assertEquals(3, $result[1]->getId());
        $this->assertEquals('JG', $result[1]->getName());
        $this->assertEquals('1974-01-26', $result[1]->getCreatedate());
    }

    /**
     * @throws Exception
     */
    public function testGetScalar()
    {
        $query = $this->infoMapper->getQuery()
            ->fields(['property'])
            ->where('iduser = :id', ['id'=>3]);

        $infoRepository = new Repository($this->dbDriver, $this->infoMapper);
        $result = $infoRepository->getScalar($query);

        $this->assertEquals(3.5, $result);
    }

    public function testGetByQueryMoreThanOne()
    {
        $query = $this->infoMapper->getQuery()
            ->where('iduser = :id', ['id'=>1])
            ->orderBy(['property']);

        $infoRepository = new Repository($this->dbDriver, $this->infoMapper);
        $result = $infoRepository->getByQuery($query);

        $this->assertEquals(1, $result[0]->getId());
        $this->assertEquals(1, $result[0]->getIduser());
        $this->assertEquals(30.4, $result[0]->getValue());

        $this->assertEquals(2, $result[1]->getId());
        $this->assertEquals(1, $result[1]->getIduser());
        $this->assertEquals(1250.96, $result[1]->getValue());
    }

    public function testJoin()
    {
        $query = $this->userMapper->getQuery()
            ->fields([
                'users.id',
                'users.name',
                'users.createdate',
                'info.property'
            ])
            ->join($this->infoMapper->getTable(), 'users.id = info.iduser')
            ->where('users.id = :id', ['id'=>1])
            ->orderBy(['users.id']);

        $result = $this->repository->getByQuery($query, [$this->infoMapper]);

        $this->assertEquals(1, $result[0][0]->getId());
        $this->assertEquals('John Doe', $result[0][0]->getName());
        $this->assertEquals('2015-05-02', $result[0][0]->getCreatedate());

        $this->assertEquals(1, $result[1][0]->getId());
        $this->assertEquals('John Doe', $result[1][0]->getName());
        $this->assertEquals('2015-05-02', $result[1][0]->getCreatedate());

        // - ------------------

        $this->assertEmpty($result[0][1]->getIduser());
        $this->assertEquals(30.4, $result[0][1]->getValue());

        $this->assertEmpty($result[1][1]->getIduser());
        $this->assertEquals(1250.96, $result[1][1]->getValue());

    }

    public function testLeftJoin()
    {
        $query = $this->userMapper->getQuery()
            ->fields([
                'users.id',
                'users.name',
                'users.createdate',
                'info.property'
            ])
            ->leftJoin($this->infoMapper->getTable(), 'users.id = info.iduser')
            ->where('users.id = :id', ['id'=>2])
            ->orderBy(['users.id']);

        $result = $this->repository->getByQuery($query, [$this->infoMapper]);

        $this->assertEquals(2, $result[0][0]->getId());
        $this->assertEquals('Jane Doe', $result[0][0]->getName());
        $this->assertEquals('2017-01-04', $result[0][0]->getCreatedate());

        // - ------------------

        $this->assertEmpty($result[0][1]->getIduser());
        $this->assertEmpty($result[0][1]->getValue());
    }

    public function testTop()
    {
        $query = $this->repository->queryInstance()
            ->top(1);

        $result = $this->repository->getByQuery($query);

        $this->assertEquals(1, $result[0]->getId());
        $this->assertEquals('John Doe', $result[0]->getName());
        $this->assertEquals('2015-05-02', $result[0]->getCreatedate());

        $this->assertEquals(1, count($result));
    }

    public function testLimit()
    {
        $query = $this->repository->queryInstance()
            ->limit(1, 1);

        $result = $this->repository->getByQuery($query);

        $this->assertEquals(2, $result[0]->getId());
        $this->assertEquals('Jane Doe', $result[0]->getName());
        $this->assertEquals('2017-01-04', $result[0]->getCreatedate());

        $this->assertEquals(1, count($result));
    }

    public function testQueryRaw()
    {
        $query = $this->repository->queryInstance()
            ->fields([
                "name",
                "julianday('2020-06-28') - julianday(createdate) as days"
            ])
            ->limit(1, 1);

        $result = $this->repository->getByQuery($query);

        $this->assertEquals(null, $result[0]->getId());
        $this->assertEquals('Jane Doe', $result[0]->getName());
        $this->assertEquals(null, $result[0]->getCreatedate());

        $this->assertEquals(1, count($result));

        $result = $this->repository->getByQueryRaw($query);
        $this->assertEquals([
            [
                "name" => "Jane Doe",
                "days" => 1271.0
            ]
        ], $result);
    }

    public $test = null;
    public $onError = null;

    public function testObserverWrongUpdate()
    {
        $this->test = null;
        $this->onError = null;

        $this->repository->addObserver(new class($this->infoMapper->getTable(), $this->repository, $this) implements ObserverProcessorInterface {
            private $table;
            private $parent;

            private $parentRepository;

            public function __construct($table, $parentRepository, $parent)
            {
                $this->table = $table;
                $this->parent = $parent;
                $this->parentRepository = $parentRepository;
            }

            #[Override]
            public function process(ObserverData $observerData): void
            {
                $this->parent->test = true;
                $this->parent->assertEquals('info', $observerData->getTable());
                $this->parent->assertEquals(ObserverEvent::Update, $observerData->getEvent());
                $this->parent->assertInstanceOf(Info::class, $observerData->getData());
                $this->parent->assertEquals(0, $observerData->getData()->getValue());
                $this->parent->assertInstanceOf(Info::class, $observerData->getOldData());
                $this->parent->assertEquals(3.5, $observerData->getOldData()->getValue());
                $this->parent->assertEquals($this->parentRepository, $observerData->getRepository());
            }

            #[Override]
            public function onError(Throwable $exception, ObserverData $observerData) : void
            {
                $this->parent->onError = true;
                $this->parent->assertInstanceOf(ExpectationFailedException::class, $exception);
                $this->parent->assertInstanceOf(Info::class, $observerData->getOldData());
                $this->parent->assertInstanceOf(Info::class, $observerData->getData());
            }

            #[Override]
            public function getObservedTable(): string
            {
                return $this->table;
            }
        });

        // This update doesn't have observer
        $users = new Users();
        $users->setName('needfail');
        $users->setCreatedate('2015-08-09');

        $this->assertEquals(null, $users->getId());
        $this->repository->save($users);
        $this->assertNull($this->test);
        $this->assertNull($this->onError);


        // This update has an observer, and you change the `test` variable
        $infoRepository = new Repository($this->dbDriver, $this->infoMapper);
        $query = $infoRepository->queryInstance()
            ->where('iduser = :id', ['id'=>3])
            ->orderBy(['property']);

        $infoRepository = new Repository($this->dbDriver, $this->infoMapper);
        $result = $infoRepository->getByQuery($query);

        // Set Zero
        $result[0]->setValue(0);
        $result[0]->setId(1);
        $infoRepository->save($result[0]);
        $this->assertTrue($this->test);
        $this->assertTrue($this->onError);
    }

    public function testObserverUpdate()
    {
        $this->test = null;

        $this->repository->addObserver(new class($this->infoMapper->getTable(), $this->repository, $this) implements ObserverProcessorInterface {
            private $table;
            private $parent;

            private $parentRepository;

            public function __construct($table, $parentRepository, $parent)
            {
                $this->table = $table;
                $this->parent = $parent;
                $this->parentRepository = $parentRepository;
            }

            #[Override]
            public function process(ObserverData $observerData): void
            {
                $this->parent->test = true;
                $this->parent->assertEquals('info', $observerData->getTable());
                $this->parent->assertEquals(ObserverEvent::Update, $observerData->getEvent());
                $this->parent->assertInstanceOf(Info::class, $observerData->getData());
                $this->parent->assertEquals(0, $observerData->getData()->getValue());
                $this->parent->assertInstanceOf(Info::class, $observerData->getOldData());
                $this->parent->assertEquals(3.5, $observerData->getOldData()->getValue());
                $this->parent->assertEquals($this->parentRepository, $observerData->getRepository());
            }

            #[Override]
            public function onError(Throwable $exception, ObserverData $observerData) : void
            {
                $this->parent->onError = true;
                $this->parent->assertEquals(null, $exception);
                $this->parent->assertInstanceOf(Info::class, $observerData->getOldData());
                $this->parent->assertInstanceOf(Info::class, $observerData->getData());
            }

            #[Override]
            public function getObservedTable(): string
            {
                return $this->table;
            }
        });

        // This update doesn't have observer
        $users = new Users();
        $users->setName('Bla99991919');
        $users->setCreatedate('2015-08-09');

        $this->assertEquals(null, $users->getId());
        $this->repository->save($users);
        $this->assertNull($this->test);
        $this->assertNull($this->onError);


        // This update has an observer, and you change the `test` variable
        $infoRepository = new Repository($this->dbDriver, $this->infoMapper);
        $query = $infoRepository->queryInstance()
            ->where('iduser = :id', ['id'=>3])
            ->orderBy(['property']);

        $result = $infoRepository->getByQuery($query);

        // Set Zero
        $result[0]->setValue(0);
        $infoRepository->save($result[0]);
        $this->assertTrue($this->test);
        $this->assertNull($this->onError);
    }

    public function testObserverDelete()
    {
        $this->test = null;

        $this->repository->addObserver(new class($this->infoMapper->getTable(), $this->repository, $this) implements ObserverProcessorInterface {
            private $table;
            private $parent;

            private $parentRepository;

            public function __construct($table, $parentRepository, $parent)
            {
                $this->table = $table;
                $this->parent = $parent;
                $this->parentRepository = $parentRepository;
            }

            #[Override]
            public function process(ObserverData $observerData): void
            {
                $this->parent->test = true;
                $this->parent->assertEquals('info', $observerData->getTable());
                $this->parent->assertEquals(ObserverEvent::Delete, $observerData->getEvent());
                $this->parent->assertNull($observerData->getData());
                $this->parent->assertEquals(["pkid" => 3], $observerData->getOldData());
                $this->parent->assertEquals($this->parentRepository, $observerData->getRepository());
            }

            #[Override]
            public function onError(Throwable $exception, ObserverData $observerData) : void
            {
                $this->parent->onError = true;
                $this->parent->assertEquals(null, $exception);
                $this->parent->assertInstanceOf(Info::class, $observerData->getOldData());
                $this->parent->assertInstanceOf(Info::class, $observerData->getData());
            }

            #[Override]
            public function getObservedTable(): string
            {
                return $this->table;
            }
        });

        $this->assertNull($this->test);
        $this->assertNull($this->onError);
        $infoRepository = new Repository($this->dbDriver, $this->infoMapper);
        $result = $infoRepository->delete(3);
        $this->assertTrue($this->test);
        $this->assertNull($this->onError);
    }

    public function testObserverInsert()
    {
        $test = null;

        $this->repository->addObserver(new class($this->infoMapper->getTable(), $this->repository, $this) implements ObserverProcessorInterface {
            private $table;
            private $parent;

            private $parentRepository;

            public function __construct($table, $parentRepository, $parent)
            {
                $this->table = $table;
                $this->parent = $parent;
                $this->parentRepository = $parentRepository;
            }

            #[Override]
            public function process(ObserverData $observerData): void
            {
                $this->parent->test = true;
                $this->parent->assertEquals('info', $observerData->getTable());
                $this->parent->assertEquals(ObserverEvent::Insert, $observerData->getEvent());
                $this->parent->assertInstanceOf(Info::class, $observerData->getData());
                $this->parent->assertEquals(4, $observerData->getData()->getId());
                $this->parent->assertEquals(1, $observerData->getData()->getIdUser());
                $this->parent->assertEquals(3, $observerData->getData()->getValue());
                $this->parent->assertNull($observerData->getOldData());
                $this->parent->assertEquals($this->parentRepository, $observerData->getRepository());
            }

            #[Override]
            public function onError(Throwable $exception, ObserverData $observerData) : void
            {
                $this->parent->onError = true;
                $this->parent->assertNull($exception);
                $this->parent->assertInstanceOf(Info::class, $observerData->getOldData());
                $this->parent->assertInstanceOf(Info::class, $observerData->getData());
            }

            #[Override]
            public function getObservedTable(): string
            {
                return $this->table;
            }
        });
        $info = new Info();
        $info->setValue("3");
        $info->setIduser(1);


        $this->assertNull($this->test);
        $this->assertNull($this->onError);
        $infoRepository = new Repository($this->dbDriver, $this->infoMapper);
        $infoRepository->save($info);
        $this->assertTrue($this->test);
        $this->assertNull($this->onError);
    }

    public function testAddSameObserverTwice()
    {
        $this->test = null;
        $this->onError = null;

        $class = new class($this->infoMapper->getTable(), $this->repository, $this) implements ObserverProcessorInterface {

            protected $table;
            public function __construct($table, $parentRepository, $parent)
            {
                $this->table = $table;
            }

            #[Override]
            public function process(ObserverData $observerData): void
            {
            }

            #[Override]
            public function onError(Throwable $exception, ObserverData $observerData): void
            {
            }

            #[Override]
            public function getObservedTable(): string
            {
                return $this->table;
            }
        };
        $this->repository->addObserver($class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Observer already exists");
        $this->repository->addObserver($class);
    }

    public function testConstraintAllow()
    {
        // This update has an observer, and you change the `test` variable
        $query = $this->infoMapper->getQuery()
            ->where('iduser = :id', ['id'=>3])
            ->orderBy(['property']);

        $infoRepository = new Repository($this->dbDriver, $this->infoMapper);
        $result = $infoRepository->getByQuery($query);

        // Set Zero
        $result[0]->setValue(2);
        $newInstance = $infoRepository->save($result[0], new RequireChangedValuesConstraint('value'));
        $this->assertEquals(2, $newInstance->getValue());
    }

    public function testConstraintNotAllow()
    {
        $this->expectException(RequireChangedValuesConstraintException::class);
        $this->expectExceptionMessage("You are not updating the property 'value'");

        // This update has an observer, and you change the `test` variable
        $query = $this->infoMapper->getQuery()
            ->where('iduser = :id', ['id'=>3])
            ->orderBy(['property']);

        $infoRepository = new Repository($this->dbDriver, $this->infoMapper);
        $result = $infoRepository->getByQuery($query);

        // Set Zero
        $result[0]->setValue(3.5);
        $newInstance = $infoRepository->save($result[0], new RequireChangedValuesConstraint('value'));
    }

    public function testConstraintCustomAllow()
    {
        // This update has an observer, and you change the `test` variable
        $query = $this->infoMapper->getQuery()
            ->where('iduser = :id', ['id' => 3])
            ->orderBy(['property']);

        $infoRepository = new Repository($this->dbDriver, $this->infoMapper);
        $result = $infoRepository->getByQuery($query);

        // Set Zero
        $result[0]->setValue(2);
        $newInstance = $infoRepository->save($result[0], new CustomConstraint(function ($oldInstance, $newInstance) {
            return $newInstance->getValue() != 3.5;
        }));
        $this->assertEquals(2, $newInstance->getValue());
    }

    public function testConstraintCustomNotAllow()
    {
        $this->expectException(UpdateConstraintException::class);
        $this->expectExceptionMessage("The Update Constraint validation failed");

        // This update has an observer, and you change the `test` variable
        $query = $this->infoMapper->getQuery()
            ->where('iduser = :id', ['id' => 3])
            ->orderBy(['property']);

        $infoRepository = new Repository($this->dbDriver, $this->infoMapper);
        $result = $infoRepository->getByQuery($query);

        // Set Zero
        $result[0]->setValue(3.5);
        $newInstance = $infoRepository->save($result[0], new CustomConstraint(function ($oldInstance, $newInstance) {
            return $newInstance->getValue() != 3.5;
        }));
    }
    public function testQueryBasic()
    {
        $query = $this->infoMapper->getQueryBasic()
            ->where('iduser = :id', ['id'=>3]);

        $infoRepository = new Repository($this->dbDriver, $this->infoMapper);
        $result = $infoRepository->getByQuery($query);

        $this->assertEquals(count($result), 1);

        $this->assertEquals(3, $result[0]->getId());
        $this->assertEquals(3, $result[0]->getIduser());
        $this->assertEquals(3.5, $result[0]->getValue());
    }

    public function testUnion()
    {
        $query = $this->infoMapper->getQueryBasic()
            ->where('id = :id1', ['id1'=>3]);

        $query2 = $this->infoMapper->getQueryBasic()
            ->where('id = :id2', ['id2'=>1]);


        $union = Union::getInstance()
            ->addQuery($query)
            ->addQuery($query2)
            ->orderBy(['iduser']);

        $infoRepository = new Repository($this->dbDriver, $this->infoMapper);
        $result = $infoRepository->getByQuery($union);

        $this->assertEquals(count($result), 2);

        $this->assertEquals(1, $result[0]->getId());
        $this->assertEquals(1, $result[0]->getIduser());
        $this->assertEquals(30.4, $result[0]->getValue());

        $this->assertEquals(3, $result[1]->getId());
        $this->assertEquals(3, $result[1]->getIduser());
        $this->assertEquals(3.5, $result[1]->getValue());
    }

    public function testMappingAttribute()
    {
        $query = new Query();
        $query->table($this->infoMapper->getTable())
            ->where('iduser = :id', ['id'=>3])
            ->orderBy(['property']);

        $infoRepository = new Repository($this->dbDriver, ModelWithAttributes::class);

        /** @var ModelWithAttributes[] $result */
        $result = $infoRepository->getByQuery($query);

        $this->assertEquals(count($result), 1);

        $this->assertEquals(3, $result[0]->getPk());
        $this->assertEquals(3, $result[0]->iduser);
        $this->assertEquals(3.5, $result[0]->value);
        $this->assertNull($result[0]->getCreatedAt()); // Because it was not set in the initial insert outside the ORM
        $this->assertNull($result[0]->getUpdatedAt()); // Because it was not set in the initial insert outside the ORM
        $this->assertNull($result[0]->getDeletedAt()); // Because it was not set in the initial insert outside the ORM
    }

    public function testMappingAttributeInsert()
    {
        $infoRepository = new Repository($this->dbDriver, ModelWithAttributes::class);

        $query = new Query();
        $query->table($this->infoMapper->getTable())
            ->where('iduser = :id', ['id' => 123])
            ->orderBy(['property']);
        $result = $infoRepository->getByQuery($query);
        $this->assertEmpty($result);

        $info = new ModelWithAttributes();
        $info->iduser = 123;
        $info->value = 98.5;
        $infoRepository->save($info);

        /** @var ModelWithAttributes[] $result */
        $result = $infoRepository->getByQuery($query);
        $this->assertEquals(count($result), 1);

        $this->assertEquals(4, $result[0]->getPk());
        $this->assertEquals(123, $result[0]->iduser);
        $this->assertEquals(98.5, $result[0]->value);
        $this->assertNotNull($result[0]->getCreatedAt());
        $this->assertNotNull($result[0]->getUpdatedAt());
        $this->assertNull($result[0]->getDeletedAt());

        // Check if the updated_at works
        sleep(1);
        $info->value = 99.5;
        $infoRepository->save($info);
        /** @var ModelWithAttributes[] $result2 */
        $result2 = $infoRepository->getByQuery($query);
        $this->assertEquals(count($result2), 1);

        $this->assertEquals(4, $result2[0]->getPk());
        $this->assertEquals(123, $result2[0]->iduser);
        $this->assertEquals(99.5, $result2[0]->value);
        $this->assertNotNull($result2[0]->getCreatedAt());
        $this->assertEquals($result[0]->getCreatedAt(), $result2[0]->getCreatedAt());
        $this->assertNotNull($result2[0]->getUpdatedAt());
        $this->assertNotEquals($result[0]->getUpdatedAt(), $result2[0]->getUpdatedAt());
        $this->assertNull($result2[0]->getDeletedAt());
    }

    public function testMappingAttributeSoftDeleteAndGetByQuery()
    {
        $infoRepository = new Repository($this->dbDriver, ModelWithAttributes::class);

        $query = $infoRepository->queryInstance()
            ->where('iduser = :id', ['id' => 3])
            ->orderBy(['property']);
        $result = $infoRepository->getByQuery($query);

        $this->assertEquals(3, $result[0]->getPk());
        $this->assertEquals(3, $result[0]->iduser);
        $this->assertEquals(3.5, $result[0]->value);
        $this->assertNull($result[0]->getCreatedAt());
        $this->assertNull($result[0]->getUpdatedAt());
        $this->assertNull($result[0]->getDeletedAt());

        $infoRepository->delete(3);

        $result = $infoRepository->getByQuery($query);
        $this->assertCount(0, $result);
    }

    public function testMappingAttributeSoftDeleteAndGetByFilter()
    {
        $infoRepository = new Repository($this->dbDriver, ModelWithAttributes::class);

        $iteratorFilter = new IteratorFilter();
        $iteratorFilter->and('iduser', Relation::EQUAL, 3);
        $result = $infoRepository->getByFilter($iteratorFilter);

        $this->assertEquals(3, $result[0]->getPk());
        $this->assertEquals(3, $result[0]->iduser);
        $this->assertEquals(3.5, $result[0]->value);
        $this->assertNull($result[0]->getCreatedAt());
        $this->assertNull($result[0]->getUpdatedAt());
        $this->assertNull($result[0]->getDeletedAt());

        $infoRepository->delete(3);

        $result = $infoRepository->getByFilter($iteratorFilter);
        $this->assertCount(0, $result);
    }

    public function testMappingAttributeSoftDeleteAndGetByPK()
    {
        $infoRepository = new Repository($this->dbDriver, ModelWithAttributes::class);

        $result = $infoRepository->get(3);

        $this->assertEquals(3, $result->getPk());
        $this->assertEquals(3, $result->iduser);
        $this->assertEquals(3.5, $result->value);
        $this->assertNull($result->getCreatedAt());
        $this->assertNull($result->getUpdatedAt());
        $this->assertNull($result->getDeletedAt());

        $infoRepository->delete(3);

        $result = $infoRepository->get(3);
        $this->assertNull($result);
    }

    public function testQueryInstanceWithModel()
    {
        $filterModel = $this->repository->entity([
            'id' => 3
        ]);

        $query = $this->repository->queryInstance($filterModel);

        $result = $this->repository->getByQuery($query);

        $this->assertEquals(count($result), 1);

        $this->assertEquals(3, $result[0]->getId());
        $this->assertEquals('JG', $result[0]->getName());
        $this->assertEquals('1974-01-26', $result[0]->getCreatedate());

    }

    public function testGetEntity()
    {
        /** @var Info $usersModel */
        $infoModel = $this->infoMapper->getEntity([
            'id' => 3,
            'iduser' => 3,
            'value' => 3.5      // Value is the object model property
        ]);

        $this->assertEquals(3, $infoModel->getId());
        $this->assertEquals(3, $infoModel->getIduser());
        $this->assertEquals(3.5, $infoModel->getValue());
    }

    public function testGetEntity2()
    {
        /** @var Info $usersModel */
        $infoModel = $this->infoMapper->getEntity([
            'id' => 3,
            'iduser' => 3,
            'property' => 3.5     // Property is the field name and is mapped to value
        ]);

        $this->assertEquals(3, $infoModel->getId());
        $this->assertEquals(3, $infoModel->getIduser());
        $this->assertEquals(3.5, $infoModel->getValue());
    }

    public function testQueryInstanceWithWrongModel()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The model must be an instance of Tests\Model\Users");

        $infoModel = $this->infoMapper->getEntity([
            'id' => 3
        ]);

        $query = $this->repository->queryInstance($infoModel);
    }

    public function testQueryWithCache()
    {
        $query = $this->infoMapper->getQueryBasic()
            ->where('id = :id1', ['id1'=>3]);

        $infoRepository = new Repository($this->dbDriver, $this->infoMapper);
        $cacheEngine = new ArrayCacheEngine();

        // Sanity test
        $result = $infoRepository->getByQuery($query);
        $this->assertCount(1, $result);

        $cacheObject = new CacheQueryResult($cacheEngine, "qry",  120);

        // Get the result and save to cache
        $result = $infoRepository->getByQuery($query, cache: $cacheObject);
        $this->assertCount(1, $result);

        // Delete the record
        $deleteQuery = DeleteQuery::getInstance()
            ->table($this->infoMapper->getTable())
            ->where('id = :id', ['id' => 3]);
        $deleteQuery->buildAndExecute($this->repository->getDbDriver());

        // Check if query with no cache the record is not found
        $result = $infoRepository->getByQuery($query);
        $this->assertCount(0, $result);

        // Check if query with cache the record is found
        $result = $infoRepository->getByQuery($query, cache: $cacheObject);
        $this->assertCount(1, $result);
    }

    public function testActiveRecordGet()
    {
        ActiveRecordModel::initialize($this->dbDriver);

        $this->assertEquals('info', ActiveRecordModel::tableName());

        //
        $model = ActiveRecordModel::get(3);

        $this->assertEquals(3, $model->getPk());
        $this->assertEquals(3, $model->iduser);
        $this->assertEquals(3.5, $model->value);
        $this->assertNull($model->getCreatedAt()); // Because it was not set in the initial insert outside the ORM
        $this->assertNull($model->getUpdatedAt()); // Because it was not set in the initial insert outside the ORM
        $this->assertNull($model->getDeletedAt()); // Because it was not set in the initial insert outside the ORM
    }

    public function testActiveRecordRefresh()
    {
        ActiveRecordModel::initialize($this->dbDriver);

        $this->assertEquals('info', ActiveRecordModel::tableName());

        /**
         * @var ActiveRecordModel $model
         */
        $model = ActiveRecordModel::get(3);

        $createdAt = $model->getCreatedAt();
        $updatedAt = $model->getUpdatedAt();
        $deletedAt = $model->getDeletedAt();

        $this->assertEquals(3, $model->getPk());
        $this->assertEquals(3, $model->iduser);
        $this->assertEquals(3.5, $model->value);
        $this->assertNull($createdAt); // Because it was not set in the initial insert outside the ORM
        $this->assertNull($updatedAt); // Because it was not set in the initial insert outside the ORM
        $this->assertNull($deletedAt); // Because it was not set in the initial insert outside the ORM

        // Update the record OUTSIDE the Active Record
        $this->dbDriver->execute("UPDATE info SET iduser = 4, property = 44.44 WHERE id = 3");

        // Check model isn't updated (which is the expected behavior)
        $this->assertEquals(3, $model->getPk());
        $this->assertEquals(3, $model->iduser);
        $this->assertEquals(3.5, $model->value);
        $this->assertEquals($createdAt, $model->getCreatedAt()); // Because it was not set in the initial insert outside the ORM
        $this->assertEquals($updatedAt, $model->getUpdatedAt()); // Because it was not set in the initial insert outside the ORM
        $this->assertEquals($deletedAt, $model->getDeletedAt()); // Because it was not set in the initial insert outside the ORM

        // Refresh the model
        $model->refresh();

        // Check model is updated
        $this->assertEquals(3, $model->getPk());
        $this->assertEquals(4, $model->iduser);
        $this->assertEquals(44.44, $model->value);
        $this->assertEquals($createdAt, $model->getCreatedAt()); // Because it was not set in the initial insert outside the ORM
        $this->assertEquals($updatedAt, $model->getUpdatedAt()); // Because it was not set in the initial insert outside the ORM
        $this->assertEquals($deletedAt, $model->getDeletedAt()); // Because it was not set in the initial insert outside the ORM
    }

    public function testActiveRecordRefreshError()
    {
        ActiveRecordModel::initialize($this->dbDriver);

        $this->expectException(OrmInvalidFieldsException::class);
        $this->expectExceptionMessage("Primary key 'pk' is null");

        $model = ActiveRecordModel::new();
        $model->refresh();
    }

    public function testActiveRecordFill()
    {
        ActiveRecordModel::initialize($this->dbDriver);

        $model = ActiveRecordModel::get(3);

        $this->assertEquals(3, $model->getPk());
        $this->assertEquals(3, $model->iduser);
        $this->assertEquals(3.5, $model->value);
        $this->assertNull($model->getCreatedAt()); // Because it was not set in the initial insert outside the ORM
        $this->assertNull($model->getUpdatedAt()); // Because it was not set in the initial insert outside the ORM
        $this->assertNull($model->getDeletedAt()); // Because it was not set in the initial insert outside the ORM

        $model->fill([
            'iduser' => 4,
            'value' => 44.44
        ]);

        $this->assertEquals(3, $model->getPk());
        $this->assertEquals(4, $model->iduser);
        $this->assertEquals(44.44, $model->value);
        $this->assertNull($model->getCreatedAt()); // Because it was not set in the initial insert outside the ORM
        $this->assertNull($model->getUpdatedAt()); // Because it was not set in the initial insert outside the ORM
        $this->assertNull($model->getDeletedAt()); // Because it was not set in the initial insert outside the ORM
    }


    public function testActiveRecordFilter()
    {
        ActiveRecordModel::initialize($this->dbDriver);

        $model = ActiveRecordModel::filter((new IteratorFilter())->and('iduser', Relation::EQUAL, 1));

        $this->assertCount(2, $model);
        $this->assertEquals(1, $model[0]->getPk());
        $this->assertEquals(1, $model[0]->iduser);
        $this->assertEquals(30.4, $model[0]->value);
        $this->assertNull($model[0]->getCreatedAt()); // Because it was not set in the initial insert outside the ORM
        $this->assertNull($model[0]->getUpdatedAt()); // Because it was not set in the initial insert outside the ORM
        $this->assertNull($model[0]->getDeletedAt()); // Because it was not set in the initial insert outside the ORM

        $this->assertEquals(2, $model[1]->getPk());
        $this->assertEquals(1, $model[1]->iduser);
        $this->assertEquals(1250.96, $model[1]->value);
        $this->assertNull($model[1]->getCreatedAt()); // Because it was not set in the initial insert outside the ORM
        $this->assertNull($model[1]->getUpdatedAt()); // Because it was not set in the initial insert outside the ORM
        $this->assertNull($model[1]->getDeletedAt()); // Because it was not set in the initial insert outside the ORM
    }

    public function testActiveRecordEmptyFilter()
    {
        ActiveRecordModel::initialize($this->dbDriver);

        $model = ActiveRecordModel::filter(new IteratorFilter());

        $this->assertCount(3, $model);
    }

    public function testActiveRecordAll()
    {
        ActiveRecordModel::initialize($this->dbDriver);

        $model = ActiveRecordModel::all();

        $this->assertCount(3, $model);
    }

    public function testActiveRecordNew()
    {
        ActiveRecordModel::initialize($this->dbDriver);

        //
        $model = ActiveRecordModel::get(4);
        $this->assertEmpty($model);

        $model = ActiveRecordModel::new([
            'iduser' => 5,
            'value' => 55.8
        ]);
        $model->save();

        $this->assertEquals(4, $model->getPk());
        $this->assertEquals(5, $model->iduser);
        $this->assertEquals(55.8, $model->value);
        $this->assertNotNull($model->getCreatedAt()); // Because it was not set in the initial insert outside the ORM
        $this->assertNotNull($model->getUpdatedAt()); // Because it was not set in the initial insert outside the ORM
        $this->assertNull($model->getDeletedAt()); // Because it was not set in the initial insert outside the ORM

        $model = ActiveRecordModel::get(4);
        $this->assertEquals(4, $model->getPk());
        $this->assertEquals(5, $model->iduser);
        $this->assertEquals(55.8, $model->value);
        $this->assertNotNull($model->getCreatedAt()); // Because it was not set in the initial insert outside the ORM
        $this->assertNotNull($model->getUpdatedAt()); // Because it was not set in the initial insert outside the ORM
        $this->assertNull($model->getDeletedAt()); // Because it was not set in the initial insert outside the ORM
    }

    public function testActiveRecordUpdate()
    {
        ActiveRecordModel::initialize($this->dbDriver);

        //
        $model = ActiveRecordModel::get(3);

        $model->value = 99.1;
        $model->save();

        $this->assertEquals(3, $model->getPk());
        $this->assertEquals(3, $model->iduser);
        $this->assertEquals(99.1, $model->value);
        $this->assertNull($model->getCreatedAt()); // Because it was not set in the initial insert outside the ORM
        $this->assertNotNull($model->getUpdatedAt()); // Because it was not set in the initial insert outside the ORM
        $this->assertNull($model->getDeletedAt()); // Because it was not set in the initial insert outside the ORM

        $model = ActiveRecordModel::get(3);
        $this->assertEquals(3, $model->getPk());
        $this->assertEquals(3, $model->iduser);
        $this->assertEquals(99.1, $model->value);
        $this->assertNull($model->getCreatedAt()); // Because it was not set in the initial insert outside the ORM
        $this->assertNotNull($model->getUpdatedAt()); // Because it was not set in the initial insert outside the ORM
        $this->assertNull($model->getDeletedAt()); // Because it was not set in the initial insert outside the ORM
    }

    public function testActiveRecordDelete()
    {
        ActiveRecordModel::initialize($this->dbDriver);

        //
        $model = ActiveRecordModel::get(3);
        $this->assertNotEmpty($model);

        $model->delete();

        $model = ActiveRecordModel::get(3);
        $this->assertEmpty($model);
    }

    public function testInitializeActiveRecordDefaultDbDriverError()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("You must initialize the ORM with a DbDriverInterface");
        ActiveRecordModel::initialize();
    }

    public function testInitializeActiveRecordDefaultDbDriver()
    {
        ORM::defaultDbDriver($this->dbDriver);
        ActiveRecordModel::initialize();
        $this->assertTrue(true);
    }

    public function testInitializeActiveRecordDefaultDbDriver2()
    {
        ORM::defaultDbDriver($this->dbDriver);
        $model = ActiveRecordModel::get(1);
        $this->assertNotNull($model);
    }
}
