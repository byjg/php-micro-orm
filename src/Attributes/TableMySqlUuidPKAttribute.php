<?php

namespace ByJG\MicroOrm\Attributes;

use Attribute;
use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\MicroOrm\Interface\UniqueIdGeneratorInterface;
use ByJG\MicroOrm\Literal\Literal;

#[Attribute(Attribute::TARGET_CLASS)]
class TableMySqlUuidPKAttribute extends TableAttribute implements UniqueIdGeneratorInterface
{
    public function __construct(string $tableName)
    {
        parent::__construct($tableName, primaryKeySeedFunction: $this);
    }

    public function process(DatabaseExecutor $executor, array|object $instance): string|Literal|int
    {
        return new Literal("X'" . $executor->getScalar("SELECT hex(uuid_to_bin(uuid()))") . "'");
    }
}
