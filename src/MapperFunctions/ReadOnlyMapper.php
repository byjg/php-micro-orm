<?php

namespace ByJG\MicroOrm\MapperFunctions;

use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\MicroOrm\Interface\MapperFunctionInterface;
use Override;

class ReadOnlyMapper implements MapperFunctionInterface
{
    #[Override]
    public function processedValue(mixed $value, mixed $instance, ?DatabaseExecutor $executor = null): mixed
    {
        return false;
    }
} 