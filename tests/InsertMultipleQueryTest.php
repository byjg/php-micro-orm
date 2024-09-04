<?php

namespace Tests;

use ByJG\MicroOrm\InsertMultipleQuery;
use PHPUnit\Framework\TestCase;

class InsertMultipleQueryTest extends TestCase
{
    /**
     * @var InsertMultipleQuery
     */
    protected $object;

    protected function setUp(): void
    {
        $this->object = new InsertMultipleQuery();
    }

    protected function tearDown(): void
    {
        $this->object = null;
    }

    public function testInsert()
    {
        $this->object->table('test');
        $this->object->fields(['fld1', 'fld2', 'fld3']);

        $this->object->addRow(['fld1' => 'A', 'fld2' => 'B', 'fld3' => 'C']);
        $this->object->addRow(['fld1' => 'D', 'fld2' => 'E', 'fld3' => 'F']);

        $params = [];
        $sql = $this->object->build($params);

        $this->assertEquals('INSERT INTO test ( fld1, fld2, fld3 )  values  ( :fld11, :fld21, :fld31 ), ( :fld12, :fld22, :fld32 )', $sql);
        $this->assertEquals([
            'fld11' => 'A',
            'fld21' => 'B',
            'fld31' => 'C',
            'fld12' => 'D',
            'fld22' => 'E',
            'fld32' => 'F',
        ], $params);
    }

    public function testInsertStatic()
    {
        $query = InsertMultipleQuery::getInstance('test', ['fld1', 'fld2', 'fld3']);

        $query->addRow(['fld1' => 'A', 'fld2' => 'B', 'fld3' => 'C']);
        $query->addRow(['fld1' => 'D', 'fld2' => 'E', 'fld3' => 'F']);

        $params = [];
        $sql = $query->build($params);

        $this->assertEquals('INSERT INTO test ( fld1, fld2, fld3 )  values  ( :fld11, :fld21, :fld31 ), ( :fld12, :fld22, :fld32 )', $sql);
        $this->assertEquals([
            'fld11' => 'A',
            'fld21' => 'B',
            'fld31' => 'C',
            'fld12' => 'D',
            'fld22' => 'E',
            'fld32' => 'F',
        ], $params);
    }

    public function testInsertError()
    {
        $query = InsertMultipleQuery::getInstance('test', ['fld1', 'fld2', 'fld3']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The row must have the same number of fields');

        $query->addRow(['fld1' => 'A', 'fld2' => 'B']);
    }

    public function testInsertError2()
    {
        $query = InsertMultipleQuery::getInstance('test', ['fld1', 'fld2', 'fld3']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("The field 'fld3' must be in the row");

        $query->addRow(['fld1' => 'A', 'fld2' => 'B', 'nonexistent' => 'C']);
    }

}
