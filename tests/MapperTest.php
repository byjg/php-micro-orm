<?php

namespace Tests;

use ByJG\MicroOrm\FieldMapping;
use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\ORM;
use Override;
use PHPUnit\Framework\TestCase;
use Tests\Model\Users;

class MapperTest extends TestCase
{
    #[Override]
    public function setUp(): void
    {
        ORM::resetMemory();
    }

    #[Override]
    public function tearDown(): void
    {
        ORM::resetMemory();
    }

    public function testAddFieldMappingTwiceOverwritesPreviousMapping()
    {
        $mapper = new Mapper(Users::class, 'users', 'id');

        // Add first field mapping
        $firstMapping = FieldMapping::create('name')->withFieldName('user_name');
        $mapper->addFieldMapping($firstMapping);

        // Add a second field mapping with the same property but different field name
        $secondMapping = FieldMapping::create('name')->withFieldName('full_name');
        $mapper->addFieldMapping($secondMapping);

        // Get the field map
        $fieldMap = $mapper->getFieldMap();

        // Should only have one mapping for 'name' property
        $this->assertArrayHasKey('name', $fieldMap);

        // The second mapping should have overwritten the first
        $nameMapping = $mapper->getFieldMap('name');
        $this->assertInstanceOf(FieldMapping::class, $nameMapping);
        $this->assertEquals('full_name', $nameMapping->getFieldName());
    }

    public function testGetFieldMapReturnsAllMappings()
    {
        $mapper = new Mapper(Users::class, 'users', 'id');

        $mapper->addFieldMapping(FieldMapping::create('name')->withFieldName('user_name'));
        $mapper->addFieldMapping(FieldMapping::create('createdate')->withFieldName('created_at'));

        $fieldMap = $mapper->getFieldMap();

        $this->assertIsArray($fieldMap);
        $this->assertCount(2, $fieldMap);
        $this->assertArrayHasKey('name', $fieldMap);
        $this->assertArrayHasKey('createdate', $fieldMap);
    }

    public function testGetFieldMapWithSpecificProperty()
    {
        $mapper = new Mapper(Users::class, 'users', 'id');

        $mapping = FieldMapping::create('name')->withFieldName('user_name');
        $mapper->addFieldMapping($mapping);
        $mapper->addFieldMapping(FieldMapping::create('createdate')->withFieldName('created_at'));

        $nameMapping = $mapper->getFieldMap('name');

        $this->assertInstanceOf(FieldMapping::class, $nameMapping);
        $this->assertEquals('name', $nameMapping->getPropertyName());
        $this->assertEquals('user_name', $nameMapping->getFieldName());
    }

    public function testGetFieldMapWithNonExistentProperty()
    {
        $mapper = new Mapper(Users::class, 'users', 'id');

        $mapper->addFieldMapping(FieldMapping::create('name')->withFieldName('user_name'));

        $result = $mapper->getFieldMap('nonexistent');

        $this->assertNull($result);
    }

    public function testAddFieldMappingWithFieldAlias()
    {
        $mapper = new Mapper(Users::class, 'users', 'id');

        $mapping = FieldMapping::create('name')
            ->withFieldName('user_name')
            ->withFieldAlias('full_name');
        $mapper->addFieldMapping($mapping);

        $fieldMap = $mapper->getFieldMap('name');

        $this->assertInstanceOf(FieldMapping::class, $fieldMap);
        $this->assertEquals('user_name', $fieldMap->getFieldName());
        $this->assertEquals('full_name', $fieldMap->getFieldAlias());
    }

    public function testAddMultipleFieldMappings()
    {
        $mapper = new Mapper(Users::class, 'users', 'id');

        $mapper->addFieldMapping(FieldMapping::create('id')->withFieldName('user_id'));
        $mapper->addFieldMapping(FieldMapping::create('name')->withFieldName('user_name'));
        $mapper->addFieldMapping(FieldMapping::create('createdate')->withFieldName('created_at'));

        $fieldMap = $mapper->getFieldMap();

        $this->assertCount(3, $fieldMap);
        $this->assertEquals('user_id', $fieldMap['id']->getFieldName());
        $this->assertEquals('user_name', $fieldMap['name']->getFieldName());
        $this->assertEquals('created_at', $fieldMap['createdate']->getFieldName());
    }
}
