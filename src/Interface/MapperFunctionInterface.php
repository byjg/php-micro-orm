<?php

namespace ByJG\MicroOrm\Interface;

use ByJG\AnyDataset\Db\DatabaseExecutor;

interface MapperFunctionInterface
{
    public function processedValue(mixed $value, mixed $instance, ?DatabaseExecutor $executor = null): mixed;
} 