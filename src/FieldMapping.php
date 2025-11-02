<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\MicroOrm\Interface\MapperFunctionInterface;
use ByJG\MicroOrm\MapperFunctions\ReadOnlyMapper;
use ByJG\MicroOrm\MapperFunctions\StandardMapper;
use InvalidArgumentException;

class FieldMapping
{
    /**
     * @var string
     */
    private string $fieldName;

    /**
     * @var MapperFunctionInterface|string
     */
    private mixed $updateFunction;

    /**
     * @var MapperFunctionInterface|string
     */
    private mixed $selectFunction;

    /**
     * @var MapperFunctionInterface|string
     */
    private mixed $insertFunction;

    /**
     * @var string
     */
    private string $propertyName;

    /**
     * @var string
     */
    private string $fieldAlias;

    private bool $syncWithDb = true;

    private ?string $parentTable = null;

    public static function create(string $propertyName): FieldMapping
    {
        return new FieldMapping($propertyName);
    }

    /**
     * FieldMapping constructor.
     * @param string $propertyName
     */
    public function __construct(string $propertyName)
    {
        $this->fieldName = $propertyName;
        $this->fieldAlias = $propertyName;
        $this->propertyName = $propertyName;

        $this->selectFunction = StandardMapper::class;
        $this->updateFunction = StandardMapper::class;
        $this->insertFunction = ReadOnlyMapper::class;
    }

    private function checkIfStringImplementsMapperInterface(string $className): void
    {
        if (!class_exists($className)) {
            throw new InvalidArgumentException("The class '$className' does not exist");
        }

        if (!in_array(MapperFunctionInterface::class, class_implements($className))) {
            throw new InvalidArgumentException("The class '$className' must implement MapperFunctionInterface");
        }
    }

    public function withUpdateFunction(MapperFunctionInterface|string $updateFunction): static
    {
        if (is_string($updateFunction)) {
            $this->checkIfStringImplementsMapperInterface($updateFunction);
        }

        $this->updateFunction = $updateFunction;
        return $this;
    }

    public function withSelectFunction(MapperFunctionInterface|string $selectFunction): static
    {
        if (is_string($selectFunction)) {
            $this->checkIfStringImplementsMapperInterface($selectFunction);
        }

        $this->selectFunction = $selectFunction;
        return $this;
    }

    public function withInsertFunction(MapperFunctionInterface|string $insertFunction): static
    {
        if (is_string($insertFunction)) {
            $this->checkIfStringImplementsMapperInterface($insertFunction);
        }

        $this->insertFunction = $insertFunction;
        return $this;
    }

    public function withFieldName(string $fieldName): static
    {
        $this->fieldName = $fieldName;
        return $this;
    }

    public function withFieldAlias(string $fieldAlias): static
    {
        $this->fieldAlias = $fieldAlias;
        return $this;
    }

    public function withParentTable(string $parentTable): static
    {
        $this->parentTable = $parentTable;
        return $this;
    }

    public function dontSyncWithDb(): static
    {
        $this->syncWithDb = false;
        $this->withUpdateFunction(ReadOnlyMapper::class);
        return $this;
    }

    /**
     * @return string
     */
    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    /**
     * @return MapperFunctionInterface|string
     */
    public function getUpdateFunction(): mixed
    {
        return $this->updateFunction;
    }

    /**
     * @return MapperFunctionInterface|string
     */
    public function getSelectFunction(): mixed
    {
        return $this->selectFunction;
    }

    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    public function getFieldAlias(): ?string
    {
        return $this->fieldAlias;
    }

    public function getSelectFunctionValue(mixed $value, mixed $instance): mixed
    {
        $function = $this->selectFunction;
        if (is_string($function)) {
            $function = new $function();
        }
        return $function->processedValue($value, $instance, null);
    }

    public function getUpdateFunctionValue(mixed $value, mixed $instance, ?DatabaseExecutor $executor = null): mixed
    {
        $function = $this->updateFunction;
        if (is_string($function)) {
            $function = new $function();
        }
        return $function->processedValue($value, $instance, $executor);
    }

    public function getInsertFunctionValue(mixed $value, mixed $instance, ?DatabaseExecutor $executor = null): mixed
    {
        $function = $this->insertFunction;
        if (is_string($function)) {
            $function = new $function();
        }
        return $function->processedValue($value, $instance, $executor);
    }

    public function isSyncWithDb(): bool
    {
        return $this->syncWithDb;
    }

    public function getParentTable(): ?string
    {
        return $this->parentTable;
    }
}