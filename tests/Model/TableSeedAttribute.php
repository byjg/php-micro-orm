<?php

namespace Tests\Model;

use Attribute;
use ByJG\MicroOrm\Attributes\TableAttribute;

#[Attribute(Attribute::TARGET_CLASS)]
class TableSeedAttribute extends TableAttribute
{
    public function __construct()
    {
        parent::__construct('users', function ($instance) {
            return 50;
        });
    }
}
