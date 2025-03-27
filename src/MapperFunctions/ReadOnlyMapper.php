<?php

namespace ByJG\MicroOrm\MapperFunctions;

use ByJG\AnyDataset\Db\DbFunctionsInterface;
use ByJG\MicroOrm\Interface\MapperFunctionInterface;
use Override;

class ReadOnlyMapper implements MapperFunctionInterface
{
    #[Override]
    public function processedValue(mixed $value, mixed $instance, ?DbFunctionsInterface $helper = null): mixed
    {
        return false;
    }
} 