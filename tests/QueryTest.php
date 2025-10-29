<?php

namespace Tests;

use ByJG\AnyDataset\Core\Enum\Relation;
use ByJG\AnyDataset\Core\IteratorFilter;
use ByJG\AnyDataset\Db\SqlStatement;
use ByJG\MicroOrm\DeleteQuery;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Literal\Literal;
use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\ORM;
use ByJG\MicroOrm\Query;
use ByJG\MicroOrm\QueryBasic;
use ByJG\MicroOrm\Recursive;
use ByJG\MicroOrm\UpdateQuery;
use Override;
use PHPUnit\Framework\TestCase;
use Tests\Model\ModelWithAttributes;

class QueryTest extends TestCase
{
    /**
     * @var Query
     */
    protected $object;

    #[Override]
    protected function setUp(): void
    {
        $this->object = new Query();
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->object = null;
        ORM::resetMemory();
    }

    public function testQueryBasic()
    {
        $this->object->table('test');
        $this->assertEquals(
            new SqlStatement('SELECT  * FROM test'),
            $this->object->build()
        );


        $this->object
            ->fields(['fld1'])
            ->fields(['fld2', 'fld3']);

        $this->assertEquals(
            new SqlStatement('SELECT  fld1, fld2, fld3 FROM test'),
            $this->object->build()
        );

        $this->object
            ->orderBy(['fld1']);

        $this->assertEquals(
            new SqlStatement('SELECT  fld1, fld2, fld3 FROM test ORDER BY fld1'),
            $this->object->build()
        );

        $this->object
            ->groupBy(['fld1', 'fld2', 'fld3']);

        $this->assertEquals(
            new SqlStatement('SELECT  fld1, fld2, fld3 FROM test GROUP BY fld1, fld2, fld3 ORDER BY fld1'),
            $this->object->build()
        );

        $this->object
            ->where('fld2 = :teste', [ 'teste' => 10 ]);

        $this->assertEquals(
            new SqlStatement('SELECT  fld1, fld2, fld3 FROM test WHERE fld2 = :teste GROUP BY fld1, fld2, fld3 ORDER BY fld1', ['teste' => 10]),
            $this->object->build()
        );

        $this->object
            ->where('fld3 = 20');

        $this->assertEquals(
            new SqlStatement('SELECT  fld1, fld2, fld3 FROM test WHERE fld2 = :teste AND fld3 = 20 GROUP BY fld1, fld2, fld3 ORDER BY fld1', ['teste' => 10]),
            $this->object->build()
        );

        $this->object
            ->where('fld1 = :teste2', [ 'teste2' => 40 ]);

        $this->assertEquals(
            new SqlStatement('SELECT  fld1, fld2, fld3 FROM test WHERE fld2 = :teste AND fld3 = 20 AND fld1 = :teste2 GROUP BY fld1, fld2, fld3 ORDER BY fld1', ['teste' => 10, 'teste2' => 40]),
            $this->object->build()
        );

        $this->object
            ->having('count(fld1) > 1');

        $this->assertEquals(
            new SqlStatement('SELECT  fld1, fld2, fld3 FROM test WHERE fld2 = :teste AND fld3 = 20 AND fld1 = :teste2 GROUP BY fld1, fld2, fld3 HAVING count(fld1) > 1 ORDER BY fld1', ['teste' => 10, 'teste2' => 40]),
            $this->object->build()
        );

        $iteratorFilter = new IteratorFilter();
        $iteratorFilter->and('fld4', Relation::EQUAL, 40);
        $this->object->where($iteratorFilter);
        $this->assertEquals(
            new SqlStatement('SELECT  fld1, fld2, fld3 FROM test WHERE fld2 = :teste AND fld3 = 20 AND fld1 = :teste2 AND  fld4 = :fld4  GROUP BY fld1, fld2, fld3 HAVING count(fld1) > 1 ORDER BY fld1', ['teste' => 10, 'teste2' => 40, 'fld4' => 40]),
            $this->object->build()
        );
    }

    public function testQueryWhereIteratorFilter()
    {
        $this->object->table('test');

        $filter = IteratorFilter::getInstance()
            ->and('fld1', Relation::EQUAL, 10)
            ->and('fld2', Relation::EQUAL, 20)
            ->or('fld1', Relation::EQUAL, 30);

        $this->object->where($filter);


        $this->assertEquals(
            new SqlStatement('SELECT  * FROM test WHERE  fld1 = :fld1  and  fld2 = :fld2  or  fld1 = :fld10 ', ['fld1' => 10, 'fld2' => '20', 'fld10' => '30']),
            $this->object->build()
        );
    }

