<?php

namespace ByJG\MicroOrm\MapperFunctions;

use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\MicroOrm\Interface\MapperFunctionInterface;
use ByJG\MicroOrm\Literal\HexUuidLiteral;
use Override;

class FormatSelectUuidMapper implements MapperFunctionInterface
{
    #[Override]
    public function processedValue(mixed $value, mixed $instance, ?DatabaseExecutor $executor = null): mixed
    {
        if (empty($value)) {
            return $value;
        }

        // The $value parameter contains the actual field value from the database
        // Convert it to formatted UUID string
        return HexUuidLiteral::getFormattedUuid($value, throwErrorIfInvalid: false);
    }
} 