<?php

namespace Tests;

use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\Attributes\FieldUuidAttribute;
use ByJG\MicroOrm\Attributes\TableAttribute;
use ByJG\MicroOrm\Interface\MapperFunctionInterface;
use ByJG\MicroOrm\Literal\HexUuidLiteral;
use ByJG\MicroOrm\Literal\LiteralInterface;
use ByJG\MicroOrm\MapperFunctions\FormatSelectUuidMapper;
use ByJG\MicroOrm\MapperFunctions\FormatUpdateUuidMapper;
use ByJG\MicroOrm\Repository;
use Override;
use PHPUnit\Framework\TestCase;

/**
 * Test class for validating UpdateFunction and SelectFunction with HexUuidLiteral as PK
 */
class HexUuidMapperFunctionTest extends TestCase
{
    protected Repository $repository;
    protected DatabaseExecutor $executor;

    #[Override]
    public function setUp(): void
    {
        $dbDriver = ConnectionUtil::getConnection("testmicroorm");
        $this->executor = DatabaseExecutor::using($dbDriver);
        $this->repository = new Repository($this->executor, HexUuidEntity::class);

        $this->executor->execute('create table hex_uuid_test (
            id binary(16) primary key,
            uuid_field binary(16),
            name varchar(100),
            description varchar(255)
        );');
    }

    #[Override]
    public function tearDown(): void
    {
        $this->executor->execute('drop table if exists hex_uuid_test;');
    }

    public function testInsertWithHexUuidPrimaryKey()
    {
        $entity = new HexUuidEntity();
        $entity->setName('Test Entity');
        $entity->setDescription('Test Description');

        // Before save, ID should be null
        $this->assertNull($entity->getId());

        // Save the entity
        $this->repository->save($entity);

        // After save, ID should be populated with a HexUuidLiteral
        /** @var string $id */
        $id = $entity->getId();
        $this->assertNotNull($id);
        $this->assertMatchesRegularExpression('/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/i', $id);
    }

    public function testSelectWithHexUuidPrimaryKey()
    {
        // Insert an entity
        $entity = new HexUuidEntity();
        $entity->setName('Select Test');
        $entity->setDescription('Testing SELECT with UUID');
        $this->repository->save($entity);

        /** @var string $savedId */
        $savedId = $entity->getId();
        $this->assertMatchesRegularExpression('/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/i', $savedId);


        // Query the entity back
        $result = $this->repository->get($savedId);

        $this->assertInstanceOf(HexUuidEntity::class, $result);
        $this->assertEquals('Select Test', $result->getName());
        $this->assertEquals('Testing SELECT with UUID', $result->getDescription());

        // Verify the ID is properly formatted
        $retrievedId = $result->getId();
        $this->assertNotNull($retrievedId);

        // The ID should be formatted as a string UUID
        $this->assertIsString($retrievedId);
        $this->assertMatchesRegularExpression('/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/i', $retrievedId);
    }

    public function testUpdateWithHexUuidPrimaryKey()
    {
        // Insert an entity
        $entity = new HexUuidEntity();
        $entity->setName('Original Name');
        $entity->setDescription('Original Description');
        $this->repository->save($entity);

        $savedId = $entity->getId();

        // Update the entity
        $entity->setName('Updated Name');
        $entity->setDescription('Updated Description');
        $this->repository->save($entity);

        // Verify the ID hasn't changed
        $this->assertEquals($savedId, $entity->getId());

        // Query back to verify update
        $query = $this->repository->getMapper()->getQuery();
        $query->where('id = :id', ['id' => new HexUuidLiteral($savedId)]);
        $result = $this->repository->getByQuery($query);

        $this->assertCount(1, $result);
        $this->assertEquals('Updated Name', $result[0]->getName());
        $this->assertEquals('Updated Description', $result[0]->getDescription());
    }

    public function testUpdateFunctionWithSecondaryUuidField()
    {
        // Insert an entity with a secondary UUID field
        $entity = new HexUuidEntity();
        $entity->setName('UUID Field Test');
        $entity->setDescription('Testing secondary UUID field');
        $entity->setUuidField('F47AC10B-58CC-4372-A567-0E02B2C3D479');

        $this->repository->save($entity);

        // Query back to verify the secondary UUID field
        $query = $this->repository->getMapper()->getQuery();
        $query->where('id = :id', ['id' => new HexUuidLiteral($entity->getId())]);
        $result = $this->repository->getByQuery($query);

        $this->assertCount(1, $result);

        // Verify the secondary UUID field is properly formatted on select
        $retrievedUuidField = $result[0]->getUuidField();
        $this->assertEquals('F47AC10B-58CC-4372-A567-0E02B2C3D479', $retrievedUuidField);
    }

    public function testUpdateSecondaryUuidField()
    {
        // Insert an entity
        $entity = new HexUuidEntity();
        $entity->setName('Update UUID Field Test');
        $entity->setUuidField('123E4567-E89B-12D3-A456-426614174000');
        $this->repository->save($entity);

        $savedId = $entity->getId();

        // Update the secondary UUID field
        $entity->setUuidField('987FED65-4321-BA98-7654-321098765432');
        $this->repository->save($entity);

        // Query back to verify the update
        $query = $this->repository->getMapper()->getQuery();
        $query->where('id = :id', ['id' => new HexUuidLiteral($savedId)]);
        $result = $this->repository->getByQuery($query);

        $this->assertCount(1, $result);
        $this->assertEquals('987FED65-4321-BA98-7654-321098765432', $result[0]->getUuidField());
    }

