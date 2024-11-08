<?php

namespace ByJG\MicroOrm\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class TableAttribute
{

    private string $tableName;
    private mixed $primaryKeySeedFunction;
    private ?string $tableAlias;

    public function __construct(string $tableName, callable $primaryKeySeedFunction = null, string $tableAlias = null)
    {

        $this->tableName = $tableName;
        $this->tableAlias = $tableAlias;
        $this->primaryKeySeedFunction = $primaryKeySeedFunction;
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
}
