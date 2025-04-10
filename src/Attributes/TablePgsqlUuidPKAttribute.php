<?php

namespace ByJG\MicroOrm\Attributes;

use Attribute;
use ByJG\AnyDataset\Db\DbDriverInterface;

#[Attribute(Attribute::TARGET_CLASS)]
class TablePgsqlUuidPKAttribute extends TableAttribute
{
    public function __construct(string $tableName)
    {
        parent::__construct($tableName, function (DbDriverInterface $dbDriver, object $entity) {
            $bytes = random_bytes(16);
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
        });
    }
}
