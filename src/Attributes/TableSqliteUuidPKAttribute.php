<?php

namespace ByJG\MicroOrm\Attributes;

use Attribute;
use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\MicroOrm\Interface\MapperFunctionInterface;
use ByJG\MicroOrm\Literal\Literal;
use InvalidArgumentException;
use Override;

#[Attribute(Attribute::TARGET_CLASS)]
class TableSqliteUuidPKAttribute extends TableAttribute implements MapperFunctionInterface
{
    public function __construct(string $tableName)
    {
        parent::__construct($tableName, primaryKeySeedFunction: $this);
    }

    #[Override]
    public function processedValue(mixed $value, mixed $instance, ?DatabaseExecutor $executor = null): mixed
    {
        if ($executor === null) {
            throw new InvalidArgumentException('DatabaseExecutor is required for TableSqliteUuidPKAttribute');
        }
        return new Literal("X'" . $executor->getScalar("SELECT hex(randomblob(16))") . "'");
    }
}
