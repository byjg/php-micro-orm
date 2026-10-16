<?php

namespace Tests;

use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\FieldMapping;
use ByJG\MicroOrm\Literal\HexUuidLiteral;
use ByJG\MicroOrm\Literal\Literal;
use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\ORM;
use ByJG\MicroOrm\ORMHelper;
use ByJG\MicroOrm\Query;
use Override;
use PHPUnit\Framework\TestCase;
use Tests\Model\Class1;
use Tests\Model\Class2;
use Tests\Model\Class3;
use Tests\Model\Class4;
use Tests\Model\Class5;

class ORMTest extends TestCase
{
    private Mapper $mapper1;
    private Mapper $mapper2;
    private Mapper $mapper3;
    private Mapper $mapper4;

    #[Override]
    public function setUp(): void
    {
        ORM::resetMemory();
        $this->mapper1 = new Mapper(Class1::class, 'table1', 'id');
        $this->mapper2 = new Mapper(Class2::class, 'table2', 'id');
        $this->mapper2->addFieldMapping(FieldMapping::create('idTable1')->withFieldName('id_table1')->withParentTable('table1'));
        $this->mapper3 = new Mapper(Class3::class);
        $this->mapper4 = new Mapper(Class4::class);
    }

    #[Override]
    public function tearDown(): void
    {
        ORM::resetMemory();
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

    public function testProcessLiteral()
    {
        $query = Query::getInstance()->table('table1');
        $query->where('field1 = :value', ['value' => new Literal('upper(field1)')]);
        $this->assertEquals("SELECT  * FROM table1 WHERE field1 = upper(field1)", $query->build()->getSql());

    }

    public function testProcessHexUuidLiteral()
    {
        $query = Query::getInstance()->table('table1');
        $query->where('field1 = :value', ['value' => new HexUuidLiteral(hex2bin('01010101010101010101010101010101'))]);
        $this->assertEquals("SELECT  * FROM table1 WHERE field1 = X'01010101010101010101010101010101'", $query->build()->getSql());
    }

    public function testProcessLiteralUnsafe()
    {
        $query = Query::getInstance()->table('table1');
        $query->where('field1 = :value', ['value' => new Literal(10)]);

        $sqlStatement = $query->build();
        $sql = $sqlStatement->getSql();
        $params = $sqlStatement->getParams();

        $sql = ORMHelper::processLiteral($sql, $params);
        $this->assertEquals("SELECT  * FROM table1 WHERE field1 = 10", $sql);
    }

    public function testProcessLiteralString()
    {
        $query = Query::getInstance()->table('table1');
        $query->where('field1 = :value', ['value' => new Literal("'testando'")]);
        $query->where('field2 = :value2', ['value2' => new Literal("'Joana D''Arc'")]);

        $sqlStatement = $query->build();
        $sql = $sqlStatement->getSql();
        $params = $sqlStatement->getParams();

        $sql = ORMHelper::processLiteral($sql, $params);
        $this->assertEquals("SELECT  * FROM table1 WHERE field1 = 'testando' AND field2 = 'Joana D''Arc'", $sql);
        $this->assertEquals([], $params);
    }

    public function testJoinRelatedDerivesOnFromRelationshipKeepingBaseTable()
    {
        // table3 has a FK id_table1 -> table1. Base stays table3; project is added.
        $sql = Query::getInstance()->table('table3')->joinRelated('table1')->build()->getSql();
        $this->assertStringContainsString('FROM table3 INNER JOIN table1 ON table1.id = table3.id_table1', $sql);
    }

    public function testLeftJoinRelated()
    {
        $sql = Query::getInstance()->table('table3')->leftJoinRelated('table1')->build()->getSql();
        $this->assertStringContainsString('LEFT JOIN table1 ON table1.id = table3.id_table1', $sql);
    }

    public function testRightJoinRelated()
    {
        $sql = Query::getInstance()->table('table3')->rightJoinRelated('table1')->build()->getSql();
        $this->assertStringContainsString('RIGHT JOIN table1 ON table1.id = table3.id_table1', $sql);
    }

    public function testJoinRelatedAutoDiscoversIntermediateTables()
    {
        // table1 is not directly related to table4 (table1 -> table2 -> table4). The
        // intermediate table2 is joined automatically, and table4's soft-delete filter
        // is kept. The base table (table1) is preserved.
        $sql = Query::getInstance()->table('table1')->joinRelated('table4')->build()->getSql();
        $this->assertEquals(
            "SELECT  * FROM table1 INNER JOIN table2 ON table1.id = table2.id_table1 "
            . "INNER JOIN table4 ON table2.id = table4.id_table2 WHERE table4.deleted_at is null",
            $sql
        );

        // table2 -> table3 auto-discovers table1 sitting between them.
        $sql = Query::getInstance()->table('table2')->joinRelated('table3')->build()->getSql();
        $this->assertEquals(
            "SELECT  * FROM table2 INNER JOIN table1 ON table1.id = table2.id_table1 "
            . "INNER JOIN table3 ON table1.id = table3.id_table1",
            $sql
        );
    }

    public function testJoinRelatedSkipsTablesAlreadyInTheQuery()
    {
        // Naming the intermediate explicitly must not join table2 twice.
        $sql = Query::getInstance()->table('table1')
            ->joinRelated('table2')
            ->joinRelated('table4')
            ->build()->getSql();
        $this->assertEquals(
            "SELECT  * FROM table1 INNER JOIN table2 ON table1.id = table2.id_table1 "
            . "INNER JOIN table4 ON table2.id = table4.id_table2 WHERE table4.deleted_at is null",
            $sql
        );
    }

    public function testJoinRelatedThrowsWhenNoRelationshipPathExists()
    {
        $this->expectException(InvalidArgumentException::class);
        Query::getInstance()->table('table1')->joinRelated('unrelated_table');
    }

    public function testIncompleteRelationshipResolvesWhenParentRegisteredLater()
    {
        // Register the CHILD (table5, FK id_table1 -> table1) BEFORE its parent.
        // At this point table1's primary key is unknown, so the relationship is
        // stored as incomplete ('?'). Registering the parent afterwards must let
        // getRelationship back-fill the real primary key.
        ORM::resetMemory();
        new Mapper(Class5::class);
        new Mapper(Class1::class, 'table1', 'id');

        $sql = Query::getInstance()->table('table1')->joinRelated('table5')->build()->getSql();
        $this->assertStringContainsString('table1.id = table5.id_table1', $sql);
        $this->assertStringNotContainsString('table1.? =', $sql);
    }

    public function testGetTableFromClassRegistersMapperOnDemand()
    {
        // Only table1 is known; Class5 (table5) has never been registered.
        ORM::resetMemory();
        new Mapper(Class1::class, 'table1', 'id');
        $this->assertNull(ORM::getMapper('table5'));

        // Resolving the class registers its mapper (reflection only) and returns its table.
        $this->assertEquals('table5', ORM::getTableFromClass(Class5::class));
        $this->assertNotNull(ORM::getMapper('table5'));

        // Idempotent: a second call reuses the already-registered mapper.
        $this->assertSame(ORM::getMapper('table5'), ORM::getMapper('table5'));
        $this->assertEquals('table5', ORM::getTableFromClass(Class5::class));
    }

    public function testJoinRelatedAcceptsModelClassRegisteringMapperOnDemand()
    {
        // table5's mapper is not pre-registered; passing the class must register it on
        // demand and join it, just like passing the 'table5' string would after setup.
        ORM::resetMemory();
        new Mapper(Class1::class, 'table1', 'id');
        $this->assertNull(ORM::getMapper('table5'));

        $sql = Query::getInstance()->table('table1')->joinRelated(Class5::class)->build()->getSql();
        $this->assertStringContainsString('INNER JOIN table5 ON table1.id = table5.id_table1', $sql);
        $this->assertNotNull(ORM::getMapper('table5'));
    }

    public function testFieldUuidAttributeRegistersParentTableRelationship()
    {
        // table1 (mapper1) is already registered in setUp. Building Class5's mapper
        // must register the FK relationship declared via FieldUuidAttribute(parentTable:).
        new Mapper(Class5::class);

        $this->assertEquals(['table1,table5'], ORM::getRelationship('table1', 'table5'));

        $data = ORM::getRelationshipData('table1', 'table5');
        $this->assertCount(1, $data);
        $this->assertEquals('table1', $data[0]['parent']);
        $this->assertEquals('table5', $data[0]['child']);
        $this->assertEquals('id', $data[0]['pk']);
        $this->assertEquals('id_table1', $data[0]['fk']);

        // And the dynamic query builder joins them without hand-written SQL.
        $sql = Query::getInstance()->table('table1')->joinRelated('table5')->build()->getSql();
        $this->assertStringContainsString('INNER JOIN table5', $sql);
        $this->assertStringContainsString('table1.id = table5.id_table1', $sql);
    }
}
