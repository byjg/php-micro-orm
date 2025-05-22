<?php

namespace ByJG\MicroOrm\MapperFunctions;

use ByJG\AnyDataset\Db\DbFunctionsInterface;
use ByJG\MicroOrm\Interface\MapperFunctionInterface;
use ByJG\MicroOrm\Literal\Literal;
use Override;

class NowUtcMapper implements MapperFunctionInterface
{
    #[Override]
    public function processedValue(mixed $value, mixed $instance, ?DbFunctionsInterface $helper = null): mixed
    {
        return new Literal($helper->sqlDate('Y-m-d H:i:s'));
    }
} 