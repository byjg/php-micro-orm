<?php

namespace Tests\Model;

use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\Attributes\TableAttribute;
use ByJG\MicroOrm\Trait\DeletedAt;

#[TableAttribute(tableName: 'table4')]
class Class4
{
    use DeletedAt;

    #[FieldAttribute(primaryKey: true)]
    public ?int $id = null;

    #[FieldAttribute(fieldName: "id_table2", parentTable: "table2")]
    public ?int $idTable2 = null;
}
