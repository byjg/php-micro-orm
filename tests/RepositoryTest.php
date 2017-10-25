<?php

namespace Test;

use ByJG\AnyDataset\DbDriverInterface;
use ByJG\AnyDataset\Factory;
use ByJG\MicroOrm\Literal;
use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\Query;
use ByJG\MicroOrm\Repository;
use ByJG\MicroOrm\Updatable;
use ByJG\Util\Uri;

require_once __DIR__ . '/Model/Users.php';
require_once __DIR__ . '/Model/UsersMap.php';
require_once __DIR__ . '/Model/Info.php';

// backward compatibility
if (!class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit_Framework_TestCase', '\PHPUnit\Framework\TestCase');
}

class RepositoryTest extends \PHPUnit\Framework\TestCase
{

    const URI='sqlite:///tmp/teste.db';

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

    public function setUp()
    {
        $this->dbDriver = Factory::getDbRelationalInstance(self::URI);

        $this->dbDriver->execute('create table users (
            id integer primary key  autoincrement, 
            name varchar(45), 
            createdate datetime);'
        );
        $this->dbDriver->execute("insert into users (name, createdate) values ('John Doe', '2017-01-02')");
        $this->dbDriver->execute("insert into users (name, createdate) values ('Jane Doe', '2017-01-04')");
        $this->dbDriver->execute("insert into users (name, createdate) values ('JG', '1974-01-26')");
        $this->userMapper = new Mapper(Users::class, 'users', 'Id');


