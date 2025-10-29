<?php

namespace Tests\Model;

use Attribute;
use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\MicroOrm\Attributes\TableAttribute;
use ByJG\MicroOrm\Interface\UniqueIdGeneratorInterface;
use ByJG\MicroOrm\Literal\Literal;
use Override;

#[Attribute(Attribute::TARGET_CLASS)]
class TableSeedAttribute extends TableAttribute implements UniqueIdGeneratorInterface
{
    public function __construct()
    {
        parent::__construct('users', TableSeedAttribute::class);
    }


    #[Override]
    public function process(DatabaseExecutor $executor, object|array $instance): string|Literal|int
    {
        return 50;
    }
}
