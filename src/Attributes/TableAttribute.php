<?php

namespace ByJG\MicroOrm\Attributes;

use Attribute;
use Closure;

#[Attribute(Attribute::TARGET_CLASS)]
class TableAttribute
{

    private string $tableName;
    private ?Closure $primaryKeySeedFunction;

    public function __construct(string $tableName, ?Closure $primaryKeySeedFunction = null)
    {

        $this->tableName = $tableName;
        $this->primaryKeySeedFunction = $primaryKeySeedFunction;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getPrimaryKeySeedFunction(): ?Closure
    {
        return $this->primaryKeySeedFunction;
    }
}
