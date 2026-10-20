<?php

namespace Tests\Model;

use ByJG\MicroOrm\Attributes\FieldUuidAttribute;
use ByJG\MicroOrm\Attributes\TableAttribute;

#[TableAttribute('table5')]
class Class5
{
    #[FieldUuidAttribute(primaryKey: true)]
    public ?string $id = null;

    #[FieldUuidAttribute(fieldName: "id_table1", parentTable: "table1")]
    public ?string $idTable1 = null;
}
