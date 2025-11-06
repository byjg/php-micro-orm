<?php

namespace Tests;

use ByJG\AnyDataset\Core\Exception\NotFoundException;
use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\MicroOrm\ActiveRecordQuery;
use ByJG\MicroOrm\InsertBulkQuery;
use ByJG\MicroOrm\ORM;
use Override;
use PHPUnit\Framework\TestCase;
use Tests\Model\ActiveRecordModel;

/**
 * Tests for ActiveRecordQuery fluent API
 */
class ActiveRecordQueryTest extends TestCase
{
    protected DatabaseExecutor $executor;

    #[Override]
    public function setUp(): void
    {
        $dbDriver = ConnectionUtil::getConnection("testactiverecordquery");

        $this->executor = DatabaseExecutor::using($dbDriver);

        $this->executor->execute('create table info (
            id integer primary key auto_increment,
            iduser INTEGER,
            property decimal(10, 2),
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at datetime);'
        );

        $insertBulk = InsertBulkQuery::getInstance('info', ['iduser', 'property']);
        $insertBulk->values(['iduser' => 1, 'property' => 30.4]);
        $insertBulk->values(['iduser' => 1, 'property' => 1250.96]);
        $insertBulk->values(['iduser' => 2, 'property' => 45.50]);
        $insertBulk->values(['iduser' => 3, 'property' => 99.99]);
        $insertBulk->buildAndExecute($this->executor);

