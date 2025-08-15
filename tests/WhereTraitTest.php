<?php

namespace Tests;

use ByJG\AnyDataset\Db\SqlStatement;
use ByJG\MicroOrm\QueryBasic;
use ByJG\MicroOrm\SqlObject;
use PHPUnit\Framework\TestCase;

class WhereTraitTest extends TestCase
{
    public function testWhereIsNull()
    {
        $query = QueryBasic::getInstance()
            ->table('test')
            ->whereIsNull('test.field');

        $this->assertEquals(
            new SqlStatement('SELECT  * FROM test WHERE test.field IS NULL', []),
            $query->build()
        );
    }

    public function testWhereIsNotNull()
    {
        $query = QueryBasic::getInstance()
            ->table('test')
            ->whereIsNotNull('test.field');

        $this->assertEquals(
            new SqlStatement('SELECT  * FROM test WHERE test.field IS NOT NULL', []),
            $query->build()
        );
    }

    public function testWhereIn()
    {
        $query = QueryBasic::getInstance()
            ->table('test')
            ->whereIn('test.field', [1, 2, 3]);

        $sql = $query->build();
        $sqlString = $sql->getSql();
        $params = $sql->getParams();

        // We need to check the SQL and parameters separately because the parameter names are dynamic
        $this->assertStringContainsString('SELECT  * FROM test WHERE test.field IN (:', $sqlString);

        // Find the parameter names from the SQL
        preg_match('/IN \(:([^,]+), :([^,]+), :([^)]+)\)/', $sqlString, $matches);
        $this->assertCount(4, $matches, "Should have found 3 parameter names in the SQL");

        // Check that the parameters exist and have the correct values
        $this->assertEquals(1, $params[$matches[1]]);
        $this->assertEquals(2, $params[$matches[2]]);
        $this->assertEquals(3, $params[$matches[3]]);
    }

    public function testWhereInEmpty()
    {
        $query = QueryBasic::getInstance()
            ->table('test')
            ->whereIn('test.field', []);

        $this->assertEquals(
            new SqlStatement('SELECT  * FROM test', []),
            $query->build()
        );
    }

    public function testCombinedWhereClauses()
    {
        $query = QueryBasic::getInstance()
            ->table('test')
            ->where('test.name = :name', ['name' => 'John'])
            ->whereIsNull('test.deleted_at')
            ->whereIn('test.status', ['active', 'pending'])
            ->whereIn('test.other', ['1', '2']);

        $sql = $query->build();
        $sqlString = $sql->getSql();
        $params = $sql->getParams();

        // Check that the SQL contains all our where clauses
        $this->assertStringContainsString('test.name = :name', $sqlString);
        $this->assertStringContainsString('test.deleted_at IS NULL', $sqlString);

        // Extract parameter names for the first whereIn
        preg_match('/test\.status IN \(:([^,]+), :([^)]+)\)/', $sqlString, $statusMatches);
        $this->assertCount(3, $statusMatches, "Should have found 2 parameter names for status");

        // Extract parameter names for the second whereIn
        preg_match('/test\.other IN \(:([^,]+), :([^)]+)\)/', $sqlString, $otherMatches);
        $this->assertCount(3, $otherMatches, "Should have found 2 parameter names for other");

        // Verify the parameters are different between the two whereIn calls
        $this->assertNotEquals($statusMatches[1], $otherMatches[1], "Parameter names should be different");
        $this->assertNotEquals($statusMatches[2], $otherMatches[2], "Parameter names should be different");
        
        // Check parameters
        $this->assertEquals('John', $params['name']);
        $this->assertEquals('active', $params[$statusMatches[1]]);
        $this->assertEquals('pending', $params[$statusMatches[2]]);
        $this->assertEquals('1', $params[$otherMatches[1]]);
        $this->assertEquals('2', $params[$otherMatches[2]]);
    }
}