<?php

namespace Tests;

use ByJG\MicroOrm\FieldMapping;
use ByJG\MicroOrm\Literal\HexUuidLiteral;
use ByJG\MicroOrm\Literal\Literal;
use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\ORM;
use ByJG\MicroOrm\ORMHelper;
use Override;
use PHPUnit\Framework\TestCase;
use Tests\Model\Class1;
use Tests\Model\Class2;
use Tests\Model\Class3;
use Tests\Model\Class4;

class ORMTest extends TestCase
{
    private Mapper $mapper1;
    private Mapper $mapper2;
    private Mapper $mapper3;
    private Mapper $mapper4;

    #[Override]
    public function setUp(): void
    {
        ORM::clearRelationships();
        $this->mapper1 = new Mapper(Class1::class, 'table1', 'id');
        $this->mapper2 = new Mapper(Class2::class, 'table2', 'id');
        $this->mapper2->addFieldMapping(FieldMapping::create('idTable1')->withFieldName('id_table1')->withParentTable('table1'));
        $this->mapper3 = new Mapper(Class3::class);
        $this->mapper4 = new Mapper(Class4::class);
    }

    #[Override]
    public function tearDown(): void
    {
        ORM::clearRelationships();
    }

    public function testSanityCheck()
    {
        $this->assertEquals([], ORM::getRelationship('table1'));
        $this->assertEquals([], ORM::getRelationship('table2'));
        $this->assertEquals([], ORM::getRelationship('table3'));
        $this->assertEquals([], ORM::getRelationship('table4'));

        $this->assertEquals(['table1,table2'], ORM::getRelationship('table1', 'table2'));
        $this->assertEquals(['table1,table3'], ORM::getRelationship('table1', 'table3'));
        $this->assertEquals(['table1,table2', 'table2,table4'], ORM::getRelationship('table1', 'table4'));

        $this->assertEquals(['table1,table2'], ORM::getRelationship('table2', 'table1'));
        $this->assertEquals(['table1,table2', 'table1,table3'], ORM::getRelationship('table2', 'table3'));
        $this->assertEquals(['table2,table4'], ORM::getRelationship('table2', 'table4'));

        $this->assertEquals(['table1,table3'], ORM::getRelationship('table3', 'table1'));
        $this->assertEquals(['table1,table3', 'table1,table2'], ORM::getRelationship('table3', 'table2'));
        $this->assertEquals(['table1,table3', 'table1,table2', 'table2,table4'], ORM::getRelationship('table3', 'table4'));

        $this->assertEquals(['table2,table4', 'table1,table2'], ORM::getRelationship('table4', 'table1'));
        $this->assertEquals(['table2,table4'], ORM::getRelationship('table4', 'table2'));
        $this->assertEquals(['table2,table4', 'table1,table2', 'table1,table3'], ORM::getRelationship('table4', 'table3'));

        $this->assertEquals(['table2,table4', 'table1,table2'], ORM::getRelationship('table4', 'table2', 'table1'));
        $this->assertEquals(['table2,table4', 'table1,table2'], ORM::getRelationship('table4', 'table1', 'table2'));
        $this->assertEquals(['table1,table2', 'table2,table4'], ORM::getRelationship('table2', 'table1', 'table4'));
        $this->assertEquals(['table1,table2', 'table2,table4'], ORM::getRelationship('table1', 'table4'));
        $this->assertEquals(['table1,table3', 'table1,table2'], ORM::getRelationship('table3', 'table2'));
    }

    public function testSanityCheckData()
    {
        $this->assertEquals([], ORM::getRelationshipData('table1'));
        $this->assertEquals([], ORM::getRelationshipData('table2'));
        $this->assertEquals([], ORM::getRelationshipData('table3'));
        $this->assertEquals([], ORM::getRelationshipData('table4'));

        $table1Table2 = ['parent' => 'table1', 'child' => 'table2', 'pk' => 'id', 'fk' => 'id_table1'];
        $table1Table3 = ['parent' => 'table1', 'child' => 'table3', 'pk' => 'id', 'fk' => 'id_table1'];
        $table2Table4 = ['parent' => 'table2', 'child' => 'table4', 'pk' => 'id', 'fk' => 'id_table2'];

        $this->assertEquals([$table1Table2], ORM::getRelationshipData('table1', 'table2'));
        $this->assertEquals([$table1Table3], ORM::getRelationshipData('table1', 'table3'));
        $this->assertEquals([$table1Table2, $table2Table4], ORM::getRelationshipData('table1', 'table4'));


        $this->assertEquals([$table1Table2], ORM::getRelationshipData('table2', 'table1'));
        $this->assertEquals([$table1Table2, $table1Table3], ORM::getRelationshipData('table2', 'table3'));
        $this->assertEquals([$table2Table4], ORM::getRelationshipData('table2', 'table4'));

        $this->assertEquals([$table1Table3], ORM::getRelationshipData('table3', 'table1'));
        $this->assertEquals([$table1Table3, $table1Table2], ORM::getRelationshipData('table3', 'table2'));
        $this->assertEquals([$table1Table3, $table1Table2, $table2Table4], ORM::getRelationshipData('table3', 'table4'));

        $this->assertEquals([$table2Table4, $table1Table2], ORM::getRelationshipData('table4', 'table1'));
        $this->assertEquals([$table2Table4], ORM::getRelationshipData('table4', 'table2'));
        $this->assertEquals([$table2Table4, $table1Table2, $table1Table3], ORM::getRelationshipData('table4', 'table3'));

        $this->assertEquals([$table2Table4, $table1Table2], ORM::getRelationshipData('table4', 'table2', 'table1'));
        $this->assertEquals([$table2Table4, $table1Table2], ORM::getRelationshipData('table4', 'table1', 'table2'));
        $this->assertEquals([$table1Table2, $table2Table4], ORM::getRelationshipData('table2', 'table1', 'table4'));
        $this->assertEquals([$table1Table2, $table2Table4], ORM::getRelationshipData('table1', 'table4'));
        $this->assertEquals([$table1Table3, $table1Table2], ORM::getRelationshipData('table3', 'table2'));
    }

