<?php

namespace ByJG\MicroOrm\MapperFunctions;

use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\MicroOrm\Interface\MapperFunctionInterface;
use ByJG\MicroOrm\Literal\HexUuidLiteral;
use ByJG\MicroOrm\Literal\LiteralInterface;
use Override;

class FormatUpdateUuidMapper implements MapperFunctionInterface
{
    #[Override]
    public function processedValue(mixed $value, mixed $instance, ?DatabaseExecutor $executor = null): mixed
    {
        if (empty($value)) {
            return null;
        }
        // A UUID literal made without knowing the database (e.g. new HexUuidLiteral() in a
        // setter) is written in the syntax of the database it goes to. Other literals pass as is.
        if ($value instanceof HexUuidLiteral || !($value instanceof LiteralInterface)) {
            return HexUuidLiteral::forDatabase($executor, $value);
        }
        return $value;
    }
} 