<?php

namespace Test;

use ByJG\AnyDataset\Db\Helpers\DbSqliteFunctions;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\InsertQuery;
use ByJG\MicroOrm\Updatable;
use PHPUnit\Framework\TestCase;

class InsertQueryTest extends TestCase
{
    /**
     * @var Updatable
     */
    protected $object;

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

        $this->object->fields(['fld1']);
        $this->object->fields(['fld2', 'fld3']);

        $params = [];
        $sql = $this->object->build($params);
        $this->assertEquals(
            [
                'INSERT INTO test( fld1, fld2, fld3 )  values ( [[fld1]], [[fld2]], [[fld3]] ) ',
                []
            ],
            [
                $sql,
                $params
            ]
        );

        $params = [];
        $sql = $this->object->build($params, new DbSqliteFunctions());
        $this->assertEquals(
            [
                'INSERT INTO `test`( `fld1`, `fld2`, `fld3` )  values ( [[fld1]], [[fld2]], [[fld3]] ) ',
                []
            ],
            [
                $sql,
                $params
            ]
        );
    }

    public function testQueryUpdatable()
    {
        $this->object->table('test');
        $this->assertEquals(
            [
                'sql' => 'SELECT  * FROM test',
                'params' => []
            ],
            $this->object->convert()->build()
        );


        $this->object
            ->fields(['fld1'])
            ->fields(['fld2', 'fld3']);

        $this->assertEquals(
            [
                'sql' => 'SELECT  fld1, fld2, fld3 FROM test',
                'params' => []
            ],
            $this->object->convert()->build()
        );

        $this->object
            ->where('fld2 = :teste', [ 'teste' => 10 ]);

        $this->assertEquals(
            [
                'sql' => 'SELECT  fld1, fld2, fld3 FROM test WHERE fld2 = :teste',
                'params' => [ 'teste' => 10 ]
            ],
            $this->object->convert()->build()
        );

        $this->object
            ->where('fld3 = 20');

        $this->assertEquals(
            [
                'sql' => 'SELECT  fld1, fld2, fld3 FROM test WHERE fld2 = :teste AND fld3 = 20',
                'params' => [ 'teste' => 10 ]
            ],
            $this->object->convert()->build()
        );

        $this->object
            ->where('fld1 = [[teste2]]', [ 'teste2' => 40 ]);

        $this->assertEquals(
            [
                'sql' => 'SELECT  fld1, fld2, fld3 FROM test WHERE fld2 = :teste AND fld3 = 20 AND fld1 = [[teste2]]',
                'params' => [ 'teste' => 10, 'teste2' => 40 ]
            ],
            $this->object->convert()->build()
        );
    }

}
