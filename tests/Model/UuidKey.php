<?php

namespace Tests\Model;

use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\Attributes\FieldUuidAttribute;
use ByJG\MicroOrm\Attributes\TableUuidPKAttribute;
use ByJG\MicroOrm\Literal\LiteralInterface;

#[TableUuidPKAttribute(tableName: 'uuidkey')]
class UuidKey
{
    #[FieldUuidAttribute(primaryKey: true)]
    public string|LiteralInterface|null $id = null;

    #[FieldAttribute]
    public ?string $name = null;

    #[FieldUuidAttribute]
    public string|LiteralInterface|null $ref = null;
}
