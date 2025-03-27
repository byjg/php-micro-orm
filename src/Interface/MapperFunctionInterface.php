<?php

namespace ByJG\MicroOrm\Interface;

use ByJG\AnyDataset\Db\DbFunctionsInterface;

interface MapperFunctionInterface
{
    public function processedValue(mixed $value, mixed $instance, ?DbFunctionsInterface $helper = null): mixed;
} 