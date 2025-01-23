<?php

namespace Tests;

use ByJG\MicroOrm\InsertBulkQuery;
use PHPUnit\Framework\TestCase;

class InsertBulkQueryTest extends TestCase
{
    public function testInsert()
    {
        $insertBulk = new InsertBulkQuery('test', ['fld1', 'fld2']);
        $insertBulk->values(['fld1' => 'A', 'fld2' => 'B']);
        $insertBulk->values(['fld1' => 'D', 'fld2' => 'E']);
        $insertBulk->values(['fld1' => 'G', 'fld2' => 'H']);

        $sqlObject = $insertBulk->build();

        $this->assertEquals('INSERT INTO test (fld1, fld2) VALUES (:p0_0, :p0_1), (:p1_0, :p1_1), (:p2_0, :p2_1)', $sqlObject->getSql());
        $this->assertEquals([
            'p0_0' => 'A',
            'p0_1' => 'B',
            'p1_0' => 'D',
            'p1_1' => 'E',
            'p2_0' => 'G',
            'p2_1' => 'H',
        ], $sqlObject->getParameters());
    }

    public function testInsertDifferentOrder()
    {
        $insertBulk = new InsertBulkQuery('test', ['fld1', 'fld2', 'fld3']);
        $insertBulk->values(['fld2' => 'B', 'fld1' => 'A', 'fld3' => 'C']);
        $insertBulk->values(['fld3' => 'F', 'fld1' => 'D', 'fld2' => 'E', 'fld4' => 'X']);
        $insertBulk->values(['fld2' => 'H', 'fld3' => 'I', 'fld1' => 'G']);

        $sqlObject = $insertBulk->build();

        $this->assertEquals('INSERT INTO test (fld1, fld2, fld3) VALUES (:p0_0, :p0_1, :p0_2), (:p1_0, :p1_1, :p1_2), (:p2_0, :p2_1, :p2_2)', $sqlObject->getSql());
        $this->assertEquals([
            'p0_0' => 'A',
            'p0_1' => 'B',
            'p0_2' => 'C',
            'p1_0' => 'D',
            'p1_1' => 'E',
            'p1_2' => 'F',
            'p2_0' => 'G',
            'p2_1' => 'H',
            'p2_2' => 'I',
        ], $sqlObject->getParameters());
    }

    public function testWrongFieldsValuesCount()
    {
        $this->expectExceptionMessage('The provided values do not match the expected fields');
        $insertBulk = new InsertBulkQuery('test', ['fld1', 'fld2', 'fld3']);
        $insertBulk->values(['fld1' => 'A', 'fld2' => 'B']);
    }

    public function testWrongFieldsValuesCount2()
    {
        $this->expectExceptionMessage('The provided values do not match the expected fields');
        $insertBulk = new InsertBulkQuery('test', ['fld1', 'fld2', 'fld3']);
        $insertBulk->values(['fld1' => 'A', 'fld2' => 'B', 'fld4' => 'C']);
    }

    public function testWrongValuesFieldsCount()
    {
        $this->expectExceptionMessage('The provided values contain more fields than expected');
        $insertBulk = new InsertBulkQuery('test', ['fld1', 'fld2', 'fld3']);
        $insertBulk->values(['fld1' => 'A', 'fld2' => 'B', 'fld3' => 'C', 'fld4' => 'D'], allowNonMatchFields: false);
    }

}
