<?php

namespace Tests;

use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\AnyDataset\Db\Factory;
use ByJG\MicroOrm\DeleteQuery;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\InsertBulkQuery;
use ByJG\MicroOrm\InsertQuery;
use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\Query;
use ByJG\MicroOrm\QueryRaw;
use ByJG\MicroOrm\Repository;
use ByJG\MicroOrm\UpdateQuery;
use ByJG\Util\Uri;
use PHPUnit\Framework\TestCase;
use Tests\Model\Users;

class BulkTest extends TestCase
{
    const URI = 'sqlite:///tmp/test-bulk.db';

    protected DbDriverInterface $dbDriver;
    protected Repository $repository;

    protected function setUp(): void
    {
        $this->dbDriver = Factory::getDbInstance(self::URI);

        // Create table and seed data similar to RepositoryTest
        $this->dbDriver->execute('create table users (
            id integer primary key  autoincrement,
            name varchar(45),
            createdate datetime);'
        );
        $insertBulk = InsertBulkQuery::getInstance('users', ['name', 'createdate']);
        $insertBulk->values(['name' => 'John Doe', 'createdate' => '2017-01-02']);
        $insertBulk->values(['name' => 'Jane Doe', 'createdate' => '2017-01-04']);
        $insertBulk->values(['name' => 'JG', 'createdate' => '1974-01-26']);
        $insertBulk->buildAndExecute($this->dbDriver);

        $mapper = new Mapper(Users::class, 'users', 'Id');
        $this->repository = new Repository($this->dbDriver, $mapper);
    }

    protected function tearDown(): void
    {
        $uri = new Uri(self::URI);
        @unlink($uri->getPath());
    }

    public function testBulkMixedQueriesWithParamCollision(): void
    {
        // Sanity check: count, specific rows
        // 3 initial rows
        $count = Query::getInstance()->fields(['count(*) as cnt'])->table('users');
        $res = $this->repository->getByQueryRaw($count);
        $this->assertEquals(3, (int)$res[0]['cnt']);

        // id=1 should have name 'John Doe'
        $row = $this->repository->get(1);
        $this->assertEquals('John Doe', $row->getName());

        // The 'Alice' should not exist
        $rows = $this->repository->getByFilter('name = :name', ['name' => 'Alice']);
        $this->assertCount(0, $rows);

        // The 'JG' should exist before bulk
        $rows = $this->repository->getByFilter('name = :name', ['name' => 'JG']);
        $this->assertCount(1, $rows);

        // Prepare queries with overlapping parameter names (:name used in multiple queries)
        $insert = InsertQuery::getInstance('users', [
            'name' => 'Alice',
            'createdate' => '2020-01-01'
        ]);

        $update = UpdateQuery::getInstance()
            ->table('users')
            ->set('name', 'Bob')
            ->where('id = :id', ['id' => 1]);

        $delete = DeleteQuery::getInstance()
            ->table('users')
            ->where('name = :name', ['name' => 'JG']);

        // Execute bulk
        $it = $this->repository->bulkExecute([$insert, $update, $delete], null);
        $this->assertEquals([], $it->toArray());

        // Validate results: count, specific rows
        // 3 initial + 1 insert - 1 delete = 3 rows
        $count = Query::getInstance()->fields(['count(*) as cnt'])->table('users');
        $res = $this->repository->getByQueryRaw($count);
        $this->assertEquals(3, (int)$res[0]['cnt']);

        // id=1 should now have name 'Bob'
        $row = $this->repository->get(1);
        $this->assertEquals('Bob', $row->getName());

        // The newly inserted 'Alice' should exist
        $rows = $this->repository->getByFilter('name = :name', ['name' => 'Alice']);
        $this->assertCount(1, $rows);

        // The deleted 'JG' should be gone
        $rows = $this->repository->getByFilter('name = :name', ['name' => 'JG']);
        $this->assertCount(0, $rows);
    }

    public function testBulkEmptyArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('You pass an empty array to bulk');

        // Should not throw and not change anything
        $this->repository->bulkExecute([], null);
    }

    public function testBulkMixedQueriesWithInvalidQuery(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid query type. Expected QueryBuilderInterface or Updatable.');

        // Prepare queries with overlapping parameter names (:name used in multiple queries)
        $insert = InsertQuery::getInstance('users', [
            'name' => 'Alice',
            'createdate' => '2020-01-01'
        ]);

        $update = UpdateQuery::getInstance()
            ->table('users')
            ->set('name', 'Bob')
            ->where('id = :id', ['id' => 1]);

        $delete = DeleteQuery::getInstance()
            ->table('users')
            ->where('name = :name', ['name' => 'JG']);

        $invalid = "invalid";

        // Execute bulk
        $this->repository->bulkExecute([$insert, $invalid, $update, $delete], null);
    }

    public function testBulkInsertAndSelectLastInsertedId(): void
    {
        // initial rows are 3; next autoincrement id should be 4 after insert
        $insert = InsertQuery::getInstance('users', [
            'name' => 'Charlie',
            'createdate' => '2025-01-01'
        ]);

        // Outer query selects last_insert_rowid() from the single-row subquery
        $selectLastId = QueryRaw::getInstance($this->repository->getDbDriver()->getDbHelper()->getSqlLastInsertId());

        $it = $this->repository->bulkExecute([$insert, $selectLastId], null);
        $result = $it->toArray();

        $this->assertCount(1, $result);
        // SQLite returns integer for last_insert_rowid(); expect 4 here
        $this->assertEquals(4, (int)$result[0]['id']);

        // Also ensure the inserted row exists
        $rows = $this->repository->getByFilter('name = :name', ['name' => 'Charlie']);
        $this->assertCount(1, $rows);
        $this->assertEquals('Charlie', $rows[0]->getName());
    }
}