        ORM::defaultDbDriver($this->executor);
        ActiveRecordModel::reset();
    }

    #[Override]
    public function tearDown(): void
    {
        $this->executor->execute('drop table if exists info;');
        ORM::resetMemory();
    }

    // Test newQuery() method

    public function testNewQueryReturnsActiveRecordQuery()
    {
        $query = ActiveRecordModel::newQuery();

        $this->assertInstanceOf(ActiveRecordQuery::class, $query);
    }

    public function testNewQueryWithWhereAndFirst()
    {
        $result = ActiveRecordModel::newQuery()
            ->where('iduser = :iduser', ['iduser' => 1])
            ->orderBy(['property'])
            ->first();

        $this->assertInstanceOf(ActiveRecordModel::class, $result);
        $this->assertEquals(1, $result->getIdUser());
        $this->assertEquals(30.4, $result->getValue());
    }

    public function testNewQueryWithToArray()
    {
        $results = ActiveRecordModel::newQuery()
            ->where('iduser = :iduser', ['iduser' => 1])
            ->orderBy(['property'])
            ->toArray();

        $this->assertCount(2, $results);
        $this->assertEquals(30.4, $results[0]->getValue());
        $this->assertEquals(1250.96, $results[1]->getValue());
    }

    // Test where() method

    public function testWhereWithFirst()
    {
        $result = ActiveRecordModel::where('iduser = :iduser', ['iduser' => 2])
            ->first();

        $this->assertInstanceOf(ActiveRecordModel::class, $result);
        $this->assertEquals(2, $result->getIdUser());
        $this->assertEquals(45.50, $result->getValue());
    }

    public function testWhereWithMultipleConditions()
    {
        $result = ActiveRecordModel::where('iduser = :iduser AND property > :min', [
            'iduser' => 1,
            'min' => 100
        ])->first();

        $this->assertInstanceOf(ActiveRecordModel::class, $result);
        $this->assertEquals(1250.96, $result->getValue());
    }

    public function testWhereChainedWithAdditionalWhere()
    {
        $result = ActiveRecordModel::where('iduser = :iduser', ['iduser' => 1])
            ->where('property > :min', ['min' => 100])
            ->first();

        $this->assertInstanceOf(ActiveRecordModel::class, $result);
        $this->assertEquals(1250.96, $result->getValue());
    }

    // Test first() method

    public function testFirstReturnsFirstRecord()
    {
        $result = ActiveRecordModel::where('iduser = :iduser', ['iduser' => 1])
            ->orderBy(['property'])
            ->first();

        $this->assertInstanceOf(ActiveRecordModel::class, $result);
        $this->assertEquals(30.4, $result->getValue());
    }

    public function testFirstReturnsNullWhenNoRecords()
    {
        $result = ActiveRecordModel::where('iduser = :iduser', ['iduser' => 999])
            ->first();

        $this->assertNull($result);
    }

    // Test firstOrFail() method

    public function testFirstOrFailReturnsRecord()
    {
        $result = ActiveRecordModel::where('iduser = :iduser', ['iduser' => 3])
            ->firstOrFail();

        $this->assertInstanceOf(ActiveRecordModel::class, $result);
        $this->assertEquals(3, $result->getIdUser());
    }

    public function testFirstOrFailThrowsExceptionWhenNoRecords()
    {
        $this->expectException(NotFoundException::class);

        ActiveRecordModel::where('iduser = :iduser', ['iduser' => 999])
            ->firstOrFail();
    }

    // Test exists() method

    public function testExistsReturnsTrueWhenRecordsExist()
    {
        $result = ActiveRecordModel::where('iduser = :iduser', ['iduser' => 1])
            ->exists();

        $this->assertTrue($result);
    }

    public function testExistsReturnsFalseWhenNoRecords()
    {
        $result = ActiveRecordModel::where('iduser = :iduser', ['iduser' => 999])
            ->exists();

        $this->assertFalse($result);
    }

    // Test existsOrFail() method

    public function testExistsOrFailReturnsTrueWhenRecordsExist()
    {
        $result = ActiveRecordModel::where('property > :min', ['min' => 50])
            ->existsOrFail();

        $this->assertTrue($result);
    }

    public function testExistsOrFailThrowsExceptionWhenNoRecords()
    {
        $this->expectException(NotFoundException::class);

        ActiveRecordModel::where('property > :max', ['max' => 10000])
            ->existsOrFail();
    }

    // Test toArray() method

    public function testToArrayReturnsAllMatchingRecords()
    {
        $results = ActiveRecordModel::where('iduser = :iduser', ['iduser' => 1])
            ->toArray();

        $this->assertCount(2, $results);
        $this->assertContainsOnlyInstancesOf(ActiveRecordModel::class, $results);
    }

    public function testToArrayWithOrderBy()
    {
        $results = ActiveRecordModel::newQuery()
            ->whereIn('iduser', [1, 2])
            ->orderBy(['property DESC'])
            ->toArray();

        $this->assertCount(3, $results);
        $this->assertEquals(1250.96, $results[0]->getValue());
        $this->assertEquals(45.50, $results[1]->getValue());
        $this->assertEquals(30.4, $results[2]->getValue());
    }

    public function testToArrayWithLimit()
    {
        $results = ActiveRecordModel::newQuery()
            ->orderBy(['property DESC'])
            ->limit(0, 2)
            ->toArray();

        $this->assertCount(2, $results);
        $this->assertEquals(1250.96, $results[0]->getValue());
    }

    // Test complex queries

    public function testComplexQueryWithMultipleConditionsAndOrdering()
    {
        $result = ActiveRecordModel::newQuery()
            ->where('property > :min', ['min' => 40])
            ->where('property < :max', ['max' => 100])
            ->orderBy(['property'])
            ->first();

        $this->assertInstanceOf(ActiveRecordModel::class, $result);
        $this->assertEquals(45.50, $result->getValue());
    }

    public function testQueryWithNoWhereClause()
    {
        $results = ActiveRecordModel::newQuery()
            ->orderBy(['id'])
            ->toArray();

        $this->assertCount(4, $results);
    }

    public function testWhereWithNoResults()
    {
        $result = ActiveRecordModel::where('1 = 0')->first();

        $this->assertNull($result);
    }

    // Test method chaining

    public function testMethodChainingReadability()
    {
        $user = ActiveRecordModel::where('iduser = :id', ['id' => 1])
            ->where('property > :min', ['min' => 1000])
            ->orderBy(['property DESC'])
            ->first();

        $this->assertInstanceOf(ActiveRecordModel::class, $user);
        $this->assertEquals(1250.96, $user->getValue());
    }

    // Test edge cases

    public function testFirstWithEmptyTable()
    {
        // Delete all records
        $this->executor->execute('DELETE FROM info');

        $result = ActiveRecordModel::newQuery()->first();

        $this->assertNull($result);
    }

    public function testExistsWithEmptyTable()
    {
        // Delete all records
        $this->executor->execute('DELETE FROM info');

        $result = ActiveRecordModel::newQuery()->exists();

        $this->assertFalse($result);
    }

    public function testToArrayWithEmptyTable()
    {
        // Delete all records
        $this->executor->execute('DELETE FROM info');

        $results = ActiveRecordModel::newQuery()->toArray();

        $this->assertEmpty($results);
    }

    // Test comparison with old query() method

    public function testNewApiCompatibleWithOldQueryMethod()
    {
        // Old way
        $oldQuery = ActiveRecordModel::joinWith()
            ->where('iduser = :iduser', ['iduser' => 1]);
        $oldResults = ActiveRecordModel::query($oldQuery);

        // New way
        $newResults = ActiveRecordModel::where('iduser = :iduser', ['iduser' => 1])
            ->toArray();

        $this->assertCount(count($oldResults), $newResults);
        $this->assertEquals($oldResults[0]->getId(), $newResults[0]->getId());
    }
}
