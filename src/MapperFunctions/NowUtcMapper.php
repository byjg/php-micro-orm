<?php

namespace ByJG\MicroOrm\MapperFunctions;

use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\MicroOrm\Interface\MapperFunctionInterface;
use ByJG\MicroOrm\Literal\Literal;
use Override;

/**
 * The current date and time, for a date column (created_at, updated_at): CURRENT_TIMESTAMP, which
 * MySQL, PostgreSQL, SQLite, SQL Server and Oracle all accept for a date or timestamp column.
 *
 * It used to be the dialect's sqlDate('Y-m-d H:i:s'), a text made to display a date: PostgreSQL
 * rejects text for a timestamp column, and SQL Server's FORMAT() pattern was not a valid one.
 */
class NowUtcMapper implements MapperFunctionInterface
{
    public const NOW = 'CURRENT_TIMESTAMP';

    #[Override]
    public function processedValue(mixed $value, mixed $instance, ?DatabaseExecutor $executor = null): mixed
    {
        return new Literal(self::NOW);
    }
} 