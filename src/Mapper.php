<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\Attributes\TableAttribute;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Exception\OrmModelInvalidException;
use ByJG\MicroOrm\Interface\EntityProcessorInterface;
use ByJG\MicroOrm\Interface\MapperFunctionInterface;
use ByJG\MicroOrm\Literal\LiteralInterface;
use ByJG\MicroOrm\PropertyHandler\MapFromDbToInstanceHandler;
use ByJG\Serializer\ObjectCopy;
use ByJG\Serializer\Serialize;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;

class Mapper
{

    private string $entity;
    private string $table;
    private array $primaryKey;
    private array $primaryKeyModel;
    private string|MapperFunctionInterface|null $primaryKeySeedFunction = null;
    private bool $softDelete = false;
    private string|EntityProcessorInterface|null $beforeInsert = null;
    private string|EntityProcessorInterface|null $beforeUpdate = null;

    /**
     * @var FieldMapping[]
     */
    private array $fieldMap = [];

    private array $fieldToProperty = [];
    private bool $preserveCaseName = false;
    private ?string $tableAlias = null;

    /**
     * Mapper constructor.
     *
     * @param string $entity
     * @param string|null $table
     * @param string|array|null $primaryKey
     * @param string|null $tableAlias
     * @throws OrmModelInvalidException
     * @throws ReflectionException
     */
    public function __construct(
        string $entity,
        ?string $table = null,
        string|array|null $primaryKey = null,
        ?string           $tableAlias = null
    ) {
        if (!class_exists($entity)) {
            throw new OrmModelInvalidException("Entity '$entity' does not exists");
        }

        $this->entity = $entity;
        if (empty($table) || empty($primaryKey)) {
            $this->processAttribute($entity);
        } else {
            $primaryKey = (array)$primaryKey;

            $this->table = $table;
            $this->tableAlias = $tableAlias;
            $this->primaryKey = array_map([$this, 'fixFieldName'], $primaryKey);
            $this->primaryKeyModel = $primaryKey;
            ORM::addMapper($this);
        }
    }

