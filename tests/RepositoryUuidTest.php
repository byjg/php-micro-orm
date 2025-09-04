<?php

namespace Tests;

use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\AnyDataset\Db\Factory;
use ByJG\MicroOrm\Literal\HexUuidLiteral;
use ByJG\MicroOrm\Repository;
use PHPUnit\Framework\TestCase;
use Tests\Model\UsersWithUuidKey;

class RepositoryUuidTest extends TestCase
{

    const URI = 'mysql://root:password@127.0.0.1';

    /**
     * @var DbDriverInterface
     */
    protected $dbDriver;

    /**
     * @var Repository
     */
    protected $repository;

    public function setUp(): void
    {
        $this->dbDriver = Factory::getDbInstance(self::URI);
        $this->dbDriver->execute('create database if not exists testmicroorm;');
        $this->dbDriver = Factory::getDbInstance(self::URI . "/testmicroorm");

        $this->dbDriver->execute('create table usersuuid (
            id binary(16) primary key,
            name varchar(45));'
        );
        $this->repository = new Repository($this->dbDriver, UsersWithUuidKey::class);
    }

    public function tearDown(): void
    {
        $this->dbDriver->execute('drop table if exists usersuuid;');
    }

    public function testGet()
    {
        $this->assertTrue(true);
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

        $query = $this->repository->getMapper()->getQuery();
        $query->top(1);
        $result = $this->repository->getByQuery($query);
        $this->assertCount(0, $result);

        $users = $this->repository->getMapper()->getEntity();
        $users->setName('Bla99991919');
        $this->assertEquals(null, $users->getId());
        $this->repository->save($users);

        $query = $this->repository->getMapper()->getQuery();
        $query->where('id = :id', ['id' => $users->getId()]);
        $result = $this->repository->getByQuery($query);
        $this->assertCount(1, $result);

        $uuid = HexUuidLiteral::getFormattedUuid($result[0]->getId(), throwErrorIfInvalid: true);
        $this->assertEquals('Bla99991919', $result[0]->getName());
    }
}
