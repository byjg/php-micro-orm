<?php

namespace Tests\Model;

use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\Attributes\FieldUuidAttribute;
use ByJG\MicroOrm\Attributes\TableMySqlUuidPKAttribute;
use ByJG\MicroOrm\Literal\LiteralInterface;

/**
 * Uses the deprecated MySQL attribute, which must keep working, now on every database.
 */
#[TableMySqlUuidPKAttribute(tableName: 'uuidkey')]
class UuidKeyWithMySqlAttribute
{
    #[FieldUuidAttribute(primaryKey: true)]
    public string|LiteralInterface|null $id = null;

    #[FieldAttribute]
    public ?string $name = null;
}
