<?php

namespace Tests;

use ByJG\AnyDataset\Db\Helpers\DbSqliteFunctions;
use ByJG\MicroOrm\SqlObject;
use ByJG\MicroOrm\Updatable;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class UpdatableTest extends TestCase
{
    /**
     * @var Updatable
     */
    protected $object;

    protected function setUp(): void
    {
        $this->object = new Updatable();
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
        $sql = $this->object->buildInsert($params);
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
        $sql = $this->object->buildInsert($params, new DbSqliteFunctions());
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

    public function testUpdate()
    {
        $this->object->table('test');

        $this->object->fields(['fld1']);
        $this->object->fields(['fld2', 'fld3']);

        $this->object->where('fld1 = [[id]]', ['id' => 10]);

        $params = [];
        $sql = $this->object->buildUpdate($params);
        $this->assertEquals(
            [
                'UPDATE test SET fld1 = [[fld1]] , fld2 = [[fld2]] , fld3 = [[fld3]]  WHERE fld1 = [[id]]',
                [ 'id' => 10 ]
            ],
            [
                $sql,
                $params
            ]
        );

        $params = [];
        $sql = $this->object->buildUpdate($params, new DbSqliteFunctions());
        $this->assertEquals(
            [
                'UPDATE `test` SET `fld1` = [[fld1]] , `fld2` = [[fld2]] , `fld3` = [[fld3]]  WHERE fld1 = [[id]]',
                [ 'id' => 10 ]
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

        $this->object->fields(['fld1']);
        $this->object->fields(['fld2', 'fld3']);

        $params = [];
        $this->object->buildUpdate($params);
    }

    public function testDelete()
    {
        $this->object->table('test');
        $this->object->where('fld1 = [[id]]', ['id' => 10]);

        $params = [];
        $sql = $this->object->buildDelete($params);
        $this->assertEquals(
            [
                'DELETE FROM test WHERE fld1 = [[id]]',
                [ 'id' => 10 ]
            ],
            [
                $sql,
                $params
            ]

        );
    }

    public function testDeleteError()
    {
        $this->expectException(InvalidArgumentException::class);

        $params = [];

        $this->object->table('test');
        $this->object->buildDelete($params);
    }

    public function testQueryUpdatable()
    {
        $this->object->table('test');
        $this->assertEquals(
            new SqlObject('SELECT  * FROM test'),
            $this->object->build()
        );

        $this->object
            ->fields(['fld1'])
            ->fields(['fld2', 'fld3']);

        $this->assertEquals(
            new SqlObject('SELECT  fld1, fld2, fld3 FROM test'),
            $this->object->build()
        );

        $this->object
            ->where('fld2 = :teste', [ 'teste' => 10 ]);

        $this->assertEquals(
            new SqlObject('SELECT  fld1, fld2, fld3 FROM test WHERE fld2 = :teste', [ 'teste' => 10 ]),
            $this->object->build()
        );

        $this->object
            ->where('fld3 = 20');

        $this->assertEquals(
            new SqlObject('SELECT  fld1, fld2, fld3 FROM test WHERE fld2 = :teste AND fld3 = 20', [ 'teste' => 10 ]),
            $this->object->build()
        );

        $this->object
            ->where('fld1 = [[teste2]]', [ 'teste2' => 40 ]);

        $this->assertEquals(
            new SqlObject('SELECT  fld1, fld2, fld3 FROM test WHERE fld2 = :teste AND fld3 = 20 AND fld1 = [[teste2]]', [ 'teste' => 10, 'teste2' => 40 ]),
            $this->object->build()
        );
    }

}
