<?php

namespace ByJG\MicroOrm\MapperFunctions;

use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\MicroOrm\Interface\MapperFunctionInterface;
use ByJG\MicroOrm\Literal\Literal;
use InvalidArgumentException;
use Override;

class NowUtcMapper implements MapperFunctionInterface
{
    #[Override]
    public function processedValue(mixed $value, mixed $instance, ?DatabaseExecutor $executor = null): mixed
    {
        if ($executor === null) {
            throw new InvalidArgumentException('DatabaseExecutor is required for NowUtcMapper');
        }
        return new Literal($executor->getHelper()->sqlDate('Y-m-d H:i:s'));
    }
} 