    /**
     * @throws ReflectionException
     * @throws OrmModelInvalidException
     */
    protected function processAttribute(string $entity): void
    {
        /** @psalm-var class-string $entity */
        $reflection = new ReflectionClass($entity);
        $attributes = $reflection->getAttributes(TableAttribute::class, ReflectionAttribute::IS_INSTANCEOF);
        if (count($attributes) == 0) {
            throw new OrmModelInvalidException("Entity '$entity' does not have the TableAttribute");
        }

        /** @var TableAttribute $tableAttribute */
        $tableAttribute = $attributes[0]->newInstance();
        $this->table = $tableAttribute->getTableName();
        $this->tableAlias = $tableAttribute->getTableAlias();
        $primaryKeySeedFunc = $tableAttribute->getPrimaryKeySeedFunction();
        if ($primaryKeySeedFunc !== null && $primaryKeySeedFunc !== '') {
            $this->withPrimaryKeySeedFunction($primaryKeySeedFunc);
        }
        $beforeInsert = $tableAttribute->getBeforeInsert();
        if ($beforeInsert !== null && $beforeInsert !== '') {
            $this->withBeforeInsert($beforeInsert);
        }
        $beforeUpdate = $tableAttribute->getBeforeUpdate();
        if ($beforeUpdate !== null && $beforeUpdate !== '') {
            $this->withBeforeUpdate($beforeUpdate);
        }
        ORM::addMapper($this);

        $this->primaryKey = [];
        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(FieldAttribute::class, ReflectionAttribute::IS_INSTANCEOF);
            if (count($attributes) == 0) {
                continue;
            }

            $fieldAttribute = $attributes[0]->newInstance();
            $fieldMapping = $fieldAttribute->getFieldMapping($property->getName());
            $this->addFieldMapping($fieldMapping);

            if ($fieldAttribute->isPrimaryKey()) {
                $this->primaryKey[] = $this->fixFieldName($fieldAttribute->getFieldName());
                $this->primaryKeyModel[] = $property->getName();
            }

            $parentTable = $fieldAttribute->getParentTable();
            $fieldName = $fieldAttribute->getFieldName();
            if ($parentTable !== null && $parentTable !== '' && $fieldName !== null && $fieldName !== '') {
                ORM::addRelationship($parentTable, $this, $fieldName);
            }
        }
    }

    public function withPrimaryKeySeedFunction(string|MapperFunctionInterface $primaryKeySeedFunction): static
    {
        $this->primaryKeySeedFunction = $primaryKeySeedFunction;
        return $this;
    }

    public function withPreserveCaseName(): static
    {
        $this->preserveCaseName = true;
        return $this;
    }

    public function withBeforeInsert(EntityProcessorInterface|string $processor): static
    {
        $this->beforeInsert = $processor;
        return $this;
    }

    public function withBeforeUpdate(EntityProcessorInterface|string $processor): static
    {
        $this->beforeUpdate = $processor;
        return $this;
    }

    public function fixFieldName(?string $field): ?string
    {
        if (is_null($field)) {
            return null;
        }

        if (!$this->preserveCaseName) {
            return strtolower($field);
        }

        return $field;
    }

    public function prepareField(array $fieldList): array
    {
        $result = [];
        foreach ($fieldList as $key => $value) {
            $fixedKey = $this->fixFieldName($key);
            if ($fixedKey !== null) {
                $result[$fixedKey] = $value;
            }
        }
        return $result;
    }

    public function addFieldMapping(FieldMapping $fieldMapping): static
    {
        $propertyName = $this->fixFieldName($fieldMapping->getPropertyName());
        $fieldName = $this->fixFieldName($fieldMapping->getFieldName());
        $fieldAlias = $this->fixFieldName($fieldMapping->getFieldAlias());

        if ($propertyName === null || $fieldName === null || $fieldAlias === null) {
            return $this;
        }

        $this->fieldMap[$propertyName] = $fieldMapping
            ->withFieldName($fieldName)
            ->withFieldAlias($fieldAlias)
        ;
        $this->fieldToProperty[$fieldName] = $propertyName;
        $this->fieldToProperty[$fieldAlias] = $propertyName;

        if ($fieldName === 'deleted_at') {
            $this->softDelete = true;
        }

        $parentTable = $fieldMapping->getParentTable();
        if ($parentTable !== null && $parentTable !== '') {
            ORM::addRelationship($parentTable, $this, $fieldMapping->getFieldName(), "?");
        }


        return $this;
    }

    public function getEntityClass(): string
    {
        return $this->entity;
    }

    /**
     * @param array $fieldValues
     * @return object
     */
    public function getEntity(array $fieldValues = []): object
    {
        $class = $this->entity;
        $instance = new $class();

        // The command below is to get all properties of the class.
        // This will allow processing all properties, even if they are not in the $fieldValues array.
        // Particularly useful for processing the selectFunction.
        $fieldValues = array_merge(Serialize::from($instance)->withStopAtFirstLevel()->toArray(), $fieldValues);
        ObjectCopy::copy($fieldValues, $instance, new MapFromDbToInstanceHandler($this));

        if (is_array($instance)) {
            $instance = (object)$instance;
        }

        return $instance;
    }

    public function getQuery(): Query
    {
        return Query::getInstance()
            ->table($this->table);
    }

    public function getQueryBasic(): QueryBasic
    {
        return QueryBasic::getInstance()
            ->table($this->table);
    }

    public function getDeleteQuery(): DeleteQuery
    {
        return DeleteQuery::getInstance()
            ->table($this->table);
    }

    public function getInsertQuery(): InsertQuery
    {
        return InsertQuery::getInstance()
            ->table($this->table);
    }

    public function getUpdateQuery(): UpdateQuery
    {
        return UpdateQuery::getInstance()
            ->table($this->table);
    }


    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    public function getTableAlias(): string
    {
        return $this->tableAlias ?? $this->table;
    }

    /**
     * @return array
     */
    public function getPrimaryKey(): array
    {
        return $this->primaryKey;
    }

    /**
     * @return array
     */
    public function getPrimaryKeyModel(): array
    {
        return $this->primaryKeyModel;
    }

    public function getPkFilter(array|string|int|LiteralInterface $pkId): array
    {
        $pkList = $this->getPrimaryKey();
        if (!is_array($pkId)) {
            $pkId = [$pkId];
        }

        if (count($pkList) !== count($pkId)) {
            throw new InvalidArgumentException("The primary key must have " . count($pkList) . " values");
        }

        $filterList = [];
        $filterKeys = [];
        foreach ($pkList as $pk) {
            $filterList[] = $pk . " = :pk$pk";
            $filterKeys["pk$pk"] = array_shift($pkId);
        }

        return [implode(' and ', $filterList), $filterKeys];
    }

    /**
     * @return bool
     */
    public function isPreserveCaseName(): bool
    {
        return $this->preserveCaseName;
    }

    /**
     * @param string|null $property
     * @return ($property is null ? array<string, FieldMapping> : FieldMapping|null)
     */
    public function getFieldMap(?string $property = null): array|FieldMapping|null
    {
        if (empty($property)) {
            return $this->fieldMap;
        }

        $property = $this->fixFieldName($property);

        if ($property === null || !isset($this->fieldMap[$property])) {
            return null;
        }

        return $this->fieldMap[$property];
    }

    public function getPropertyName(string $fieldName): string
    {
        return $this->fieldToProperty[$fieldName] ?? $fieldName;
    }

    /**
     * @param string|null $fieldName
     * @return string|null
     */
    public function getFieldAlias(?string $fieldName = null): ?string
    {
        $fieldMapVar = $this->getFieldMap($fieldName);
        if (empty($fieldMapVar) || is_array($fieldMapVar)) {
            return null;
        }

        return $fieldMapVar->getFieldAlias();
    }

    /**
     * @param DatabaseExecutor $executor
     * @param object $instance
     * @return mixed|null
     */
    public function generateKey(DatabaseExecutor $executor, object $instance): mixed
    {
        if (empty($this->primaryKeySeedFunction)) {
            return null;
        }

        if (is_string($this->primaryKeySeedFunction)) {
            $primaryKeyFunction = new $this->primaryKeySeedFunction();
        } else {
            $primaryKeyFunction = $this->primaryKeySeedFunction;
        }

        return $primaryKeyFunction->processedValue(null, $instance, $executor);
    }

    public function isSoftDeleteEnabled(): bool
    {
        return $this->softDelete;
    }

    /**
     * @return mixed
     */
    public function getBeforeInsert(): mixed
    {
        return $this->beforeInsert;
    }

    /**
     * @return mixed
     */
    public function getBeforeUpdate(): mixed
    {
        return $this->beforeUpdate;
    }
}
