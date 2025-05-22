<?php

namespace ByJG\MicroOrm\Attributes;

use Attribute;
use ByJG\MicroOrm\Interface\EntityProcessorInterface;

#[Attribute(Attribute::TARGET_CLASS)]
class TableAttribute
{

    private string $tableName;
    private mixed $primaryKeySeedFunction;
    private ?string $tableAlias;
    private string|EntityProcessorInterface|null $beforeInsert = null;
    private string|EntityProcessorInterface|null $beforeUpdate = null;

    public function __construct(
        string                          $tableName,
        callable                        $primaryKeySeedFunction = null,
        string                          $tableAlias = null,
        string|EntityProcessorInterface $beforeInsert = null,
        string|EntityProcessorInterface $beforeUpdate = null
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

    public function getPrimaryKeySeedFunction(): ?callable
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
