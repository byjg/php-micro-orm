<?php

namespace Tests\Model;

use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\MicroOrm\Interface\MapperFunctionInterface;

/**
 * A mapper function that counts how many instances were created
 */
class CountingMapper implements MapperFunctionInterface
{
    public static int $instances = 0;

    public function __construct()
    {
        self::$instances++;
    }

    #[\Override]
    public function processedValue(mixed $value, mixed $instance, ?DatabaseExecutor $executor = null): mixed
    {
        return "mapped:$value";
    }
}
