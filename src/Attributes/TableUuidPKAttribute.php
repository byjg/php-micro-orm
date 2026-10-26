<?php

namespace ByJG\MicroOrm\Attributes;

use Attribute;
use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\MicroOrm\Interface\EntityValidatorInterface;
use ByJG\MicroOrm\Interface\MapperFunctionInterface;
use ByJG\MicroOrm\Literal\HexUuidLiteral;
use Override;

/**
 * A table whose primary key is a UUID, generated in PHP on insert: a version 7 UUID
 * (time-ordered), written in the syntax of the database (see HexUuidLiteral::forDatabase()).
 */
#[Attribute(Attribute::TARGET_CLASS)]
class TableUuidPKAttribute extends TableAttribute implements MapperFunctionInterface
{
    public function __construct(string $tableName, string|EntityValidatorInterface|null $validator = null)
    {
        parent::__construct($tableName, primaryKeySeedFunction: $this, validator: $validator);
    }

    #[Override]
    public function processedValue(mixed $value, mixed $instance, ?DatabaseExecutor $executor = null): mixed
    {
        return HexUuidLiteral::forDatabase($executor, HexUuidLiteral::v7());
    }
}
