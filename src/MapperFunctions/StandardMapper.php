<?php

namespace ByJG\MicroOrm\MapperFunctions;

use ByJG\AnyDataset\Db\DbFunctionsInterface;
use ByJG\MicroOrm\Interface\MapperFunctionInterface;
use Override;

class StandardMapper implements MapperFunctionInterface
{
    #[Override]
    public function processedValue(mixed $value, mixed $instance, ?DbFunctionsInterface $helper = null): mixed
    {
        if (empty($value) && $value !== 0 && $value !== '0' && $value !== false) {
            return null;
        }
        return $value;
    }
} 