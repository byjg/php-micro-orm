<?php

namespace ByJG\MicroOrm\Attributes;

use Attribute;
use ByJG\MicroOrm\FieldMapping;
use ByJG\MicroOrm\Interface\MapperFunctionInterface;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_PROPERTY)]
class FieldAttribute
{
    private ?string $propertyName;

    /**
     * @param bool|null $primaryKey
     * @param string|null $fieldName
     * @param string|null $fieldAlias
     * @param bool|null $syncWithDb
     * @param MapperFunctionInterface|string|null $updateFunction
     * @param MapperFunctionInterface|string|null $selectFunction
     * @param MapperFunctionInterface|string|null $insertFunction
     * @param string|null $parentTable
     */
    public function __construct(
        private ?bool                               $primaryKey = null,
        private ?string                             $fieldName = null,
        private ?string                             $fieldAlias = null,
        private ?bool                               $syncWithDb = null,
        private MapperFunctionInterface|string|null $updateFunction = null,
        private MapperFunctionInterface|string|null $selectFunction = null,
        private MapperFunctionInterface|string|null $insertFunction = null,
        private ?string                             $parentTable = null
    )
    {
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