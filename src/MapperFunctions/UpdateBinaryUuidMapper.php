<?php

namespace ByJG\MicroOrm\MapperFunctions;

use ByJG\MicroOrm\Interface\DbFunctionsInterface;
use ByJG\MicroOrm\Interface\MapperFunctionInterface;
use ByJG\MicroOrm\Literal\HexUuidLiteral;
use ByJG\MicroOrm\Literal\Literal;
use Override;

class UpdateBinaryUuidMapper implements MapperFunctionInterface
{
    #[Override]
    public function processedValue(mixed $value, mixed $instance, ?\ByJG\AnyDataset\Db\DbFunctionsInterface $helper = null): mixed
    {
        if (empty($value)) {
            return null;
        }
        if (!($value instanceof Literal)) {
            $value = new HexUuidLiteral($value);
        }
        return $value;
    }
} 