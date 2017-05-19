<?php
/**
 * User: jg
 * Date: 04/04/17
 * Time: 19:18
 */

namespace Test;

use ByJG\MicroOrm\Insert;
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
            $this->object->build()
        );


        $this->object
            ->fields(['fld1'])
            ->fields(['fld2', 'fld3']);

        $this->assertEquals(
            [
                'sql' => 'SELECT  fld1, fld2, fld3 FROM test',
                'params' => null
            ],
            $this->object->build()
        );

        $this->object
            ->orderBy(['fld1']);

        $this->assertEquals(
            [
                'sql' => 'SELECT  fld1, fld2, fld3 FROM test ORDER BY fld1',
                'params' => null
            ],
            $this->object->build()
        );

        $this->object
            ->groupBy(['fld1', 'fld2', 'fld3']);

        $this->assertEquals(
            [
                'sql' => 'SELECT  fld1, fld2, fld3 FROM test GROUP BY fld1, fld2, fld3 ORDER BY fld1',
                'params' => null
            ],
            $this->object->build()
        );

        $this->object
            ->where('fld2 = :teste', [ 'teste' => 10 ]);

        $this->assertEquals(
            [
                'sql' => 'SELECT  fld1, fld2, fld3 FROM test WHERE fld2 = :teste GROUP BY fld1, fld2, fld3 ORDER BY fld1',
                'params' => [ 'teste' => 10 ]
            ],
            $this->object->build()
        );

        $this->object
            ->where('fld3 = 20');

        $this->assertEquals(
            [
                'sql' => 'SELECT  fld1, fld2, fld3 FROM test WHERE fld2 = :teste AND fld3 = 20 GROUP BY fld1, fld2, fld3 ORDER BY fld1',
                'params' => [ 'teste' => 10 ]
            ],
            $this->object->build()
        );

        $this->object
            ->where('fld1 = [[teste2]]', [ 'teste2' => 40 ]);

        $this->assertEquals(
            [
                'sql' => 'SELECT  fld1, fld2, fld3 FROM test WHERE fld2 = :teste AND fld3 = 20 AND fld1 = [[teste2]] GROUP BY fld1, fld2, fld3 ORDER BY fld1',
                'params' => [ 'teste' => 10, 'teste2' => 40 ]
            ],
            $this->object->build()
        );
    }

    public function testLiteral()
    {
        $query = Query::getInstance()
            ->table('test')
            ->where('field = :field', ['field' => new Literal('ABC')]);

        $result = $query->build();

        $this->assertEquals(
            [
                'sql' => 'SELECT  * FROM test WHERE field = ABC',
                'params' => []
            ],
            $result
        );
    }

    public function testLiteral2()
    {
        $query = Query::getInstance()
            ->table('test')
            ->where('field = :field', ['field' => new Literal('ABC')])
            ->where('other = :other', ['other' => 'test']);

        $result = $query->build();

        $this->assertEquals(
            [
                'sql' => 'SELECT  * FROM test WHERE field = ABC AND other = :other',
                'params' => ['other' => 'test']
            ],
            $result
        );
    }
}
