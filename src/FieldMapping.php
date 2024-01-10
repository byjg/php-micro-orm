<?php

namespace ByJG\MicroOrm;

use Closure;

class FieldMapping
{
    /**
     * @var string
     */
    private string $fieldName;

    /**
     * @var Closure
     */
    private Closure $updateFunction;

    /**
     * @var Closure
     */
    private Closure $selectFunction;

    /**
     * @var string
     */
    private string $propertyName;

    /**
     * @var string
     */
    private string $fieldAlias;

    private bool $syncWithDb = true;

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

        $this->selectFunction = MapperClosure::standard();
        $this->updateFunction = MapperClosure::standard();
    }

    public function withUpdateFunction(Closure $updateFunction): static
    {
        $this->updateFunction = $updateFunction;
        return $this;
    }

    public function withSelectFunction(Closure $selectFunction): static
    {
        $this->selectFunction = $selectFunction;
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

    public function dontSyncWithDb(): static
    {
        $this->syncWithDb = false;
        $this->withUpdateFunction(MapperClosure::readOnly());
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
     * @return Closure
     */
    public function getUpdateFunction(): Closure
    {
        return $this->updateFunction;
    }

    /**
     * @return Closure
     */
    public function getSelectFunction(): Closure
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
        return call_user_func_array($this->selectFunction, [$value, $instance]);
    }

    public function getUpdateFunctionValue(mixed $value, mixed $instance): mixed
    {
        return call_user_func_array($this->updateFunction, [$value, $instance]);
    }

    public function isSyncWithDb(): bool
    {
        return $this->syncWithDb;
    }
}