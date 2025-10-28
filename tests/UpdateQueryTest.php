<?php

namespace Tests;

use ByJG\AnyDataset\Db\Factory;
use ByJG\AnyDataset\Db\Helpers\DbMysqlFunctions;
use ByJG\AnyDataset\Db\Helpers\DbPgsqlFunctions;
use ByJG\AnyDataset\Db\Helpers\DbSqliteFunctions;
use ByJG\AnyDataset\Db\SqlStatement;
use ByJG\AnyDataset\Db\PdoMysql;
use ByJG\AnyDataset\Db\PdoObj;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Query;
use ByJG\MicroOrm\SqlObject;
use ByJG\MicroOrm\SqlObjectEnum;
use ByJG\MicroOrm\UpdateQuery;
use Override;
use ByJG\Util\Uri;
use PHPUnit\Framework\TestCase;

class UpdateQueryTest extends TestCase
{
    /**
     * @var UpdateQuery
     */
    protected $object;

    #[Override]
    protected function setUp(): void
    {
        $this->object = new UpdateQuery();
    }

    #[Override]
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

        $this->object->where('fld1 = :id', ['id' => 10]);

        $sqlStatement = $this->object->build();
        $this->assertEquals(
            new SqlStatement(
                'UPDATE test SET fld1 = :fld1 , fld2 = :fld2 , fld3 = :fld3  WHERE fld1 = :id',
                ['id' => 10, 'fld1' => 'A', 'fld2' => 'B', 'fld3' => 'C']
            ),
            $sqlStatement
        );

