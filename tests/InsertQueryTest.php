<?php

namespace Tests;

use ByJG\AnyDataset\Db\Helpers\DbSqliteFunctions;
use ByJG\AnyDataset\Db\SqlStatement;
use ByJG\MicroOrm\InsertQuery;
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

        $this->object->set('fld1', 'A');
        $this->object->set('fld2', 'B');
        $this->object->set('fld3', 'C');

        $sqlStatement = $this->object->build();
        $this->assertEquals(
            new SqlStatement('INSERT INTO test( fld1, fld2, fld3 )  values ( :fld1, :fld2, :fld3 ) ', ['fld1' => 'A', 'fld2' => 'B', 'fld3' => 'C']),
            $sqlStatement
        );

        $sqlStatement = $this->object->build(new DbSqliteFunctions());
        $this->assertEquals(
            new SqlStatement('INSERT INTO `test`( `fld1`, `fld2`, `fld3` )  values ( :fld1, :fld2, :fld3 ) ', ['fld1' => 'A', 'fld2' => 'B', 'fld3' => 'C']),
            $sqlStatement
        );
    }

    public function testQueryUpdatable()
    {
        $this->object->table('test');
        $this->assertEquals(
            new SqlStatement('SELECT  * FROM test'),
            $this->object->convert()->build()
        );

        $this->object->set('fld1', 'A');
        $this->object->set('fld2', 'B');
        $this->object->set('fld3', 'C');

        $this->assertEquals(
            new SqlStatement('SELECT  fld1, fld2, fld3 FROM test'),
            $this->object->convert()->build()
        );

        $this->object
            ->where('fld2 = :teste', [ 'teste' => 10 ]);

        $this->assertEquals(
            new SqlStatement('SELECT  fld1, fld2, fld3 FROM test WHERE fld2 = :teste', ['teste' => 10]),
            $this->object->convert()->build()
        );

        $this->object
            ->where('fld3 = 20');

        $this->assertEquals(
            new SqlStatement('SELECT  fld1, fld2, fld3 FROM test WHERE fld2 = :teste AND fld3 = 20', ['teste' => 10]),
            $this->object->convert()->build()
        );

        $this->object
            ->where('fld1 = :teste2', [ 'teste2' => 40 ]);

        $this->assertEquals(
            new SqlStatement('SELECT  fld1, fld2, fld3 FROM test WHERE fld2 = :teste AND fld3 = 20 AND fld1 = :teste2', ['teste' => 10, 'teste2' => 40]),
            $this->object->convert()->build()
        );
    }

}
