<?php

namespace Tests\Model;

use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\Attributes\TableAttribute;

#[TableAttribute(tableName: 'orders')]
class Order
{
    #[FieldAttribute(primaryKey: true)]
    public ?int $id = null;

    #[FieldAttribute]
    public ?OrderStatus $status = null;
}
