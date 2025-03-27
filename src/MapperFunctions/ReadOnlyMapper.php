<?php

namespace ByJG\MicroOrm\MapperFunctions;

use ByJG\MicroOrm\Interface\DbFunctionsInterface;
use ByJG\MicroOrm\Interface\MapperFunctionInterface;
use Override;

class ReadOnlyMapper implements MapperFunctionInterface
{
    #[Override]
    public function processedValue(mixed $value, mixed $instance, ?\ByJG\AnyDataset\Db\DbFunctionsInterface $helper = null): mixed
    {
        return false;
    }
} 