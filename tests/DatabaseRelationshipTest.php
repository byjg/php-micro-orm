<?php

namespace Tests;

use ByJG\MicroOrm\DatabaseRelationship;
use ByJG\MicroOrm\FieldMapping;
use ByJG\MicroOrm\Mapper;
use PHPUnit\Framework\TestCase;
use Tests\Model\Class1;
use Tests\Model\Class2;
use Tests\Model\Class3;
use Tests\Model\Class4;

class DatabaseRelationshipTest extends TestCase
{
    private Mapper $mapper1;
    private Mapper $mapper2;
    private Mapper $mapper3;
    private Mapper $mapper4;

    public function setUp(): void
    {
        $this->mapper1 = new Mapper(Class1::class, 'table1', 'id');

        $this->mapper2 = new Mapper(Class2::class, 'table2', 'id');
        $this->mapper2->addFieldMapping(FieldMapping::create('idTable1')->withFieldName('id_table1')->withParentTable('table1'));

        $this->mapper3 = new Mapper(Class3::class);

        $this->mapper4 = new Mapper(Class4::class);
    }

    public function testSanityCheck()
    {
        $this->assertEquals([], DatabaseRelationship::getRelationship('table1,table2'));
        $this->assertEquals([], DatabaseRelationship::getRelationship('table1,table3'));
        $this->assertEquals([], DatabaseRelationship::getRelationship('table1,table4'));

        $this->assertEquals(['table1,table2'], DatabaseRelationship::getRelationship('table2', 'table1'));
        $this->assertEquals(['table1,table2', 'table1,table3'], DatabaseRelationship::getRelationship('table2', 'table3'));
        $this->assertEquals(['table2,table4'], DatabaseRelationship::getRelationship('table2', 'table4'));

        $this->assertEquals(['table1,table3'], DatabaseRelationship::getRelationship('table3', 'table1'));
        $this->assertEquals(['table1,table3', 'table1,table2'], DatabaseRelationship::getRelationship('table3', 'table2'));
        $this->assertEquals(['table1,table3', 'table1,table2', 'table2,table4'], DatabaseRelationship::getRelationship('table3', 'table4'));

        $this->assertEquals(['table2,table4', 'table1,table2'], DatabaseRelationship::getRelationship('table4', 'table1'));
        $this->assertEquals(['table2,table4'], DatabaseRelationship::getRelationship('table4', 'table2'));
        $this->assertEquals(['table2,table4', 'table1,table2', 'table1,table3'], DatabaseRelationship::getRelationship('table4', 'table3'));
    }

    public function testSanityCheckData()
    {
        $this->assertEquals([], DatabaseRelationship::getRelationshipData('table1,table2'));
        $this->assertEquals([], DatabaseRelationship::getRelationshipData('table1,table3'));
        $this->assertEquals([], DatabaseRelationship::getRelationshipData('table1,table4'));

        $table1Table2 = ['parent' => 'table1', 'child' => 'table2', 'pk' => 'id', 'fk' => 'id_table1'];
        $table1Table3 = ['parent' => 'table1', 'child' => 'table3', 'pk' => 'id', 'fk' => 'id_table1'];
        $table2Table4 = ['parent' => 'table2', 'child' => 'table4', 'pk' => 'id', 'fk' => 'id_table2'];


        $this->assertEquals([$table1Table2], DatabaseRelationship::getRelationshipData('table2', 'table1'));
        $this->assertEquals([$table1Table2, $table1Table3], DatabaseRelationship::getRelationshipData('table2', 'table3'));
        $this->assertEquals([$table2Table4], DatabaseRelationship::getRelationshipData('table2', 'table4'));

        $this->assertEquals([$table1Table3], DatabaseRelationship::getRelationshipData('table3', 'table1'));
        $this->assertEquals([$table1Table3, $table1Table2], DatabaseRelationship::getRelationshipData('table3', 'table2'));
        $this->assertEquals([$table1Table3, $table1Table2, $table2Table4], DatabaseRelationship::getRelationshipData('table3', 'table4'));

        $this->assertEquals([$table2Table4, $table1Table2], DatabaseRelationship::getRelationshipData('table4', 'table1'));
        $this->assertEquals([$table2Table4], DatabaseRelationship::getRelationshipData('table4', 'table2'));
        $this->assertEquals([$table2Table4, $table1Table2, $table1Table3], DatabaseRelationship::getRelationshipData('table4', 'table3'));
    }

