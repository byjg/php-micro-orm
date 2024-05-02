<?php

namespace Test;

use ByJG\AnyDataset\Db\Helpers\DbSqliteFunctions;
use ByJG\MicroOrm\Updatable;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\UpdateQuery;
use PHPUnit\Framework\TestCase;

class UpdateQueryTest extends TestCase
{
    /**
     * @var Updatable
     */
    protected $object;

    protected function setUp(): void
    {
        $this->object = new UpdateQuery();
    }

    protected function tearDown(): void
    {
        $this->object = null;
    }

    public function testUpdate()
    {
        $this->object->table('test');

        $this->object->set('fld1', 'A');
        $this->object->set('fld2', 'B');
        $this->object->set('fld3', 'C');

        $this->object->where('fld1 = [[id]]', ['id' => 10]);

        $params = [];
        $sql = $this->object->build($params);
        $this->assertEquals(
            [
                'UPDATE test SET fld1 = [[fld1]] , fld2 = [[fld2]] , fld3 = [[fld3]]  WHERE fld1 = [[id]]',
                [ 'id' => 10, 'fld1' => 'A', 'fld2' => 'B', 'fld3' => 'C' ]
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
                'UPDATE `test` SET `fld1` = [[fld1]] , `fld2` = [[fld2]] , `fld3` = [[fld3]]  WHERE fld1 = [[id]]',
                [ 'id' => 10, 'fld1' => 'A', 'fld2' => 'B', 'fld3' => 'C' ]
            ],
            [
                $sql,
                $params
            ]
        );
    }

    public function testUpdateError()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->object->table('test');

        $this->object->set('fld1', 'A');
        $this->object->set('fld2', 'B');
        $this->object->set('fld3', 'C');

        $params = [];
        $this->object->build($params);
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
            ->set('fld1', 'A')
            ->set('fld2', 'B')
            ->set('fld3', 'C');

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
