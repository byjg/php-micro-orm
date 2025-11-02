<?php

namespace ByJG\MicroOrm\Attributes;

use Attribute;
use ByJG\MicroOrm\Interface\EntityProcessorInterface;
use ByJG\MicroOrm\Interface\MapperFunctionInterface;

#[Attribute(Attribute::TARGET_CLASS)]
class TableAttribute
{

    private string $tableName;
    private string|MapperFunctionInterface|null $primaryKeySeedFunction;
    private ?string $tableAlias;
    private string|EntityProcessorInterface|null $beforeInsert = null;
    private string|EntityProcessorInterface|null $beforeUpdate = null;

    public function __construct(
        string                                 $tableName,
        string|MapperFunctionInterface|null $primaryKeySeedFunction = null,
        ?string                              $tableAlias = null,
        string|EntityProcessorInterface|null $beforeInsert = null,
        string|EntityProcessorInterface|null $beforeUpdate = null
    )
    {
        $this->tableName = $tableName;
        $this->tableAlias = $tableAlias;
        $this->primaryKeySeedFunction = $primaryKeySeedFunction;
        $this->beforeInsert = $beforeInsert;
        $this->beforeUpdate = $beforeUpdate;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getTableAlias(): ?string
    {
        return $this->tableAlias;
    }

    public function getPrimaryKeySeedFunction(): string|MapperFunctionInterface|null
    {
        return $this->primaryKeySeedFunction;
    }

    public function getBeforeInsert(): string|EntityProcessorInterface|null
    {
        return $this->beforeInsert;
    }

    public function getBeforeUpdate(): string|EntityProcessorInterface|null
    {
        return $this->beforeUpdate;
    }
}
