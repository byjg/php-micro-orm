<?php

namespace Tests;

use ByJG\MicroOrm\InsertSelectQuery;
use ByJG\MicroOrm\QueryBasic;
use ByJG\MicroOrm\SqlObject;
use PHPUnit\Framework\TestCase;

class InsertSelectQueryTest extends TestCase
{
    /**
     * @var InsertSelectQuery
     */
    protected $object;

    protected function setUp(): void
    {
        $this->object = new InsertSelectQuery();
    }

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

        $sqlObject = $this->object->build();

        $this->assertEquals('INSERT INTO test ( fld1, fld2, fld3 ) SELECT  fldA, fldB, fldC FROM table2', $sqlObject->getSql());
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

        $sqlObject = $this->object->build();

        $this->assertEquals('INSERT INTO test ( fld1, fld2, fld3 ) SELECT  fldA, fldB, fldC FROM table2 WHERE fldA = :valueA', $sqlObject->getSql());
        $this->assertEquals(['valueA' => 1], $sqlObject->getParameters());
    }

    public function testInsertSqlObject()
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

        $this->object->fromSqlObject($fromObject);
        $sqlObject = $this->object->build();

        $this->assertEquals('INSERT INTO test ( fld1, fld2, fld3 ) SELECT  fldA, fldB, fldC FROM table2 WHERE fldA = :valueA', $sqlObject->getSql());
        $this->assertEquals(['valueA' => 1], $sqlObject->getParameters());
    }

    public function testWithoutQuery()
    {
        $this->object->table('test');
        $this->object->fields(['fld1', 'fld2', 'fld3']);

        $this->expectException(\ByJG\MicroOrm\Exception\OrmInvalidFieldsException::class);
        $this->expectExceptionMessage('You must specify the query for insert');
        $this->object->build();
    }

    public function testWithBothQueryandSqlObject()
    {
        $this->object->table('test');
        $this->object->fields(['fld1', 'fld2', 'fld3']);
        $this->object->fromQuery(new QueryBasic());
        $this->object->fromSqlObject(new SqlObject("SELECT * FROM table2"));

        $this->expectException(\ByJG\MicroOrm\Exception\OrmInvalidFieldsException::class);
        $this->expectExceptionMessage('You must specify only one query for insert');
        $this->object->build();
    }

}
