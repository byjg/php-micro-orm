<?php

namespace Test;

use ByJG\MicroOrm\Exception\InvalidArgumentException;
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

    protected function setUp(): void
    {
        $this->object = new Query();
    }

    protected function tearDown(): void
    {
        $this->object = null;
    }

    public function testQueryBasic()
    {
        $this->object->table('test');
        $this->assertEquals(
            [
                'sql' => 'SELECT  * FROM test',
                'params' => []
            ],
            $this->object->build()
        );


        $this->object
            ->fields(['fld1'])
            ->fields(['fld2', 'fld3']);

        $this->assertEquals(
            [
                'sql' => 'SELECT  fld1, fld2, fld3 FROM test',
                'params' => []
            ],
            $this->object->build()
        );

        $this->object
            ->orderBy(['fld1']);

        $this->assertEquals(
            [
                'sql' => 'SELECT  fld1, fld2, fld3 FROM test ORDER BY fld1',
                'params' => []
            ],
            $this->object->build()
        );

        $this->object
            ->groupBy(['fld1', 'fld2', 'fld3']);

        $this->assertEquals(
            [
                'sql' => 'SELECT  fld1, fld2, fld3 FROM test GROUP BY fld1, fld2, fld3 ORDER BY fld1',
                'params' => []
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

    public function testCrossJoin()
    {
        $query = Query::getInstance()
            ->table('foo')
            ->crossJoin("bar")
            ->where('foo.field = :field', ['field' => new Literal('ABC')])
            ->where('bar.other = :other', ['other' => 'test']);

        $result = $query->build();

        $this->assertEquals(
            [
                'sql' => 'SELECT  * FROM foo CROSS JOIN bar WHERE foo.field = ABC AND bar.other = :other',
                'params' => ['other' => 'test']
            ],
            $result
        );
    }

    public function testCrossJoinAlias()
    {
        $query = Query::getInstance()
            ->table('foo')
            ->crossJoin("bar", "b")
            ->where('foo.field = :field', ['field' => new Literal('ABC')])
            ->where('b.other = :other', ['other' => 'test']);

        $result = $query->build();

        $this->assertEquals(
            [
                'sql' => 'SELECT  * FROM foo CROSS JOIN bar as b WHERE foo.field = ABC AND b.other = :other',
                'params' => ['other' => 'test']
            ],
            $result
        );
    }

    public function testSubQueryTable()
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
            ->table($subQuery, 'sq')
            ->where('sq.date < :date', ['date' => '2020-06-01']);

        $result = $query->build();

        $this->assertEquals(
            [
                'sql' => 'SELECT  * FROM (SELECT  id, max(date) as date FROM subtest GROUP BY id) as sq WHERE sq.date < :date',
                'params' => ['date' => '2020-06-01']
            ],
            $result
        );

    }

    /**
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    public function testSubQueryTableWithoutAlias()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("SubQuery requires you define an alias");

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
            ->table($subQuery)
            ->where('sq.date < :date', ['date' => '2020-06-01']);

        $result = $query->build();
    }

    /**
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    public function testSubQueryTableWithFilter()
    {
        $subQuery = Query::getInstance()
            ->table("subtest")
            ->fields(
                [
                    "id",
                    "max(date) as date"
                ]
            )
            ->where("date > :test", ['test' => "2020-06-01"])
            ->groupBy(["id"])
        ;

        $query = Query::getInstance()
            ->table($subQuery, 'sq')
            ->where('sq.date < :date', ['date' => '2020-06-28']);

        $result = $query->build();

        $this->assertEquals(
            [
                'sql' => 'SELECT  * FROM (SELECT  id, max(date) as date FROM subtest WHERE date > :test GROUP BY id) as sq WHERE sq.date < :date',
                'params' => [
                    'test' => '2020-06-01',
                    'date' => '2020-06-28',
                ]
            ],
            $result
        );
    }

    public function testSubQueryJoin()
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
     */
    public function testSubQueryJoinWithoutAlias()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("SubQuery requires you define an alias");

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
     */
    public function testSubQueryJoinWithFilter()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("SubQuery does not support filters");

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

    public function testSubQueryField()
    {
        $subQuery = Query::getInstance()
            ->table("subtest")
            ->fields(
                [
                    "max(date) as date"
                ]
            )
        ;

        $query = Query::getInstance()
            ->table('test')
            ->fields(
                [
                    "test.id",
                    "test.name",
                    "test.date",
                ]
            )
            ->field($subQuery, 'subdate')
            ->where('test.date < :date', ['date' => '2020-06-28']);

        $result = $query->build();

        $this->assertEquals(
            [
                'sql' => 'SELECT  test.id, test.name, test.date, (SELECT  max(date) as date FROM subtest) as subdate FROM test WHERE test.date < :date',
                'params' => ['date' => '2020-06-28']
            ],
            $result
        );

    }
}
