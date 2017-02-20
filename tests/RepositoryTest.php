<?php

namespace Test;

use ByJG\AnyDataset\DbDriverInterface;
use ByJG\AnyDataset\Factory;
use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\Query;
use ByJG\MicroOrm\Repository;
use ByJG\Util\Uri;

require_once 'Users.php';
require_once 'Info.php';

class RepositoryTest extends \PHPUnit_Framework_TestCase
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
        $this->userMapper = new Mapper(Users::class, 'users', 'id');


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
        $this->assertEquals(1, $users->id);
        $this->assertEquals('John Doe', $users->name);
        $this->assertEquals('2017-01-02', $users->createdate);

        $users = $this->repository->get(2);
        $this->assertEquals(2, $users->id);
        $this->assertEquals('Jane Doe', $users->name);
        $this->assertEquals('2017-01-04', $users->createdate);
    }

    public function testInsert()
    {
        $users = new Users();
        $users->name = 'Bla99991919';
        $users->createdate = '2015-08-09';
        $this->repository->save($users);

        $users2 = $this->repository->get(4);

        $this->assertEquals(4, $users2->id);
        $this->assertEquals('Bla99991919', $users2->name);
        $this->assertEquals('2015-08-09', $users2->createdate);
    }

    public function testUpdate()
    {
        $users = $this->repository->get(1);

        $users->name = 'New Name';
        $users->createdate = '2016-01-09';
        $this->repository->save($users);

        $users2 = $this->repository->get(1);
        $this->assertEquals(1, $users2->id);
        $this->assertEquals('New Name', $users2->name);
        $this->assertEquals('2016-01-09', $users2->createdate);

        $users2 = $this->repository->get(2);
        $this->assertEquals(2, $users2->id);
        $this->assertEquals('Jane Doe', $users2->name);
        $this->assertEquals('2017-01-04', $users2->createdate);
    }

    public function testDelete()
    {
        $this->repository->delete(1);
        $this->assertEmpty($this->repository->get(1));

        $users = $this->repository->get(2);
        $this->assertEquals(2, $users->id);
        $this->assertEquals('Jane Doe', $users->name);
        $this->assertEquals('2017-01-04', $users->createdate);
    }

    public function testDelete2()
    {
        $query = new Query();
        $query->table($this->userMapper->getTable())
            ->where('name like :name', ['name'=>'Jane%']);

        $this->repository->deleteByQuery($query);

        $users = $this->repository->get(1);
        $this->assertEquals(1, $users->id);
        $this->assertEquals('John Doe', $users->name);
        $this->assertEquals('2017-01-02', $users->createdate);

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

        $this->assertEquals(2, $result[0]->id);
        $this->assertEquals(1, $result[0]->iduser);
        $this->assertEquals('ggg', $result[0]->value);

        $this->assertEquals(1, $result[1]->id);
        $this->assertEquals(1, $result[1]->iduser);
        $this->assertEquals('xxx', $result[1]->value);
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

        $this->assertEquals(1, $result[0][0]->id);
        $this->assertEquals('John Doe', $result[0][0]->name);
        $this->assertEquals('2017-01-02', $result[0][0]->createdate);

        $this->assertEquals(1, $result[1][0]->id);
        $this->assertEquals('John Doe', $result[1][0]->name);
        $this->assertEquals('2017-01-02', $result[1][0]->createdate);

        // - ------------------

        $this->assertEmpty($result[0][1]->iduser);
        $this->assertEquals('xxx', $result[0][1]->value);

        $this->assertEmpty($result[1][1]->iduser);
        $this->assertEquals('ggg', $result[1][1]->value);

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

        $this->assertEquals(2, $result[0][0]->id);
        $this->assertEquals('Jane Doe', $result[0][0]->name);
        $this->assertEquals('2017-01-04', $result[0][0]->createdate);

        // - ------------------

        $this->assertEmpty($result[0][1]->iduser);
        $this->assertEmpty($result[0][1]->value);
    }

    public function testTop()
    {
        $query = new Query();
        $query->table($this->userMapper->getTable());

        $result = $this->repository->top(1)->getByQuery($query);

        $this->assertEquals(1, $result[0]->id);
        $this->assertEquals('John Doe', $result[0]->name);
        $this->assertEquals('2017-01-02', $result[0]->createdate);

        $this->assertEquals(1, count($result));
    }

    public function testLimit()
    {
        $query = new Query();
        $query->table($this->userMapper->getTable());

        $result = $this->repository->limit(1, 1)->getByQuery($query);

        $this->assertEquals(2, $result[0]->id);
        $this->assertEquals('Jane Doe', $result[0]->name);
        $this->assertEquals('2017-01-04', $result[0]->createdate);

        $this->assertEquals(1, count($result));
    }
}
