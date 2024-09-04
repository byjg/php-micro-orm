<?php

namespace Tests;

use ByJG\MicroOrm\InsertMultipleQuery;
use ByJG\MicroOrm\SqlObject;
use ByJG\MicroOrm\SqlObjectEnum;
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

}
