<?php

namespace ByJG\MicroOrm\Attributes;

use Attribute;
use ByJG\MicroOrm\FieldMapping;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_PROPERTY)]
class FieldAttribute
{
    private ?string $fieldName;
    private mixed $updateFunction;
    private mixed $selectFunction;
    private mixed $insertFunction;
    private ?string $fieldAlias;
    private ?bool $syncWithDb;
    private ?bool $primaryKey;
    private ?string $propertyName;
    private ?string $parentTable;

    public function __construct(
        bool $primaryKey = null,
        string $fieldName = null,
        string $fieldAlias = null,
        bool $syncWithDb = null,
        callable $updateFunction = null,
        callable $selectFunction = null,
        callable $insertFunction = null,
        string   $parentTable = null
    )
    {
        $this->primaryKey = $primaryKey;
        $this->fieldName = $fieldName;
        $this->fieldAlias = $fieldAlias;
        $this->insertFunction = $insertFunction;
        $this->updateFunction = $updateFunction;
        $this->selectFunction = $selectFunction;
        $this->syncWithDb = $syncWithDb;
        $this->parentTable = $parentTable;

        if ($this->syncWithDb === false && !is_null($this->updateFunction)) {
            throw new InvalidArgumentException("You cannot have an updateFunction when syncWithDb is false");
        }
    }

    public function getFieldMapping(string $propertyName): FieldMapping
    {
        $this->propertyName = $propertyName;
        $fieldMapping = FieldMapping::create($propertyName);
        $fieldMapping->withFieldName($this->fieldName ?? $propertyName);

        if (!is_null($this->updateFunction)) {
            $fieldMapping->withUpdateFunction($this->updateFunction);
        }
        if (!is_null($this->selectFunction)) {
            $fieldMapping->withSelectFunction($this->selectFunction);
        }
        if (!is_null($this->insertFunction)) {
            $fieldMapping->withInsertFunction($this->insertFunction);
        }
        if (!is_null($this->fieldAlias)) {
            $fieldMapping->withFieldAlias($this->fieldAlias);
        }
        if ($this->syncWithDb === false) {
            $fieldMapping->dontSyncWithDb();
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

    public function getParentTable(): ?string
    {
        return $this->parentTable;
    }
}