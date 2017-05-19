<?php
/**
 * User: jg
 * Date: 04/04/17
 * Time: 19:18
 */

namespace Test;

use ByJG\AnyDataset\Store\Helpers\DbSqliteFunctions;
use ByJG\MicroOrm\Updatable;

// backward compatibility
if (!class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit_Framework_TestCase', '\PHPUnit\Framework\TestCase');
}

class UpdatableTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Updatable
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new Updatable();
    }

    protected function tearDown()
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

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testUpdateError()
    {
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

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDeleteError()
    {
        $params = [];

        $this->object->table('test');
        $this->object->buildDelete($params);
    }
}
