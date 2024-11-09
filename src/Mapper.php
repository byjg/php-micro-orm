<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\Attributes\TableAttribute;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Exception\OrmModelInvalidException;
use ByJG\MicroOrm\Literal\LiteralInterface;
use ByJG\Serializer\ObjectCopy;
use Closure;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;

class Mapper
{

    private string $entity;
    private string $table;
    private array $primaryKey;
    private array $primaryKeyModel;
    private mixed $primaryKeySeedFunction = null;
    private bool $softDelete = false;

    /**
     * @var FieldMapping[]
     */
    private array $fieldMap = [];
    private bool $preserveCaseName = false;
    private ?string $tableAlias = null;

    /**
     * Mapper constructor.
     *
     * @param string $entity
     * @param string|null $table
     * @param string|array|null $primaryKey
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
        $reflection = new ReflectionClass($entity);
        $attributes = $reflection->getAttributes(TableAttribute::class, ReflectionAttribute::IS_INSTANCEOF);
        if (count($attributes) == 0) {
            throw new OrmModelInvalidException("Entity '$entity' does not have the TableAttribute");
        }

        /** @var TableAttribute $tableAttribute */
        $tableAttribute = $attributes[0]->newInstance();
        $this->table = $tableAttribute->getTableName();
        $this->tableAlias = $tableAttribute->getTableAlias();
        if (!empty($tableAttribute->getPrimaryKeySeedFunction())) {
            $this->withPrimaryKeySeedFunction($tableAttribute->getPrimaryKeySeedFunction());
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

            if (!empty($fieldAttribute->getParentTable())) {
                ORM::addRelationship($fieldAttribute->getParentTable(), $this, $fieldAttribute->getFieldName());
            }
        }
    }

    public function withPrimaryKeySeedFunction(callable $primaryKeySeedFunction): static
    {
        $this->primaryKeySeedFunction = $primaryKeySeedFunction;
        return $this;
    }

    public function withPreserveCaseName(): static
    {
        $this->preserveCaseName = true;
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
            $result[$this->fixFieldName($key)] = $value;
        }
        return $result;
    }

    public function addFieldMapping(FieldMapping $fieldMapping): static
    {
        $propertyName = $this->fixFieldName($fieldMapping->getPropertyName());
        $fieldName = $this->fixFieldName($fieldMapping->getFieldName());
        $fieldAlias = $this->fixFieldName($fieldMapping->getFieldAlias());
        $this->fieldMap[$propertyName] = $fieldMapping
            ->withFieldName($fieldName)
            ->withFieldAlias($fieldAlias)
        ;

        if ($fieldName === 'deleted_at') {
            $this->softDelete = true;
        }

        if (!empty($fieldMapping->getParentTable())) {
            ORM::addRelationship($fieldMapping->getParentTable(), $this, $fieldMapping->getFieldName(), "?");
        }


        return $this;
    }

    /**
     * @param string $property
     * @param string $fieldName
     * @param Closure|null $updateFunction
     * @param Closure|null $selectFunction
     * @return $this
     * @deprecated Use addFieldMapping instead
     */
    public function addFieldMap(string $property, string $fieldName, Closure $updateFunction = null, Closure $selectFunction = null): static
    {
        $fieldMapping = FieldMapping::create($property)
            ->withFieldName($fieldName);

        if (!is_null($updateFunction)) {
            $fieldMapping->withUpdateFunction($updateFunction);
        }
        if (!is_null($selectFunction)) {
            $fieldMapping->withSelectFunction($selectFunction);
        }

        return $this->addFieldMapping($fieldMapping);
    }

    /**
     * @param array $fieldValues
     * @return object
     */
    public function getEntity(array $fieldValues = []): object
    {
        $class = $this->entity;
        $instance = new $class();

        if (empty($fieldValues)) {
            return $instance;
        }

        foreach ((array)$this->getFieldMap() as $property => $fieldMap) {
            if (!empty($fieldMap->getFieldAlias()) && isset($fieldValues[$fieldMap->getFieldAlias()])) {
                $fieldValues[$fieldMap->getFieldName()] = $fieldValues[$fieldMap->getFieldAlias()];
            }
            if ($property != $fieldMap->getFieldName() && isset($fieldValues[$fieldMap->getFieldName()])) {
                $fieldValues[$property] = $fieldValues[$fieldMap->getFieldName()];
                unset($fieldValues[$fieldMap->getFieldName()]);
            }
        }
        ObjectCopy::copy($fieldValues, $instance);

        foreach ((array)$this->getFieldMap() as $property => $fieldMap) {
            $fieldValues[$property] = $fieldMap->getSelectFunctionValue($fieldValues[$property] ?? "", $instance);
        }
        if (count($this->getFieldMap()) > 0) {
            ObjectCopy::copy($fieldValues, $instance);
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
     * @return FieldMapping[]|FieldMapping|null
     */
    public function getFieldMap(string $property = null): array|FieldMapping|null
    {
        if (empty($property)) {
            return $this->fieldMap;
        }

        $property = $this->fixFieldName($property);

        if (!isset($this->fieldMap[$property])) {
            return null;
        }

        return $this->fieldMap[$property];
    }

    /**
     * @param string|null $fieldName
     * @return string|null
     */
    public function getFieldAlias(?string $fieldName = null): ?string
    {
        $fieldMapVar = $this->getFieldMap($fieldName);
        if (empty($fieldMapVar)) {
            return null;
        }

        return $fieldMapVar->getFieldAlias();
    }

    /**
     * @param DbDriverInterface $dbDriver
     * @return mixed|null
     */
    public function generateKey(DbDriverInterface $dbDriver, object $instance): mixed
    {
        if (empty($this->primaryKeySeedFunction)) {
            return null;
        }

        return call_user_func_array($this->primaryKeySeedFunction, [$dbDriver, $instance]);
    }

    public function isSoftDeleteEnabled(): bool
    {
        return $this->softDelete;
    }
}
