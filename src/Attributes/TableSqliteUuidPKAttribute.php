<?php

namespace ByJG\MicroOrm\Attributes;

use Attribute;

/**
 * @deprecated Use TableUuidPKAttribute, which generates the UUID in PHP and works on every
 *             database. This class only remains so existing models keep working.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class TableSqliteUuidPKAttribute extends TableUuidPKAttribute
{
}