        $sqlStatement = $this->object->build(new DbSqliteFunctions());
        $this->assertEquals(
            new SqlStatement(
                'UPDATE `test` SET `fld1` = :fld1 , `fld2` = :fld2 , `fld3` = :fld3  WHERE fld1 = :id',
                ['id' => 10, 'fld1' => 'A', 'fld2' => 'B', 'fld3' => 'C']
            ),
            $sqlStatement
        );
    }

    public function testUpdateError()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->object->table('test');

        $this->object->set('fld1', 'A');
        $this->object->set('fld2', 'B');
        $this->object->set('fld3', 'C');

        $this->object->build();
    }

    public function testQueryUpdatable()
    {
        $this->object->table('test');
        $this->assertEquals(
            new SqlStatement('SELECT  * FROM test'),
            $this->object->convert()->build()
        );


        $this->object
            ->set('fld1', 'A')
            ->set('fld2', 'B')
            ->set('fld3', 'C');

        $this->assertEquals(
            new SqlStatement('SELECT  fld1, fld2, fld3 FROM test'),
            $this->object->convert()->build()
        );

        $this->object
            ->where('fld2 = :teste', [ 'teste' => 10 ]);

        $this->assertEquals(
            new SqlStatement('SELECT  fld1, fld2, fld3 FROM test WHERE fld2 = :teste', ['teste' => 10]),
            $this->object->convert()->build()
        );

        $this->object
            ->where('fld3 = 20');

        $this->assertEquals(
            new SqlStatement('SELECT  fld1, fld2, fld3 FROM test WHERE fld2 = :teste AND fld3 = 20', ['teste' => 10]),
            $this->object->convert()->build()
        );

        $this->object
            ->where('fld1 = :teste2', [ 'teste2' => 40 ]);

        $this->assertEquals(
            new SqlStatement('SELECT  fld1, fld2, fld3 FROM test WHERE fld2 = :teste AND fld3 = 20 AND fld1 = :teste2', ['teste' => 10, 'teste2' => 40]),
            $this->object->convert()->build()
        );
    }

    public function testUpdateJoinFail()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->object->table('test');
        $this->object->join('table2', 'table2.id = test.id');
        $this->object->build();
    }

    public function testUpdateJoinMySQl()
    {
        $this->object->table('test');
        $this->object->join('table2', 'table2.id = test.id');
        $this->object->set('fld1', 'A');
        $this->object->set('fld2', 'B');
        $this->object->set('fld3', 'C');
        $this->object->where('fld1 = :id', ['id' => 10]);

        $sqlStatement = $this->object->build(new DbMysqlFunctions());
        $this->assertEquals(
            new SqlStatement(
                'UPDATE `test` INNER JOIN `table2` ON table2.id = test.id SET `fld1` = :fld1 , `fld2` = :fld2 , `fld3` = :fld3  WHERE fld1 = :id',
                ['id' => 10, 'fld1' => 'A', 'fld2' => 'B', 'fld3' => 'C']
            ),
            $sqlStatement
        );
    }

    public function testUpdateJoinMySQlAlias()
    {
        $this->object->table('test');
        $this->object->join('table2', 't2.id = test.id', 't2');
        $this->object->set('fld1', 'A');
        $this->object->set('fld2', 'B');
        $this->object->set('fld3', 'C');
        $this->object->where('fld1 = :id', ['id' => 10]);

        $sqlObject = $this->object->build(new DbMysqlFunctions());
        $this->assertEquals(
            new SqlObject(
                'UPDATE `test` INNER JOIN `table2` AS t2 ON t2.id = test.id SET `fld1` = :fld1 , `fld2` = :fld2 , `fld3` = :fld3  WHERE fld1 = :id',
                ['id' => 10, 'fld1' => 'A', 'fld2' => 'B', 'fld3' => 'C'],
                SqlObjectEnum::UPDATE
            ),
            $sqlObject
        );
    }

    public function testUpdateJoinMySQlSubqueryAlias()
    {
        $query = Query::getInstance()
            ->table('table2')
            ->where('id = 10');
        $this->object->table('test');
        $this->object->join($query, 't2.id = test.id', 't2');
        $this->object->set('fld1', 'A');
        $this->object->set('fld2', 'B');
        $this->object->set('fld3', 'C');
        $this->object->where('fld1 = :id', ['id' => 10]);


        $dbDriver = new class extends PdoMysql
        {
            public function __construct()
            {
                $this->pdoObj = new PdoObj(new Uri('mysql://root:root@localhost/test'));
            }
        };

        $sqlObject = $this->object->build($dbDriver);
        $this->assertEquals(
            new SqlObject(
                'UPDATE `test` INNER JOIN (SELECT  * FROM table2 WHERE id = 10) AS t2 ON t2.id = test.id SET `fld1` = :fld1 , `fld2` = :fld2 , `fld3` = :fld3  WHERE fld1 = :id',
                ['id' => 10, 'fld1' => 'A', 'fld2' => 'B', 'fld3' => 'C'],
                SqlObjectEnum::UPDATE
            ),
            $sqlObject
        );
    }

    public function testUpdateJoinPostgres()
    {
        $this->object->table('test');
        $this->object->join('table2', 'table2.id = test.id');
        $this->object->set('fld1', 'A');
        $this->object->set('fld2', 'B');
        $this->object->set('fld3', 'C');
        $this->object->where('fld1 = :id', ['id' => 10]);

        $sqlStatement = $this->object->build(new DbPgsqlFunctions());
        $this->assertEquals(
            new SqlStatement(
                'UPDATE "test" SET "fld1" = :fld1 , "fld2" = :fld2 , "fld3" = :fld3  FROM "table2" ON table2.id = test.id WHERE fld1 = :id',
                ['id' => 10, 'fld1' => 'A', 'fld2' => 'B', 'fld3' => 'C']
            ),
            $sqlStatement
        );
    }

    public function testSetLiteral()
    {
        $this->object->table('test');
        $this->object->setLiteral('counter', 'counter + 1');
        $this->object->where('id = :id', ['id' => 10]);

        $sqlObject = $this->object->build();
        $this->assertEquals(
            new SqlStatement(
                'UPDATE test SET counter = counter + 1  WHERE id = :id',
                ['id' => 10]
            ),
            $sqlObject
        );

        // Test with database helper
        $sqlObject = $this->object->build(new DbMysqlFunctions());
        $this->assertEquals(
            new SqlStatement(
                'UPDATE `test` SET `counter` = counter + 1  WHERE id = :id',
                ['id' => 10]
            ),
            $sqlObject
        );
    }
}
