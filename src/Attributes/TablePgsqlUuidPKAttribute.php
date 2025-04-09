<?php

namespace ByJG\MicroOrm\Attributes;

use Attribute;
use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\MicroOrm\Literal\Literal;

#[Attribute(Attribute::TARGET_CLASS)]
class TablePgsqlUuidPKAttribute extends TableAttribute
{
    public function __construct(string $tableName)
    {
        parent::__construct($tableName, function (DbDriverInterface $dbDriver, object $entity) {
            return bin2hex(random_bytes(16));
        });
    }
}