    public function testGetQuery()
    {
        $query = ORM::getQueryInstance('table1');
        $this->assertEquals("SELECT  * FROM table1", $query->build()->getSql());

        $query = ORM::getQueryInstance('table2', 'table1');
        $this->assertEquals("SELECT  * FROM table1 INNER JOIN table2 ON table1.id = table2.id_table1", $query->build()->getSql());

        $query = ORM::getQueryInstance('table4', 'table2', 'table1');
        $this->assertEquals("SELECT  * FROM table2 INNER JOIN table4 ON table2.id = table4.id_table2 INNER JOIN table2 ON table1.id = table2.id_table1 WHERE table4.deleted_at is null", $query->build()->getSql());

        $query = ORM::getQueryInstance('table4', 'table1', 'table2');
        $this->assertEquals("SELECT  * FROM table2 INNER JOIN table4 ON table2.id = table4.id_table2 INNER JOIN table2 ON table1.id = table2.id_table1 WHERE table4.deleted_at is null", $query->build()->getSql());

        $query = ORM::getQueryInstance('table2', 'table1', 'table4');
        $this->assertEquals("SELECT  * FROM table1 INNER JOIN table2 ON table1.id = table2.id_table1 INNER JOIN table4 ON table2.id = table4.id_table2 WHERE table4.deleted_at is null", $query->build()->getSql());

        $query = ORM::getQueryInstance('table1', 'table4');
        $this->assertEquals("SELECT  * FROM table1 INNER JOIN table2 ON table1.id = table2.id_table1 INNER JOIN table4 ON table2.id = table4.id_table2 WHERE table4.deleted_at is null", $query->build()->getSql());

        $query = ORM::getQueryInstance('table2', 'table3');
        $this->assertEquals("SELECT  * FROM table1 INNER JOIN table2 ON table1.id = table2.id_table1 INNER JOIN table3 ON table1.id = table3.id_table1", $query->build()->getSql());
    }

    public function testProcessLiteral()
    {
        $query = ORM::getQueryInstance('table1');
        $query->where('field1 = :value', ['value' => new Literal('upper(field1)')]);
        $this->assertEquals("SELECT  * FROM table1 WHERE field1 = upper(field1)", $query->build()->getSql());

    }

    public function testProcessHexUuidLiteral()
    {
        $query = ORM::getQueryInstance('table1');
        $query->where('field1 = :value', ['value' => new HexUuidLiteral(hex2bin('01010101010101010101010101010101'))]);
        $this->assertEquals("SELECT  * FROM table1 WHERE field1 = X'01010101010101010101010101010101'", $query->build()->getSql());
    }

    public function testProcessLiteralUnsafe()
    {
        $query = ORM::getQueryInstance('table1');
        $query->where('field1 = :value', ['value' => new Literal(10)]);

        $sqlStatement = $query->build();
        $sql = $sqlStatement->getSql();
        $params = $sqlStatement->getParams();

        $sql = ORMHelper::processLiteral($sql, $params);
        $this->assertEquals("SELECT  * FROM table1 WHERE field1 = 10", $sql);
    }

    public function testProcessLiteralString()
    {
        $query = ORM::getQueryInstance('table1');
        $query->where('field1 = :value', ['value' => new Literal("'testando'")]);
        $query->where('field2 = :value2', ['value2' => new Literal("'Joana D''Arc'")]);

        $sqlStatement = $query->build();
        $sql = $sqlStatement->getSql();
        $params = $sqlStatement->getParams();

        $sql = ORMHelper::processLiteral($sql, $params);
        $this->assertEquals("SELECT  * FROM table1 WHERE field1 = 'testando' AND field2 = 'Joana D''Arc'", $sql);
        $this->assertEquals([], $params);
    }
}
