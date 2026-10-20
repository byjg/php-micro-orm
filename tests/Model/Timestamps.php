<?php

namespace Tests\Model;

use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\Attributes\TableAttribute;
use ByJG\MicroOrm\Trait\CreatedAt;
use ByJG\MicroOrm\Trait\DeletedAt;
use ByJG\MicroOrm\Trait\UpdatedAt;

#[TableAttribute(tableName: 'timestamps')]
class Timestamps
{
    use CreatedAt;
    use UpdatedAt;
    use DeletedAt;

    #[FieldAttribute(primaryKey: true)]
    public ?int $id = null;

    #[FieldAttribute]
    public ?string $name = null;
}
