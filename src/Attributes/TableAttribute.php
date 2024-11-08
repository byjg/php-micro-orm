<?php

namespace ByJG\MicroOrm\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class TableAttribute
{

    private string $tableName;
    private mixed $primaryKeySeedFunction;

    public function __construct(string $tableName, callable $primaryKeySeedFunction = null)
    {

        $this->tableName = $tableName;
        $this->primaryKeySeedFunction = $primaryKeySeedFunction;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getPrimaryKeySeedFunction(): ?callable
    {
        return $this->primaryKeySeedFunction;
    }
}
