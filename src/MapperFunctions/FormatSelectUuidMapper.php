<?php

namespace ByJG\MicroOrm\MapperFunctions;

use ByJG\AnyDataset\Db\DbFunctionsInterface;
use ByJG\MicroOrm\Interface\MapperFunctionInterface;
use ByJG\MicroOrm\Literal\HexUuidLiteral;
use Override;

class FormatSelectUuidMapper implements MapperFunctionInterface
{
    #[Override]
    public function processedValue(mixed $value, mixed $instance, ?DbFunctionsInterface $helper = null): mixed
    {
        // Handle array instances (from database results)
        if (is_array($instance)) {
            if (!empty($instance['uuid'])) {
                return HexUuidLiteral::getFormattedUuid($instance['uuid']);
            }
            return $value;
        }

        // Handle object instances
        if (!method_exists($instance, 'getUuid')) {
            return $value;
        }
        if (!empty($instance->getUuid())) {
            return HexUuidLiteral::getFormattedUuid($instance->getUuid());
        }
        return $value;
    }
} 