        $this->dbDriver->execute('create table info (
            id integer primary key  autoincrement,
            iduser INTEGER,
            property varchar(45));'
        );
        $this->dbDriver->execute("insert into info (iduser, property) values (1, 'xxx')");
        $this->dbDriver->execute("insert into info (iduser, property) values (1, 'ggg')");
        $this->dbDriver->execute("insert into info (iduser, property) values (3, 'bbb')");
        $this->infoMapper = new Mapper(Info::class, 'info', 'id');
        $this->infoMapper->addFieldMap('value', 'property');

        $this->repository = new Repository($this->dbDriver, $this->userMapper);
    }

    public function tearDown()
    {
        $uri = new Uri(self::URI);
        unlink($uri->getPath());
    }

    public function testGet()
    {
        $users = $this->repository->get(1);
        $this->assertEquals(1, $users->getId());
        $this->assertEquals('John Doe', $users->getName());
        $this->assertEquals('2017-01-02', $users->getCreatedate());

        $users = $this->repository->get(2);
        $this->assertEquals(2, $users->getId());
        $this->assertEquals('Jane Doe', $users->getName());
        $this->assertEquals('2017-01-04', $users->getCreatedate());

        $users = $this->repository->get(new Literal(1));
        $this->assertEquals(1, $users->getId());
        $this->assertEquals('John Doe', $users->getName());
        $this->assertEquals('2017-01-02', $users->getCreatedate());
    }

    public function testGetSelectMask()
    {
        $this->userMapper = new Mapper(UsersMap::class, 'users', 'id');
        $this->repository = new Repository($this->dbDriver, $this->userMapper);

        $this->userMapper->addFieldMap(
            'name',
            'name',
            null,
            function ($value, $instance) {
                return '[' . strtoupper($value) . '] - ' . $instance->getCreatedate();
            }
        );

        $this->userMapper->addFieldMap(
            'year',
            'createdate',
            null,
            function ($value, $instance) {
                $date = new \DateTime($value);
                return $date->format('Y');
            }
        );

        $users = $this->repository->get(1);
        $this->assertEquals(1, $users->getId());
        $this->assertEquals('[JOHN DOE] - 2017-01-02', $users->getName());
        $this->assertEquals('2017-01-02', $users->getCreatedate());
        $this->assertEquals('2017', $users->getYear());

        $users = $this->repository->get(2);
        $this->assertEquals(2, $users->getId());
        $this->assertEquals('[JANE DOE] - 2017-01-04', $users->getName());
        $this->assertEquals('2017-01-04', $users->getCreatedate());
        $this->assertEquals('2017', $users->getYear());
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

    public function testInsertLiteral()
    {
        $users = new Users();
        $users->setName(new Literal("X'6565'"));
        $users->setCreatedate('2015-08-09');

        $this->assertEquals(null, $users->getId());
        $this->repository->save($users);
        $this->assertEquals(4, $users->getId());

        $users2 = $this->repository->get(4);

        $this->assertEquals(4, $users2->getId());
        $this->assertEquals('ee', $users2->getName());
        $this->assertEquals('2015-08-09', $users2->getCreatedate());
    }

    public function testInsertKeyGen()
    {
        $this->infoMapper = new Mapper(
            Users::class,
            'users',
            'id',
            function () {
                return 50;
            }
        );
        $this->repository = new Repository($this->dbDriver, $this->infoMapper);

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

    public function testInsertUpdateMask()
    {
        $this->userMapper = new Mapper(UsersMap::class, 'users', 'id');
        $this->repository = new Repository($this->dbDriver, $this->userMapper);

        $this->userMapper->addFieldMap(
            'name',
            'name',
            function ($value, $instance) {
                return 'Sr. ' . $value . ' - ' . $instance->getCreatedate();
            }
        );

        $this->userMapper->addFieldMap(
            'year',
            'createdate',
            null,
            function ($value, $instance) {
                $date = new \DateTime($value);
                return $date->format('Y');
            }
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
        $this->assertEquals('2015', $users2->getYear());
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

    public function testUpdateLiteral()
    {
        $users = $this->repository->get(1);
        $users->setName(new Literal("X'6565'"));
        $this->repository->save($users);

        $users2 = $this->repository->get(1);

        $this->assertEquals(1, $users2->getId());
        $this->assertEquals('ee', $users2->getName());
        $this->assertEquals('2017-01-02', $users2->getCreatedate());
    }

    public function testUpdateMask()
    {
        $this->userMapper = new Mapper(UsersMap::class, 'users', 'id');
        $this->repository = new Repository($this->dbDriver, $this->userMapper);

        $this->userMapper->addFieldMap(
            'name',
            'name',
            function ($value, $instance) {
                return 'Sr. ' . $value;
            }
        );

        $this->userMapper->addFieldMap(
            'year',
            'createdate',
            null,
            function ($value, $instance) {
                $date = new \DateTime($value);
                return $date->format('Y');
            }
        );

        $users = $this->repository->get(1);

        $users->setName('New Name');
        $users->setCreatedate('2016-01-09');
        $this->repository->save($users);

        $users2 = $this->repository->get(1);
        $this->assertEquals(1, $users2->getId());
        $this->assertEquals('Sr. New Name', $users2->getName());
        $this->assertEquals('2016-01-09', $users2->getCreatedate());
        $this->assertEquals('2016', $users2->getYear());

        $users2 = $this->repository->get(2);
        $this->assertEquals(2, $users2->getId());
        $this->assertEquals('Jane Doe', $users2->getName());
        $this->assertEquals('2017-01-04', $users2->getCreatedate());
        $this->assertEquals('2017', $users2->getYear());
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
        $query = Updatable::getInstance()
            ->table($this->userMapper->getTable())
            ->where('name like :name', ['name'=>'Jane%']);

        $this->repository->deleteByQuery($query);

        $users = $this->repository->get(1);
        $this->assertEquals(1, $users->getId());
        $this->assertEquals('John Doe', $users->getName());
        $this->assertEquals('2017-01-02', $users->getCreatedate());

        $users = $this->repository->get(2);
        $this->assertEmpty($users);
    }

    public function testWhere()
    {
        $query = new Query();
        $query->table($this->infoMapper->getTable())
            ->where('iduser = :id', ['id'=>1])
            ->orderBy(['property']);

        $infoRepository = new Repository($this->dbDriver, $this->infoMapper);
        $result = $infoRepository->getByQuery($query);

        $this->assertEquals(2, $result[0]->getId());
        $this->assertEquals(1, $result[0]->getIduser());
        $this->assertEquals('ggg', $result[0]->getValue());

        $this->assertEquals(1, $result[1]->getId());
        $this->assertEquals(1, $result[1]->getIduser());
        $this->assertEquals('xxx', $result[1]->getValue());
    }

    public function testJoin()
    {
        $query = new Query();
        $query->table($this->userMapper->getTable())
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
        $this->assertEquals('2017-01-02', $result[0][0]->getCreatedate());

        $this->assertEquals(1, $result[1][0]->getId());
        $this->assertEquals('John Doe', $result[1][0]->getName());
        $this->assertEquals('2017-01-02', $result[1][0]->getCreatedate());

        // - ------------------

        $this->assertEmpty($result[0][1]->getIduser());
        $this->assertEquals('xxx', $result[0][1]->getValue());

        $this->assertEmpty($result[1][1]->getIduser());
        $this->assertEquals('ggg', $result[1][1]->getValue());

    }

    public function testLeftJoin()
    {
        $query = new Query();
        $query->table($this->userMapper->getTable())
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
        $query = Query::getInstance()
            ->table($this->userMapper->getTable())
            ->top(1);

        $result = $this->repository->getByQuery($query);

        $this->assertEquals(1, $result[0]->getId());
        $this->assertEquals('John Doe', $result[0]->getName());
        $this->assertEquals('2017-01-02', $result[0]->getCreatedate());

        $this->assertEquals(1, count($result));
    }

    public function testLimit()
    {
        $query = Query::getInstance()
            ->table($this->userMapper->getTable())
            ->limit(1, 1);

        $result = $this->repository->getByQuery($query);

        $this->assertEquals(2, $result[0]->getId());
        $this->assertEquals('Jane Doe', $result[0]->getName());
        $this->assertEquals('2017-01-04', $result[0]->getCreatedate());

        $this->assertEquals(1, count($result));
    }
}
