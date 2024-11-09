<?php

namespace Tests\Model;

use ByJG\MicroOrm\Attributes\TableAttribute;
use ByJG\MicroOrm\Trait\ActiveRecord;

#[TableAttribute("info")]
class ActiveRecordModel extends ModelWithAttributes
{
    use ActiveRecord;
}