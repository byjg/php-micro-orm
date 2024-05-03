<?php

namespace ByJG\MicroOrm\Attributes;

use Attribute;
use ByJG\MicroOrm\FieldMapping;
use Closure;
use function PHPUnit\Framework\isNull;

#[Attribute(Attribute::TARGET_PROPERTY)]
class FieldAttribute
{
    private ?string $fieldName;
    private ?Closure $updateFunction;
    private ?Closure $selectFunction;
    private ?string $fieldAlias;
    private ?bool $syncWithDb;
    private ?bool $primaryKey;

    private ?string $propertyName;

    public function __construct(
        bool $primaryKey = null,
        string $fieldName = null,
        string $fieldAlias = null,
        bool $syncWithDb = null,
        Closure $updateFunction = null,
        Closure $selectFunction = null
    )
    {
        $this->primaryKey = $primaryKey;
        $this->fieldName = $fieldName;
        $this->fieldAlias = $fieldAlias;
        $this->updateFunction = $updateFunction;
        $this->selectFunction = $selectFunction;
        $this->syncWithDb = $syncWithDb;
    }

    public function getFieldMapping(string $propertyName): ?FieldMapping
    {
        $this->propertyName = $propertyName;
        $empty = true;
        $fieldMapping = FieldMapping::create($propertyName);
        if (!is_null($this->fieldName)) {
            $fieldMapping->withFieldName($this->fieldName);
            $empty = false;
        }
        if (!is_null($this->updateFunction)) {
            $fieldMapping->withUpdateFunction($this->updateFunction);
            $empty = false;
        }
        if (!is_null($this->selectFunction)) {
            $fieldMapping->withSelectFunction($this->selectFunction);
            $empty = false;
        }
        if (!is_null($this->fieldAlias)) {
            $fieldMapping->withFieldAlias($this->fieldAlias);
            $empty = false;
        }
        if ($this->syncWithDb === false) {
            $fieldMapping->dontSyncWithDb();
            $empty = false;
        }
        if ($empty) {
            return null;
        }
        return $fieldMapping;
    }

    public function getPropertyName(): ?string
    {
        return $this->propertyName;
    }

    public function getFieldName(): ?string
    {
        return $this->fieldName ?? $this->propertyName;
    }

    public function isPrimaryKey(): ?bool
    {
        return $this->primaryKey;
    }
}