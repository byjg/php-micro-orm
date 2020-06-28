<?php

namespace Test;

use ByJG\MicroOrm\Insert;
use ByJG\MicroOrm\Literal;
use ByJG\MicroOrm\Query;
use PHPUnit\Framework\TestCase;

class QueryTest extends TestCase
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

    public function testTableAlias()
    {
        $this->object->table('test', "t");
        $this->object->where("1 = 1");
        $this->assertEquals(
            [
                'sql' => 'SELECT  * FROM test as t WHERE 1 = 1',
                'params' => []
            ],
            $this->object->build()
        );
    }

    public function testJoin()
    {
        $query = Query::getInstance()
            ->table('foo')
            ->join("bar", 'foo.id = bar.id')
            ->where('foo.field = :field', ['field' => new Literal('ABC')])
            ->where('bar.other = :other', ['other' => 'test']);

        $result = $query->build();

        $this->assertEquals(
            [
                'sql' => 'SELECT  * FROM foo INNER JOIN bar ON foo.id = bar.id WHERE foo.field = ABC AND bar.other = :other',
                'params' => ['other' => 'test']
            ],
            $result
        );
    }

    public function testJoinAlias()
    {
        $query = Query::getInstance()
            ->table('foo')
            ->join("bar", 'foo.id = b.id', 'b')
            ->where('foo.field = :field', ['field' => new Literal('ABC')])
            ->where('b.other = :other', ['other' => 'test']);

        $result = $query->build();

        $this->assertEquals(
            [
                'sql' => 'SELECT  * FROM foo INNER JOIN bar as b ON foo.id = b.id WHERE foo.field = ABC AND b.other = :other',
                'params' => ['other' => 'test']
            ],
            $result
        );
    }

    public function testLeftJoin()
    {
        $query = Query::getInstance()
            ->table('foo')
            ->leftJoin("bar", 'foo.id = bar.id')
            ->where('foo.field = :field', ['field' => new Literal('ABC')])
            ->where('bar.other = :other', ['other' => 'test']);

        $result = $query->build();

        $this->assertEquals(
            [
                'sql' => 'SELECT  * FROM foo LEFT JOIN bar ON foo.id = bar.id WHERE foo.field = ABC AND bar.other = :other',
                'params' => ['other' => 'test']
            ],
            $result
        );
    }

    public function testLeftJoinAlias()
    {
        $query = Query::getInstance()
            ->table('foo')
            ->leftJoin("bar", 'foo.id = b.id', "b")
            ->where('foo.field = :field', ['field' => new Literal('ABC')])
            ->where('b.other = :other', ['other' => 'test']);

        $result = $query->build();

        $this->assertEquals(
            [
                'sql' => 'SELECT  * FROM foo LEFT JOIN bar as b ON foo.id = b.id WHERE foo.field = ABC AND b.other = :other',
                'params' => ['other' => 'test']
            ],
            $result
        );
    }

    public function testRightJoin()
    {
        $query = Query::getInstance()
            ->table('foo')
            ->rightJoin("bar", 'foo.id = bar.id')
            ->where('foo.field = :field', ['field' => new Literal('ABC')])
            ->where('bar.other = :other', ['other' => 'test']);

        $result = $query->build();

        $this->assertEquals(
            [
                'sql' => 'SELECT  * FROM foo RIGHT JOIN bar ON foo.id = bar.id WHERE foo.field = ABC AND bar.other = :other',
                'params' => ['other' => 'test']
            ],
            $result
        );
    }

    public function testRightJoinAlias()
    {
        $query = Query::getInstance()
            ->table('foo')
            ->rightJoin("bar", 'foo.id = b.id', "b")
            ->where('foo.field = :field', ['field' => new Literal('ABC')])
            ->where('b.other = :other', ['other' => 'test']);

        $result = $query->build();

        $this->assertEquals(
            [
                'sql' => 'SELECT  * FROM foo RIGHT JOIN bar as b ON foo.id = b.id WHERE foo.field = ABC AND b.other = :other',
                'params' => ['other' => 'test']
            ],
            $result
        );
    }

    public function testSubQuery()
    {
        $subQuery = Query::getInstance()
            ->table("subtest")
            ->fields(
                [
                    "id",
                    "max(date) as date"
                ]
            )
            ->groupBy(["id"])
        ;

        $query = Query::getInstance()
            ->table('test')
            ->join($subQuery, 'test.id = sq.id', 'sq')
            ->where('test.date < :date', ['date' => '2020-06-28']);

        $result = $query->build();

        $this->assertEquals(
            [
                'sql' => 'SELECT  * FROM test INNER JOIN (SELECT  id, max(date) as date FROM subtest GROUP BY id) as sq ON test.id = sq.id WHERE test.date < :date',
                'params' => ['date' => '2020-06-28']
            ],
            $result
        );

    }

    /**
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     * @expectedException \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @expectedExceptionMessage SubQuery requires you define an alias
     */
    public function testSubQueryWithoutAlias()
    {
        $subQuery = Query::getInstance()
            ->table("subtest")
            ->fields(
                [
                    "id",
                    "max(date) as date"
                ]
            )
            ->groupBy(["id"])
        ;

        $query = Query::getInstance()
            ->table('test')
            ->join($subQuery, 'test.id = sq.id')
            ->where('test.date < :date', ['date' => '2020-06-28']);

        $result = $query->build();
    }

    /**
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     * @expectedException \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @expectedExceptionMessage SubQuery does not support filters
     */
    public function testSubQueryWithoFilter()
    {
        $subQuery = Query::getInstance()
            ->table("subtest")
            ->fields(
                [
                    "id",
                    "max(date) as date"
                ]
            )
            ->where("date > ':test'", ['test' => "2020-06-01"])
            ->groupBy(["id"])
        ;

        $query = Query::getInstance()
            ->table('test')
            ->join($subQuery, 'test.id = sq.id')
            ->where('test.date < :date', ['date' => '2020-06-28']);

        $result = $query->build();
    }
}
