<?php

namespace Tests;

use ByJG\AnyDataset\Db\SqlStatement;
use ByJG\MicroOrm\Exception\OrmInvalidFieldsException;
use ByJG\MicroOrm\InsertSelectQuery;
use ByJG\MicroOrm\QueryBasic;
use Override;
use PHPUnit\Framework\TestCase;

class InsertSelectQueryTest extends TestCase
{
    /**
     * @var InsertSelectQuery
     */
    protected $object;

    #[Override]
    protected function setUp(): void
    {
        $this->object = new InsertSelectQuery();
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->object = null;
    }

    public function testInsert()
    {
        $this->object->table('test');
        $this->object->fields(['fld1', 'fld2', 'fld3']);

        $query = new QueryBasic();
        $query->table('table2');
        $query->field('fldA');
        $query->field('fldB');
        $query->field('fldC');
        $this->object->fromQuery($query);

        $sqlStatement = $this->object->build();

        $this->assertEquals('INSERT INTO test ( fld1, fld2, fld3 ) SELECT  fldA, fldB, fldC FROM table2', $sqlStatement->getSql());
    }

    public function testInsertSelectParam()
    {
        $this->object->table('test');
        $this->object->fields(['fld1', 'fld2', 'fld3']);

        $query = new QueryBasic();
        $query->table('table2');
        $query->field('fldA');
        $query->field('fldB');
        $query->field('fldC');
        $query->where('fldA = :valueA', ['valueA' => 1]);
        $this->object->fromQuery($query);

        $sqlStatement = $this->object->build();

        $this->assertEquals('INSERT INTO test ( fld1, fld2, fld3 ) SELECT  fldA, fldB, fldC FROM table2 WHERE fldA = :valueA', $sqlStatement->getSql());
        $this->assertEquals(['valueA' => 1], $sqlStatement->getParams());
    }

    public function testInsertSqlStatement()
    {
        $this->object->table('test');
        $this->object->fields(['fld1', 'fld2', 'fld3']);

        $query = new QueryBasic();
        $query->table('table2');
        $query->field('fldA');
        $query->field('fldB');
        $query->field('fldC');
        $query->where('fldA = :valueA', ['valueA' => 1]);
        $fromObject = $query->build();

        $this->object->fromSqlStatement($fromObject);
        $sqlStatement = $this->object->build();

        $this->assertEquals('INSERT INTO test ( fld1, fld2, fld3 ) SELECT  fldA, fldB, fldC FROM table2 WHERE fldA = :valueA', $sqlStatement->getSql());
        $this->assertEquals(['valueA' => 1], $sqlStatement->getParams());
    }

    public function testWithoutQuery()
    {
        $this->object->table('test');
        $this->object->fields(['fld1', 'fld2', 'fld3']);

        $this->expectException(OrmInvalidFieldsException::class);
        $this->expectExceptionMessage('You must specify the query for insert');
        $this->object->build();
    }

    public function testWithBothQueryandSqlStatement()
    {
        $this->object->table('test');
        $this->object->fields(['fld1', 'fld2', 'fld3']);
        $this->object->fromQuery(new QueryBasic());
        $this->object->fromSqlStatement(new SqlStatement("SELECT * FROM table2"));

        $this->expectException(OrmInvalidFieldsException::class);
        $this->expectExceptionMessage('You must specify only one query for insert');
        $this->object->build();
    }

}
