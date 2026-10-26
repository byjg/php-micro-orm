<?php

namespace Tests\Model;

use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\Attributes\FieldUuidAttribute;
use ByJG\MicroOrm\Attributes\TableUuidPKAttribute;
use ByJG\MicroOrm\Literal\LiteralInterface;

#[TableUuidPKAttribute(tableName: 'uuidkey', validator: RecordingValidator::class)]
class UuidKeyWithValidator
{
    #[FieldUuidAttribute(primaryKey: true)]
    public string|LiteralInterface|null $id = null;

    #[FieldAttribute]
    public ?string $name = null;

    public function getId(): string|LiteralInterface|null
    {
        return $this->id;
    }
}
