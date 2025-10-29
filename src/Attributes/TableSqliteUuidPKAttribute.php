<?php

namespace ByJG\MicroOrm\Attributes;

use Attribute;
use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\MicroOrm\Interface\UniqueIdGeneratorInterface;
use ByJG\MicroOrm\Literal\Literal;

#[Attribute(Attribute::TARGET_CLASS)]
class TableSqliteUuidPKAttribute extends TableAttribute implements UniqueIdGeneratorInterface
{
    public function __construct(string $tableName)
    {
        parent::__construct($tableName, primaryKeySeedFunction: $this);
    }

    public function process(DatabaseExecutor $executor, array|object $instance): string|Literal|int
    {
        return new Literal("X'" . $executor->getScalar("SELECT hex(randomblob(16))") . "'");
    }
}
