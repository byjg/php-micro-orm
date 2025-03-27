<?php

namespace ByJG\MicroOrm\MapperFunctions;

use ByJG\AnyDataset\Db\DbFunctionsInterface;
use ByJG\MicroOrm\Interface\MapperFunctionInterface;
use ByJG\MicroOrm\Literal\HexUuidLiteral;
use Override;

class SelectBinaryUuidMapper implements MapperFunctionInterface
{
    #[Override]
    public function processedValue(mixed $value, mixed $instance, ?DbFunctionsInterface $helper = null): mixed
    {
        $fieldValue = HexUuidLiteral::getFormattedUuid($value, false);
        if (is_null($fieldValue)) {
            return null;
        }
        return $fieldValue;
    }
} 