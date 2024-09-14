<?php

namespace Tests;

use ByJG\AnyDataset\Db\Helpers\DbSqliteFunctions;
use ByJG\MicroOrm\InsertQuery;
use ByJG\MicroOrm\SqlObject;
use ByJG\MicroOrm\SqlObjectEnum;
use PHPUnit\Framework\TestCase;

class InsertQueryTest extends TestCase
{
    /**
     * @var InsertQuery|null
     */
    protected ?InsertQuery $object;

    protected function setUp(): void
    {
        $this->object = new InsertQuery();
    }

    protected function tearDown(): void
    {
        $this->object = null;
    }

    public function testInsert()
    {
        $this->object->table('test');

        $this->object->field('fld1', 'A');
        $this->object->field('fld2', 'B');
        $this->object->field('fld3', 'C');

        $sqlObject = $this->object->build();
        $this->assertEquals(
            new SqlObject('INSERT INTO test( fld1, fld2, fld3 )  values ( :fld1, :fld2, :fld3 ) ', [ 'fld1' => 'A', 'fld2'=> 'B', 'fld3' => 'C' ], SqlObjectEnum::INSERT),
            $sqlObject
        );

        $sqlObject = $this->object->build(new DbSqliteFunctions());
        $this->assertEquals(
            new SqlObject('INSERT INTO `test`( `fld1`, `fld2`, `fld3` )  values ( :fld1, :fld2, :fld3 ) ', [ 'fld1' => 'A', 'fld2'=> 'B', 'fld3' => 'C' ], SqlObjectEnum::INSERT),
            $sqlObject
        );
    }

    public function testQueryUpdatable()
    {
        $this->object->table('test');
        $this->assertEquals(
            new SqlObject('SELECT  * FROM test'),
            $this->object->convert()->build()
        );

        $this->object->field('fld1', 'A');
        $this->object->field('fld2', 'B');
        $this->object->field('fld3', 'C');

        $this->assertEquals(
            new SqlObject('SELECT  fld1, fld2, fld3 FROM test'),
            $this->object->convert()->build()
        );

        $this->object
            ->where('fld2 = :teste', [ 'teste' => 10 ]);

        $this->assertEquals(
            new SqlObject('SELECT  fld1, fld2, fld3 FROM test WHERE fld2 = :teste', [ 'teste' => 10 ]),
            $this->object->convert()->build()
        );

        $this->object
            ->where('fld3 = 20');

        $this->assertEquals(
            new SqlObject('SELECT  fld1, fld2, fld3 FROM test WHERE fld2 = :teste AND fld3 = 20', [ 'teste' => 10 ]),
            $this->object->convert()->build()
        );

        $this->object
            ->where('fld1 = :teste2', [ 'teste2' => 40 ]);

        $this->assertEquals(
            new SqlObject('SELECT  fld1, fld2, fld3 FROM test WHERE fld2 = :teste AND fld3 = 20 AND fld1 = :teste2', [ 'teste' => 10, 'teste2' => 40 ]),
            $this->object->convert()->build()
        );
    }

}
