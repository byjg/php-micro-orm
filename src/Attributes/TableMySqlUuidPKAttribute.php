<?php

namespace ByJG\MicroOrm\Attributes;

use Attribute;
use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\MicroOrm\Interface\MapperFunctionInterface;
use ByJG\MicroOrm\Literal\Literal;
use Override;

#[Attribute(Attribute::TARGET_CLASS)]
class TableMySqlUuidPKAttribute extends TableAttribute implements MapperFunctionInterface
{
    public function __construct(string $tableName)
    {
        parent::__construct($tableName, primaryKeySeedFunction: $this);
    }

    #[Override]
    public function processedValue(mixed $value, mixed $instance, ?DatabaseExecutor $executor = null): mixed
    {
        return new Literal("X'" . $executor->getScalar("SELECT hex(uuid_to_bin(uuid()))") . "'");
    }
}
