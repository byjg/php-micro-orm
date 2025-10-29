<?php

namespace ByJG\MicroOrm\Attributes;

use Attribute;
use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\MicroOrm\Interface\UniqueIdGeneratorInterface;
use ByJG\MicroOrm\Literal\Literal;

#[Attribute(Attribute::TARGET_CLASS)]
class TableUuidPKAttribute extends TableAttribute implements UniqueIdGeneratorInterface
{
    public function __construct(string $tableName)
    {
        parent::__construct($tableName, primaryKeySeedFunction: $this);
    }

    public function process(DatabaseExecutor $executor, array|object $instance): string|Literal|int
    {
        return new Literal("X'" . bin2hex(random_bytes(16)) . "'");
    }
}