    /**
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function testConvertQueryToQueryBasic()
    {
        $query = Query::getInstance()
            ->table('test')
            ->fields(['fld1'])
            ->fields(['fld2', 'fld3'])
            ->orderBy(['fld1'])
            ->groupBy(['fld1', 'fld2', 'fld3'])
            ->having('count(fld1) > 1')
            ->join('table2', 'table2.id = test.id')
            ->leftJoin('table3', 'table3.id = test.id')
            ->rightJoin('table4', 'table4.id = test.id')
            ->crossJoin('table5', 'table5.id = test.id')
            ->withRecursive(new Recursive('table6'))
            ->where('fld2 = :teste', [ 'teste' => 10 ])
            ->where('fld3 = 20')
            ->where('fld1 = :teste2', [ 'teste2' => 40 ]);

        $expectedSql = 'WITH RECURSIVE table6() AS (SELECT  UNION ALL SELECT  FROM table6 WHERE ) SELECT  fld1, fld2, fld3 FROM test INNER JOIN table2 ON table2.id = test.id LEFT JOIN table3 ON table3.id = test.id RIGHT JOIN table4 ON table4.id = test.id CROSS JOIN table5 as table5.id = test.id WHERE fld2 = :teste AND fld3 = 20 AND fld1 = :teste2 GROUP BY fld1, fld2, fld3 HAVING count(fld1) > 1 ORDER BY fld1';

        $this->assertEquals(
            new SqlStatement($expectedSql, ['teste' => 10, 'teste2' => 40]),
            $query->build()
        );

        $queryBasic = $query->getQueryBasic();
        $expectedSql2 = 'WITH RECURSIVE table6() AS (SELECT  UNION ALL SELECT  FROM table6 WHERE ) SELECT  fld1, fld2, fld3 FROM test INNER JOIN table2 ON table2.id = test.id LEFT JOIN table3 ON table3.id = test.id RIGHT JOIN table4 ON table4.id = test.id CROSS JOIN table5 as table5.id = test.id WHERE fld2 = :teste AND fld3 = 20 AND fld1 = :teste2 GROUP BY fld1, fld2, fld3 HAVING count(fld1) > 1';
        $this->assertEquals(
            new SqlStatement($expectedSql2, ['teste' => 10, 'teste2' => 40]),
            $queryBasic->build()
        );
    }

    public function testLiteral()
    {
        $query = Query::getInstance()
            ->table('test')
            ->where('field = :field', ['field' => new Literal('ABC')]);

        $result = $query->build();

        $this->assertEquals(
            new SqlStatement('SELECT  * FROM test WHERE field = ABC', []),
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
            new SqlStatement('SELECT  * FROM test WHERE field = ABC AND other = :other', ['other' => 'test']),
            $result
        );
    }

    public function testTableAlias()
    {
        $this->object->table('test', "t");
        $this->object->where("1 = 1");
        $this->assertEquals(
            new SqlStatement('SELECT  * FROM test as t WHERE 1 = 1', []),
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
            new SqlStatement('SELECT  * FROM foo INNER JOIN bar ON foo.id = bar.id WHERE foo.field = ABC AND bar.other = :other', ['other' => 'test']),
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
            new SqlStatement('SELECT  * FROM foo INNER JOIN bar as b ON foo.id = b.id WHERE foo.field = ABC AND b.other = :other', ['other' => 'test']),
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
            new SqlStatement('SELECT  * FROM foo LEFT JOIN bar ON foo.id = bar.id WHERE foo.field = ABC AND bar.other = :other', ['other' => 'test']),
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
            new SqlStatement('SELECT  * FROM foo LEFT JOIN bar as b ON foo.id = b.id WHERE foo.field = ABC AND b.other = :other', ['other' => 'test']),
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
            new SqlStatement('SELECT  * FROM foo RIGHT JOIN bar ON foo.id = bar.id WHERE foo.field = ABC AND bar.other = :other', ['other' => 'test']),
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
            new SqlStatement('SELECT  * FROM foo RIGHT JOIN bar as b ON foo.id = b.id WHERE foo.field = ABC AND b.other = :other', ['other' => 'test']),
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
            new SqlStatement('SELECT  * FROM foo CROSS JOIN bar WHERE foo.field = ABC AND bar.other = :other', ['other' => 'test']),
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
            new SqlStatement('SELECT  * FROM foo CROSS JOIN bar as b WHERE foo.field = ABC AND b.other = :other', ['other' => 'test']),
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
            new SqlStatement('SELECT  * FROM (SELECT  id, max(date) as date FROM subtest GROUP BY id) as sq WHERE sq.date < :date', ['date' => '2020-06-01']),
            $result
        );

    }

    /**
     * @throws InvalidArgumentException
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
     * @throws InvalidArgumentException
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
            new SqlStatement('SELECT  * FROM (SELECT  id, max(date) as date FROM subtest WHERE date > :test GROUP BY id) as sq WHERE sq.date < :date', [
                    'test' => '2020-06-01',
                    'date' => '2020-06-28',
                ]),
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
            new SqlStatement('SELECT  * FROM test INNER JOIN (SELECT  id, max(date) as date FROM subtest GROUP BY id) as sq ON test.id = sq.id WHERE test.date < :date', ['date' => '2020-06-28']),
            $result
        );

    }

    /**
     * @throws InvalidArgumentException
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
     * @throws InvalidArgumentException
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
            new SqlStatement('SELECT  test.id, test.name, test.date, (SELECT  max(date) as date FROM subtest) as subdate FROM test WHERE test.date < :date', ['date' => '2020-06-28']),
            $result
        );

    }

    public function testSoftDeleteQuery()
    {
        // Sanity test
        $query = Query::getInstance()
            ->table('info')
            ->where('iduser = :id', ['id' => 3])
            ->orderBy(['property']);
        $this->assertEquals(new SqlStatement("SELECT  * FROM info WHERE iduser = :id ORDER BY property", ["id" => 3]), $query->build());

        // Test with soft delete
        new Mapper(ModelWithAttributes::class);
        $query = Query::getInstance()
            ->table('info')
            ->where('iduser = :id', ['id' => 3])
            ->orderBy(['property']);
        $this->assertEquals(new SqlStatement("SELECT  * FROM info WHERE iduser = :id AND info.deleted_at is null ORDER BY property", ["id" => 3]), $query->build());

        // Test Unsafe
        $query->unsafe();
        $this->assertEquals(new SqlStatement("SELECT  * FROM info WHERE iduser = :id ORDER BY property", ["id" => 3]), $query->build());
    }

    public function testSoftDeleteUpdate()
    {
        // Sanity test
        $query = UpdateQuery::getInstance()
            ->table('info')
            ->set('property', 'value')
            ->where('iduser = :id', ['id' => 3]);
        $this->assertEquals(new SqlStatement("UPDATE info SET property = :property  WHERE iduser = :id", ["property" => "value", "id" => 3]), $query->build());

        // Test with soft delete
        new Mapper(ModelWithAttributes::class);
        $query = UpdateQuery::getInstance()
            ->table('info')
            ->set('property', 'value')
            ->where('iduser = :id', ['id' => 3]);
        $this->assertEquals(new SqlStatement("UPDATE info SET property = :property  WHERE iduser = :id AND info.deleted_at is null", ["property" => "value", "id" => 3]), $query->build());

        // Test Unsafe
        $query->unsafe();
        $this->assertEquals(new SqlStatement("UPDATE info SET property = :property  WHERE iduser = :id", ["property" => "value", "id" => 3]), $query->build());
    }

    public function testSoftDeleteDelete()
    {
        // Sanity test
        $query = DeleteQuery::getInstance()
            ->table('info')
            ->where('iduser = :id', ['id' => 3]);
        $this->assertEquals(new SqlStatement("DELETE FROM info WHERE iduser = :id", ["id" => 3]), $query->build());

        // Test with soft delete
        new Mapper(ModelWithAttributes::class);
        $query = DeleteQuery::getInstance()
            ->table('info')
            ->where('iduser = :id', ['id' => 3]);
        $this->assertEquals(new SqlStatement("DELETE FROM info WHERE iduser = :id AND info.deleted_at is null", ["id" => 3]), $query->build());

        // Test Unsafe
        $query->unsafe();
        $this->assertEquals(new SqlStatement("DELETE FROM info WHERE iduser = :id", ["id" => 3]), $query->build());
    }

    public function testQueryBasicDistinct()
    {
        // Test without distinct
        $queryBasic = QueryBasic::getInstance()
            ->table('test')
            ->fields(['fld1', 'fld2']);

        $this->assertEquals(
            new SqlStatement('SELECT  fld1, fld2 FROM test'),
            $queryBasic->build()
        );

        // Test with distinct
        $queryBasic = QueryBasic::getInstance()
            ->table('test')
            ->fields(['fld1', 'fld2'])
            ->distinct();

        $this->assertEquals(
            new SqlStatement('SELECT DISTINCT  fld1, fld2 FROM test'),
            $queryBasic->build()
        );
    }

    public function testQueryDistinct()
    {
        // Test without distinct
        $query = Query::getInstance()
            ->table('test')
            ->fields(['fld1', 'fld2']);

        $this->assertEquals(
            new SqlStatement('SELECT  fld1, fld2 FROM test'),
            $query->build()
        );

        // Test with distinct
        $query = Query::getInstance()
            ->table('test')
            ->fields(['fld1', 'fld2'])
            ->distinct();

        $this->assertEquals(
            new SqlStatement('SELECT DISTINCT  fld1, fld2 FROM test'),
            $query->build()
        );

        // Test getQueryBasic preserves distinct
        $queryBasic = $query->getQueryBasic();
        $this->assertEquals(
            new SqlStatement('SELECT DISTINCT  fld1, fld2 FROM test'),
            $queryBasic->build()
        );
    }
}
