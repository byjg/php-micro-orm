<?php

namespace ByJG\MicroOrm\Attributes;

use Attribute;
use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\MicroOrm\Interface\UniqueIdGeneratorInterface;
use ByJG\MicroOrm\Literal\Literal;
use Override;

#[Attribute(Attribute::TARGET_CLASS)]
class TablePgsqlUuidPKAttribute extends TableAttribute implements UniqueIdGeneratorInterface
{
    public function __construct(string $tableName)
    {
        parent::__construct($tableName, primaryKeySeedFunction: $this);
    }

    #[Override]
    public function process(DatabaseExecutor $executor, array|object $instance): string|Literal|int
    {
        $bytes = random_bytes(16);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
