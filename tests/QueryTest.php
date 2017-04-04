<?php
/**
 * User: jg
 * Date: 04/04/17
 * Time: 19:18
 */

namespace Test;

use ByJG\AnyDataset\Store\Helpers\DbSqliteFunctions;
use ByJG\MicroOrm\Literal;
use ByJG\MicroOrm\Query;

// backward compatibility
if (!class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit_Framework_TestCase', '\PHPUnit\Framework\TestCase');
}

class QueryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Query
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new Query();
    }

    protected function tearDown()
    {
        $this->object = null;
    }

    public function testQueryBasic()
    {
        $this->object->table('test');
        $this->assertEquals(
            [
                'sql' => 'SELECT  * FROM test',
                'params' => null
            ],
            $this->object->getSelect()
        );


        $this->object
            ->fields(['fld1'])
            ->fields(['fld2', 'fld3']);

        $this->assertEquals(
            [
                'sql' => 'SELECT  fld1, fld2, fld3 FROM test',
                'params' => null
            ],
            $this->object->getSelect()
        );

        $this->object
            ->orderBy(['fld1']);

        $this->assertEquals(
            [
                'sql' => 'SELECT  fld1, fld2, fld3 FROM test ORDER BY fld1',
                'params' => null
            ],
            $this->object->getSelect()
        );

        $this->object
            ->groupBy(['fld1', 'fld2', 'fld3']);

        $this->assertEquals(
            [
                'sql' => 'SELECT  fld1, fld2, fld3 FROM test GROUP BY fld1, fld2, fld3 ORDER BY fld1',
                'params' => null
            ],
            $this->object->getSelect()
        );

        $this->object
            ->where('fld2 = :teste', [ 'teste' => 10 ]);

        $this->assertEquals(
            [
                'sql' => 'SELECT  fld1, fld2, fld3 FROM test WHERE fld2 = :teste GROUP BY fld1, fld2, fld3 ORDER BY fld1',
                'params' => [ 'teste' => 10 ]
            ],
            $this->object->getSelect()
        );

        $this->object
            ->where('fld3 = 20');

        $this->assertEquals(
            [
                'sql' => 'SELECT  fld1, fld2, fld3 FROM test WHERE fld2 = :teste AND fld3 = 20 GROUP BY fld1, fld2, fld3 ORDER BY fld1',
                'params' => [ 'teste' => 10 ]
            ],
            $this->object->getSelect()
        );

        $this->object
            ->where('fld1 = [[teste2]]', [ 'teste2' => 40 ]);

        $this->assertEquals(
            [
                'sql' => 'SELECT  fld1, fld2, fld3 FROM test WHERE fld2 = :teste AND fld3 = 20 AND fld1 = [[teste2]] GROUP BY fld1, fld2, fld3 ORDER BY fld1',
                'params' => [ 'teste' => 10, 'teste2' => 40 ]
            ],
            $this->object->getSelect()
        );
    }

    public function testInsert()
    {
        $this->object->table('test');

        $this->object->fields(['fld1']);
        $this->object->fields(['fld2', 'fld3']);

        $this->assertEquals(
            'INSERT INTO test( fld1, fld2, fld3 )  values ( [[fld1]], [[fld2]], [[fld3]] ) ',
            $this->object->getInsert()
        );

        $this->assertEquals(
            'INSERT INTO `test`( `fld1`, `fld2`, `fld3` )  values ( [[fld1]], [[fld2]], [[fld3]] ) ',
            $this->object->getInsert(new DbSqliteFunctions())
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
            $this->object->getUpdate()
        );

        $this->assertEquals(
            [
                'sql' => 'UPDATE `test` SET `fld1` = [[fld1]] , `fld2` = [[fld2]] , `fld3` = [[fld3]]  WHERE fld1 = [[id]]',
                'params' => [ 'id' => 10 ]
            ],
            $this->object->getUpdate(new DbSqliteFunctions())
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

        $this->object->getUpdate();
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
            $this->object->getDelete()
        );
    }

    /**
     * @expectedException \Exception
     */
    public function testDeleteError()
    {
        $this->object->table('test');
        $this->object->getDelete();
    }
}
