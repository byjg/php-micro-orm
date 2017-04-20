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

        $this->assertEquals(
            'INSERT INTO test( fld1, fld2, fld3 )  values ( [[fld1]], [[fld2]], [[fld3]] ) ',
            $this->object->buildInsert()
        );

        $this->assertEquals(
            'INSERT INTO `test`( `fld1`, `fld2`, `fld3` )  values ( [[fld1]], [[fld2]], [[fld3]] ) ',
            $this->object->buildInsert(new DbSqliteFunctions())
        );
    }

    public function testUpdate()
    {
        $this->object->table('test');

        $this->object->fields(['fld1']);
        $this->object->fields(['fld2', 'fld3']);

        $this->object->where('fld1 = [[id]]', ['id' => 10]);

        $this->assertEquals(
            [
                'sql' => 'UPDATE test SET fld1 = [[fld1]] , fld2 = [[fld2]] , fld3 = [[fld3]]  WHERE fld1 = [[id]]',
                'params' => [ 'id' => 10 ]
            ],
            $this->object->buildUpdate()
        );

        $this->assertEquals(
            [
                'sql' => 'UPDATE `test` SET `fld1` = [[fld1]] , `fld2` = [[fld2]] , `fld3` = [[fld3]]  WHERE fld1 = [[id]]',
                'params' => [ 'id' => 10 ]
            ],
            $this->object->buildUpdate(new DbSqliteFunctions())
        );
    }

    /**
     * @expectedException \Exception
     */
    public function testUpdateError()
    {
        $this->object->table('test');

        $this->object->fields(['fld1']);
        $this->object->fields(['fld2', 'fld3']);

        $this->object->buildUpdate();
    }

    public function testDelete()
    {
        $this->object->table('test');
        $this->object->where('fld1 = [[id]]', ['id' => 10]);

        $this->assertEquals(
            [
                'sql' => 'DELETE FROM test WHERE fld1 = [[id]]',
                'params' => [ 'id' => 10 ]
            ],
            $this->object->buildDelete()
        );
    }

    /**
     * @expectedException \Exception
     */
    public function testDeleteError()
    {
        $this->object->table('test');
        $this->object->buildDelete();
    }
}
