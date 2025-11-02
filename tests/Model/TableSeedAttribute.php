<?php

namespace Tests\Model;

use Attribute;
use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\MicroOrm\Attributes\TableAttribute;
use ByJG\MicroOrm\Interface\MapperFunctionInterface;
use Override;

#[Attribute(Attribute::TARGET_CLASS)]
class TableSeedAttribute extends TableAttribute implements MapperFunctionInterface
{
    public function __construct()
    {
        parent::__construct('users', TableSeedAttribute::class);
    }

    #[Override]
    public function processedValue(mixed $value, mixed $instance, ?DatabaseExecutor $executor = null): mixed
    {
        return 50;
    }
}