    public function testMerge()
    {
        $this->assertEquals(['table1,table2'], DatabaseRelationship::getRelationship('table2', 'table1'));
        $this->assertEquals(['table2,table4', 'table1,table2'], DatabaseRelationship::getRelationship('table4', 'table2', 'table1'));
        $this->assertEquals(['table2,table4', 'table1,table2'], DatabaseRelationship::getRelationship('table4', 'table1', 'table2'));
        $this->assertEquals(['table1,table2', 'table2,table4'], DatabaseRelationship::getRelationship('table2', 'table1', 'table4'));
        $this->assertEquals(['table1,table2', 'table2,table4'], DatabaseRelationship::getRelationship('table1', 'table4'));
        $this->assertEquals(['table1,table3', 'table1,table2'], DatabaseRelationship::getRelationship('table3', 'table2'));
    }

    public function testMergeData()
    {
        $table1Table2 = ['parent' => 'table1', 'child' => 'table2', 'pk' => 'id', 'fk' => 'id_table1'];
        $table1Table3 = ['parent' => 'table1', 'child' => 'table3', 'pk' => 'id', 'fk' => 'id_table1'];
        $table2Table4 = ['parent' => 'table2', 'child' => 'table4', 'pk' => 'id', 'fk' => 'id_table2'];


        $this->assertEquals([$table1Table2], DatabaseRelationship::getRelationshipData('table2', 'table1'));
        $this->assertEquals([$table2Table4, $table1Table2], DatabaseRelationship::getRelationshipData('table4', 'table2', 'table1'));
        $this->assertEquals([$table2Table4, $table1Table2], DatabaseRelationship::getRelationshipData('table4', 'table1', 'table2'));
        $this->assertEquals([$table1Table2, $table2Table4], DatabaseRelationship::getRelationshipData('table2', 'table1', 'table4'));
        $this->assertEquals([$table1Table2, $table2Table4], DatabaseRelationship::getRelationshipData('table1', 'table4'));
        $this->assertEquals([$table1Table3, $table1Table2], DatabaseRelationship::getRelationshipData('table3', 'table2'));
    }

    public function testGetQuery()
    {
        $query = DatabaseRelationship::getQueryInstance('table2', 'table1');
        $this->assertEquals("SELECT  * FROM table1 INNER JOIN table2 ON table1.id = table2.id_table1", $query->build()->getSql());

        $query = DatabaseRelationship::getQueryInstance('table4', 'table2', 'table1');
        $this->assertEquals("SELECT  * FROM table2 INNER JOIN table4 ON table2.id = table4.id_table2 INNER JOIN table2 ON table1.id = table2.id_table1 WHERE table4.deleted_at is null", $query->build()->getSql());

        $query = DatabaseRelationship::getQueryInstance('table4', 'table1', 'table2');
        $this->assertEquals("SELECT  * FROM table2 INNER JOIN table4 ON table2.id = table4.id_table2 INNER JOIN table2 ON table1.id = table2.id_table1 WHERE table4.deleted_at is null", $query->build()->getSql());

        $query = DatabaseRelationship::getQueryInstance('table2', 'table1', 'table4');
        $this->assertEquals("SELECT  * FROM table1 INNER JOIN table2 ON table1.id = table2.id_table1 INNER JOIN table4 ON table2.id = table4.id_table2 WHERE table4.deleted_at is null", $query->build()->getSql());

        $query = DatabaseRelationship::getQueryInstance('table1', 'table4');
        $this->assertEquals("SELECT  * FROM table1 INNER JOIN table2 ON table1.id = table2.id_table1 INNER JOIN table4 ON table2.id = table4.id_table2 WHERE table4.deleted_at is null", $query->build()->getSql());

        $query = DatabaseRelationship::getQueryInstance('table2', 'table3');
        $this->assertEquals("SELECT  * FROM table1 INNER JOIN table2 ON table1.id = table2.id_table1 INNER JOIN table3 ON table1.id = table3.id_table1", $query->build()->getSql());
    }

}