    public function testSelectWithNullUuidField()
    {
        // Insert an entity without a secondary UUID field
        $entity = new HexUuidEntity();
        $entity->setName('Null UUID Test');
        $entity->setDescription('Testing null UUID field');
        $this->repository->save($entity);

        // Query back
        $result = $this->repository->get($entity->getId());

        $this->assertNull($result->getUuidField());
    }

    public function testMultipleEntitiesWithDifferentUuids()
    {
        // Insert multiple entities
        $entity1 = new HexUuidEntity();
        $entity1->setName('Entity 1');
        $entity1->setUuidField('11111111-1111-1111-1111-111111111111');
        $this->repository->save($entity1);

        $entity2 = new HexUuidEntity();
        $entity2->setName('Entity 2');
        $entity2->setUuidField('22222222-2222-2222-2222-222222222222');
        $this->repository->save($entity2);

        $entity3 = new HexUuidEntity();
        $entity3->setName('Entity 3');
        $entity3->setUuidField('33333333-3333-3333-3333-333333333333');
        $this->repository->save($entity3);

        // Query all entities
        $query = $this->repository->getMapper()->getQuery();
        $query->orderBy(['name']);
        $result = $this->repository->getByQuery($query);

        $this->assertCount(3, $result);
        $this->assertEquals('Entity 1', $result[0]->getName());
        $this->assertEquals('11111111-1111-1111-1111-111111111111', $result[0]->getUuidField());
        $this->assertEquals('Entity 2', $result[1]->getName());
        $this->assertEquals('22222222-2222-2222-2222-222222222222', $result[1]->getUuidField());
        $this->assertEquals('Entity 3', $result[2]->getName());
        $this->assertEquals('33333333-3333-3333-3333-333333333333', $result[2]->getUuidField());
    }

    public function testDeleteWithHexUuidPrimaryKey()
    {
        // Insert an entity
        $entity = new HexUuidEntity();
        $entity->setName('To Delete');
        $this->repository->save($entity);

        $savedId = $entity->getId();

        // Verify it exists
        $query = $this->repository->getMapper()->getQuery();
        $query->where('id = :id', ['id' => new HexUuidLiteral($savedId)]);
        $result = $this->repository->getByQuery($query);
        $this->assertCount(1, $result);

        // Delete the entity
        $this->repository->delete($entity->getId());

        // Verify it's deleted
        $result = $this->repository->getByQuery($query);
        $this->assertCount(0, $result);
    }

    public function testBinaryUuidStorageAndRetrieval()
    {
        // Insert an entity
        $entity = new HexUuidEntity();
        $entity->setName('Binary Test');
        $entity->setUuidField('F47AC10B-58CC-4372-A567-0E02B2C3D479');
        $this->repository->save($entity);

        // Query the raw database to verify binary storage
        $rawQuery = "SELECT id, uuid_field, name FROM hex_uuid_test WHERE name = 'Binary Test'";
        $iterator = $this->executor->getIterator($rawQuery);
        $this->assertTrue($iterator->valid());

        $row = $iterator->current();

        // Verify the UUID is stored as binary (16 bytes)
        $this->assertEquals(16, strlen($row->get('id')));
        $this->assertEquals(16, strlen($row->get('uuid_field')));

        // Verify we can convert it back to the correct format
        $formattedId = HexUuidLiteral::getFormattedUuid($row->get('id'));
        $this->assertMatchesRegularExpression('/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/i', $formattedId);

        $formattedUuidField = HexUuidLiteral::getFormattedUuid($row->get('uuid_field'));
        $this->assertEquals('F47AC10B-58CC-4372-A567-0E02B2C3D479', $formattedUuidField);
    }
}

/**
 * UUID Generator for the test entity
 */
class HexUuidGenerator implements MapperFunctionInterface
{
    #[Override]
    public function processedValue(mixed $value, mixed $instance, ?DatabaseExecutor $executor = null): mixed
    {
        return new HexUuidLiteral(bin2hex(random_bytes(16)));
    }
}

/**
 * Test entity with HexUuidLiteral as primary key and mapper functions
 */
#[TableAttribute(tableName: 'hex_uuid_test', primaryKeySeedFunction: HexUuidGenerator::class)]
class HexUuidEntity
{
    #[FieldAttribute(
        primaryKey: true,
        updateFunction: FormatUpdateUuidMapper::class,
        selectFunction: FormatSelectUuidMapper::class
    )]
    protected string|LiteralInterface|null $id = null;

    #[FieldUuidAttribute(fieldName: 'uuid_field')]
    protected ?string $uuidField = null;

    #[FieldAttribute]
    protected ?string $name = null;

    #[FieldAttribute]
    protected ?string $description = null;

    public function getId(): string|LiteralInterface|null
    {
        return $this->id;
    }

    public function setId(string|LiteralInterface|null $id): void
    {
        $this->id = $id;
    }

    public function getUuidField(): ?string
    {
        return $this->uuidField;
    }

    public function setUuidField(?string $uuidField): void
    {
        $this->uuidField = $uuidField;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
}
